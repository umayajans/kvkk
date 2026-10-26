<?php
/**
 * Çerez ve hizmet taraması (sunucu tarafı, dış servis kullanılmaz)
 *
 * @package KVKK_Cerez
 */

// Doğrudan erişimi engelle.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Tarayıcı
 *
 * Yönetim panelindeki "Siteyi Tara" butonu sayfa listesini alır ve her sayfayı sırayla ister.
 * Sunucu sayfayı anonim olarak (yöneticilere gizlenen izleme kodları da görünsün diye) ve
 * engellemeyi atlayan tek kullanımlık bir anahtarla çeker; HTML'deki script, iframe ve
 * piksellerden hizmetleri, Set-Cookie başlıklarından çerezleri çıkarır.
 */
class KVKKC_Scanner {

    /**
     * Tarama anahtarının saklandığı transient.
     */
    const TOKEN = 'kvkkc_scan_token';

    /**
     * Kancalar.
     */
    public static function init() {
        add_action( 'template_redirect', array( __CLASS__, 'mark_scan_request' ), -1 );

        if ( is_admin() ) {
            add_action( 'wp_ajax_kvkkc_scan_start', array( __CLASS__, 'ajax_start' ) );
            add_action( 'wp_ajax_kvkkc_scan_url', array( __CLASS__, 'ajax_url' ) );
        }
    }

    /**
     * İstek geçerli bir tarama isteği mi?
     *
     * @return bool
     */
    public static function is_scan_request() {
        static $result = null;

        if ( null === $result ) {
            $given  = isset( $_GET['kvkkc_scan'] ) && is_string( $_GET['kvkkc_scan'] ) ? sanitize_text_field( wp_unslash( $_GET['kvkkc_scan'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- anahtar transient ile doğrulanır.
            $token  = '' !== $given ? get_transient( self::TOKEN ) : '';
            $result = '' !== $given && is_string( $token ) && '' !== $token && hash_equals( $token, $given );
        }

        return $result;
    }

    /**
     * Tarama isteğinde engellenmemiş HTML hiçbir önbelleğe girmesin.
     */
    public static function mark_scan_request() {
        if ( ! self::is_scan_request() ) {
            return;
        }

        if ( ! defined( 'DONOTCACHEPAGE' ) ) {
            define( 'DONOTCACHEPAGE', true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- önbellek eklentilerinin ortak sabiti.
        }
        do_action( 'litespeed_control_set_nocache', 'kvkk-cerez tarama' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- LiteSpeed Cache kancası.
        nocache_headers();
    }

    /**
     * Taramayı başlatır: anahtar üretir, taranacak adresleri döndürür.
     */
    public static function ajax_start() {
        check_ajax_referer( 'kvkkc_admin' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Yetkiniz yok.', 'kvkk-cerez' ) ), 403 );
        }

        $limit = isset( $_POST['limit'] ) ? min( 60, max( 1, absint( $_POST['limit'] ) ) ) : 20;

        set_transient( self::TOKEN, wp_generate_password( 32, false ), 15 * MINUTE_IN_SECONDS );

        update_option(
            'kvkkc_scan',
            array(
                'time'     => time(),
                'pages'    => 0,
                'errors'   => array(),
                'services' => KVKKC_Services::detect_installed(),
                'cookies'  => array(),
                'hosts'    => array(),
            ),
            false
        );

        wp_send_json_success( array( 'urls' => self::urls( $limit ) ) );
    }

    /**
     * Tek bir adresi tarar ve sonucu birikimli kayda ekler.
     */
    public static function ajax_url() {
        check_ajax_referer( 'kvkkc_admin' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Yetkiniz yok.', 'kvkk-cerez' ) ), 403 );
        }

        $url   = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';
        $host  = (string) wp_parse_url( home_url(), PHP_URL_HOST );
        $token = get_transient( self::TOKEN );

        if ( '' === $url || (string) wp_parse_url( $url, PHP_URL_HOST ) !== $host ) {
            wp_send_json_error( array( 'message' => __( 'Yalnızca bu sitenin adresleri taranabilir.', 'kvkk-cerez' ) ), 400 );
        }
        if ( ! is_string( $token ) || '' === $token ) {
            wp_send_json_error( array( 'message' => __( 'Tarama süresi doldu, lütfen yeniden başlatın.', 'kvkk-cerez' ) ), 400 );
        }

        $response = wp_safe_remote_get(
            add_query_arg( 'kvkkc_scan', $token, $url ),
            array(
                'timeout'     => 20,
                'redirection' => 3,
                'sslverify'   => apply_filters( 'https_local_ssl_verify', false ), // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- çekirdeğin kendi isteklerinde kullandığı filtre.
                'user-agent'  => 'KVKK Cerez Tarayici; ' . home_url( '/' ),
            )
        );

        $scan = get_option( 'kvkkc_scan', array() );
        $scan = is_array( $scan ) ? $scan : array();
        $scan = array_merge(
            array(
                'pages'    => 0,
                'errors'   => array(),
                'services' => array(),
                'cookies'  => array(),
                'hosts'    => array(),
            ),
            $scan
        );

        $code = is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );

        if ( is_wp_error( $response ) || $code >= 400 ) {
            $reason           = is_wp_error( $response ) ? $response->get_error_message() : 'HTTP ' . $code;
            $scan['errors'][] = array( $url, $reason );
            $scan['errors']   = array_slice( $scan['errors'], -20 );
            update_option( 'kvkkc_scan', $scan, false );
            wp_send_json_success( array( 'error' => $reason ) );
        }

        $cookies = array();
        foreach ( wp_remote_retrieve_cookies( $response ) as $cookie ) {
            $cookies[ $cookie->name ] = (int) $cookie->expires;
        }

        $result = self::analyze( wp_remote_retrieve_body( $response ), $cookies, $host, KVKKC_Services::inventory() );

        foreach ( KVKKC_Services::all() as $id => $service ) {
            if ( '' !== $service['header'] && '' !== wp_remote_retrieve_header( $response, $service['header'] ) ) {
                $result['services'][] = $id;
            }
        }

        ++$scan['pages'];
        $scan['services'] = array_values( array_unique( array_merge( $scan['services'], $result['services'] ) ) );
        $scan['hosts']    = array_values( array_unique( array_merge( $scan['hosts'], $result['hosts'] ) ) );
        $scan['cookies']  = array_merge( $scan['cookies'], $result['cookies'] );
        update_option( 'kvkkc_scan', $scan, false );

        wp_send_json_success( array( 'services' => count( $result['services'] ) ) );
    }

    /**
     * Taranacak adresler: ana sayfa, yasal sayfalar ve son güncellenen içerikler.
     *
     * @param int $limit Azami adres sayısı.
     * @return string[]
     */
    private static function urls( $limit ) {
        $urls = array( home_url( '/' ) );

        foreach ( array( 'page_cerez', 'page_aydinlatma' ) as $key ) {
            if ( kvkkc_setting( $key ) ) {
                $urls[] = (string) kvkkc_setting( $key );
            }
        }

        $types = array_values( array_filter( get_post_types( array( 'public' => true ) ), 'is_post_type_viewable' ) );
        $types = array_diff( $types, array( 'attachment' ) );

        $ids = get_posts(
            array(
                'post_type'      => $types,
                'post_status'    => 'publish',
                'has_password'   => false,
                'posts_per_page' => $limit,
                'orderby'        => 'modified',
                'order'          => 'DESC',
                'fields'         => 'ids',
                'no_found_rows'  => true,
            )
        );

        foreach ( $ids as $id ) {
            $urls[] = get_permalink( $id );
        }

        return array_slice( array_values( array_unique( array_filter( $urls ) ) ), 0, $limit );
    }

    /**
     * Sayfa çıktısını çözümler.
     *
     * Engellenmiş (data-kvkkc-src) etiketler de okunur; yönlendirme sonrası anahtar kaybolsa bile
     * sonuç doğru çıkar.
     *
     * @param string $html      Sayfa HTML'i.
     * @param array  $cookies   Çerez adı => bitiş zamanı (0 oturum).
     * @param string $site_host Sitenin alan adı.
     * @param array  $inventory Envanter (özel hizmet işaretleri için).
     * @return array array( 'services' => string[], 'hosts' => string[], 'cookies' => array )
     */
    public static function analyze( $html, $cookies, $site_host, $inventory = array() ) {
        $rules    = KVKKC_Services::detect_rules( $inventory );
        $services = array();
        $hosts    = array();
        $site     = preg_replace( '/^www\./', '', strtolower( $site_host ) );

        $p = new WP_HTML_Tag_Processor( (string) $html );
        while ( $p->next_tag() ) {
            $tag  = $p->get_tag();
            $urls = array();

            if ( 'SCRIPT' === $tag ) {
                $src = $p->get_attribute( 'src' );
                $src = is_string( $src ) && '' !== $src ? $src : $p->get_attribute( 'data-kvkkc-src' );
                if ( is_string( $src ) && '' !== $src ) {
                    $urls[] = array( $src, true );
                } else {
                    $text  = $p->get_modifiable_text();
                    $found = KVKKC_Services::match_inline( $text, $rules );
                    if ( '' !== $found ) {
                        $services[] = $found;
                    }
                    foreach ( $rules['scan'] as $signature ) {
                        if ( count( $signature[0] ) === count(
                            array_filter(
                                $signature[0],
                                function ( $part ) use ( $text ) {
                                    return false !== strpos( $text, $part );
                                }
                            )
                        ) ) {
                            $services[] = $signature[1];
                        }
                    }
                }
            } elseif ( 'IFRAME' === $tag || 'IMG' === $tag ) {
                foreach ( array( 'src', 'data-src', 'data-lazy-load', 'data-kvkkc-src' ) as $attribute ) {
                    $value = $p->get_attribute( $attribute );
                    if ( is_string( $value ) && '' !== $value ) {
                        $urls[] = array( $value, 'IFRAME' === $tag );
                    }
                }
            } elseif ( 'LINK' === $tag ) {
                $value = $p->get_attribute( 'href' );
                if ( is_string( $value ) ) {
                    $urls[] = array( $value, false );
                }
            }

            // Sayfa oluşturucuların veri öznitelikleri (ör. Elementor video ayarları) de adres taşır.
            foreach ( (array) $p->get_attribute_names_with_prefix( 'data-' ) as $name ) {
                $value = $p->get_attribute( $name );
                if ( is_string( $value ) && preg_match_all( '#https?:(?:\\\\?/){2}[^\s"\'<>,]+#', $value, $matches ) ) {
                    foreach ( $matches[0] as $found_url ) {
                        $found = KVKKC_Services::match_url( str_replace( '\/', '/', $found_url ), $rules );
                        if ( '' !== $found ) {
                            $services[] = $found;
                        }
                    }
                }
            }

            foreach ( $urls as $item ) {
                list( $url, $active ) = $item;

                $found = KVKKC_Services::match_url( $url, $rules );
                if ( '' !== $found ) {
                    $services[] = $found;
                    continue;
                }

                // Bilinmeyen üçüncü taraf alan adları yalnızca çalışan içerikte (script, iframe) raporlanır.
                $host = strtolower( (string) wp_parse_url( 0 === strpos( $url, '//' ) ? 'https:' . $url : $url, PHP_URL_HOST ) );
                $bare = preg_replace( '/^www\./', '', $host );
                if ( $active && '' !== $host && $bare !== $site && substr( $bare, -strlen( $site ) - 1 ) !== '.' . $site ) {
                    $hosts[] = $host;
                }
            }
        }

        $found_cookies = array();
        foreach ( $cookies as $name => $expires ) {
            $service                         = KVKKC_Services::cookie_service( (string) $name, $inventory );
            $found_cookies[ (string) $name ] = array(
                'service' => $service,
                'expires' => (int) $expires,
            );
            // Çerezi tanınan hizmet sayfada başka iz bırakmasa da bulunanlara eklenir (ör. PHPSESSID).
            if ( '' !== $service ) {
                $services[] = $service;
            }
        }

        $services = array_values( array_unique( $services ) );
        $hosts    = array_values( array_unique( $hosts ) );
        sort( $services );
        sort( $hosts );

        return array(
            'services' => $services,
            'hosts'    => $hosts,
            'cookies'  => $found_cookies,
        );
    }
}
