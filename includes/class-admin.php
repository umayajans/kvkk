<?php
/**
 * Yönetim paneli: menü, ayar şeması, kayıt işlemleri
 *
 * @package KVKK_Cerez
 */

// Doğrudan erişimi engelle.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin sınıfı
 */
class KVKKC_Admin {

    /**
     * Eklenti sayfalarının hook adları (asset yükleme kontrolü için).
     *
     * @var string[]
     */
    private $hooks = array();

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'wp_ajax_kvkkc_preview', array( $this, 'ajax_preview' ) );
        add_action( 'wp_ajax_kvkkc_bump_version', array( $this, 'ajax_bump_version' ) );
        add_action( 'admin_post_kvkkc_save_inventory', array( $this, 'save_inventory' ) );
        add_action( 'admin_post_kvkkc_add_services', array( $this, 'add_services' ) );
        add_action( 'admin_post_kvkkc_export_csv', array( $this, 'export_csv' ) );
        add_action( 'update_option_kvkkc_settings', 'kvkkc_purge_caches' );
        add_filter( 'plugin_action_links_' . KVKKC_BASENAME, array( $this, 'action_links' ) );
    }

    /**
     * Menü ve alt sayfalar.
     */
    public function add_menu() {
        $icon = 'data:image/svg+xml;base64,' . base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="black" d="M10 1.5 3 4.2v5.1c0 4.3 2.9 7.8 7 9.2 4.1-1.4 7-4.9 7-9.2V4.2L10 1.5Zm-1.1 11.9L5.6 10l1.3-1.3 2 2 4.3-4.4 1.3 1.3-5.6 5.8Z"/></svg>' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- menü ikonu.

        $this->hooks[] = add_menu_page( __( 'Umay Çerez Yöneticisi', 'umay-cerez-yoneticisi' ), __( 'Umay Çerez', 'umay-cerez-yoneticisi' ), 'manage_options', 'kvkk-cerez', array( $this, 'page_settings' ), $icon, 81 );
        $this->hooks[] = add_submenu_page( 'kvkk-cerez', __( 'Ayarlar', 'umay-cerez-yoneticisi' ), __( 'Ayarlar', 'umay-cerez-yoneticisi' ), 'manage_options', 'kvkk-cerez', array( $this, 'page_settings' ) );
        $this->hooks[] = add_submenu_page( 'kvkk-cerez', __( 'Çerez Envanteri', 'umay-cerez-yoneticisi' ), __( 'Çerez Envanteri', 'umay-cerez-yoneticisi' ), 'manage_options', 'kvkk-cerez-envanter', array( $this, 'page_inventory' ) );
        $this->hooks[] = add_submenu_page( 'kvkk-cerez', __( 'Rıza Kayıtları', 'umay-cerez-yoneticisi' ), __( 'Rıza Kayıtları', 'umay-cerez-yoneticisi' ), 'manage_options', 'kvkk-cerez-kayitlar', array( $this, 'page_consents' ) );
    }

    /**
     * Ayar kaydı (options.php üzerinden kaydedilir).
     */
    public function register_settings() {
        register_setting(
            'kvkkc_settings_group',
            'kvkkc_settings',
            array(
                'type'              => 'array',
                'sanitize_callback' => array( __CLASS__, 'sanitize' ),
                'default'           => array(),
            )
        );
    }

    /**
     * Eklenti listesindeki "Ayarlar" bağlantısı.
     *
     * @param array $links Bağlantılar.
     * @return array
     */
    public function action_links( $links ) {
        array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php?page=kvkk-cerez' ) ) . '">' . esc_html__( 'Ayarlar', 'umay-cerez-yoneticisi' ) . '</a>' );
        return $links;
    }

    /**
     * Admin asset'leri yalnızca eklenti sayfalarında yüklenir.
     *
     * @param string $hook Sayfa hook adı.
     */
    public function enqueue_assets( $hook ) {
        if ( ! in_array( $hook, $this->hooks, true ) ) {
            return;
        }

        if ( 'toplevel_page_kvkk-cerez' === $hook ) {
            wp_enqueue_media();
        }

        wp_enqueue_style( 'kvkkc-admin', KVKKC_URL . 'admin/css/admin.css', array(), KVKKC_VERSION );
        wp_enqueue_script( 'kvkkc-admin', KVKKC_URL . 'admin/js/admin.js', array(), KVKKC_VERSION, array( 'in_footer' => true ) );

        wp_localize_script(
            'kvkkc-admin',
            'kvkkcAdmin',
            array(
                'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
                'nonce'     => wp_create_nonce( 'kvkkc_admin' ),
                'bannerJs'  => KVKKC_URL . 'public/js/banner.js?ver=' . KVKKC_VERSION,
                'bannerCss' => KVKKC_URL . 'public/css/banner.css?ver=' . KVKKC_VERSION,
                'siteName'  => get_bloginfo( 'name' ),
                'i18n'      => array(
                    'scanStart'     => __( 'Sayfa listesi hazırlanıyor...', 'umay-cerez-yoneticisi' ),
                    /* translators: 1: taranan sayfa sayısı, 2: toplam sayfa sayısı */
                    'scanning'      => __( '%1$d / %2$d sayfa tarandı', 'umay-cerez-yoneticisi' ),
                    'scanDone'      => __( 'Tarama tamamlandı, sonuçlar yükleniyor...', 'umay-cerez-yoneticisi' ),
                    'scanFailed'    => __( 'Tarama başlatılamadı. Sayfayı yenileyip tekrar deneyin.', 'umay-cerez-yoneticisi' ),
                    'bumpConfirm'   => __( 'Tüm ziyaretçilerden çerez tercihi yeniden istenecek. Devam edilsin mi?', 'umay-cerez-yoneticisi' ),
                    'chooseLogo'    => __( 'Logo seç', 'umay-cerez-yoneticisi' ),
                    'useLogo'       => __( 'Bu görseli kullan', 'umay-cerez-yoneticisi' ),
                    'removeConfirm' => __( 'Bu hizmet envanterden çıkarılsın mı?', 'umay-cerez-yoneticisi' ),
                    'copied'        => __( 'Kopyalandı', 'umay-cerez-yoneticisi' ),
                ),
            )
        );
    }

    /**
     * Ayar sekmeleri ve bölümleri.
     *
     * @return array
     */
    public static function tabs() {
        return array(
            'gorunum'     => array(
                'label'    => __( 'Görünüm', 'umay-cerez-yoneticisi' ),
                'sections' => array(
                    'yerlesim' => __( 'Yerleşim', 'umay-cerez-yoneticisi' ),
                    'butonlar' => __( 'Butonlar', 'umay-cerez-yoneticisi' ),
                    'yuzen'    => __( 'Yüzen tercih butonu', 'umay-cerez-yoneticisi' ),
                ),
                'icon'     => '<rect x="3" y="4" width="18" height="16" rx="2.5"/><path d="M3 15h18"/>',
            ),
            'renk'        => array(
                'label'    => __( 'Renk ve yazı', 'umay-cerez-yoneticisi' ),
                'sections' => array(
                    'renkler' => __( 'Renkler', 'umay-cerez-yoneticisi' ),
                    'yazi'    => __( 'Yazı ve köşeler', 'umay-cerez-yoneticisi' ),
                ),
                'icon'     => '<path d="M12 3a9 9 0 1 0 0 18c1.1 0 1.6-.8 1.6-1.6 0-.5-.2-.8-.4-1.1-.3-.3-.4-.6-.4-1 0-.9.7-1.6 1.6-1.6H16a5 5 0 0 0 5-5C21 6.5 17 3 12 3Z"/><circle cx="7.5" cy="11" r="1"/><circle cx="10" cy="7" r="1"/><circle cx="14.5" cy="7" r="1"/>',
            ),
            'metinler'    => array(
                'label'    => __( 'Metinler', 'umay-cerez-yoneticisi' ),
                'sections' => array(
                    'banner'    => __( 'Çerez bandı', 'umay-cerez-yoneticisi' ),
                    'tercihler' => __( 'Tercihler penceresi', 'umay-cerez-yoneticisi' ),
                    'yertutucu' => __( 'Engellenen içerik', 'umay-cerez-yoneticisi' ),
                ),
                'icon'     => '<path d="M5 6h14M5 11h14M5 16h9"/>',
            ),
            'kategoriler' => array(
                'label'    => __( 'Kategoriler', 'umay-cerez-yoneticisi' ),
                'sections' => array(
                    'zorunlu'   => __( 'Zorunlu', 'umay-cerez-yoneticisi' ),
                    'islevsel'  => __( 'İşlevsel', 'umay-cerez-yoneticisi' ),
                    'analitik'  => __( 'Analitik', 'umay-cerez-yoneticisi' ),
                    'pazarlama' => __( 'Pazarlama', 'umay-cerez-yoneticisi' ),
                ),
                'icon'     => '<rect x="4" y="4" width="7" height="7" rx="1.5"/><rect x="13" y="4" width="7" height="7" rx="1.5"/><rect x="4" y="13" width="7" height="7" rx="1.5"/><rect x="13" y="13" width="7" height="7" rx="1.5"/>',
            ),
            'sayfalar'    => array(
                'label'    => __( 'Yasal sayfalar', 'umay-cerez-yoneticisi' ),
                'sections' => array(
                    'sayfalar' => __( 'Yasal metinler', 'umay-cerez-yoneticisi' ),
                ),
                'icon'     => '<path d="M7 3h7l4 4v14H7z"/><path d="M14 3v4h4M10 12h5M10 16h5"/>',
            ),
            'davranis'    => array(
                'label'    => __( 'Davranış', 'umay-cerez-yoneticisi' ),
                'sections' => array(
                    'riza'        => __( 'Rıza', 'umay-cerez-yoneticisi' ),
                    'entegrasyon' => __( 'Google ve Tag Manager', 'umay-cerez-yoneticisi' ),
                ),
                'icon'     => '<path d="M4 7h10M18 7h2M4 17h4M12 17h8"/><circle cx="16" cy="7" r="2"/><circle cx="10" cy="17" r="2"/>',
            ),
            'gelismis'    => array(
                'label'    => __( 'Gelişmiş', 'umay-cerez-yoneticisi' ),
                'sections' => array(
                    'kurallar' => __( 'Engelleme kuralları', 'umay-cerez-yoneticisi' ),
                    'kayit'    => __( 'Rıza kayıtları', 'umay-cerez-yoneticisi' ),
                    'stil'     => __( 'Özel CSS', 'umay-cerez-yoneticisi' ),
                ),
                'icon'     => '<path d="m8 8-4 4 4 4M16 8l4 4-4 4M13.5 5l-3 14"/>',
            ),
        );
    }

    /**
     * Ayar alanları şeması: hem render hem sanitize bu tanımdan beslenir.
     *
     * @return array
     */
    public static function fields() {
        $category_fields = array();
        foreach ( kvkkc_categories() as $category ) {
            $category_fields[ 'cat_' . $category . '_name' ] = array(
                'section' => $category,
                'label'   => __( 'Başlık', 'umay-cerez-yoneticisi' ),
                'type'    => 'text',
            );
            $category_fields[ 'cat_' . $category . '_desc' ] = array(
                'section' => $category,
                'label'   => __( 'Açıklama', 'umay-cerez-yoneticisi' ),
                'type'    => 'textarea',
                'rows'    => 3,
            );
        }

        $fields = array(
            'layout'           => array(
                'section' => 'yerlesim',
                'label'   => __( 'Bant tipi', 'umay-cerez-yoneticisi' ),
                'type'    => 'choice',
                'style'   => 'cards',
                'options' => array(
                    'bar'   => __( 'Şerit', 'umay-cerez-yoneticisi' ),
                    'card'  => __( 'Kart', 'umay-cerez-yoneticisi' ),
                    'modal' => __( 'Ortada pencere', 'umay-cerez-yoneticisi' ),
                ),
            ),
            'bar_position'     => array(
                'section' => 'yerlesim',
                'label'   => __( 'Şerit konumu', 'umay-cerez-yoneticisi' ),
                'type'    => 'choice',
                'show_if' => array( 'layout', 'bar' ),
                'options' => array(
                    'bottom' => __( 'Altta', 'umay-cerez-yoneticisi' ),
                    'top'    => __( 'Üstte', 'umay-cerez-yoneticisi' ),
                ),
            ),
            'card_position'    => array(
                'section' => 'yerlesim',
                'label'   => __( 'Kart konumu', 'umay-cerez-yoneticisi' ),
                'type'    => 'choice',
                'show_if' => array( 'layout', 'card' ),
                'options' => array(
                    'left'   => __( 'Sol alt', 'umay-cerez-yoneticisi' ),
                    'center' => __( 'Orta alt', 'umay-cerez-yoneticisi' ),
                    'right'  => __( 'Sağ alt', 'umay-cerez-yoneticisi' ),
                ),
            ),
            'card_width'       => array(
                'section' => 'yerlesim',
                'label'   => __( 'Kart genişliği', 'umay-cerez-yoneticisi' ),
                'type'    => 'range',
                'min'     => 320,
                'max'     => 640,
                'step'    => 10,
                'unit'    => 'px',
                'show_if' => array( 'layout', 'card' ),
            ),
            'overlay'          => array(
                'section' => 'yerlesim',
                'label'   => __( 'Arka plan', 'umay-cerez-yoneticisi' ),
                'type'    => 'choice',
                'options' => array(
                    'none' => __( 'Yok', 'umay-cerez-yoneticisi' ),
                    'dim'  => __( 'Karart', 'umay-cerez-yoneticisi' ),
                    'blur' => __( 'Karart ve bulanıklaştır', 'umay-cerez-yoneticisi' ),
                ),
            ),
            'lock_scroll'      => array(
                'section' => 'yerlesim',
                'label'   => __( 'Sayfa kilidi', 'umay-cerez-yoneticisi' ),
                'type'    => 'checkbox',
                'text'    => __( 'Seçim yapılana kadar sayfa kaydırılamasın', 'umay-cerez-yoneticisi' ),
                'desc'    => __( 'Reddet butonu her zaman görünür kalır; ziyaretçi tek tıkla siteye devam edebilir.', 'umay-cerez-yoneticisi' ),
            ),
            'animation'        => array(
                'section' => 'yerlesim',
                'label'   => __( 'Açılış animasyonu', 'umay-cerez-yoneticisi' ),
                'type'    => 'choice',
                'options' => array(
                    'none'  => __( 'Yok', 'umay-cerez-yoneticisi' ),
                    'fade'  => __( 'Belirme', 'umay-cerez-yoneticisi' ),
                    'slide' => __( 'Kayma', 'umay-cerez-yoneticisi' ),
                ),
                'desc'    => __( 'Hareketi azaltmayı seçen ziyaretçilerde animasyon kendiliğinden kapanır.', 'umay-cerez-yoneticisi' ),
            ),
            'logo'             => array(
                'section' => 'yerlesim',
                'label'   => __( 'Logo', 'umay-cerez-yoneticisi' ),
                'type'    => 'media',
            ),
            'button_order'     => array(
                'section' => 'butonlar',
                'label'   => __( 'Buton sırası', 'umay-cerez-yoneticisi' ),
                'type'    => 'choice',
                'options' => array(
                    'accept_first' => __( 'Önce Kabul Et', 'umay-cerez-yoneticisi' ),
                    'reject_first' => __( 'Önce Reddet', 'umay-cerez-yoneticisi' ),
                ),
            ),
            'prefs_style'      => array(
                'section' => 'butonlar',
                'label'   => __( 'Ayarlar butonu', 'umay-cerez-yoneticisi' ),
                'type'    => 'choice',
                'options' => array(
                    'equal'   => __( 'Diğerleriyle aynı', 'umay-cerez-yoneticisi' ),
                    'outline' => __( 'Çerçeveli', 'umay-cerez-yoneticisi' ),
                ),
                'desc'    => __( 'Rehber, kabul, ret ve tercih butonlarının renk, boyut ve punto açısından eşit sunulmasını iyi uygulama olarak gösterir. Kabul ve Reddet butonları her durumda aynı görünür.', 'umay-cerez-yoneticisi' ),
            ),
            'show_categories'  => array(
                'section' => 'butonlar',
                'label'   => __( 'Hızlı seçim', 'umay-cerez-yoneticisi' ),
                'type'    => 'checkbox',
                'text'    => __( 'Kategori anahtarlarını bantta da göster', 'umay-cerez-yoneticisi' ),
            ),
            'float_enabled'    => array(
                'section' => 'yuzen',
                'label'   => __( 'Yüzen buton', 'umay-cerez-yoneticisi' ),
                'type'    => 'checkbox',
                'text'    => __( 'Seçim yapıldıktan sonra köşede çerez ayarları butonu göster', 'umay-cerez-yoneticisi' ),
                'desc'    => __( 'Rehber, verilen iznin kolayca geri alınabilmesi için sayfanın köşesinde kalıcı bir simge önerir. Kapatırsanız [kvkk_cerez_ayarlari] kısa kodunu alt bilgiye ekleyin.', 'umay-cerez-yoneticisi' ),
            ),
            'float_position'   => array(
                'section' => 'yuzen',
                'label'   => __( 'Konum', 'umay-cerez-yoneticisi' ),
                'type'    => 'choice',
                'options' => array(
                    'left'  => __( 'Sol alt', 'umay-cerez-yoneticisi' ),
                    'right' => __( 'Sağ alt', 'umay-cerez-yoneticisi' ),
                ),
            ),
            'float_icon'       => array(
                'section' => 'yuzen',
                'label'   => __( 'Simge', 'umay-cerez-yoneticisi' ),
                'type'    => 'choice',
                'style'   => 'icons',
                'options' => array(
                    'cookie'      => __( 'Kurabiye', 'umay-cerez-yoneticisi' ),
                    'fingerprint' => __( 'Parmak izi', 'umay-cerez-yoneticisi' ),
                    'shield'      => __( 'Kalkan', 'umay-cerez-yoneticisi' ),
                ),
            ),
            'float_label'      => array(
                'section' => 'yuzen',
                'label'   => __( 'Erişilebilir ad', 'umay-cerez-yoneticisi' ),
                'type'    => 'text',
                'desc'    => __( 'Ekran okuyucuların okuduğu ve fareyle üzerine gelince görünen metin.', 'umay-cerez-yoneticisi' ),
            ),
            'color_bg'         => array(
                'section' => 'renkler',
                'label'   => __( 'Zemin', 'umay-cerez-yoneticisi' ),
                'type'    => 'color',
            ),
            'color_text'       => array(
                'section' => 'renkler',
                'label'   => __( 'Metin', 'umay-cerez-yoneticisi' ),
                'type'    => 'color',
            ),
            'color_link'       => array(
                'section' => 'renkler',
                'label'   => __( 'Bağlantı', 'umay-cerez-yoneticisi' ),
                'type'    => 'color',
            ),
            'color_btn_bg'     => array(
                'section' => 'renkler',
                'label'   => __( 'Buton zemini', 'umay-cerez-yoneticisi' ),
                'type'    => 'color',
            ),
            'color_btn_text'   => array(
                'section' => 'renkler',
                'label'   => __( 'Buton metni', 'umay-cerez-yoneticisi' ),
                'type'    => 'color',
            ),
            'color_accent'     => array(
                'section' => 'renkler',
                'label'   => __( 'Açık anahtar', 'umay-cerez-yoneticisi' ),
                'type'    => 'color',
            ),
            'color_border'     => array(
                'section' => 'renkler',
                'label'   => __( 'Kenarlık', 'umay-cerez-yoneticisi' ),
                'type'    => 'color',
            ),
            'auto_dark'        => array(
                'section' => 'renkler',
                'label'   => __( 'Koyu mod', 'umay-cerez-yoneticisi' ),
                'type'    => 'checkbox',
                'text'    => __( 'Ziyaretçinin cihazı koyu moddaysa koyu renkleri kullan', 'umay-cerez-yoneticisi' ),
            ),
            'font'             => array(
                'section' => 'yazi',
                'label'   => __( 'Yazı tipi', 'umay-cerez-yoneticisi' ),
                'type'    => 'choice',
                'options' => array(
                    'inherit' => __( 'Sitenin yazı tipi', 'umay-cerez-yoneticisi' ),
                    'system'  => __( 'Cihazın yazı tipi', 'umay-cerez-yoneticisi' ),
                ),
            ),
            'font_size'        => array(
                'section' => 'yazi',
                'label'   => __( 'Yazı boyutu', 'umay-cerez-yoneticisi' ),
                'type'    => 'range',
                'min'     => 12,
                'max'     => 18,
                'unit'    => 'px',
            ),
            'radius'           => array(
                'section' => 'yazi',
                'label'   => __( 'Bant köşesi', 'umay-cerez-yoneticisi' ),
                'type'    => 'range',
                'min'     => 0,
                'max'     => 28,
                'unit'    => 'px',
            ),
            'btn_radius'       => array(
                'section' => 'yazi',
                'label'   => __( 'Buton köşesi', 'umay-cerez-yoneticisi' ),
                'type'    => 'range',
                'min'     => 0,
                'max'     => 28,
                'unit'    => 'px',
            ),
            'shadow'           => array(
                'section' => 'yazi',
                'label'   => __( 'Gölge', 'umay-cerez-yoneticisi' ),
                'type'    => 'checkbox',
                'text'    => __( 'Banda yumuşak gölge ver', 'umay-cerez-yoneticisi' ),
            ),
            'title'            => array(
                'section' => 'banner',
                'label'   => __( 'Başlık', 'umay-cerez-yoneticisi' ),
                'type'    => 'text',
            ),
            'message'          => array(
                'section' => 'banner',
                'label'   => __( 'Bilgilendirme metni', 'umay-cerez-yoneticisi' ),
                'type'    => 'html',
                'rows'    => 6,
                'desc'    => __( 'Rehber, ilk ekranda çerezlerin amaçlarının (analitik, reklam, kişiselleştirme) ve taraflarının (birinci, üçüncü taraf) kısaca belirtilmesini önerir. Bağlantı için <a> etiketi kullanılabilir; yasal sayfalara bağlantılar metnin altına kendiliğinden eklenir.', 'umay-cerez-yoneticisi' ),
            ),
            'btn_accept'       => array(
                'section' => 'banner',
                'label'   => __( 'Kabul butonu', 'umay-cerez-yoneticisi' ),
                'type'    => 'text',
            ),
            'btn_reject'       => array(
                'section' => 'banner',
                'label'   => __( 'Ret butonu', 'umay-cerez-yoneticisi' ),
                'type'    => 'text',
            ),
            'btn_prefs'        => array(
                'section' => 'banner',
                'label'   => __( 'Ayarlar butonu', 'umay-cerez-yoneticisi' ),
                'type'    => 'text',
            ),
            'prefs_title'      => array(
                'section' => 'tercihler',
                'label'   => __( 'Başlık', 'umay-cerez-yoneticisi' ),
                'type'    => 'text',
            ),
            'prefs_intro'      => array(
                'section' => 'tercihler',
                'label'   => __( 'Giriş metni', 'umay-cerez-yoneticisi' ),
                'type'    => 'html',
                'rows'    => 4,
            ),
            'btn_save'         => array(
                'section' => 'tercihler',
                'label'   => __( 'Kaydet butonu', 'umay-cerez-yoneticisi' ),
                'type'    => 'text',
            ),
            'always_active'    => array(
                'section' => 'tercihler',
                'label'   => __( 'Zorunlu kategori etiketi', 'umay-cerez-yoneticisi' ),
                'type'    => 'text',
            ),
            'placeholder_text' => array(
                'section' => 'yertutucu',
                'label'   => __( 'Açıklama', 'umay-cerez-yoneticisi' ),
                'type'    => 'text',
                'desc'    => __( 'Rıza verilmeden engellenen video ve haritaların yerinde görünür. {kategori} ifadesi kategori adıyla değiştirilir.', 'umay-cerez-yoneticisi' ),
            ),
            'placeholder_btn'  => array(
                'section' => 'yertutucu',
                'label'   => __( 'Buton', 'umay-cerez-yoneticisi' ),
                'type'    => 'text',
            ),
        );

        $fields += $category_fields;

        $fields += array(
            'page_aydinlatma'     => array(
                'section' => 'sayfalar',
                'label'   => __( 'KVKK Aydınlatma Metni', 'umay-cerez-yoneticisi' ),
                'type'    => 'url',
                'desc'    => __( 'Bantta ve tercihler penceresinde gösterilir.', 'umay-cerez-yoneticisi' ),
            ),
            'page_cerez'          => array(
                'section' => 'sayfalar',
                'label'   => __( 'Çerez Politikası', 'umay-cerez-yoneticisi' ),
                'type'    => 'url',
                'desc'    => __( 'Bantta ve tercihler penceresinde gösterilir. Çerez tablosu için bu sayfaya [kvkk_cerez_tablosu] kısa kodunu ekleyin.', 'umay-cerez-yoneticisi' ),
            ),
            'page_gizlilik'       => array(
                'section' => 'sayfalar',
                'label'   => __( 'Gizlilik Politikası', 'umay-cerez-yoneticisi' ),
                'type'    => 'url',
                'desc'    => __( 'Tercihler penceresinde gösterilir.', 'umay-cerez-yoneticisi' ),
            ),
            'page_acik_riza'      => array(
                'section' => 'sayfalar',
                'label'   => __( 'Açık Rıza Metni', 'umay-cerez-yoneticisi' ),
                'type'    => 'url',
                'desc'    => __( 'Tercihler penceresinde gösterilir.', 'umay-cerez-yoneticisi' ),
            ),
            'consent_days'        => array(
                'section' => 'riza',
                'label'   => __( 'Tercihin geçerlilik süresi', 'umay-cerez-yoneticisi' ),
                'type'    => 'number',
                'min'     => 30,
                'max'     => 395,
                'unit'    => __( 'gün', 'umay-cerez-yoneticisi' ),
                'desc'    => __( 'Süre dolunca tercih yeniden sorulur. Rehber, hatırlatmanın çerez ömrüyle orantılı olmasını önerir; örneklerinde 365 gün kullanılır.', 'umay-cerez-yoneticisi' ),
            ),
            'reload_on_withdraw'  => array(
                'section' => 'riza',
                'label'   => __( 'İzin geri alınınca', 'umay-cerez-yoneticisi' ),
                'type'    => 'checkbox',
                'text'    => __( 'Kapatılan kategorinin çerezlerini sil ve sayfayı yenile', 'umay-cerez-yoneticisi' ),
                'desc'    => __( 'Çalışmaya başlamış bir izleme kodu ancak sayfa yenilenince tamamen durur.', 'umay-cerez-yoneticisi' ),
            ),
            'block_enabled'       => array(
                'section' => 'riza',
                'label'   => __( 'Otomatik engelleme', 'umay-cerez-yoneticisi' ),
                'type'    => 'checkbox',
                'text'    => __( 'İzin verilmeyen kategorilerin kodlarını sayfa yüklenmeden engelle', 'umay-cerez-yoneticisi' ),
                'desc'    => __( 'Kapatırsanız izleme kodları ziyaretçi seçim yapmadan çalışır. Yalnızca sorun ararken kısa süreliğine kapatın.', 'umay-cerez-yoneticisi' ),
            ),
            'consent_mode'        => array(
                'section' => 'entegrasyon',
                'label'   => __( 'Google İzin Modu v2', 'umay-cerez-yoneticisi' ),
                'type'    => 'checkbox',
                'text'    => __( 'Google etiketlerine izin durumunu bildir (Consent Mode)', 'umay-cerez-yoneticisi' ),
                'desc'    => __( 'Sayfa başında tüm izinler "reddedildi" olarak bildirilir, ziyaretçinin seçimiyle güncellenir.', 'umay-cerez-yoneticisi' ),
            ),
            'gtm_event'           => array(
                'section' => 'entegrasyon',
                'label'   => __( 'Tag Manager olayı', 'umay-cerez-yoneticisi' ),
                'type'    => 'checkbox',
                'text'    => __( 'Her seçimde dataLayer\'a kvkkc_consent olayı gönder', 'umay-cerez-yoneticisi' ),
                'desc'    => __( 'GTM içindeki Google dışı etiketleri bu olay ve kvkkc_analitik, kvkkc_pazarlama değişkenleriyle tetikleyin.', 'umay-cerez-yoneticisi' ),
            ),
            'rules'               => array(
                'section'     => 'kurallar',
                'label'       => __( 'Elle kurallar', 'umay-cerez-yoneticisi' ),
                'type'        => 'rules',
                'rows'        => 6,
                'placeholder' => "ornek-izleme.com|pazarlama\ncdn.ornek.com/widget.js|islevsel",
                'desc'        => __( 'Her satıra "adres veya kod parçası|kategori" yazın. Kategori: zorunlu, islevsel, analitik, pazarlama. "zorunlu" yazılan bir kural o kodu engellemeden çıkarır.', 'umay-cerez-yoneticisi' ),
            ),
            'z_index'             => array(
                'section' => 'kurallar',
                'label'   => __( 'Katman sırası (z-index)', 'umay-cerez-yoneticisi' ),
                'type'    => 'number',
                'min'     => 1,
                'max'     => 2147483647,
                'desc'    => __( 'Bant temanın sabit menüsünün altında kalıyorsa artırın.', 'umay-cerez-yoneticisi' ),
            ),
            'store_ip_hash'       => array(
                'section' => 'kayit',
                'label'   => __( 'IP özeti', 'umay-cerez-yoneticisi' ),
                'type'    => 'checkbox',
                'text'    => __( 'Kırpılmış IP adresinin tek yönlü özetini kaydet', 'umay-cerez-yoneticisi' ),
                'desc'    => __( 'Ham IP ve tarayıcı bilgisi hiçbir durumda saklanmaz. Özet, bir rızanın hangi ağdan verildiğini doğrulamaya yeter.', 'umay-cerez-yoneticisi' ),
            ),
            'retention_months'    => array(
                'section' => 'kayit',
                'label'   => __( 'Saklama süresi', 'umay-cerez-yoneticisi' ),
                'type'    => 'number',
                'min'     => 1,
                'max'     => 120,
                'unit'    => __( 'ay', 'umay-cerez-yoneticisi' ),
                'desc'    => __( 'Süresi dolan kayıtlar her gün otomatik silinir.', 'umay-cerez-yoneticisi' ),
            ),
            'delete_on_uninstall' => array(
                'section' => 'kayit',
                'label'   => __( 'Eklenti silinince', 'umay-cerez-yoneticisi' ),
                'type'    => 'checkbox',
                'text'    => __( 'Rıza kayıtlarını da sil', 'umay-cerez-yoneticisi' ),
                'desc'    => __( 'Kapalıyken eklenti silinse bile kayıtlar ispat için veritabanında kalır.', 'umay-cerez-yoneticisi' ),
            ),
            'custom_css'          => array(
                'section'     => 'stil',
                'label'       => __( 'Özel CSS', 'umay-cerez-yoneticisi' ),
                'type'        => 'css',
                'rows'        => 8,
                'placeholder' => '.kvkkc .kvkkc-title { letter-spacing: .02em; }',
                'desc'        => __( 'Bandın tüm öğeleri .kvkkc ile başlayan sınıflar taşır. Tema stillerini geçmek için seçicilere .kvkkc ile başlayın.', 'umay-cerez-yoneticisi' ),
            ),
        );

        return $fields;
    }

    /**
     * Ayarları şemaya göre temizler. Gönderilmeyen alanlar mevcut değerini korur,
     * gönderilmeyen onay kutuları kapalı sayılır.
     *
     * @param mixed $input Ham ayarlar.
     * @return array
     */
    public static function sanitize( $input ) {
        $input    = is_array( $input ) ? $input : array();
        $defaults = KVKKC_Activator::default_settings();
        $clean    = array();

        foreach ( self::fields() as $key => $field ) {
            $type = $field['type'];

            if ( ! array_key_exists( $key, $input ) ) {
                $clean[ $key ] = 'checkbox' === $type ? 0 : kvkkc_setting( $key );
                continue;
            }

            $raw = is_scalar( $input[ $key ] ) ? (string) $input[ $key ] : '';

            switch ( $type ) {
                case 'checkbox':
                    $value = ( '' === $raw || '0' === $raw ) ? 0 : 1;
                    break;

                case 'choice':
                    $value = isset( $field['options'][ $raw ] ) ? $raw : $defaults[ $key ];
                    break;

                case 'range':
                case 'number':
                    $value = min( $field['max'], max( $field['min'], (int) $raw ) );
                    break;

                case 'color':
                    $color = sanitize_hex_color( $raw );
                    $value = $color ? $color : $defaults[ $key ];
                    break;

                case 'textarea':
                    $value = self::cut( sanitize_textarea_field( $raw ), 1000 );
                    break;

                case 'html':
                    $value = trim( wp_kses( $raw, self::allowed_html() ) );
                    break;

                case 'url':
                    $value = esc_url_raw( trim( $raw ) );
                    break;

                case 'media':
                    $value = absint( $raw );
                    break;

                case 'css':
                    $value = self::sanitize_css( $raw );
                    break;

                case 'rules':
                    $lines = array();
                    foreach ( KVKKC_Services::parse_rules( $raw ) as $rule ) {
                        $lines[] = $rule[0] . '|' . $rule[1];
                    }
                    $value = implode( "\n", $lines );
                    break;

                default:
                    $value = self::cut( sanitize_text_field( $raw ), 200 );
            }

            $clean[ $key ] = $value;
        }

        return $clean;
    }

    /**
     * Metinlerde izin verilen HTML.
     *
     * @return array
     */
    public static function allowed_html() {
        return array(
            'a'      => array(
                'href'   => true,
                'target' => true,
                'rel'    => true,
            ),
            'strong' => array(),
            'b'      => array(),
            'em'     => array(),
            'i'      => array(),
            'br'     => array(),
        );
    }

    /**
     * Özel CSS: etiketler, script çalıştırabilecek ve dış dosya çağıran ifadeler çıkarılır.
     * Bant rızadan önce göründüğü için mutlak adresli url() (başka bir sunucuya istek) da
     * kaldırılır; göreli adresler ve data: kalır.
     *
     * @param string $css CSS.
     * @return string
     */
    public static function sanitize_css( $css ) {
        $css = wp_strip_all_tags( (string) $css );
        $css = preg_replace( '/(javascript|vbscript)\s*:|expression\s*\(|@import|behavior\s*:|-moz-binding/i', '', $css );
        $css = preg_replace( '#url\(\s*[\'"]?\s*(?:[a-z][a-z0-9+.-]*:)?//[^)]*\)#i', 'none', $css );
        return self::cut( trim( $css ), 20000 );
    }

    /**
     * Uzunluk sınırı (çok baytlı karakter güvenli).
     *
     * @param string $text Metin.
     * @param int    $max  Azami karakter.
     * @return string
     */
    private static function cut( $text, $max ) {
        return function_exists( 'mb_substr' ) ? mb_substr( $text, 0, $max ) : substr( $text, 0, $max );
    }

    /**
     * Rehber kontrol listesi: ayarların KVKK Çerez Rehberi (Temmuz 2025) önerileriyle karşılaştırması.
     * Hukuki değerlendirme yapmaz, yalnızca ayarları rehberdeki maddelerle eşleştirir.
     *
     * @param array $s Ayarlar (temizlenmiş).
     * @return array array( array( 'state' => ok|warn|fail, 'text' => string, 'ref' => string ), ... )
     */
    public static function checks( $s ) {
        $contrast_text = self::contrast( $s['color_text'], $s['color_bg'] );
        $contrast_btn  = self::contrast( $s['color_btn_text'], $s['color_btn_bg'] );
        $scan          = get_option( 'kvkkc_scan', array() );

        return array(
            array(
                'state' => 'ok',
                'text'  => __( 'Kabul ve Reddet aynı renk, boyut ve puntoda', 'umay-cerez-yoneticisi' ),
                'ref'   => self::page_ref( 30 ),
            ),
            array(
                'state' => 'equal' === $s['prefs_style'] ? 'ok' : 'warn',
                'text'  => 'equal' === $s['prefs_style'] ? __( 'Ayarlar butonu da eşit sunuluyor', 'umay-cerez-yoneticisi' ) : __( 'Ayarlar butonu diğerlerinden farklı görünüyor', 'umay-cerez-yoneticisi' ),
                'ref'   => self::page_ref( 30 ),
            ),
            array(
                'state' => 'ok',
                'text'  => __( 'Kategoriler ön işaretsiz, zorunlu dışındakiler kapalı başlar', 'umay-cerez-yoneticisi' ),
                'ref'   => self::page_ref( 31 ),
            ),
            array(
                'state' => $s['block_enabled'] ? 'ok' : 'fail',
                'text'  => $s['block_enabled'] ? __( 'Kodlar izin verilene kadar engelleniyor', 'umay-cerez-yoneticisi' ) : __( 'Otomatik engelleme kapalı: çerezler izinsiz yerleşir', 'umay-cerez-yoneticisi' ),
                'ref'   => self::page_ref( 29 ),
            ),
            array(
                'state' => '' !== $s['page_aydinlatma'] && '' !== $s['page_cerez'] ? 'ok' : 'fail',
                'text'  => '' !== $s['page_aydinlatma'] && '' !== $s['page_cerez'] ? __( 'Aydınlatma metni ve çerez politikası bağlı', 'umay-cerez-yoneticisi' ) : __( 'Aydınlatma metni veya çerez politikası seçilmedi', 'umay-cerez-yoneticisi' ),
                'ref'   => self::page_ref( 30 ),
            ),
            array(
                'state' => $s['float_enabled'] ? 'ok' : 'warn',
                'text'  => $s['float_enabled'] ? __( 'Tercih köşedeki simgeden her an değiştirilebilir', 'umay-cerez-yoneticisi' ) : __( 'Yüzen buton kapalı: kısa kodu alt bilgiye eklediğinizden emin olun', 'umay-cerez-yoneticisi' ),
                'ref'   => self::page_ref( 29 ),
            ),
            array(
                'state' => $s['consent_days'] <= 365 ? 'ok' : 'warn',
                /* translators: %d: gün sayısı */
                'text'  => sprintf( __( 'Tercih %d günde bir yeniden soruluyor', 'umay-cerez-yoneticisi' ), $s['consent_days'] ),
                'ref'   => self::page_ref( 30 ),
            ),
            array(
                'state' => min( $contrast_text, $contrast_btn ) >= 4.5 ? 'ok' : 'warn',
                /* translators: %s: kontrast oranı */
                'text'  => sprintf( __( 'Metin kontrastı %s:1', 'umay-cerez-yoneticisi' ), number_format_i18n( min( $contrast_text, $contrast_btn ), 1 ) ),
                'ref'   => 'WCAG',
            ),
            array(
                'state' => ! empty( $scan['time'] ) ? 'ok' : 'warn',
                'text'  => ! empty( $scan['time'] ) ? __( 'Site taranmış, çerez listesi hazır', 'umay-cerez-yoneticisi' ) : __( 'Site henüz taranmadı', 'umay-cerez-yoneticisi' ),
                'ref'   => 'EK-4',
            ),
        );
    }

    /**
     * Rehberdeki sayfa numarası etiketi.
     *
     * @param int $page Sayfa numarası.
     * @return string
     */
    private static function page_ref( $page ) {
        /* translators: %d: KVKK Çerez Rehberi'ndeki sayfa numarası */
        return sprintf( __( 's. %d', 'umay-cerez-yoneticisi' ), $page );
    }

    /**
     * WCAG kontrast oranı.
     *
     * @param string $a Renk (#rrggbb).
     * @param string $b Renk (#rrggbb).
     * @return float
     */
    public static function contrast( $a, $b ) {
        $lum = function ( $hex ) {
            $hex = ltrim( (string) $hex, '#' );
            if ( 3 === strlen( $hex ) ) {
                $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
            }
            $rgb = array_map( 'hexdec', str_split( str_pad( $hex, 6, '0' ), 2 ) );
            foreach ( $rgb as $i => $c ) {
                $c         = $c / 255;
                $rgb[ $i ] = $c <= 0.03928 ? $c / 12.92 : pow( ( $c + 0.055 ) / 1.055, 2.4 );
            }
            return 0.2126 * $rgb[0] + 0.7152 * $rgb[1] + 0.0722 * $rgb[2];
        };

        $l1 = $lum( $a );
        $l2 = $lum( $b );

        return ( max( $l1, $l2 ) + 0.05 ) / ( min( $l1, $l2 ) + 0.05 );
    }

    /**
     * Canlı önizleme: formdaki değerler temizlenip banner yapılandırmasına çevrilir.
     * Önizleme ile sitedeki bant aynı kodla üretildiği için birebir aynıdır.
     */
    public function ajax_preview() {
        check_ajax_referer( 'kvkkc_admin' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( null, 403 );
        }

        $raw      = isset( $_POST['kvkkc_settings'] ) && is_array( $_POST['kvkkc_settings'] ) ? wp_unslash( $_POST['kvkkc_settings'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitize() ile temizlenir.
        $settings = self::sanitize( $raw );

        wp_send_json_success(
            array(
                'config' => KVKKC_Frontend::config( $settings, true ),
                'checks' => self::checks( $settings ),
            )
        );
    }

    /**
     * "Yeniden rıza iste": politika sürümü artar, eski tercihler geçersiz olur.
     */
    public function ajax_bump_version() {
        check_ajax_referer( 'kvkkc_admin' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( null, 403 );
        }

        update_option( 'kvkkc_policy_version', kvkkc_policy_version() + 1 );
        kvkkc_purge_caches();

        wp_send_json_success( array( 'version' => kvkkc_policy_version() ) );
    }

    /**
     * Envanter formunu kaydeder.
     */
    public function save_inventory() {
        check_admin_referer( 'kvkkc_inventory' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Yetkiniz yok.', 'umay-cerez-yoneticisi' ), 403 );
        }

        $raw = isset( $_POST['inventory'] ) && is_array( $_POST['inventory'] ) ? wp_unslash( $_POST['inventory'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitize_inventory() ile temizlenir.

        update_option( 'kvkkc_inventory', KVKKC_Services::sanitize_inventory( $raw ) );
        kvkkc_purge_caches();

        wp_safe_redirect( add_query_arg( 'kvkkc_notice', 'saved', admin_url( 'admin.php?page=kvkk-cerez-envanter' ) ) );
        exit;
    }

    /**
     * Taramada bulunan veya katalogdan seçilen hizmetleri ve bilinmeyen çerezleri envantere ekler.
     */
    public function add_services() {
        check_admin_referer( 'kvkkc_add_services' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Yetkiniz yok.', 'umay-cerez-yoneticisi' ), 403 );
        }

        $inventory = KVKKC_Services::inventory();
        $ids       = isset( $_POST['services'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['services'] ) ) : array();

        foreach ( $ids as $id ) {
            if ( 'ozel' === $id ) {
                $inventory[ 'ozel-' . strtolower( wp_generate_password( 6, false ) ) ] = array(
                    'name'     => __( 'Yeni hizmet', 'umay-cerez-yoneticisi' ),
                    'category' => 'islevsel',
                    'party'    => 3,
                );
            } elseif ( ! isset( $inventory[ $id ] ) && KVKKC_Services::get( $id ) ) {
                $inventory[ $id ] = KVKKC_Services::template( $id );
            }
        }

        // Bilinmeyen çerezler seçilen kategoriye göre "Bu site" kayıtlarına eklenir.
        $cookies = isset( $_POST['cookies'] ) && is_array( $_POST['cookies'] ) ? wp_unslash( $_POST['cookies'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitize_inventory() ile temizlenir.
        foreach ( $cookies as $name => $category ) {
            if ( ! in_array( $category, kvkkc_categories(), true ) ) {
                continue;
            }
            $key = 'site-' . $category;
            if ( ! isset( $inventory[ $key ] ) ) {
                $inventory[ $key ] = array(
                    /* translators: %s: kategori adı */
                    'name'     => sprintf( __( 'Site çerezleri (%s)', 'umay-cerez-yoneticisi' ), KVKKC_Frontend::category_name( $category ) ),
                    'provider' => get_bloginfo( 'name' ),
                    'category' => $category,
                    'party'    => 1,
                    'cookies'  => array(),
                );
            }
            $inventory[ $key ]['cookies'][] = array(
                'name'     => (string) $name,
                'duration' => '',
                'purpose'  => '',
            );
        }

        update_option( 'kvkkc_inventory', KVKKC_Services::sanitize_inventory( $inventory ) );
        kvkkc_purge_caches();

        wp_safe_redirect( add_query_arg( 'kvkkc_notice', 'added', admin_url( 'admin.php?page=kvkk-cerez-envanter' ) ) );
        exit;
    }

    /**
     * Rıza kayıtlarını CSV olarak indirir.
     */
    public function export_csv() {
        check_admin_referer( 'kvkkc_export_csv' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Yetkiniz yok.', 'umay-cerez-yoneticisi' ), 403 );
        }

        global $wpdb;

        $rows = $wpdb->get_results( $wpdb->prepare( 'SELECT consent_id, created_at, action, categories, policy_version, ip_hash, page FROM %i ORDER BY id DESC', kvkkc_table() ), ARRAY_N ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

        nocache_headers();
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=riza-kayitlari-' . gmdate( 'Y-m-d' ) . '.csv' );

        $out = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- çıktı akışı.
        fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- Excel için UTF-8 BOM.
        fputcsv(
            $out,
            array(
                __( 'Rıza No', 'umay-cerez-yoneticisi' ),
                __( 'Tarih (UTC)', 'umay-cerez-yoneticisi' ),
                __( 'Eylem', 'umay-cerez-yoneticisi' ),
                __( 'İzin Verilen Kategoriler', 'umay-cerez-yoneticisi' ),
                __( 'Politika Sürümü', 'umay-cerez-yoneticisi' ),
                __( 'IP Özeti', 'umay-cerez-yoneticisi' ),
                __( 'Sayfa', 'umay-cerez-yoneticisi' ),
            ),
            ';'
        );
        foreach ( $rows as $row ) {
            fputcsv( $out, array_map( array( 'KVKKC_Consent_Log', 'csv_cell' ), $row ), ';' );
        }
        fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
        exit;
    }

    /**
     * Ayarlar sayfası.
     */
    public function page_settings() {
        include KVKKC_DIR . 'admin/partials/settings-page.php';
    }

    /**
     * Envanter sayfası.
     */
    public function page_inventory() {
        include KVKKC_DIR . 'admin/partials/inventory-page.php';
    }

    /**
     * Rıza kayıtları sayfası.
     */
    public function page_consents() {
        include KVKKC_DIR . 'admin/partials/consents-page.php';
    }

    /**
     * Uygulama çubuğu.
     *
     * @param string $current Etkin sayfa.
     */
    public static function render_header( $current ) {
        $pages = array(
            'kvkk-cerez'          => __( 'Ayarlar', 'umay-cerez-yoneticisi' ),
            'kvkk-cerez-envanter' => __( 'Çerez Envanteri', 'umay-cerez-yoneticisi' ),
            'kvkk-cerez-kayitlar' => __( 'Rıza Kayıtları', 'umay-cerez-yoneticisi' ),
        );
        ?>
        <header class="kvkkc-appbar">
            <div class="kvkkc-brand">
                <span class="kvkkc-brand-mark" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2.8 4.5 5.7v5.5c0 4.7 3.1 8.5 7.5 10 4.4-1.5 7.5-5.3 7.5-10V5.7L12 2.8Z"/><path d="m8.6 12.2 2.3 2.3 4.6-4.8"/></svg>
                </span>
                <span class="kvkkc-brand-text">
                    <strong><?php esc_html_e( 'Umay Çerez Yöneticisi', 'umay-cerez-yoneticisi' ); ?></strong>
                    <span><?php echo esc_html( wp_parse_url( home_url(), PHP_URL_HOST ) ); ?></span>
                </span>
            </div>
            <nav class="kvkkc-appnav" aria-label="<?php esc_attr_e( 'Eklenti sayfaları', 'umay-cerez-yoneticisi' ); ?>">
                <?php foreach ( $pages as $slug => $label ) : ?>
                    <a class="kvkkc-appnav-link<?php echo $current === $slug ? ' is-active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=' . $slug ) ); ?>"<?php echo $current === $slug ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $label ); ?></a>
                <?php endforeach; ?>
            </nav>
            <div class="kvkkc-version" data-kvkkc-version>
                <span class="kvkkc-version-label"><?php esc_html_e( 'Politika sürümü', 'umay-cerez-yoneticisi' ); ?></span>
                <strong data-kvkkc-version-number><?php echo esc_html( 'v' . kvkkc_policy_version() ); ?></strong>
                <button type="button" class="kvkkc-link-button" data-kvkkc-bump><?php esc_html_e( 'Yeniden rıza iste', 'umay-cerez-yoneticisi' ); ?></button>
            </div>
        </header>
        <?php
    }

    /**
     * Tek bir ayar alanını çizer.
     *
     * @param string $key   Alan anahtarı.
     * @param array  $field Şema.
     * @param mixed  $value Değer.
     */
    public static function render_field( $key, $field, $value ) {
        $id      = 'kvkkc-' . str_replace( '_', '-', $key );
        $name    = 'kvkkc_settings[' . $key . ']';
        $type    = $field['type'];
        $show_if = isset( $field['show_if'] ) ? implode( ':', $field['show_if'] ) : '';
        $grouped = in_array( $type, array( 'choice', 'checkbox' ), true );
        ?>
        <div class="kvkkc-field kvkkc-field--<?php echo esc_attr( $type ); ?>"<?php echo '' !== $show_if ? ' data-show-if="' . esc_attr( $show_if ) . '"' : ''; ?>>
            <?php if ( $grouped ) : ?>
                <span class="kvkkc-field-label" id="<?php echo esc_attr( $id ); ?>-label"><?php echo esc_html( $field['label'] ); ?></span>
            <?php else : ?>
                <label class="kvkkc-field-label" for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $field['label'] ); ?></label>
            <?php endif; ?>

            <div class="kvkkc-field-control">
                <?php
                switch ( $type ) {
                    case 'choice':
                        $style = isset( $field['style'] ) ? $field['style'] : 'segmented';
                        ?>
                        <div class="kvkkc-choice kvkkc-choice--<?php echo esc_attr( $style ); ?>" role="radiogroup" aria-labelledby="<?php echo esc_attr( $id ); ?>-label">
                            <?php foreach ( $field['options'] as $option => $label ) : ?>
                                <label class="kvkkc-choice-item">
                                    <input type="radio" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $option ); ?>" <?php checked( $value, $option ); ?>>
                                    <span class="kvkkc-choice-face">
                                        <?php if ( 'cards' === $style ) : ?>
                                            <?php echo wp_kses( self::layout_thumb( $option ), kvkkc_svg_allowed_html() ); ?>
                                        <?php elseif ( 'icons' === $style ) : ?>
                                            <?php echo wp_kses( KVKKC_Frontend::icon( $option, 22 ), kvkkc_svg_allowed_html() ); ?>
                                        <?php endif; ?>
                                        <span class="kvkkc-choice-text"><?php echo esc_html( $label ); ?></span>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <?php
                        break;

                    case 'checkbox':
                        ?>
                        <label class="kvkkc-switch-row">
                            <input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="0">
                            <input type="checkbox" class="kvkkc-switch-input" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="1" <?php checked( (int) $value, 1 ); ?>>
                            <span class="kvkkc-switch" aria-hidden="true"></span>
                            <span><?php echo esc_html( $field['text'] ); ?></span>
                        </label>
                        <?php
                        break;

                    case 'color':
                        ?>
                        <span class="kvkkc-color">
                            <input type="color" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>">
                            <code data-kvkkc-color-value><?php echo esc_html( $value ); ?></code>
                        </span>
                        <?php
                        break;

                    case 'range':
                        ?>
                        <span class="kvkkc-range">
                            <input type="range" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" min="<?php echo esc_attr( $field['min'] ); ?>" max="<?php echo esc_attr( $field['max'] ); ?>" step="<?php echo esc_attr( isset( $field['step'] ) ? $field['step'] : 1 ); ?>">
                            <output for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $value . $field['unit'] ); ?></output>
                        </span>
                        <?php
                        break;

                    case 'number':
                        ?>
                        <span class="kvkkc-number">
                            <input type="number" class="small-text" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" min="<?php echo esc_attr( $field['min'] ); ?>" max="<?php echo esc_attr( $field['max'] ); ?>">
                            <?php if ( ! empty( $field['unit'] ) ) : ?>
                                <span class="kvkkc-unit"><?php echo esc_html( $field['unit'] ); ?></span>
                            <?php endif; ?>
                        </span>
                        <?php
                        break;

                    case 'textarea':
                    case 'html':
                    case 'css':
                    case 'rules':
                        $mono = in_array( $type, array( 'css', 'rules' ), true ) ? ' kvkkc-mono' : '';
                        ?>
                        <textarea id="<?php echo esc_attr( $id ); ?>" class="large-text<?php echo esc_attr( $mono ); ?>" name="<?php echo esc_attr( $name ); ?>" rows="<?php echo esc_attr( isset( $field['rows'] ) ? $field['rows'] : 3 ); ?>" placeholder="<?php echo esc_attr( isset( $field['placeholder'] ) ? $field['placeholder'] : '' ); ?>" spellcheck="<?php echo $mono ? 'false' : 'true'; ?>"><?php echo esc_textarea( $value ); ?></textarea>
                        <?php
                        break;

                    case 'url':
                        ?>
                        <input type="text" inputmode="url" class="regular-text" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" list="kvkkc-pages" placeholder="<?php echo esc_attr( home_url( '/ornek-sayfa/' ) ); ?>">
                        <?php
                        break;

                    case 'media':
                        $src = $value ? wp_get_attachment_image_url( (int) $value, 'medium' ) : '';
                        ?>
                        <span class="kvkkc-media" data-kvkkc-media>
                            <input type="hidden" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>">
                            <span class="kvkkc-media-thumb"<?php echo $src ? '' : ' hidden'; ?>><img src="<?php echo esc_url( (string) $src ); ?>" alt=""></span>
                            <button type="button" class="button" data-kvkkc-media-pick><?php esc_html_e( 'Görsel seç', 'umay-cerez-yoneticisi' ); ?></button>
                            <button type="button" class="button-link button-link-delete" data-kvkkc-media-clear<?php echo $src ? '' : ' hidden'; ?>><?php esc_html_e( 'Kaldır', 'umay-cerez-yoneticisi' ); ?></button>
                        </span>
                        <?php
                        break;

                    default:
                        ?>
                        <input type="text" class="regular-text" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php echo esc_attr( isset( $field['placeholder'] ) ? $field['placeholder'] : '' ); ?>">
                        <?php
                }
                ?>

                <?php if ( ! empty( $field['desc'] ) ) : ?>
                    <p class="kvkkc-field-desc"><?php echo esc_html( $field['desc'] ); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Bant tipi seçimindeki küçük çizimler.
     *
     * @param string $layout Bant tipi.
     * @return string
     */
    private static function layout_thumb( $layout ) {
        $shapes = array(
            'bar'   => '<rect x="4" y="42" width="72" height="12" rx="2" class="kvkkc-thumb-banner"/>',
            'card'  => '<rect x="6" y="30" width="34" height="22" rx="3" class="kvkkc-thumb-banner"/>',
            'modal' => '<rect x="4" y="4" width="72" height="52" rx="3" class="kvkkc-thumb-dim"/><rect x="22" y="16" width="36" height="28" rx="3" class="kvkkc-thumb-banner"/>',
        );

        return '<svg class="kvkkc-thumb" viewBox="0 0 80 60" aria-hidden="true"><rect x="0.5" y="0.5" width="79" height="59" rx="5" class="kvkkc-thumb-page"/><path d="M10 12h34M10 18h46M10 24h28" class="kvkkc-thumb-lines"/>' . $shapes[ $layout ] . '</svg>';
    }
}
