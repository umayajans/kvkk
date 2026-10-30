<?php
/**
 * Aktivasyon, veritabanı şeması ve varsayılan ayarlar
 *
 * @package KVKK_Cerez
 */

// Doğrudan erişimi engelle.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Aktivasyon işlemleri
 */
class KVKKC_Activator {

    /**
     * Aktivasyon.
     */
    public static function activate() {
        self::create_tables();

        add_option( 'kvkkc_policy_version', 1 );
        add_option( 'kvkkc_inventory', KVKKC_Services::default_inventory() );
        add_option( 'kvkkc_settings', self::detect_pages() );

        if ( ! wp_next_scheduled( 'kvkkc_cleanup' ) ) {
            wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'kvkkc_cleanup' );
        }
    }

    /**
     * Devre dışı bırakma: zamanlanmış temizlik kaldırılır, veriler korunur.
     */
    public static function deactivate() {
        wp_clear_scheduled_hook( 'kvkkc_cleanup' );
    }

    /**
     * Rıza kayıtları tablosunu oluşturur veya günceller.
     */
    public static function create_tables() {
        global $wpdb;

        $table_name      = kvkkc_table();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            consent_id char(36) NOT NULL,
            created_at datetime NOT NULL,
            action varchar(20) NOT NULL,
            categories varchar(100) NOT NULL DEFAULT '',
            policy_version int(10) unsigned NOT NULL DEFAULT 1,
            ip_hash char(64) NOT NULL DEFAULT '',
            page varchar(255) NOT NULL DEFAULT '',
            PRIMARY KEY  (id),
            KEY consent_id (consent_id),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );

        update_option( 'kvkkc_db_version', KVKKC_VERSION );
    }

    /**
     * Yaygın yasal sayfa adreslerini bulur (ör. /cerez-politikasi/). Yalnızca ilk kurulumda çalışır.
     *
     * @return array Bulunan sayfa ayarları.
     */
    private static function detect_pages() {
        $slugs = array(
            'page_aydinlatma' => array( 'kvkk-aydinlatma-metni', 'aydinlatma-metni', 'kvkk' ),
            'page_cerez'      => array( 'cerez-politikasi', 'cerez-aydinlatma-metni', 'cerezler' ),
            'page_gizlilik'   => array( 'gizlilik-politikasi', 'gizlilik' ),
            'page_acik_riza'  => array( 'acik-riza-metni', 'acik-riza' ),
        );

        $found = array();
        foreach ( $slugs as $key => $candidates ) {
            foreach ( $candidates as $slug ) {
                $page = get_page_by_path( $slug );
                if ( $page && 'publish' === $page->post_status ) {
                    $found[ $key ] = get_permalink( $page );
                    break;
                }
            }
        }

        if ( empty( $found['page_gizlilik'] ) && (int) get_option( 'wp_page_for_privacy_policy' ) ) {
            $found['page_gizlilik'] = get_permalink( (int) get_option( 'wp_page_for_privacy_policy' ) );
        }

        return $found;
    }

    /**
     * Varsayılan ayarlar. kvkkc_setting() kayıtlı değer yoksa veya boşsa bunları kullanır.
     *
     * Metinler KVKK Çerez Rehberi'nin (Temmuz 2025) önerdiği içeriğe göre yazıldı: amaçlar,
     * birinci/üçüncü taraf ayrımı ve tercihin değiştirilebileceği yer ilk katmanda yer alır.
     *
     * @return array
     */
    public static function default_settings() {
        static $defaults = null;

        if ( null !== $defaults ) {
            return $defaults;
        }

        $defaults = array(
            // Görünüm.
            'layout'              => 'card',
            'bar_position'        => 'bottom',
            'card_position'       => 'left',
            'card_width'          => 480,
            'overlay'             => 'none',
            'lock_scroll'         => 0,
            'show_categories'     => 0,
            'button_order'        => 'accept_first',
            'prefs_style'         => 'equal',
            'animation'           => 'slide',
            'logo'                => 0,

            // Renk ve yazı.
            'color_bg'            => '#ffffff',
            'color_text'          => '#1d2433',
            'color_link'          => '#2b59c3',
            'color_btn_bg'        => '#1d2433',
            'color_btn_text'      => '#ffffff',
            'color_accent'        => '#2b59c3',
            'color_border'        => '#e3e6ee',
            'auto_dark'           => 0,
            'font'                => 'inherit',
            'font_size'           => 14,
            'radius'              => 16,
            'btn_radius'          => 10,
            'shadow'              => 1,

            // Metinler.
            'title'               => __( 'Çerez Tercihleriniz', 'umay-cerez-yoneticisi' ),
            'message'             => __( 'Sitemizin çalışması için zorunlu çerezler kullanıyoruz. Ziyaretinizi ölçmek (analitik), size uygun reklamlar göstermek (pazarlama) ve tercihlerinizi hatırlamak (işlevsel) için kendimize (birinci taraf) ve iş ortaklarımıza (üçüncü taraf) ait çerezleri ise yalnızca izin verirseniz kullanırız. Seçiminizi dilediğiniz zaman sayfanın köşesindeki çerez simgesinden değiştirebilirsiniz.', 'umay-cerez-yoneticisi' ),
            'btn_accept'          => __( 'Tümünü Kabul Et', 'umay-cerez-yoneticisi' ),
            'btn_reject'          => __( 'Tümünü Reddet', 'umay-cerez-yoneticisi' ),
            'btn_prefs'           => __( 'Çerez Ayarları', 'umay-cerez-yoneticisi' ),
            'btn_save'            => __( 'Seçimi Kaydet', 'umay-cerez-yoneticisi' ),
            'prefs_title'         => __( 'Çerez Ayarları', 'umay-cerez-yoneticisi' ),
            'prefs_intro'         => __( 'Sitemizde kullanılan çerezleri amaçlarına göre grupladık. Zorunlu çerezler sitenin çalışması için gereklidir ve kapatılamaz. Diğer kategoriler yalnızca izin verdiğinizde etkinleşir; verdiğiniz izni dilediğiniz zaman bu ekrandan geri alabilirsiniz.', 'umay-cerez-yoneticisi' ),
            'always_active'       => __( 'Her zaman aktif', 'umay-cerez-yoneticisi' ),
            'placeholder_text'    => __( 'Bu içerik {kategori} kullanır. Görüntülemek için bu kategoriye izin vermeniz gerekir.', 'umay-cerez-yoneticisi' ),
            'placeholder_btn'     => __( 'İzin ver ve içeriği yükle', 'umay-cerez-yoneticisi' ),

            // Kategoriler.
            'cat_zorunlu_name'    => __( 'Zorunlu Çerezler', 'umay-cerez-yoneticisi' ),
            'cat_zorunlu_desc'    => __( 'Sitenin güvenli ve doğru çalışması için gereklidir: oturum, güvenlik, yük dengeleme ve çerez tercihinizin saklanması gibi. Bu çerezler için izin istenmez ve kapatılamaz.', 'umay-cerez-yoneticisi' ),
            'cat_islevsel_name'   => __( 'İşlevsel Çerezler', 'umay-cerez-yoneticisi' ),
            'cat_islevsel_desc'   => __( 'Harita, sohbet penceresi veya dil seçimi gibi kullandığınız özelliklerin tercihlerinizi hatırlayarak çalışmasını sağlar.', 'umay-cerez-yoneticisi' ),
            'cat_analitik_name'   => __( 'Analitik Çerezler', 'umay-cerez-yoneticisi' ),
            'cat_analitik_desc'   => __( 'Sitemizin nasıl kullanıldığını (ziyaret edilen sayfalar, ziyaret süresi gibi) ölçerek hizmetimizi geliştirmemize yardımcı olur. Bu çerezlerin bir kısmı yurt dışında bulunan hizmet sağlayıcılara aittir.', 'umay-cerez-yoneticisi' ),
            'cat_pazarlama_name'  => __( 'Pazarlama Çerezleri', 'umay-cerez-yoneticisi' ),
            'cat_pazarlama_desc'  => __( 'İlgi alanlarınıza uygun reklamlar göstermek, reklamların etkinliğini ölçmek ve gömülü video gibi üçüncü taraf içerikleri sunmak için kullanılır. Bu çerezler yurt dışında bulunan iş ortaklarımıza aittir.', 'umay-cerez-yoneticisi' ),

            // Yüzen buton.
            'float_enabled'       => 1,
            'float_position'      => 'left',
            'float_icon'          => 'cookie',
            'float_label'         => __( 'Çerez Ayarları', 'umay-cerez-yoneticisi' ),

            // Yasal sayfalar.
            'page_aydinlatma'     => '',
            'page_cerez'          => '',
            'page_gizlilik'       => '',
            'page_acik_riza'      => '',

            // Davranış.
            'consent_days'        => 365,
            'reload_on_withdraw'  => 1,
            'block_enabled'       => 1,

            // Entegrasyonlar.
            'consent_mode'        => 1,
            'gtm_event'           => 1,

            // Gelişmiş.
            'rules'               => '',
            'z_index'             => 99999,
            'store_ip_hash'       => 1,
            'retention_months'    => 24,
            'delete_on_uninstall' => 0,
        );

        return $defaults;
    }
}
