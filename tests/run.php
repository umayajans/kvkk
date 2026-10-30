<?php
/**
 * WordPress gerektirmeyen bağımsız kontroller.
 *
 * Çalıştırma: php tests/run.php
 * HTML API sınıfları bir WordPress kurulumundan alınır. Varsayılan yol ../tdsite,
 * farklıysa: KVKKC_WP_DIR=/yol/wordpress php tests/run.php
 *
 * @package KVKK_Cerez
 */

if ( 'cli' !== PHP_SAPI ) {
    exit;
}

define( 'ABSPATH', __DIR__ . '/' );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'DAY_IN_SECONDS', 86400 );
define( 'KVKKC_BASENAME', 'kvkk-cerez/kvkk-cerez.php' );

$kvkkc_wp_dir = getenv( 'KVKKC_WP_DIR' ) ? rtrim( getenv( 'KVKKC_WP_DIR' ), '/\\' ) : dirname( __DIR__, 2 ) . '/tdsite';
if ( ! is_file( $kvkkc_wp_dir . '/wp-includes/html-api/class-wp-html-tag-processor.php' ) ) {
    echo 'WordPress bulunamadı: ' . $kvkkc_wp_dir . ' (KVKKC_WP_DIR ile yol verin)' . PHP_EOL;
    exit( 1 );
}

// ---------------------------------------------------------------------
// Minimal WordPress stub'ları (yalnızca test edilen kodun ihtiyacı kadar)
// ---------------------------------------------------------------------
$GLOBALS['kvkkc_test_options']    = array();
$GLOBALS['kvkkc_test_transients'] = array();

function __( $text ) {
    return $text;
}

function _x( $text ) {
    return $text;
}

function _doing_it_wrong() {
}

function wp_kses_uri_attributes() {
    return array( 'action', 'cite', 'codebase', 'data', 'formaction', 'href', 'poster', 'src', 'srcset', 'usemap', 'xmlns' );
}

function esc_url( $url ) {
    return str_replace( array( '"', "'", '<', '>', ' ' ), '', (string) $url );
}

function esc_url_raw( $url ) {
    $url = trim( (string) $url );
    return preg_match( '#^(https?:)?//|^/#i', $url ) ? esc_url( $url ) : '';
}

function esc_attr( $text ) {
    return htmlspecialchars( (string) $text, ENT_QUOTES );
}

function esc_html( $text ) {
    return htmlspecialchars( (string) $text, ENT_QUOTES );
}

function wp_parse_url( $url, $component = -1 ) {
    return parse_url( $url, $component );
}

function wp_strip_all_tags( $text ) {
    return trim( strip_tags( (string) $text ) );
}

function sanitize_text_field( $text ) {
    return trim( preg_replace( '/[\r\n\t ]+/', ' ', strip_tags( (string) $text ) ) );
}

function sanitize_textarea_field( $text ) {
    return trim( strip_tags( (string) $text ) );
}

function sanitize_key( $key ) {
    return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) );
}

function sanitize_hex_color( $color ) {
    return preg_match( '/^#([A-Fa-f0-9]{3}){1,2}$/', (string) $color ) ? $color : null;
}

function wp_kses( $text, $allowed ) {
    $text = preg_replace( '#<(script|style)[^>]*>.*?</\1>#is', '', (string) $text );
    $text = strip_tags( $text, '<' . implode( '><', array_keys( $allowed ) ) . '>' );
    return preg_replace( '/\s+on[a-z]+="[^"]*"/i', '', $text );
}

function absint( $value ) {
    return abs( (int) $value );
}

function wp_json_encode( $data, $flags = 0 ) {
    return json_encode( $data, $flags );
}

function wp_salt( $scheme = '' ) {
    return 'test-salt-' . $scheme;
}

function wp_generate_uuid4() {
    return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0x0fff ) | 0x4000, mt_rand( 0, 0x3fff ) | 0x8000, mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ) );
}

function get_option( $name, $default = false ) {
    return array_key_exists( $name, $GLOBALS['kvkkc_test_options'] ) ? $GLOBALS['kvkkc_test_options'][ $name ] : $default;
}

function get_bloginfo() {
    return 'Test Sitesi';
}

function get_transient( $key ) {
    $item = isset( $GLOBALS['kvkkc_test_transients'][ $key ] ) ? $GLOBALS['kvkkc_test_transients'][ $key ] : null;
    return ( $item && $item[1] > time() ) ? $item[0] : false;
}

function set_transient( $key, $value, $ttl ) {
    $GLOBALS['kvkkc_test_transients'][ $key ] = array( $value, time() + (int) $ttl );
    return true;
}

function _wp_can_use_pcre_u() {
    return true;
}

$GLOBALS['kvkkc_test_filters'] = array();
$GLOBALS['kvkkc_test_ajax']    = false;

function add_filter( $hook ) {
    $GLOBALS['kvkkc_test_filters'][] = $hook;
    return true;
}

function wp_unslash( $value ) {
    return is_string( $value ) ? stripslashes( $value ) : $value;
}

function wp_doing_ajax() {
    return $GLOBALS['kvkkc_test_ajax'];
}

function is_wp_error( $thing ) {
    return $thing instanceof WP_Error;
}

function home_url( $path = '' ) {
    return 'http://test.local' . $path;
}

function site_url( $path = '' ) {
    return 'http://test.local' . $path;
}

/**
 * WP_REST_Request yerine yalnızca başlık okuyan sahte istek.
 */
class KVKKC_Test_Request {
    private $headers;

    public function __construct( $headers ) {
        $this->headers = $headers;
    }

    public function get_header( $key ) {
        return isset( $this->headers[ $key ] ) ? $this->headers[ $key ] : null;
    }
}

class WP_Error {
    private $code;

    public function __construct( $code = '' ) {
        $this->code = $code;
    }

    public function get_error_code() {
        return $this->code;
    }
}

foreach ( array( 'compat-utf8.php', 'utf8.php', 'class-wp-token-map.php', 'html-api/html5-named-character-references.php', 'html-api/class-wp-html-attribute-token.php', 'html-api/class-wp-html-span.php', 'html-api/class-wp-html-text-replacement.php', 'html-api/class-wp-html-decoder.php', 'html-api/class-wp-html-tag-processor.php' ) as $kvkkc_file ) {
    require_once $kvkkc_wp_dir . '/wp-includes/' . $kvkkc_file;
}

require dirname( __DIR__ ) . '/includes/functions.php';
require dirname( __DIR__ ) . '/includes/class-activator.php';
require dirname( __DIR__ ) . '/includes/class-services.php';
require dirname( __DIR__ ) . '/includes/class-blocker.php';
require dirname( __DIR__ ) . '/includes/class-consent-log.php';
require dirname( __DIR__ ) . '/includes/class-scanner.php';
require dirname( __DIR__ ) . '/includes/class-admin.php';
require dirname( __DIR__ ) . '/includes/class-integrations.php';

// ---------------------------------------------------------------------
// Mini assert
// ---------------------------------------------------------------------
$GLOBALS['kvkkc_checks'] = 0;
$GLOBALS['kvkkc_fails']  = 0;

function check( $label, $actual, $expected ) {
    ++$GLOBALS['kvkkc_checks'];
    if ( $actual !== $expected ) {
        ++$GLOBALS['kvkkc_fails'];
        echo 'FAIL ' . $label . ': beklenen ' . var_export( $expected, true ) . ', gelen ' . var_export( $actual, true ) . PHP_EOL;
    }
}

function has( $label, $haystack, $needle, $expected = true ) {
    check( $label, false !== strpos( $haystack, $needle ), $expected );
}

/**
 * Öznitelik sırasından bağımsız: verilen etiketlerden biri tüm öznitelik parçalarını içeriyor mu?
 */
function tag_has( $label, $html, $tag, array $parts ) {
    $found = false;
    if ( preg_match_all( '#<' . $tag . '\b[^>]*>#i', $html, $m ) ) {
        foreach ( $m[0] as $open ) {
            $ok = true;
            foreach ( $parts as $part ) {
                $ok = $ok && false !== strpos( $open, $part );
            }
            $found = $found || $ok;
        }
    }
    check( $label, $found, true );
}

// ---------------------------------------------------------------------
// Servis veritabanı ve URL eşleme
// ---------------------------------------------------------------------
$rules = KVKKC_Services::block_rules( array(), '' );

$url_cases = array(
    'gtag GA4'                 => array( 'https://www.googletagmanager.com/gtag/js?id=G-PVCFSJQD8H', 'analitik' ),
    'gtag Ads en uzun eşleşme' => array( 'https://www.googletagmanager.com/gtag/js?id=AW-359009430', 'pazarlama' ),
    'gtm.js'                   => array( 'https://www.googletagmanager.com/gtm.js?id=GTM-TK78ZGV', 'analitik' ),
    'protokolsüz meta'         => array( '//connect.facebook.net/en_US/fbevents.js', 'pazarlama' ),
    'youtube iframe api'       => array( 'https://www.youtube.com/iframe_api', 'pazarlama' ),
    'youtube nocookie'         => array( 'https://www.youtube-nocookie.com/embed/abc', 'pazarlama' ),
    'yandex'                   => array( 'https://mc.yandex.ru/metrika/tag.js', 'analitik' ),
    'clarity'                  => array( 'https://www.clarity.ms/tag/abc', 'analitik' ),
    'google maps işlevsel'     => array( 'https://www.google.com/maps/embed?pb=1', 'islevsel' ),
    'recaptcha zorunlu'        => array( 'https://www.google.com/recaptcha/api.js', 'zorunlu' ),
    'büyük harf host'          => array( 'HTTPS://WWW.GOOGLETAGMANAGER.COM/gtm.js', 'analitik' ),
    'alan adı sınırı (önek)'   => array( 'https://evil-googletagmanager.com/gtm.js', '' ),
    'alan adı sınırı (sonek)'  => array( 'https://www.googletagmanager.com.evil.com/gtm.js', '' ),
    'birinci taraf'            => array( '/wp-content/plugins/x/app.js', '' ),
    'site adresi'              => array( 'http://tdsite.test/wp-includes/js/jquery/jquery.min.js', '' ),
    'boş'                      => array( '', '' ),
);
foreach ( $url_cases as $label => $case ) {
    check( 'match_url: ' . $label, KVKKC_Services::match_url( $case[0], $rules ), $case[1] );
}

$inline_cases = array(
    'gtm snippet'      => array( "(function(w,d,s,l,i){j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;})(window,document,'script','dataLayer','GTM-X');", 'analitik' ),
    'yandex snippet'   => array( '(function(m,e,t,r,i,k,a){})(window, document, "script", "https://mc.yandex.ru/metrika/tag.js", "ym"); ym(1, "init", {});', 'analitik' ),
    'meta snippet'     => array( "!function(f,b,e,v,n,t,s){}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');", 'pazarlama' ),
    'clarity snippet'  => array( '(function(c,l,a,r,i,t,y){t.src="https://www.clarity.ms/tag/"+i;})(window, document, "clarity", "script", "x");', 'analitik' ),
    'elementor config' => array( 'var elementorFrontendConfig = {"urls":{"assets":"https:\/\/tdsite.test\/"},"youtube":"https:\/\/www.youtube.com\/watch"};', '' ),
    'gtag kuyruğu'     => array( "window.dataLayer = window.dataLayer || []; function gtag(){dataLayer.push(arguments);} gtag('config','G-1');", '' ),
);
foreach ( $inline_cases as $label => $case ) {
    check( 'match_inline: ' . $label, KVKKC_Services::match_inline( $case[0], $rules ), $case[1] );
}

$manual = KVKKC_Services::block_rules( array(), "ornek-izleme.com|pazarlama\ngoogletagmanager.com/gtm.js|zorunlu\nozel_kod(|analitik" );
check( 'elle kural: alan adı', KVKKC_Services::match_url( 'https://cdn.ornek-izleme.com/a.js', $manual ), 'pazarlama' );
check( 'elle kural: zorunlu izin listesi kazanır', KVKKC_Services::match_url( 'https://www.googletagmanager.com/gtm.js?id=1', $manual ), 'zorunlu' );
check( 'elle kural: satır içi kod', KVKKC_Services::match_inline( 'ozel_kod("x");', $manual ), 'analitik' );

$cookie_cases = array(
    '_ga'                        => 'google-analytics',
    '_ga_PVCFSJQD8H'             => 'google-analytics',
    '_gcl_au'                    => 'google-ads',
    '_fbp'                       => 'meta-pixel',
    '_ym_uid'                    => 'yandex-metrica',
    'wordpress_logged_in_abc123' => 'wordpress',
    'kvkkc_consent'                => 'kvkk-cerez',
    'pum-1234'                   => 'popup-maker',
    'bilinmeyen_cerez'           => '',
);
foreach ( $cookie_cases as $name => $expected ) {
    check( 'cookie_service: ' . $name, KVKKC_Services::cookie_service( $name ), $expected );
}

foreach ( KVKKC_Services::all() as $kvkkc_id => $kvkkc_service ) {
    check( 'servis kategorisi geçerli: ' . $kvkkc_id, in_array( $kvkkc_service['category'], kvkkc_categories(), true ), true );
    check( 'servis adı var: ' . $kvkkc_id, '' !== $kvkkc_service['name'], true );
    foreach ( $kvkkc_service['markers'] as $kvkkc_marker ) {
        check( 'işaret geçerli: ' . $kvkkc_marker, null !== KVKKC_Services::parse_marker( $kvkkc_marker ), true );
    }
}

// Envanter kategorisi yerleşik varsayılanı ezer.
$inv_rules = KVKKC_Services::block_rules(
    array(
        'google-tag-manager' => array(
            'category' => 'zorunlu',
            'markers'  => array( 'googletagmanager.com/gtm.js' ),
        ),
    ),
    ''
);
check( 'envanter kategorisi önceliklidir', KVKKC_Services::match_url( 'https://www.googletagmanager.com/gtm.js', $inv_rules ), 'zorunlu' );
check( 'envanter satır içi kategorisi', KVKKC_Services::match_inline( "j.src='https://www.googletagmanager.com/gtm.js?id='", $inv_rules ), 'zorunlu' );

// ---------------------------------------------------------------------
// Engelleyici: HTML yeniden yazımı
// ---------------------------------------------------------------------
function rw( $html ) {
    return KVKKC_Blocker::rewrite( $html, $GLOBALS['rules'] );
}

$out = rw( '<script src="https://www.googletagmanager.com/gtag/js?id=G-1&amp;l=dl" async></script>' );
has( 'script src: tip text/plain', $out, 'type="text/plain"' );
has( 'script src: kategori', $out, 'data-kvkkc-cat="analitik"' );
has( 'script src: adres taşındı ve &amp; korunur', $out, 'data-kvkkc-src="https://www.googletagmanager.com/gtag/js?id=G-1&amp;l=dl"' );
has( 'script src: src silindi', $out, ' src=', false );
has( 'script src: async kalır', $out, 'async' );

$out = rw( "<script>(function(w,d,s,l,i){j.src='https://www.googletagmanager.com/gtm.js?id='+i;})(window,document,'script','dataLayer','GTM-X');</script>" );
tag_has( 'gtm satır içi: engellendi', $out, 'script', array( 'type="text/plain"', 'data-kvkkc-cat="analitik"' ) );
has( 'gtm satır içi: içerik aynen', $out, "j.src='https://www.googletagmanager.com/gtm.js?id='+i;" );

$json = '<script type="application/ld+json">{"url":"https://www.youtube.com/x","fbevents.js":1}</script>';
check( 'ld+json dokunulmaz', rw( $json ), $json );

$out = rw( '<script type="module" src="https://static.hotjar.com/c/x.js"></script>' );
has( 'module: özgün tip saklanır', $out, 'data-kvkkc-type="module"' );
has( 'module: text/plain', $out, 'type="text/plain"' );

$skip = '<script data-kvkkc-skip src="https://www.googletagmanager.com/gtag/js?id=G-1"></script>';
check( 'data-kvkkc-skip dokunulmaz', rw( $skip ), $skip );

$own = "<script id=\"kvkkc-guard\">var x='https://www.googletagmanager.com/gtm.js';</script>";
check( 'kendi scriptimiz dokunulmaz', rw( $own ), $own );

$plain = '<script type="text/plain" data-category="analytics" src="https://www.googletagmanager.com/gtm.js"></script>';
check( 'zaten text/plain olan dokunulmaz', rw( $plain ), $plain );

$first = '<script src="/wp-content/plugins/x/app.js"></script><script src="https://www.google.com/recaptcha/api.js"></script>';
check( 'birinci taraf ve zorunlu dokunulmaz', rw( $first ), $first );

$out = rw( '<iframe src="https://www.youtube.com/embed/abc?feature=oembed" width="560" height="315" class="video"></iframe>' );
has( 'iframe: adres taşındı', $out, 'data-kvkkc-src="https://www.youtube.com/embed/abc?feature=oembed"' );
has( 'iframe: src silindi', $out, ' src=', false );
has( 'iframe: sınıf eklendi', $out, 'kvkkc-blocked' );
has( 'iframe: mevcut sınıf korunur', $out, 'video' );
has( 'iframe: kategori', $out, 'data-kvkkc-cat="pazarlama"' );

$out = rw( '<iframe class="elementor-video-iframe" data-lazy-load="https://player.vimeo.com/video/1"></iframe>' );
has( 'elementor lazy iframe: adres taşındı', $out, 'data-kvkkc-src="https://player.vimeo.com/video/1"' );
has( 'elementor lazy iframe: data-lazy-load silindi', $out, 'data-lazy-load', false );

$out = rw( '<iframe src="about:blank" data-src="https://www.youtube.com/embed/x"></iframe>' );
has( 'lazy data-src iframe engellendi', $out, 'data-kvkkc-src="https://www.youtube.com/embed/x"' );

$out = rw( '<noscript><img height="1" width="1" src="https://www.facebook.com/tr?id=1&ev=PageView"/></noscript><img src="https://www.facebook.com/tr?id=2">' );
tag_has( 'noscript img engellendi', $out, 'img', array( 'height="1"', 'data-kvkkc-src="https://www.facebook.com/tr?id=1&amp;ev=PageView"' ) );
has( 'noscript dışındaki img dokunulmaz', $out, '<img src="https://www.facebook.com/tr?id=2">' );

$out = rw( '<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-X" height="0"></iframe></noscript>' );
has( 'noscript iframe engellendi', $out, 'data-kvkkc-src="https://www.googletagmanager.com/ns.html?id=GTM-X"' );

$out = rw( '<link rel="preconnect" href="https://www.googletagmanager.com"><link rel="stylesheet" href="https://fonts.googleapis.com/css">' );
has( 'preconnect kaldırıldı', $out, 'data-kvkkc-href="https://www.googletagmanager.com"' );
has( 'stil dosyası dokunulmaz', $out, '<link rel="stylesheet" href="https://fonts.googleapis.com/css">' );
$shared = '<link rel="preconnect" href="https://www.gstatic.com"><link rel="dns-prefetch" href="//www.google.com">';
check( 'zorunlu hizmetle paylaşılan alan adına dokunulmaz', rw( $shared ), $shared );
check( 'match_host: yalnızca rıza gerektiren alan adı', KVKKC_Services::match_host( 'https://mc.yandex.ru', $rules ), 'analitik' );
check( 'match_host: paylaşılan alan adı', KVKKC_Services::match_host( 'https://www.google.com/', $rules ), '' );

check( 'HTML olmayan girdi', rw( '{"a":1}' ), '{"a":1}' );
check( 'boş girdi', rw( '' ), '' );

// Gerçek sayfa (yerelde varsa): engelleme sayısı ve süre.
$fixture = __DIR__ . '/fixtures/tdsite-home-raw.html';
if ( is_file( $fixture ) ) {
    $html  = file_get_contents( $fixture );
    $start = microtime( true );
    $out   = rw( $html );
    $ms    = ( microtime( true ) - $start ) * 1000;
    check( 'fikstür: gtm engellendi', 1 === preg_match( '#<script[^>]*type="text/plain"[^>]*>(?:(?!</script>).)*googletagmanager\.com/gtm\.js#s', $out ), true );
    check( 'fikstür: yandex engellendi', 1 === preg_match( '#<script[^>]*type="text/plain"[^>]*>(?:(?!</script>).)*mc\.yandex\.ru/metrika/tag\.js#s', $out ), true );
    check( 'fikstür: gtag.js engellendi', false !== strpos( $out, 'data-kvkkc-src="https://www.googletagmanager.com/gtag/js?id=G-PVCFSJQD8H"' ), true );
    check( 'fikstür: boyut korunur', abs( strlen( $out ) - strlen( $html ) ) < 2000, true );
    check( 'fikstür: süre 150 ms altında', $ms < 150, true );
    printf( 'fikstür: %d KB, %.1f ms' . PHP_EOL, strlen( $html ) / 1024, $ms );
}

// ---------------------------------------------------------------------
// Rıza çerezi
// ---------------------------------------------------------------------
$uuid = '6f1c2a3b-4d5e-4f60-8a7b-9c0d1e2f3a4b';
$raw  = kvkkc_consent_cookie_value( 3, 1730000000, $uuid, array( 'pazarlama', 'analitik' ) );
check( 'çerez değeri biçimi', $raw, '3.1730000000.' . $uuid . '.analitik-pazarlama' );
check(
    'çerez çözümleme',
    kvkkc_parse_consent( $raw ),
    array(
        'v'  => 3,
        't'  => 1730000000,
        'id' => $uuid,
        'c'  => array( 'analitik', 'pazarlama' ),
    )
);
check(
    'çerez: kimliksiz ve kategorisiz',
    kvkkc_parse_consent( '2.1730000000..' ),
    array(
        'v'  => 2,
        't'  => 1730000000,
        'id' => '',
        'c'  => array(),
    )
);
check( 'çerez: bozuk', kvkkc_parse_consent( 'abc' ), null );
check(
    'çerez: bilinmeyen kategori atılır',
    kvkkc_parse_consent( '1.5..analitik-hacker-zorunlu' ),
    array(
        'v'  => 1,
        't'  => 5,
        'id' => '',
        'c'  => array( 'analitik' ),
    )
);
check( 'çerez: geçersiz kimlik', kvkkc_parse_consent( '1.5.<script>.analitik' ), null );
check( 'çerez: dizi değil', kvkkc_parse_consent( array( 'x' ) ), null );

$GLOBALS['kvkkc_test_options']['kvkkc_policy_version'] = 3;
check( 'has_consent: zorunlu her zaman', kvkkc_has_consent( 'zorunlu', '' ), true );
check( 'has_consent: verilen kategori', kvkkc_has_consent( 'analitik', $raw ), true );
check( 'has_consent: verilmeyen kategori', kvkkc_has_consent( 'islevsel', $raw ), false );
check( 'has_consent: çerez yok', kvkkc_has_consent( 'analitik', '' ), false );
$GLOBALS['kvkkc_test_options']['kvkkc_policy_version'] = 4;
check( 'has_consent: eski sürüm geçersiz', kvkkc_has_consent( 'analitik', $raw ), false );

// ---------------------------------------------------------------------
// Rıza kaydı doğrulama
// ---------------------------------------------------------------------
$rec = KVKKC_Consent_Log::normalize(
    array(
        'action'     => 'custom',
        'categories' => array( 'pazarlama', 'analitik', 'analitik', 'zorunlu', 'x' ),
        'version'    => 4,
        'page'       => 'https://tdsite.test/hakkimizda/?utm_source=x&ad=Ali',
        'id'         => $uuid,
    )
);
check( 'normalize: kategoriler süzülür ve sıralanır', $rec['categories'], array( 'analitik', 'pazarlama' ) );
check( 'normalize: yalnızca yol saklanır', $rec['page'], '/hakkimizda/' );
check( 'normalize: geçerli kimlik korunur', $rec['id'], $uuid );
check( 'normalize: sürüm', $rec['version'], 4 );

$rec = KVKKC_Consent_Log::normalize(
    array(
        'action'     => 'accept_all',
        'categories' => array(),
        'version'    => 9,
        'page'       => '/',
        'id'         => 'sahte',
    )
);
check( 'normalize: tümünü kabul tüm kategoriler', $rec['categories'], array( 'islevsel', 'analitik', 'pazarlama' ) );
check( 'normalize: sahte kimlik yerine yenisi', 1 === preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $rec['id'] ), true );
check( 'normalize: sürüm güncelden büyük olamaz', $rec['version'], 4 );

$rec = KVKKC_Consent_Log::normalize(
    array(
        'action'     => 'reject_all',
        'categories' => array( 'analitik' ),
        'version'    => 4,
    )
);
check( 'normalize: tümünü reddet kategori bırakmaz', $rec['categories'], array() );
check( 'normalize: sayfa yoksa boş', $rec['page'], '' );

check(
    'normalize: geçersiz eylem',
    is_wp_error(
        KVKKC_Consent_Log::normalize(
            array(
                'action'  => 'drop table',
                'version' => 1,
            )
        )
    ),
    true
);
$rec = KVKKC_Consent_Log::normalize(
    array(
        'action'     => 'custom',
        'categories' => 'analitik',
        'version'    => 1,
    )
);
check( 'normalize: kategoriler dizi değil', $rec['categories'], array() );
$rec = KVKKC_Consent_Log::normalize(
    array(
        'action'  => 'custom',
        'version' => 1,
        'page'    => '/' . str_repeat( 'a', 400 ),
    )
);
check( 'normalize: uzun sayfa yolu kırpılır', strlen( $rec['page'] ), 255 );

// İzin denetimi: istek sitenin kendi sayfasından gelmeli.
foreach ( array(
    'başlıksız istek reddedilir'   => array( array( 'host' => 'test.local' ), false ),
    'aynı site Origin geçer'       => array( array( 'host' => 'test.local', 'origin' => 'http://test.local' ), true ),
    'Origin null reddedilir'       => array( array( 'host' => 'test.local', 'origin' => 'null' ), false ),
    'başka site Origin reddedilir' => array( array( 'host' => 'test.local', 'origin' => 'https://evil.example' ), false ),
    'Origin yoksa Referer geçer'   => array( array( 'host' => 'test.local:8080', 'referer' => 'http://test.local:8080/sayfa/' ), true ),
) as $kvkkc_label => $kvkkc_case ) {
    check( 'same_origin: ' . $kvkkc_label, true === KVKKC_Consent_Log::same_origin( new KVKKC_Test_Request( $kvkkc_case[0] ) ), $kvkkc_case[1] );
}

// ---------------------------------------------------------------------
// IP özeti, hız sınırı, CSV
// ---------------------------------------------------------------------
check( 'ip_hash: aynı /24 aynı özet', kvkkc_ip_hash( '88.230.1.2' ), kvkkc_ip_hash( '88.230.1.250' ) );
check( 'ip_hash: farklı /24 farklı özet', kvkkc_ip_hash( '88.230.1.2' ) !== kvkkc_ip_hash( '88.230.2.2' ), true );
check( 'ip_hash: ham IP içermez', false === strpos( kvkkc_ip_hash( '88.230.1.2' ), '88.230' ), true );
check( 'ip_hash: 64 karakter', strlen( kvkkc_ip_hash( '2a02:ff0:1:2::1' ) ), 64 );
check( 'ip_hash: aynı IPv6 /48 aynı özet', kvkkc_ip_hash( '2a02:ff0:1:2::1' ), kvkkc_ip_hash( '2a02:ff0:1:ffff::9' ) );
check( 'ip_hash: geçersiz', kvkkc_ip_hash( 'abc' ), '' );

check( 'throttle: başlangıç serbest', kvkkc_throttle( 'k', 2, 60 ), 0 );
kvkkc_throttle( 'k', 2, 60, true );
check( 'throttle: 1. kayıt sonrası serbest', kvkkc_throttle( 'k', 2, 60 ), 0 );
kvkkc_throttle( 'k', 2, 60, true );
check( 'throttle: sınırda bekleme süresi', kvkkc_throttle( 'k', 2, 60 ) > 0, true );

$csv_cases = array(
    '=1+1'     => "'=1+1",
    '+90532'   => "'+90532",
    '-x'       => "'-x",
    '@SUM(A1)' => "'@SUM(A1)",
    "\tx"      => "'\tx",
    'analitik' => 'analitik',
    ''         => '',
);
foreach ( $csv_cases as $input => $expected ) {
    check( 'csv_cell: ' . json_encode( (string) $input ), KVKKC_Consent_Log::csv_cell( (string) $input ), $expected );
}

// ---------------------------------------------------------------------
// Ayarların temizlenmesi
// ---------------------------------------------------------------------
$defaults = KVKKC_Activator::default_settings();
$clean    = KVKKC_Admin::sanitize(
    array(
        'layout'        => 'popup<script>',
        'color_bg'      => 'kırmızı',
        'color_text'    => '#112233',
        'font_size'     => '99',
        'consent_days'  => '0',
        'float_enabled' => '1',
        'title'         => '<b>Çerez</b> Tercihleri',
        'message'       => '<p onclick="x()">Metin <a href="/cerez/">link</a><script>alert(1)</script></p>',
        'custom_css'    => '.kvkkc{color:red}',
        'rules'         => "ornek-izleme.com|pazarlama\n# yorum\nbos|olmayan\n|analitik\nx.com/a | Analitik ",
        'bilinmeyen'    => 'x',
    )
);
check( 'sanitize: geçersiz seçim varsayılana döner', $clean['layout'], $defaults['layout'] );
check( 'sanitize: geçersiz renk varsayılana döner', $clean['color_bg'], $defaults['color_bg'] );
check( 'sanitize: geçerli renk', $clean['color_text'], '#112233' );
check( 'sanitize: sayı üst sınır', $clean['font_size'], 18 );
check( 'sanitize: sayı alt sınır', $clean['consent_days'], 30 );
check( 'sanitize: onay kutusu açık', $clean['float_enabled'], 1 );
check( 'sanitize: işaretlenmeyen onay kutusu kapalı', $clean['lock_scroll'], 0 );
check( 'sanitize: düz metin etiket temizlenir', $clean['title'], 'Çerez Tercihleri' );
has( 'sanitize: mesajda link kalır', $clean['message'], '<a href="/cerez/">link</a>' );
has( 'sanitize: mesajda script kalmaz', $clean['message'], '<script', false );
has( 'sanitize: mesajda olay özniteliği kalmaz', $clean['message'], 'onclick', false );
check( 'sanitize: bilinmeyen anahtar atılır', array_key_exists( 'bilinmeyen', $clean ), false );
check( 'sanitize: eski özel CSS anahtarı atılır', array_key_exists( 'custom_css', $clean ), false );
check( 'sanitize: kurallar normalize', $clean['rules'], "ornek-izleme.com|pazarlama\nx.com/a|analitik" );
check( 'parse_rules', KVKKC_Services::parse_rules( $clean['rules'] ), array( array( 'ornek-izleme.com', 'pazarlama' ), array( 'x.com/a', 'analitik' ) ) );
check( 'sanitize: her şema alanı dolu', array_keys( $clean ) == array_keys( KVKKC_Admin::fields() ), true );

// ---------------------------------------------------------------------
// Envanter temizliği
// ---------------------------------------------------------------------
$inv = KVKKC_Services::sanitize_inventory(
    array(
        'google-analytics' => array(
            'name'     => 'GA <b>x</b>',
            'category' => 'yanlis',
            'party'    => '3',
            'cookies'  => array(
                array(
                    'name'     => '_ga',
                    'duration' => '2 yıl',
                    'purpose'  => 'Ziyaretçiyi ayırt eder.',
                ),
                array( 'name' => '' ),
            ),
            'markers'  => "google-analytics.com\nbad marker!\nanalytics.google.com/g/collect",
        ),
        'Özel Hizmet!'     => array(
            'name'     => 'Özel',
            'category' => 'analitik',
        ),
        'bos'              => array( 'name' => '' ),
        'dizi-degil'       => 'x',
    )
);
check( 'envanter: kimlikler', array_keys( $inv ), array( 'google-analytics', 'zelhizmet' ) );
check( 'envanter: ad temizlenir', $inv['google-analytics']['name'], 'GA x' );
check( 'envanter: geçersiz kategori yerleşik varsayılana döner', $inv['google-analytics']['category'], 'analitik' );
check( 'envanter: boş çerez satırı atılır', count( $inv['google-analytics']['cookies'] ), 1 );
check( 'envanter: geçersiz işaret atılır', $inv['google-analytics']['markers'], array( 'google-analytics.com', 'analytics.google.com/g/collect' ) );
check( 'envanter: taraf', $inv['google-analytics']['party'], 3 );
check( 'envanter: özel hizmet kategorisi', $inv['zelhizmet']['category'], 'analitik' );

// ---------------------------------------------------------------------
// Tarayıcı: sayfa çözümleme
// ---------------------------------------------------------------------
$scan = KVKKC_Scanner::analyze(
    '<head><script src="https://www.googletagmanager.com/gtag/js?id=G-1"></script>'
    . "<script>j.src='https://www.googletagmanager.com/gtm.js?id='+i;</script>"
    . '<script src="https://cdn.bilinmeyen-izleme.net/t.js"></script>'
    . '<script src="http://tdsite.test/wp-includes/js/jquery.js"></script>'
    . '<script src="https://cdn.tdsite.test/x.js"></script>'
    . '</head><body><iframe src="https://www.youtube.com/embed/x"></iframe>'
    . '<noscript><img src="https://mc.yandex.ru/watch/1"></noscript></body>',
    array(
        '_lscache_vary' => 0,
        'tdf_kaynak'    => 1800000000,
    ),
    'tdsite.test'
);
check( 'tarama: hizmetler (çerezi tanınan LiteSpeed dahil)', $scan['services'], array( 'google-analytics', 'google-tag-manager', 'litespeed', 'yandex-metrica', 'youtube' ) );
check( 'tarama: bilinmeyen üçüncü taraf alan adı', $scan['hosts'], array( 'cdn.bilinmeyen-izleme.net' ) );
check( 'tarama: bilinen çerez hizmete bağlanır', $scan['cookies']['_lscache_vary']['service'], 'litespeed' );
check( 'tarama: bilinmeyen çerez', $scan['cookies']['tdf_kaynak']['service'], '' );

$scan = KVKKC_Scanner::analyze(
    '<div class="elementor-widget-video" data-settings="{&quot;youtube_url&quot;:&quot;https:\/\/www.youtube.com\/watch?v=x&quot;}"></div>'
    . '<script>var pysOptions = {"staticEvents":{"facebook":{"init_event":[{"pixelIds":["1"]}]}}};</script>',
    array( 'PHPSESSID' => 0 ),
    'tdsite.test'
);
check( 'tarama: elementor, PYS ve tanınan çerezin hizmeti', $scan['services'], array( 'meta-pixel', 'php-oturum', 'youtube' ) );
check( 'tarama: PHP oturum çerezi', $scan['cookies']['PHPSESSID']['service'], 'php-oturum' );
check( 'tarama imzası engellemede kullanılmaz', KVKKC_Services::match_inline( 'var pysOptions = {"facebook":{"a":1}};', $rules ), '' );

// ---------------------------------------------------------------------
// PixelYourSite entegrasyonu
// ---------------------------------------------------------------------
KVKKC_Integrations::init();
foreach ( array( 'pys_disable_tiktok_by_gdpr', 'pys_check_consent_by_gdpr', 'pys_analytics_storage_mode', 'pys_ad_storage_mode', 'pys_ad_user_data_mode', 'pys_ad_personalization_mode' ) as $kvkkc_hook ) {
    check( 'pys filtresi bağlı: ' . $kvkkc_hook, in_array( $kvkkc_hook, $GLOBALS['kvkkc_test_filters'], true ), true );
}

$GLOBALS['kvkkc_test_options']['kvkkc_policy_version'] = 1;
$_COOKIE['kvkkc_consent']                           = '1.1730000000..analitik';

$GLOBALS['kvkkc_test_ajax'] = false;
check( 'pys izin modu: sayfa çıktısında analitik reddedildi', KVKKC_Integrations::pys_mode_analytics(), false );
check( 'pys izin modu: sayfa çıktısında reklam reddedildi', KVKKC_Integrations::pys_mode_ads(), false );
check( 'pys piksel: sayfa çıktısında kapalı', KVKKC_Integrations::pys_analytics_off(), true );

$GLOBALS['kvkkc_test_ajax'] = true;
check( 'pys izin modu: AJAX, analitik izni var', KVKKC_Integrations::pys_mode_analytics(), true );
check( 'pys izin modu: AJAX, pazarlama izni yok', KVKKC_Integrations::pys_mode_ads(), false );
check( 'pys piksel: AJAX, analitik açık', KVKKC_Integrations::pys_analytics_off(), false );
check( 'pys piksel: AJAX, pazarlama kapalı', KVKKC_Integrations::pys_marketing_off(), true );
check( 'pys sunucu olayı: tiktok pazarlamaya bağlı', KVKKC_Integrations::pys_check_consent( true, 'tiktok' ), false );
check( 'pys sunucu olayı: ga analitiğe bağlı', KVKKC_Integrations::pys_check_consent( true, 'ga' ), true );
unset( $_COOKIE['kvkkc_consent'] );
$GLOBALS['kvkkc_test_ajax'] = false;

// ---------------------------------------------------------------------
echo $GLOBALS['kvkkc_fails']
    ? $GLOBALS['kvkkc_fails'] . '/' . $GLOBALS['kvkkc_checks'] . ' kontrol BAŞARISIZ' . PHP_EOL
    : 'OK (' . $GLOBALS['kvkkc_checks'] . ' kontrol)' . PHP_EOL;
exit( $GLOBALS['kvkkc_fails'] ? 1 : 0 );
