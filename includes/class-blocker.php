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
 * Sayfa HTML'i tamponlanır ve WP_HTML_Tag_Processor ile taranır. Rıza gerektiren script'lerin
 * tipi text/plain yapılır, iframe ve noscript piksellerinin adresi data-kvkkc-src'ye taşınır.
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
     * Tamponun açıldığı seviye (açılmadıysa 0).
     *
     * @var int
     */
    private static $level = 0;

    /**
     * Kancalar.
     */
    public static function init() {
        add_action( 'template_redirect', array( __CLASS__, 'start' ), 0 );
        add_action( 'shutdown', array( __CLASS__, 'end' ), 0 );
    }

    /**
     * Tamponu başlatır.
     */
    public static function start() {
        if ( self::should_buffer() ) {
            ob_start( array( __CLASS__, 'callback' ) );
            self::$level = ob_get_level();
        }
    }

    /**
     * Tamponu kapatır. Üstünde başka bir eklentinin tamponu açık kaldıysa ona dokunulmaz;
     * o durumda WordPress'in kapanıştaki wp_ob_end_flush_all() çağrısı hepsini sırayla boşaltır.
     */
    public static function end() {
        if ( self::$level && ob_get_level() === self::$level ) {
            ob_end_flush();
        }
        self::$level = 0;
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
     * Tampon geri çağrısı. Bir hata olursa sayfa değiştirilmeden gönderilir.
     *
     * @param string $html Sayfa çıktısı.
     * @return string
     */
    public static function callback( $html ) {
        if ( ! self::is_html_response() ) {
            return $html;
        }

        try {
            return self::rewrite( $html, KVKKC_Services::block_rules() );
        } catch ( Throwable $e ) {
            return $html;
        }
    }

    /**
     * Yanıt HTML mi? Content-Type başlığı açıkça başka bir tür söylüyorsa dokunulmaz.
     *
     * @return bool
     */
    private static function is_html_response() {
        foreach ( headers_list() as $header ) {
            if ( 0 === stripos( $header, 'content-type:' ) ) {
                return false !== stripos( $header, 'html' );
            }
        }
        return true;
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
