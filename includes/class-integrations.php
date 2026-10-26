<?php
/**
 * Diğer eklentilerle uyum: WP Consent API, PixelYourSite, LiteSpeed, WP Rocket
 *
 * @package KVKK_Cerez
 */

// Doğrudan erişimi engelle.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Entegrasyonlar
 */
class KVKKC_Integrations {

    /**
     * PixelYourSite piksel anahtarı => kategori.
     *
     * @var array
     */
    const PYS_PIXELS = array(
        'facebook'   => 'pazarlama',
        'ga'         => 'analitik',
        'gtm'        => 'analitik',
        'google_ads' => 'pazarlama',
        'bing'       => 'pazarlama',
        'pinterest'  => 'pazarlama',
        'reddit'     => 'pazarlama',
        'tiktok'     => 'pazarlama',
    );

    /**
     * Kancalar. Diğer eklentiler bizden sonra yüklenebileceği için filtreler koşulsuz eklenir;
     * eklenti yoksa hiç çağrılmazlar.
     */
    public static function init() {
        // WP Consent API: bu eklenti rıza sağlayıcısıdır, rıza tipi "optin".
        add_filter( 'wp_get_consent_type', array( __CLASS__, 'consent_type' ) );
        add_filter( 'wp_consent_api_registered_' . KVKKC_BASENAME, '__return_true' );

        // PixelYourSite.
        foreach ( array( 'pys_disable_facebook_by_gdpr', 'pys_disable_google_ads_by_gdpr', 'pys_disable_tiktok_by_gdpr', 'pys_disable_bing_by_gdpr', 'pys_disable_pinterest_by_gdpr', 'pys_disable_reddit_by_gdpr', 'pys_disable_externalID_by_gdpr', 'pys_disable_all_cookie' ) as $filter ) {
            add_filter( $filter, array( __CLASS__, 'pys_marketing_off' ) );
        }
        add_filter( 'pys_disable_analytics_by_gdpr', array( __CLASS__, 'pys_analytics_off' ) );
        add_filter( 'pys_check_consent_by_gdpr', array( __CLASS__, 'pys_check_consent' ), 10, 2 );

        // PYS, Google etiketlerini kendisi yüklediğinde kendi İzin Modu varsayılanını gönderir ve
        // filtre yoksa bu değer "granted" olur. Filtreler bağlanınca varsayılan "denied" gider;
        // ziyaretçi izin verdiğinde bandın gönderdiği "update" komutu bunu geçersiz kılar.
        add_filter( 'pys_analytics_storage_mode', array( __CLASS__, 'pys_mode_analytics' ) );
        foreach ( array( 'pys_ad_storage_mode', 'pys_ad_user_data_mode', 'pys_ad_personalization_mode' ) as $filter ) {
            add_filter( $filter, array( __CLASS__, 'pys_mode_ads' ) );
        }

        // Önbellek eklentileri bant dosyalarını ertelemesin, birleştirmesin.
        add_filter( 'litespeed_optm_js_defer_exc', array( __CLASS__, 'exclude_js' ) );
        add_filter( 'litespeed_optimize_js_excludes', array( __CLASS__, 'exclude_js' ) );
        add_filter( 'litespeed_optimize_css_excludes', array( __CLASS__, 'exclude_css' ) );
        add_filter( 'litespeed_ucss_whitelist', array( __CLASS__, 'ucss_whitelist' ) );
        add_filter( 'rocket_exclude_defer_js', array( __CLASS__, 'exclude_js' ) );
        add_filter( 'rocket_delay_js_exclusions', array( __CLASS__, 'exclude_js' ) );
        add_filter( 'rocket_exclude_js', array( __CLASS__, 'exclude_js' ) );
        add_filter( 'rocket_exclude_inline_js', array( __CLASS__, 'exclude_js' ) );
    }

    /**
     * PYS İzin Modu: analytics_storage "granted" olsun mu?
     *
     * @return bool
     */
    public static function pys_mode_analytics() {
        return ! self::pys_off( 'analitik' );
    }

    /**
     * PYS İzin Modu: reklam türleri (ad_storage, ad_user_data, ad_personalization) "granted" olsun mu?
     *
     * @return bool
     */
    public static function pys_mode_ads() {
        return ! self::pys_off( 'pazarlama' );
    }

    /**
     * WP Consent API rıza tipi.
     *
     * @return string
     */
    public static function consent_type() {
        return 'optin';
    }

    /**
     * PixelYourSite sayfa yapılandırması.
     *
     * Sayfa önbelleğe girdiği için normal sayfa çıktısında pikseller her zaman kapalı bildirilir;
     * gerçek durum PYS'nin "GDPR AJAX" isteğinde ziyaretçinin çereziyle hesaplanır.
     *
     * @param string $category Kategori.
     * @return bool true ise kapalı.
     */
    private static function pys_off( $category ) {
        // Tarama isteği önbelleğe girmez; piksellerin görünmesi gerekir ki taramada tanınsın.
        if ( KVKKC_Scanner::is_scan_request() ) {
            return false;
        }
        return ! wp_doing_ajax() || ! kvkkc_has_consent( $category );
    }

    /**
     * Pazarlama pikselleri ve PYS çerezleri.
     *
     * @return bool
     */
    public static function pys_marketing_off() {
        return self::pys_off( 'pazarlama' );
    }

    /**
     * Analitik pikseller.
     *
     * @return bool
     */
    public static function pys_analytics_off() {
        return self::pys_off( 'analitik' );
    }

    /**
     * Sunucu tarafı olaylar (Meta Conversions API vb.) her istekte ziyaretçinin çereziyle kontrol edilir.
     *
     * @param bool   $allowed PYS'nin kendi kararı.
     * @param string $pixel   Piksel anahtarı.
     * @return bool
     */
    public static function pys_check_consent( $allowed, $pixel ) {
        $category = isset( self::PYS_PIXELS[ $pixel ] ) ? self::PYS_PIXELS[ $pixel ] : 'pazarlama';
        return $allowed && kvkkc_has_consent( $category );
    }

    /**
     * JS hariç tutma listeleri.
     *
     * @param mixed $items Mevcut liste.
     * @return array
     */
    public static function exclude_js( $items ) {
        return array_merge( (array) $items, array( 'kvkkc-guard', 'kvkkc-banner', 'kvkkcGuard', 'kvkkcConfig' ) );
    }

    /**
     * CSS hariç tutma listesi: bant stillerinin satır içi etiketi birleştirilmez.
     *
     * @param mixed $items Mevcut liste.
     * @return array
     */
    public static function exclude_css( $items ) {
        return array_merge( (array) $items, array( 'kvkkc-banner-inline-css' ) );
    }

    /**
     * UCSS kullanılmayan seçicileri siler; bant JavaScript ile çizildiği için seçicileri korunur.
     *
     * @param mixed $items Mevcut liste.
     * @return array
     */
    public static function ucss_whitelist( $items ) {
        return array_merge( (array) $items, array( '.kvkkc', '.kvkkc-ph', '.kvkkc-blocked', '.kvkkc-policy', 'kvkkc-locked', 'kvkkc-dark-auto' ) );
    }

    /**
     * Yönetim panelinde gösterilen uyum durumu.
     *
     * @return array array( array( state: ok|warn|info, title, text ), ... )
     */
    public static function status() {
        $items = array();

        if ( function_exists( 'PYS' ) ) {
            $ajax    = (bool) PYS()->getOption( 'gdpr_ajax_enabled' );
            $items[] = array(
                'state' => $ajax ? 'ok' : 'warn',
                'title' => 'PixelYourSite',
                'text'  => $ajax
                    ? __( 'Meta ve Google Ads pikselleri ziyaretçinin pazarlama iznine göre çalışıyor; sunucu tarafı olaylar da aynı izne bağlı.', 'umay-cerez-yoneticisi' )
                    : __( 'PixelYourSite > GDPR sekmesinde "For Developers" kartını açın ve "Enable AJAX filter values update" anahtarını açıp kaydedin. Kapalıyken pikseller güvenli tarafta kalır ve izin veren ziyaretçilerde de çalışmaz.', 'umay-cerez-yoneticisi' ),
            );
        }

        if ( defined( 'GTM4WP_OPTIONS' ) ) {
            $options = get_option( GTM4WP_OPTIONS, array() );
            $granted = false;
            if ( ! empty( $options['integrate-consent-mode'] ) ) {
                foreach ( $options as $key => $value ) {
                    $granted = $granted || ( 0 === strpos( (string) $key, 'integrate-consent-mode-' ) && ! empty( $value ) );
                }
            }
            $items[] = array(
                'state' => $granted ? 'warn' : 'ok',
                'title' => 'GTM4WP',
                'text'  => $granted
                    ? __( 'GTM4WP\'nin kendi İzin Modu varsayılanında "granted" değerler var ve bu eklentinin varsayılanını eziyor. GTM4WP > Integration > Consent Mode seçeneğini kapatın.', 'umay-cerez-yoneticisi' )
                    : __( 'Tag Manager kapsayıcısı ziyaretçi izin verene kadar yüklenmez. GTM içindeki Google dışı etiketleri kvkkc_consent olayına bağlayın.', 'umay-cerez-yoneticisi' ),
            );
        }

        if ( defined( 'GOOGLESITEKIT_VERSION' ) ) {
            $api     = function_exists( 'wp_has_consent' );
            $items[] = array(
                'state' => $api ? 'ok' : 'info',
                'title' => 'Site Kit',
                'text'  => $api
                    ? __( 'Site Kit, WP Consent API üzerinden bu eklentinin izinlerini okuyor.', 'umay-cerez-yoneticisi' )
                    : __( 'Google Analytics etiketi izin verilene kadar engelleniyor. İsterseniz WP Consent API eklentisini kurarak Site Kit\'in izinleri doğrudan okumasını sağlayabilirsiniz.', 'umay-cerez-yoneticisi' ),
            );
        }

        if ( defined( 'LSCWP_V' ) || defined( 'WP_ROCKET_VERSION' ) ) {
            $items[] = array(
                'state' => 'ok',
                'title' => defined( 'LSCWP_V' ) ? 'LiteSpeed Cache' : 'WP Rocket',
                'text'  => __( 'Bant dosyaları erteleme ve birleştirme işlemlerinden muaf tutuluyor; ayar kaydettiğinizde sayfa önbelleği temizlenir.', 'umay-cerez-yoneticisi' ),
            );
        }

        if ( isset( $_SERVER['HTTP_CF_RAY'] ) && ! defined( 'KVKKC_TRUSTED_PROXY_HEADER' ) ) {
            $items[] = array(
                'state' => 'warn',
                'title' => 'Cloudflare',
                'text'  => __( 'Site Cloudflare arkasında. Rıza kayıtlarındaki IP özeti ve hız sınırı için wp-config.php dosyasına define( \'KVKKC_TRUSTED_PROXY_HEADER\', \'HTTP_CF_CONNECTING_IP\' ); satırını ekleyin.', 'umay-cerez-yoneticisi' ),
            );
        }

        return $items;
    }
}
