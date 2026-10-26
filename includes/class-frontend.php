<?php
/**
 * Ön yüz: koruma scripti, çerez bandı, kısa kodlar
 *
 * @package KVKK_Cerez
 */

// Doğrudan erişimi engelle.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Ön yüz
 */
class KVKKC_Frontend {

    /**
     * Kancalar.
     */
    public static function init() {
        add_action( 'wp_head', array( __CLASS__, 'print_guard' ), 0 );
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
        add_filter( 'wp_script_attributes', array( __CLASS__, 'script_attributes' ) );
        add_filter( 'wp_inline_script_attributes', array( __CLASS__, 'script_attributes' ) );
        add_shortcode( 'kvkk_cerez_tablosu', array( __CLASS__, 'shortcode_table' ) );
        add_shortcode( 'kvkk_cerez_ayarlari', array( __CLASS__, 'shortcode_settings_link' ) );
    }

    /**
     * Bant bu istekte gösterilmeli mi? Sayfa oluşturucuların düzenleme ekranlarında ve tarama
     * isteklerinde gösterilmez. Giriş yapmış kullanıcılar ve yöneticiler de bandı görür.
     *
     * @return bool
     */
    public static function is_active() {
        return ! is_admin() && ! self::is_builder_preview() && ! KVKKC_Scanner::is_scan_request();
    }

    /**
     * Sayfa oluşturucu önizlemesi mi? (Elementor, Beaver, Divi, Bricks, Oxygen, Customizer)
     *
     * Adres parametresine yalnızca içerik düzenleyebilen oturumlarda bakılır. Aksi halde herkes
     * adrese ?et_fb ekleyerek engellemeyi kapatabilir ve önbellek bu kopyayı saklayabilirdi.
     *
     * @return bool
     */
    public static function is_builder_preview() {
        if ( function_exists( 'is_customize_preview' ) && is_customize_preview() ) {
            return true;
        }

        if ( ! is_user_logged_in() || ! current_user_can( 'edit_posts' ) ) {
            return false;
        }

        foreach ( array( 'elementor-preview', 'fl_builder', 'et_fb', 'bricks', 'ct_builder', 'vcv-editable', 'brizy-edit-iframe' ) as $param ) {
            if ( isset( $_GET[ $param ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- yalnızca varlık kontrolü, veri işlenmez.
                return true;
            }
        }

        return false;
    }

    /**
     * Head'in en başına koruma scripti: Consent Mode varsayılanı ve sonradan eklenen
     * script/iframe'lerin engellenmesi. GTM4WP (öncelik 2/10) ve Site Kit (1) bundan sonra gelir.
     */
    public static function print_guard() {
        if ( ! self::is_active() ) {
            return;
        }

        $rules = KVKKC_Services::block_rules();
        $data  = array(
            'v'  => kvkkc_policy_version(),
            'b'  => (bool) kvkkc_setting( 'block_enabled' ),
            'cm' => (bool) kvkkc_setting( 'consent_mode' ),
            'r'  => $rules['hosts'],
            'm'  => $rules['manual'],
        );

        wp_print_inline_script_tag(
            'window.kvkkcGuard=' . wp_json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP ) . ';' . self::compact_js( self::asset( 'public/js/guard.js' ) ),
            array(
                'id'               => 'kvkkc-guard',
                'data-no-optimize' => '1',
                'data-no-defer'    => '1',
                'data-cfasync'     => 'false',
            )
        );
    }

    /**
     * Bant scripti ve yapılandırması.
     */
    public static function enqueue() {
        if ( ! self::is_active() ) {
            return;
        }

        wp_enqueue_script(
            'kvkkc-banner',
            KVKKC_URL . 'public/js/banner.js',
            array(),
            KVKKC_VERSION,
            array(
                'in_footer' => true,
                'strategy'  => 'defer',
            )
        );

        wp_add_inline_script( 'kvkkc-banner', 'window.kvkkcConfig=' . wp_json_encode( self::config(), JSON_HEX_TAG | JSON_HEX_AMP ) . ';', 'before' );

        // Bant stilleri dosyadan satır içi basılır, ayrı bir istek açılmaz. LiteSpeed'in CSS
        // birleştirmesi bu etiketi kimliğinden tanıyıp dışarıda bırakır (KVKKC_Integrations).
        wp_register_style( 'kvkkc-banner', false, array(), KVKKC_VERSION );
        wp_enqueue_style( 'kvkkc-banner' );
        wp_add_inline_style( 'kvkkc-banner', self::asset( 'public/css/banner.css' ) );
    }

    /**
     * Bant scriptleri önbellek eklentilerinin erteleme ve birleştirme işlemlerinden muaf tutulur.
     *
     * @param array $attributes Script öznitelikleri.
     * @return array
     */
    public static function script_attributes( $attributes ) {
        if ( isset( $attributes['id'] ) && 0 === strpos( $attributes['id'], 'kvkkc-banner-js' ) ) {
            $attributes['data-no-optimize'] = '1';
            $attributes['data-no-defer']    = '1';
            $attributes['data-cfasync']     = 'false';
        }
        return $attributes;
    }

    /**
     * Eklenti dosyasının içeriği (istek başına bir kez okunur).
     *
     * @param string $path Eklenti klasörüne göre yol.
     * @return string
     */
    private static function asset( $path ) {
        static $cache = array();

        if ( ! isset( $cache[ $path ] ) ) {
            $content        = file_get_contents( KVKKC_DIR . $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- yerel eklenti dosyası.
            $cache[ $path ] = false === $content ? '' : trim( $content );
        }

        return $cache[ $path ];
    }

    /**
     * Her sayfanın head'ine basılan scriptten yorum satırları ve girintiler çıkarılır.
     * Yalnızca guard.js için kullanılır; dosyada satır sonu yorumu ve yorum benzeri dizge yoktur.
     *
     * @param string $js Kaynak.
     * @return string
     */
    private static function compact_js( $js ) {
        $js = preg_replace( '#/\*.*?\*/#s', '', $js );
        $js = preg_replace( '#^\s*//.*$#m', '', $js );
        return trim( preg_replace( '#\n\s*#', "\n", $js ) );
    }

    /**
     * Ayar değeri; önizlemede formdan gelen ayarlar kullanılır.
     *
     * @param string     $key      Anahtar.
     * @param array|null $settings Temizlenmiş ayarlar veya null (kayıtlı ayarlar).
     * @return mixed
     */
    private static function value( $key, $settings ) {
        if ( null === $settings ) {
            return kvkkc_setting( $key );
        }
        if ( isset( $settings[ $key ] ) && '' !== $settings[ $key ] ) {
            return $settings[ $key ];
        }
        $defaults = KVKKC_Activator::default_settings();
        return isset( $defaults[ $key ] ) ? $defaults[ $key ] : '';
    }

    /**
     * Kategori adı.
     *
     * @param string $category Kategori anahtarı.
     * @return string
     */
    public static function category_name( $category ) {
        return (string) kvkkc_setting( 'cat_' . $category . '_name' );
    }

    /**
     * Bandın yapılandırması (sitede ve yönetim panelindeki önizlemede aynı).
     *
     * @param array|null $settings Ayarlar; null ise kayıtlı ayarlar.
     * @param bool       $preview  Önizleme mi? Önizlemede çerez ve kayıt yazılmaz.
     * @return array
     */
    public static function config( $settings = null, $preview = false ) {
        $v = function ( $key ) use ( $settings ) {
            return self::value( $key, $settings );
        };

        $days   = (int) $v( 'consent_days' );
        $layout = $v( 'layout' );
        $tables = self::tables( $days );
        $logo   = (int) $v( 'logo' ) ? wp_get_attachment_image_url( (int) $v( 'logo' ), 'medium' ) : '';

        $categories = array();
        $clear      = array();
        foreach ( kvkkc_categories() as $category ) {
            $categories[] = array(
                'key'     => $category,
                'name'    => $v( 'cat_' . $category . '_name' ),
                'desc'    => $v( 'cat_' . $category . '_desc' ),
                'cookies' => isset( $tables[ $category ] ) ? $tables[ $category ] : array(),
            );
            if ( 'zorunlu' !== $category && ! empty( $tables[ $category ] ) ) {
                foreach ( $tables[ $category ] as $row ) {
                    if ( empty( $row['s'] ) ) {
                        $clear[ $category ][] = $row['n'];
                    }
                }
            }
        }

        $links = array();
        foreach ( array(
            'page_cerez'      => __( 'Çerez Politikası', 'kvkk-cerez' ),
            'page_aydinlatma' => __( 'KVKK Aydınlatma Metni', 'kvkk-cerez' ),
            'page_gizlilik'   => __( 'Gizlilik Politikası', 'kvkk-cerez' ),
            'page_acik_riza'  => __( 'Açık Rıza Metni', 'kvkk-cerez' ),
        ) as $key => $label ) {
            if ( '' !== (string) $v( $key ) ) {
                $links[] = array( $label, esc_url_raw( (string) $v( $key ) ), in_array( $key, array( 'page_cerez', 'page_aydinlatma' ), true ) );
            }
        }

        $youtube = KVKKC_Services::block_rules();
        $embed   = KVKKC_Services::match_url( 'https://www.youtube.com/iframe_api', $youtube );

        return array(
            'v'          => kvkkc_policy_version(),
            'days'       => $days,
            'rest'       => esc_url_raw( rest_url( 'kvkkc/v1/consent' ) ),
            'preview'    => (bool) $preview,
            'layout'     => $layout,
            'pos'        => 'bar' === $layout ? $v( 'bar_position' ) : ( 'card' === $layout ? $v( 'card_position' ) : 'center' ),
            'width'      => (int) $v( 'card_width' ),
            'overlay'    => $v( 'overlay' ),
            'lock'       => (bool) $v( 'lock_scroll' ),
            'anim'       => $v( 'animation' ),
            'order'      => $v( 'button_order' ),
            'prefsStyle' => $v( 'prefs_style' ),
            'quick'      => (bool) $v( 'show_categories' ),
            'font'       => $v( 'font' ),
            'shadow'     => (bool) $v( 'shadow' ),
            'autoDark'   => (bool) $v( 'auto_dark' ),
            'z'          => (int) $v( 'z_index' ),
            'vars'       => array(
                'bg'         => $v( 'color_bg' ),
                'text'       => $v( 'color_text' ),
                'link'       => $v( 'color_link' ),
                'btn-bg'     => $v( 'color_btn_bg' ),
                'btn-text'   => $v( 'color_btn_text' ),
                'accent'     => $v( 'color_accent' ),
                'border'     => $v( 'color_border' ),
                'size'       => (int) $v( 'font_size' ) . 'px',
                'radius'     => (int) $v( 'radius' ) . 'px',
                'btn-radius' => (int) $v( 'btn_radius' ) . 'px',
                'width'      => (int) $v( 'card_width' ) . 'px',
            ),
            'logo'       => $logo ? $logo : '',
            'css'        => (string) $v( 'custom_css' ),
            'text'       => array(
                'title'       => $v( 'title' ),
                'message'     => $v( 'message' ),
                'accept'      => $v( 'btn_accept' ),
                'reject'      => $v( 'btn_reject' ),
                'prefs'       => $v( 'btn_prefs' ),
                'save'        => $v( 'btn_save' ),
                'prefsTitle'  => $v( 'prefs_title' ),
                'prefsIntro'  => $v( 'prefs_intro' ),
                'always'      => $v( 'always_active' ),
                'phText'      => $v( 'placeholder_text' ),
                'phButton'    => $v( 'placeholder_btn' ),
                'floatLabel'  => $v( 'float_label' ),
                'region'      => __( 'Çerez bildirimi', 'kvkk-cerez' ),
                'close'       => __( 'Kapat', 'kvkk-cerez' ),
                'showCookies' => __( 'Çerezleri göster', 'kvkk-cerez' ),
                'colName'     => __( 'Çerez', 'kvkk-cerez' ),
                'colProvider' => __( 'Sağlayıcı', 'kvkk-cerez' ),
                'colPurpose'  => __( 'Amaç', 'kvkk-cerez' ),
                'colDuration' => __( 'Süre', 'kvkk-cerez' ),
                'service'     => __( 'Hizmet', 'kvkk-cerez' ),
                'noCookies'   => __( 'Bu kategoride şu an çerez bulunmuyor.', 'kvkk-cerez' ),
                'consentId'   => __( 'Rıza No', 'kvkk-cerez' ),
                'consentDate' => __( 'Tarih', 'kvkk-cerez' ),
                'saved'       => __( 'Çerez tercihiniz kaydedildi.', 'kvkk-cerez' ),
            ),
            'links'      => $links,
            'cats'       => $categories,
            'clear'      => $clear,
            'float'      => array(
                'on'   => (bool) $v( 'float_enabled' ),
                'pos'  => $v( 'float_position' ),
                'icon' => self::icon( $v( 'float_icon' ), 24 ),
            ),
            'reload'     => (bool) $v( 'reload_on_withdraw' ),
            'gtm'        => (bool) $v( 'gtm_event' ),
            'cm'         => (bool) $v( 'consent_mode' ),
            'yt'         => '' !== $embed && 'zorunlu' !== $embed ? $embed : '',
        );
    }

    /**
     * Envanterden kategori bazında çerez satırları. Çerezi listelenmemiş hizmetler (ör. Tag
     * Manager) hizmet satırı olarak görünür.
     *
     * @param int $days Rıza süresi (gün), kendi çerezimizin süresi için.
     * @return array kategori => array( array( n: ad, p: sağlayıcı, u: amaç, d: süre, t: taraf, s: hizmet satırı mı ) )
     */
    public static function tables( $days ) {
        $site   = get_bloginfo( 'name' );
        $tables = array();

        foreach ( KVKKC_Services::inventory() as $entry ) {
            if ( ! is_array( $entry ) || empty( $entry['category'] ) ) {
                continue;
            }

            $provider = ! empty( $entry['provider'] ) ? $entry['provider'] : $site;
            $party    = isset( $entry['party'] ) ? (int) $entry['party'] : 3;
            $cookies  = isset( $entry['cookies'] ) ? (array) $entry['cookies'] : array();
            $purpose  = isset( $entry['purpose'] ) ? (string) $entry['purpose'] : '';

            if ( ! $cookies ) {
                $tables[ $entry['category'] ][] = array(
                    'n' => $entry['name'],
                    'p' => $provider,
                    'u' => $purpose,
                    'd' => '',
                    't' => $party,
                    's' => 1,
                );
                continue;
            }

            foreach ( $cookies as $cookie ) {
                $tables[ $entry['category'] ][] = array(
                    'n' => $cookie['name'],
                    'p' => $provider,
                    'u' => '' !== $cookie['purpose'] ? $cookie['purpose'] : $purpose,
                    'd' => str_replace( '{gun}', (string) $days, $cookie['duration'] ),
                    't' => $party,
                    's' => 0,
                );
            }
        }

        return $tables;
    }

    /**
     * [kvkk_cerez_tablosu] Çerez politikası sayfası için tablo (Rehber EK-4 sütunları).
     *
     * @param array $atts kategori: tek kategori; aktarim: "hayir" ise yurt dışı listesi gizlenir.
     * @return string
     */
    public static function shortcode_table( $atts ) {
        $atts   = shortcode_atts(
            array(
                'kategori' => '',
                'aktarim'  => 'evet',
            ),
            $atts,
            'kvkk_cerez_tablosu'
        );
        $tables = self::tables( (int) kvkkc_setting( 'consent_days' ) );

        ob_start();
        echo '<div class="kvkkc-policy">';
        foreach ( kvkkc_categories() as $category ) {
            if ( '' !== $atts['kategori'] && $atts['kategori'] !== $category ) {
                continue;
            }
            ?>
            <section class="kvkkc-policy-cat">
                <h3><?php echo esc_html( self::category_name( $category ) ); ?></h3>
                <p><?php echo esc_html( (string) kvkkc_setting( 'cat_' . $category . '_desc' ) ); ?></p>
                <?php if ( empty( $tables[ $category ] ) ) : ?>
                    <p class="kvkkc-policy-empty"><?php esc_html_e( 'Bu kategoride şu an çerez bulunmuyor.', 'kvkk-cerez' ); ?></p>
                <?php else : ?>
                    <div class="kvkkc-policy-scroll">
                        <table class="kvkkc-policy-table">
                            <thead>
                                <tr>
                                    <th scope="col"><?php esc_html_e( 'Çerezin adı', 'kvkk-cerez' ); ?></th>
                                    <th scope="col"><?php esc_html_e( 'Sağlayıcı', 'kvkk-cerez' ); ?></th>
                                    <th scope="col"><?php esc_html_e( 'Tipi', 'kvkk-cerez' ); ?></th>
                                    <th scope="col"><?php esc_html_e( 'Amacı', 'kvkk-cerez' ); ?></th>
                                    <th scope="col"><?php esc_html_e( 'Saklama süresi', 'kvkk-cerez' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ( $tables[ $category ] as $row ) : ?>
                                    <tr>
                                        <td><?php echo $row['s'] ? esc_html( $row['n'] ) : '<code>' . esc_html( $row['n'] ) . '</code>'; ?></td>
                                        <td><?php echo esc_html( $row['p'] ); ?></td>
                                        <td><?php echo 1 === $row['t'] ? esc_html__( 'Birinci taraf', 'kvkk-cerez' ) : esc_html__( 'Üçüncü taraf', 'kvkk-cerez' ); ?></td>
                                        <td><?php echo esc_html( $row['u'] ); ?></td>
                                        <td><?php echo '' !== $row['d'] ? esc_html( $row['d'] ) : '&ndash;'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
            <?php
        }

        $abroad = self::abroad();
        if ( 'hayir' !== $atts['aktarim'] && '' === $atts['kategori'] && $abroad ) {
            ?>
            <section class="kvkkc-policy-cat">
                <h3><?php esc_html_e( 'Yurt dışında bulunan hizmet sağlayıcılar', 'kvkk-cerez' ); ?></h3>
                <p><?php esc_html_e( 'Aşağıdaki hizmetler, yalnızca ilgili kategoriye izin vermeniz halinde çalışır ve verilerinizin bu sağlayıcıların bulunduğu ülkelere aktarılmasına yol açabilir.', 'kvkk-cerez' ); ?></p>
                <ul>
                    <?php foreach ( $abroad as $provider => $item ) : ?>
                        <li><strong><?php echo esc_html( $provider ); ?></strong> (<?php echo esc_html( $item['country'] ); ?>): <?php echo esc_html( implode( ', ', $item['services'] ) ); ?></li>
                    <?php endforeach; ?>
                </ul>
            </section>
            <?php
        }

        echo '<p class="kvkkc-policy-manage">' . wp_kses_post( self::shortcode_settings_link( array( 'text' => __( 'Çerez tercihlerimi değiştir', 'kvkk-cerez' ) ) ) ) . '</p>';
        echo '</div>';

        return (string) ob_get_clean();
    }

    /**
     * Envanterdeki üçüncü taraf ve yurt dışı sağlayıcılar.
     *
     * @return array sağlayıcı => array( country, services[] )
     */
    public static function abroad() {
        $list = array();
        foreach ( KVKKC_Services::inventory() as $entry ) {
            if ( ! is_array( $entry ) || empty( $entry['country'] ) || empty( $entry['provider'] ) || false !== stripos( $entry['country'], 'Türkiye' ) ) {
                continue;
            }
            $list[ $entry['provider'] ]['country']    = $entry['country'];
            $list[ $entry['provider'] ]['services'][] = $entry['name'];
        }
        ksort( $list );
        return $list;
    }

    /**
     * [kvkk_cerez_ayarlari] Tercihler penceresini açan bağlantı. Menüye "#kvkkc-ayarlar" adresli
     * bir bağlantı eklemek de aynı işi görür.
     *
     * @param array $atts text: bağlantı metni.
     * @return string
     */
    public static function shortcode_settings_link( $atts ) {
        $atts = shortcode_atts(
            array( 'text' => __( 'Çerez Ayarları', 'kvkk-cerez' ) ),
            $atts,
            'kvkk_cerez_ayarlari'
        );

        return '<a href="#kvkkc-ayarlar" class="kvkkc-open-prefs" role="button">' . esc_html( $atts['text'] ) . '</a>';
    }

    /**
     * Yüzen buton ve seçim ekranı simgeleri.
     *
     * @param string $name Simge adı.
     * @param int    $size Boyut (px).
     * @return string
     */
    public static function icon( $name, $size = 24 ) {
        $paths = array(
            'cookie'      => '<path d="M12 3a9 9 0 1 0 9 9 3 3 0 0 1-3.6-3A3 3 0 0 1 13.9 5.4 3 3 0 0 1 12 3Z"/><circle cx="8.5" cy="10.5" r="1.1"/><circle cx="14.5" cy="15" r="1.1"/><circle cx="9.5" cy="15.5" r=".9"/><circle cx="12" cy="11.5" r=".6"/>',
            'fingerprint' => '<path d="M7.5 19.5c1.2-2 1.8-4.4 1.8-7a2.7 2.7 0 0 1 5.4 0c0 1.3-.1 2.6-.4 3.8"/><path d="M4.6 16.3c.6-1.2.9-2.5.9-3.8a6.5 6.5 0 0 1 11.5-4.2"/><path d="M11 21c1.6-2.6 2.4-5.6 2.4-8.5"/><path d="M18.8 11.2c.1.4.1.9.1 1.3 0 2.4-.4 4.7-1.1 6.8"/><path d="M3.4 9.6A9 9 0 0 1 17.5 4.9"/>',
            'shield'      => '<path d="M12 2.8 4.5 5.7v5.5c0 4.7 3.1 8.5 7.5 10 4.4-1.5 7.5-5.3 7.5-10V5.7L12 2.8Z"/><path d="m8.8 12.3 2.2 2.2 4.3-4.5"/>',
        );
        $path  = isset( $paths[ $name ] ) ? $paths[ $name ] : $paths['cookie'];

        return '<svg viewBox="0 0 24 24" width="' . (int) $size . '" height="' . (int) $size . '" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $path . '</svg>';
    }
}
