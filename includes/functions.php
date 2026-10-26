<?php
/**
 * Ortak yardımcı fonksiyonlar
 *
 * @package KVKK_Cerez
 */

// Doğrudan erişimi engelle.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Çerez kategorileri (sıra önemlidir: banner, tablo ve kayıtlar bu sırayı izler).
 *
 * @return string[]
 */
function kvkkc_categories() {
    return array( 'zorunlu', 'islevsel', 'analitik', 'pazarlama' );
}

/**
 * Rıza istenen (kapatılabilir) kategoriler.
 *
 * @return string[]
 */
function kvkkc_optional_categories() {
    return array( 'islevsel', 'analitik', 'pazarlama' );
}

/**
 * Rıza kayıtları tablosunun tam adı.
 *
 * @return string
 */
function kvkkc_table() {
    global $wpdb;
    return $wpdb->prefix . 'kvkkc_consents';
}

/**
 * Tek bir ayar değerini döndürür. Kayıtlı değer yoksa veya boşsa varsayılan kullanılır.
 *
 * @param string $key Ayar anahtarı.
 * @return mixed
 */
function kvkkc_setting( $key ) {
    $settings = get_option( 'kvkkc_settings', array() );

    if ( is_array( $settings ) && isset( $settings[ $key ] ) && '' !== $settings[ $key ] ) {
        return $settings[ $key ];
    }

    $defaults = KVKKC_Activator::default_settings();
    return isset( $defaults[ $key ] ) ? $defaults[ $key ] : '';
}

/**
 * Geçerli politika sürümü. "Yeniden rıza iste" ile artar; eski sürümle verilen rızalar geçersiz sayılır.
 *
 * @return int
 */
function kvkkc_policy_version() {
    return max( 1, (int) get_option( 'kvkkc_policy_version', 1 ) );
}

/**
 * UUID v4 biçim kontrolü.
 *
 * @param mixed $value Değer.
 * @return bool
 */
function kvkkc_is_uuid( $value ) {
    return is_string( $value ) && 1 === preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $value );
}

/**
 * Rıza çerezi değeri: "sürüm.zaman.rızaNo.kategori-kategori".
 *
 * Virgül ve artı gibi karakterler çerez değerinde sorun çıkardığı için nokta ve tire kullanılır.
 *
 * @param int      $version    Politika sürümü.
 * @param int      $time       Unix zamanı.
 * @param string   $id         Rıza no (boş olabilir).
 * @param string[] $categories İzin verilen kategoriler.
 * @return string
 */
function kvkkc_consent_cookie_value( $version, $time, $id, $categories ) {
    $categories = array_values( array_intersect( kvkkc_optional_categories(), (array) $categories ) );
    return (int) $version . '.' . (int) $time . '.' . ( kvkkc_is_uuid( $id ) ? $id : '' ) . '.' . implode( '-', $categories );
}

/**
 * Rıza çerezini çözer.
 *
 * @param mixed $raw Çerez değeri.
 * @return array|null array( 'v' => int, 't' => int, 'id' => string, 'c' => string[] ) ya da geçersizse null.
 */
function kvkkc_parse_consent( $raw ) {
    if ( ! is_string( $raw ) || strlen( $raw ) > 200 ) {
        return null;
    }

    $parts = explode( '.', $raw );
    if ( 4 !== count( $parts ) || ! ctype_digit( $parts[0] ) || ! ctype_digit( $parts[1] ) ) {
        return null;
    }

    if ( '' !== $parts[2] && ! kvkkc_is_uuid( $parts[2] ) ) {
        return null;
    }

    $given = '' === $parts[3] ? array() : explode( '-', $parts[3] );

    return array(
        'v'  => (int) $parts[0],
        't'  => (int) $parts[1],
        'id' => $parts[2],
        'c'  => array_values( array_intersect( kvkkc_optional_categories(), $given ) ),
    );
}

/**
 * Ziyaretçinin bir kategoriye geçerli rızası var mı?
 *
 * @param string      $category Kategori anahtarı.
 * @param string|null $raw      Çerez değeri; verilmezse istekteki çerez okunur.
 * @return bool
 */
function kvkkc_has_consent( $category, $raw = null ) {
    if ( 'zorunlu' === $category ) {
        return true;
    }

    if ( null === $raw ) {
        $raw = isset( $_COOKIE['kvkkc_consent'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['kvkkc_consent'] ) ) : '';
    }

    $consent = kvkkc_parse_consent( $raw );

    return $consent && kvkkc_policy_version() === $consent['v'] && in_array( $category, $consent['c'], true );
}

/**
 * İstemci IP adresi.
 *
 * Varsayılan olarak yalnızca REMOTE_ADDR kullanılır; X-Forwarded-For gibi başlıklar
 * istemci tarafından taklit edilebilir. Site Cloudflare veya başka bir proxy arkasındaysa
 * wp-config.php'de proxy'nin üzerine yazdığı başlık tanımlanır:
 *     define( 'KVKKC_TRUSTED_PROXY_HEADER', 'HTTP_CF_CONNECTING_IP' );
 *
 * @return string Geçerli IP veya boş string.
 */
function kvkkc_client_ip() {
    $headers = array( 'REMOTE_ADDR' );
    if ( defined( 'KVKKC_TRUSTED_PROXY_HEADER' ) && KVKKC_TRUSTED_PROXY_HEADER ) {
        array_unshift( $headers, KVKKC_TRUSTED_PROXY_HEADER );
    }

    foreach ( $headers as $header ) {
        if ( empty( $_SERVER[ $header ] ) || ! is_string( $_SERVER[ $header ] ) ) {
            continue;
        }
        // Liste ise güvenilen proxy'nin eklediği en sağdaki değer alınır.
        $parts = explode( ',', sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) ) );
        $ip    = trim( end( $parts ) );
        if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
            return $ip;
        }
    }

    return '';
}

/**
 * Hız sınırı için IP anahtarı: IPv6 adresleri /64 ağına indirgenir
 * (tek bir bağlantı /64 içinde sınırsız adres değiştirebilir).
 *
 * @param string $ip IP adresi.
 * @return string
 */
function kvkkc_ip_bucket( $ip ) {
    if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
        return bin2hex( substr( inet_pton( $ip ), 0, 8 ) ) . '/64';
    }
    return (string) $ip;
}

/**
 * Rıza kaydı için IP özeti. Ham IP saklanmaz: IPv4 son okteti, IPv6 /48 sonrası sıfırlanır,
 * ardından siteye özgü anahtarla HMAC alınır. Aynı ağdan gelen bir talebi doğrulamaya yeter,
 * kişiyi tek başına tanımlamaz.
 *
 * @param string $ip IP adresi.
 * @return string 64 karakterlik özet veya geçersiz IP'de boş string.
 */
function kvkkc_ip_hash( $ip ) {
    if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
        return '';
    }

    $packed = inet_pton( $ip );
    $keep   = 4 === strlen( $packed ) ? 3 : 6;
    $masked = substr( $packed, 0, $keep ) . str_repeat( "\0", strlen( $packed ) - $keep );

    return hash_hmac( 'sha256', $masked, wp_salt( 'nonce' ) );
}

/**
 * Sabit pencereli sayaç (hız sınırı).
 *
 * Pencere ilk kayıtla başlar ve sonraki kayıtlarla uzamaz.
 *
 * @param string $key    Sayaç anahtarı.
 * @param int    $limit  Pencere içinde izin verilen olay sayısı.
 * @param int    $window Pencere süresi (saniye).
 * @param bool   $hit    true ise bir olay kaydedilir.
 * @return int Sınıra ulaşıldıysa pencerenin bitmesine kalan saniye, aksi halde 0.
 */
function kvkkc_throttle( $key, $limit, $window, $hit = false ) {
    $name = 'kvkkc_t_' . md5( $key );
    $now  = time();
    $data = get_transient( $name );

    if ( ! is_array( $data ) || ! isset( $data['n'], $data['t'] ) || $data['t'] + $window <= $now ) {
        $data = array(
            'n' => 0,
            't' => $now,
        );
    }

    if ( $hit ) {
        ++$data['n'];
        set_transient( $name, $data, max( 1, $data['t'] + $window - $now ) );
    }

    return $data['n'] >= $limit ? max( 1, $data['t'] + $window - $now ) : 0;
}

/**
 * Rıza kaydının veritabanına yazıldığı tek nokta.
 *
 * Kayıtlar ileride Türkiye'deki bir rıza kasasına gönderilecekse değişiklik yalnızca burada yapılır.
 *
 * @param array $record consent_id, action, categories, policy_version, ip_hash, page.
 * @return bool
 */
function kvkkc_store_consent( array $record ) {
    global $wpdb;

    return false !== $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- özel tablo.
        kvkkc_table(),
        array(
            'consent_id'     => $record['consent_id'],
            'created_at'     => gmdate( 'Y-m-d H:i:s' ),
            'action'         => $record['action'],
            'categories'     => implode( ',', $record['categories'] ),
            'policy_version' => (int) $record['policy_version'],
            'ip_hash'        => $record['ip_hash'],
            'page'           => $record['page'],
        ),
        array( '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
    );
}

/**
 * Sayfa önbelleklerini temizler. Banner ayarları önbelleğe alınan HTML'in içinde
 * taşındığı için her kayıttan sonra çağrılır.
 */
function kvkkc_purge_caches() {
    do_action( 'litespeed_purge_all' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- LiteSpeed Cache kancası.

    if ( function_exists( 'rocket_clean_domain' ) ) {
        rocket_clean_domain();
    }
    if ( function_exists( 'w3tc_flush_all' ) ) {
        w3tc_flush_all();
    }
    if ( function_exists( 'wp_cache_clear_cache' ) ) {
        wp_cache_clear_cache();
    }
}

/**
 * Yönetim ekranındaki sabit SVG simgeleri basılırken izin verilen etiketler (wp_kses ile).
 *
 * @return array
 */
function kvkkc_svg_allowed_html() {
    $shape = array(
        'class'        => true,
        'd'            => true,
        'x'            => true,
        'y'            => true,
        'width'        => true,
        'height'       => true,
        'rx'           => true,
        'cx'           => true,
        'cy'           => true,
        'r'            => true,
        'fill'         => true,
        'stroke'       => true,
        'stroke-width' => true,
    );

    return array(
        'svg'    => array(
            'class'           => true,
            'viewbox'         => true,
            'width'           => true,
            'height'          => true,
            'fill'            => true,
            'stroke'          => true,
            'stroke-width'    => true,
            'stroke-linecap'  => true,
            'stroke-linejoin' => true,
            'aria-hidden'     => true,
            'focusable'       => true,
        ),
        'path'   => $shape,
        'rect'   => $shape,
        'circle' => $shape,
    );
}
