<?php
/**
 * Rıza öncesi engelleme: sayfa çıktısındaki izleme kodlarını etkisizleştirir
 *
 * @package KVKK_Cerez
 */

// Doğrudan erişimi engelle.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Engelleyici
 *
 * Sayfa HTML'i WP_HTML_Tag_Processor ile taranır. Rıza gerektiren script'lerin tipi text/plain
 * yapılır, iframe ve noscript piksellerinin adresi data-kvkkc-src'ye taşınır.
 *
 * HTML'e iki yoldan ulaşılır. Çekirdeğin şablon çıktı tamponu (WordPress 6.9+) açıksa
 * wp_template_enhancement_output_buffer filtresi kullanılır. Tampon bir eklenti tarafından
 * kapatılmışsa (Elementor 6.9 ve üstünde kapatır) veya WordPress daha eskiyse, şablon
 * includes/template-wrapper.php üzerinden yüklenir ve tampon render_template() içinde açılıp kapanır.
 * Çıktı her ziyaretçi için aynıdır; rıza durumuna göre açma işi tarayıcıda yapılır. Bu sayede
 * sayfa önbelleği (LiteSpeed, WP Rocket) ile sorunsuz çalışır.
 */
class KVKKC_Blocker {

    /**
     * Engellenmeyen script tipleri dışında kalanlar (JSON, şablon vb.) hiç ellenmez.
     *
     * @var string[]
     */
    const JS_TYPES = array( '', 'text/javascript', 'application/javascript', 'text/ecmascript', 'application/ecmascript', 'module' );

    /**
     * Ara şablonun yükleyeceği asıl şablon.
     *
     * @var string
     */
    private static $template = '';

    /**
     * Kancalar.
     */
    public static function init() {
        add_action( 'template_redirect', array( __CLASS__, 'start' ), 0 );
    }

    /**
     * Engellemeyi bağlar. Çekirdek filtre şablon yüklenmeden önce eklendiği için çekirdek tamponu
     * kendiliğinden açar; açılmayacaksa maybe_wrap() ara şablona geçer.
     */
    public static function start() {
        if ( self::should_buffer() ) {
            add_filter( 'wp_template_enhancement_output_buffer', array( __CLASS__, 'callback' ) );
            add_filter( 'template_include', array( __CLASS__, 'maybe_wrap' ), PHP_INT_MAX );
        }
    }

    /**
     * Çekirdek tampon bu istekte açılmayacaksa asıl şablonu ara şablonla sarar.
     *
     * @param string $template Yüklenecek şablon.
     * @return string
     */
    public static function maybe_wrap( $template ) {
        $core = function_exists( 'wp_should_output_buffer_template_for_enhancement' ) && wp_should_output_buffer_template_for_enhancement();
        if ( $core || ! is_string( $template ) || '' === $template || ! is_file( $template ) ) {
            return $template;
        }

        self::$template = $template;
        return KVKKC_DIR . 'includes/template-wrapper.php';
    }

    /**
     * Asıl şablonu tamponlayarak yükler. Tampon bu fonksiyonda açılır ve finally ile her durumda
     * kapanır; şablonun kapatmadan bıraktığı iç tamponlar da sırayla boşaltılır. Çıktıyı callback()
     * işler.
     */
    public static function render_template() {
        $level = ob_get_level();
        ob_start( array( __CLASS__, 'callback' ) );
        try {
            load_template( self::$template, false );
        } finally {
            while ( ob_get_level() > $level ) {
                ob_end_flush();
            }
        }
    }

    /**
     * Bu istekte engelleme yapılmalı mı?
     *
     * @return bool
     */
    public static function should_buffer() {
        if ( ! kvkkc_setting( 'block_enabled' ) || is_admin() || wp_doing_ajax() || wp_doing_cron() || is_feed() || is_robots() || is_trackback() ) {
            return false;
        }

        if ( ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) || defined( 'LSCACHE_IS_ESI' ) ) {
            return false;
        }

        return ! KVKKC_Frontend::is_builder_preview() && ! KVKKC_Scanner::is_scan_request();
    }

    /**
     * Şablon çıktı filtresi. Bir hata olursa sayfa değiştirilmeden gönderilir.
     *
     * @param string $html Sayfa çıktısı.
     * @return string
     */
    public static function callback( $html ) {
        try {
            return self::rewrite( $html, KVKKC_Services::block_rules() );
        } catch ( Throwable $e ) {
            return $html;
        }
    }

    /**
     * HTML'i yeniden yazar.
     *
     * @param string $html  HTML.
     * @param array  $rules KVKKC_Services::block_rules() çıktısı.
     * @return string
     */
    public static function rewrite( $html, $rules ) {
        if ( ! is_string( $html ) || false === strpos( $html, '<' ) ) {
            return $html;
        }

        $p           = new WP_HTML_Tag_Processor( $html );
        $in_noscript = false;
        $changed     = false;

        while ( $p->next_tag( array( 'tag_closers' => 'visit' ) ) ) {
            $tag = $p->get_tag();

            if ( 'NOSCRIPT' === $tag ) {
                $in_noscript = ! $p->is_tag_closer();
                continue;
            }

            if ( $p->is_tag_closer() ) {
                continue;
            }

            if ( 'SCRIPT' === $tag ) {
                $changed = self::script( $p, $rules ) || $changed;
            } elseif ( 'IFRAME' === $tag ) {
                $changed = self::source( $p, $rules, array( 'src', 'data-src', 'data-lazy-load', 'data-lazy-src' ) ) || $changed;
            } elseif ( 'IMG' === $tag && $in_noscript ) {
                // Yalnızca noscript içindeki pikseller ele alınır; noscript dışında izleme görseli pek görülmez.
                $changed = self::source( $p, $rules, array( 'src' ) ) || $changed;
            } elseif ( 'LINK' === $tag ) {
                $changed = self::hint( $p, $rules ) || $changed;
            }
        }

        return $changed ? $p->get_updated_html() : $html;
    }

    /**
     * Script etiketi: dış kaynak adresi ya da satır içi içerik eşleşirse etkisizleştirilir.
     *
     * @param WP_HTML_Tag_Processor $p     İşlemci.
     * @param array                 $rules Kurallar.
     * @return bool Değişiklik yapıldı mı?
     */
    private static function script( $p, $rules ) {
        $type = $p->get_attribute( 'type' );
        $type = is_string( $type ) ? strtolower( trim( $type ) ) : '';

        if ( ! in_array( $type, self::JS_TYPES, true ) || null !== $p->get_attribute( 'data-kvkkc-skip' ) ) {
            return false;
        }

        $id = $p->get_attribute( 'id' );
        if ( is_string( $id ) && 0 === strpos( $id, 'kvkkc-' ) ) {
            return false;
        }

        $src      = $p->get_attribute( 'src' );
        $has_src  = is_string( $src ) && '' !== trim( $src );
        $category = $has_src ? KVKKC_Services::match_url( $src, $rules ) : KVKKC_Services::match_inline( $p->get_modifiable_text(), $rules );

        if ( '' === $category || 'zorunlu' === $category ) {
            return false;
        }

        $p->set_attribute( 'type', 'text/plain' );
        $p->set_attribute( 'data-kvkkc-cat', $category );

        if ( '' !== $type ) {
            $p->set_attribute( 'data-kvkkc-type', $type );
        }

        if ( $has_src ) {
            $p->set_attribute( 'data-kvkkc-src', $src );
            $p->remove_attribute( 'src' );
        }

        return true;
    }

    /**
     * Adres taşıyan etiketler (iframe, noscript pikseli): eşleşen adres data-kvkkc-src'ye taşınır.
     *
     * @param WP_HTML_Tag_Processor $p          İşlemci.
     * @param array                 $rules      Kurallar.
     * @param string[]              $attributes Bakılacak öznitelikler (sırayla).
     * @return bool
     */
    private static function source( $p, $rules, $attributes ) {
        foreach ( $attributes as $attribute ) {
            $url = $p->get_attribute( $attribute );
            if ( ! is_string( $url ) || '' === trim( $url ) ) {
                continue;
            }

            $category = KVKKC_Services::match_url( $url, $rules );
            if ( '' === $category || 'zorunlu' === $category ) {
                continue;
            }

            $p->set_attribute( 'data-kvkkc-src', $url );
            $p->set_attribute( 'data-kvkkc-cat', $category );
            $p->remove_attribute( $attribute );
            $p->add_class( 'kvkkc-blocked' );
            return true;
        }

        return false;
    }

    /**
     * Kaynak ipuçları (preconnect, preload vb.) rıza öncesi bağlantı kurmasın.
     *
     * @param WP_HTML_Tag_Processor $p     İşlemci.
     * @param array                 $rules Kurallar.
     * @return bool
     */
    private static function hint( $p, $rules ) {
        $rel  = strtolower( (string) $p->get_attribute( 'rel' ) );
        $href = $p->get_attribute( 'href' );

        if ( ! is_string( $href ) || ! preg_match( '/\b(preconnect|dns-prefetch|preload|modulepreload|prefetch)\b/', $rel ) ) {
            return false;
        }

        $category = KVKKC_Services::match_url( $href, $rules );
        $category = '' !== $category ? $category : KVKKC_Services::match_host( $href, $rules );
        if ( '' === $category || 'zorunlu' === $category ) {
            return false;
        }

        $p->set_attribute( 'data-kvkkc-href', $href );
        $p->remove_attribute( 'href' );
        return true;
    }
}
