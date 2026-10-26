<?php
/**
 * Rıza kayıtları: herkese açık REST ucu, saklama süresi temizliği ve CSV
 *
 * @package KVKK_Cerez
 */

// Doğrudan erişimi engelle.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Rıza kayıtları
 */
class KVKKC_Consent_Log {

    /**
     * Geçerli eylemler.
     *
     * @var string[]
     */
    const ACTIONS = array( 'accept_all', 'reject_all', 'custom', 'withdraw' );

    /**
     * IP kovası başına saatlik kayıt sınırı. Aşılırsa tercih yine çerezle uygulanır, yalnızca kayıt atlanır.
     */
    const HOURLY_LIMIT = 120;

    /**
     * Kancalar.
     */
    public static function init() {
        add_action( 'rest_api_init', array( __CLASS__, 'register_route' ) );
        add_action( 'kvkkc_cleanup', array( __CLASS__, 'cleanup' ) );
    }

    /**
     * POST /wp-json/kvkkc/v1/consent
     *
     * Önbellekten gelen sayfalarda nonce tazeliği garanti edilemeyeceği için uç kimlik istemez;
     * bunun yerine istek sitenin kendi sayfalarından gelmeli (same_origin), girdi sıkı doğrulanır
     * ve hız sınırı uygulanır.
     */
    public static function register_route() {
        register_rest_route(
            'kvkkc/v1',
            '/consent',
            array(
                'methods'             => 'POST',
                'callback'            => array( __CLASS__, 'handle' ),
                'permission_callback' => array( __CLASS__, 'same_origin' ),
                'args'                => array(
                    'action'     => array(
                        'type'     => 'string',
                        'enum'     => self::ACTIONS,
                        'required' => true,
                    ),
                    'categories' => array(
                        'type'     => 'array',
                        'items'    => array(
                            'type' => 'string',
                            'enum' => kvkkc_categories(),
                        ),
                        'maxItems' => 4,
                        'default'  => array(),
                    ),
                    'version'    => array(
                        'type'     => 'integer',
                        'minimum'  => 1,
                        'required' => true,
                    ),
                    'page'       => array(
                        'type'      => 'string',
                        'maxLength' => 2000,
                        'default'   => '',
                    ),
                    'id'         => array(
                        'type'      => 'string',
                        'maxLength' => 36,
                        'default'   => '',
                    ),
                ),
            )
        );
    }

    /**
     * İstek sitenin kendi sayfasından mı geliyor? Tarayıcılar başka bir siteden yapılan POST
     * isteklerinde Origin başlığını her zaman gönderir; böylece başka bir site ziyaretçi adına
     * rıza kaydı yazamaz. Başlıksız istekler tarayıcı dışı araçlardan gelir ve bunlar başlığı
     * zaten taklit edebileceği için onlara karşı hız sınırı uygulanır.
     *
     * @param WP_REST_Request $request İstek.
     * @return true|WP_Error
     */
    public static function same_origin( $request ) {
        $source = $request->get_header( 'origin' );
        if ( null === $source ) {
            $source = $request->get_header( 'referer' );
        }
        if ( null === $source ) {
            return true;
        }

        $host    = strtolower( (string) wp_parse_url( $source, PHP_URL_HOST ) );
        $allowed = array(
            strtolower( (string) $request->get_header( 'host' ) ),
            strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) ),
            strtolower( (string) wp_parse_url( site_url(), PHP_URL_HOST ) ),
        );
        // Host başlığı port içerebilir (ör. örnek.test:8080).
        $allowed[0] = preg_replace( '/:\d+$/', '', $allowed[0] );

        if ( '' !== $host && in_array( $host, $allowed, true ) ) {
            return true;
        }

        return new WP_Error( 'kvkkc_cross_origin', __( 'Bu istek yalnızca sitenin kendi sayfalarından yapılabilir.', 'umay-cerez-yoneticisi' ), array( 'status' => 403 ) );
    }

    /**
     * Rıza kaydını işler, çerezi sunucu tarafında yazar.
     *
     * @param WP_REST_Request $request İstek.
     * @return WP_REST_Response|WP_Error
     */
    public static function handle( $request ) {
        $record = self::normalize( $request->get_params() );
        if ( is_wp_error( $record ) ) {
            return $record;
        }

        $ip     = kvkkc_client_ip();
        $logged = 0 === kvkkc_throttle( 'consent|' . kvkkc_ip_bucket( $ip ), self::HOURLY_LIMIT, HOUR_IN_SECONDS );

        if ( $logged ) {
            $logged = kvkkc_store_consent(
                array(
                    'consent_id'     => $record['id'],
                    'action'         => $record['action'],
                    'categories'     => $record['categories'],
                    'policy_version' => $record['version'],
                    'ip_hash'        => kvkkc_setting( 'store_ip_hash' ) ? kvkkc_ip_hash( $ip ) : '',
                    'page'           => $record['page'],
                )
            );
            kvkkc_throttle( 'consent|' . kvkkc_ip_bucket( $ip ), self::HOURLY_LIMIT, HOUR_IN_SECONDS, true );
        }

        $time = time();
        self::set_cookie( kvkkc_consent_cookie_value( $record['version'], $time, $record['id'], $record['categories'] ) );

        $response = new WP_REST_Response(
            array(
                'id'     => $record['id'],
                'time'   => $time,
                'logged' => $logged,
            )
        );
        $response->header( 'Cache-Control', 'no-store' );

        return $response;
    }

    /**
     * Rıza çerezini sunucu tarafında yazar. JavaScript ile yazılan çerezler Safari'de 7 gün
     * sonra silindiği için kalıcı kopya buradan gönderilir.
     *
     * @param string $value Çerez değeri.
     */
    private static function set_cookie( $value ) {
        if ( headers_sent() ) {
            return;
        }

        $days = (int) kvkkc_setting( 'consent_days' );

        setcookie(
            'kvkkc_consent',
            $value,
            array(
                'expires'  => time() + $days * DAY_IN_SECONDS,
                'path'     => COOKIEPATH ? COOKIEPATH : '/',
                'secure'   => is_ssl(),
                'httponly' => false,
                'samesite' => 'Lax',
            )
        );
    }

    /**
     * İstek parametrelerini doğrular ve kayda hazırlar.
     *
     * @param array $params Ham parametreler.
     * @return array|WP_Error array( action, categories, version, page, id )
     */
    public static function normalize( $params ) {
        $action = isset( $params['action'] ) ? $params['action'] : '';
        if ( ! in_array( $action, self::ACTIONS, true ) ) {
            return new WP_Error( 'kvkkc_invalid_action', __( 'Geçersiz eylem.', 'umay-cerez-yoneticisi' ), array( 'status' => 400 ) );
        }

        $given = isset( $params['categories'] ) && is_array( $params['categories'] ) ? $params['categories'] : array();

        if ( 'accept_all' === $action ) {
            $categories = kvkkc_optional_categories();
        } elseif ( 'reject_all' === $action ) {
            $categories = array();
        } else {
            $categories = array_values( array_intersect( kvkkc_optional_categories(), $given ) );
        }

        $version = isset( $params['version'] ) ? (int) $params['version'] : 1;
        $version = min( max( 1, $version ), kvkkc_policy_version() );

        // Sorgu dizesi kişisel veri taşıyabilir (kampanya parametreleri, e-posta); yalnızca yol saklanır.
        $page = isset( $params['page'] ) && is_string( $params['page'] ) ? wp_parse_url( $params['page'], PHP_URL_PATH ) : '';
        $page = is_string( $page ) ? substr( sanitize_text_field( $page ), 0, 255 ) : '';

        $id = isset( $params['id'] ) ? $params['id'] : '';

        return array(
            'action'     => $action,
            'categories' => $categories,
            'version'    => $version,
            'page'       => $page,
            'id'         => kvkkc_is_uuid( $id ) ? $id : wp_generate_uuid4(),
        );
    }

    /**
     * Saklama süresi dolan kayıtları siler (günlük cron).
     */
    public static function cleanup() {
        global $wpdb;

        $months = max( 1, (int) kvkkc_setting( 'retention_months' ) );
        $limit  = gmdate( 'Y-m-d H:i:s', strtotime( '-' . $months . ' months' ) );

        $wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE created_at < %s', kvkkc_table(), $limit ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }

    /**
     * CSV formül enjeksiyonuna karşı hücre değeri. =, +, -, @ veya sekme ile başlayan değerler
     * Excel'de formül olarak çalışmasın diye tırnakla başlatılır.
     *
     * @param mixed $value Hücre değeri.
     * @return string
     */
    public static function csv_cell( $value ) {
        $value = (string) $value;
        return ( '' !== $value && false !== strpos( "=+-@\t\r", $value[0] ) ) ? "'" . $value : $value;
    }

    /**
     * Eylem etiketleri.
     *
     * @return array
     */
    public static function action_labels() {
        return array(
            'accept_all' => __( 'Tümünü kabul', 'umay-cerez-yoneticisi' ),
            'reject_all' => __( 'Tümünü ret', 'umay-cerez-yoneticisi' ),
            'custom'     => __( 'Özel seçim', 'umay-cerez-yoneticisi' ),
            'withdraw'   => __( 'Geri alma', 'umay-cerez-yoneticisi' ),
        );
    }
}
