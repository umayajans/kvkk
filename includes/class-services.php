<?php
/**
 * Yerleşik hizmet ve çerez veritabanı, envanter ve eşleme kuralları
 *
 * @package KVKK_Cerez
 */

// Doğrudan erişimi engelle.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Hizmetler
 *
 * İşaretler (markers) "alanadi.com/yol-oneki" biçimindedir: alan adı tam ya da alt alan adı
 * olarak eşleşir, yol öneki adresin yol ve sorgu kısmının başıyla karşılaştırılır. Satır içi
 * imzalar (inline) yalnızca bilinen yükleyici kodlarında geçen özgün dizgelerdir; genel alan
 * adları bilerek kullanılmaz, aksi halde tema ayarlarını taşıyan scriptler de engellenir.
 */
class KVKKC_Services {

    /**
     * Yerleşik hizmetler.
     *
     * Çerez satırı: array( ad, süre, amaç ). Adın sonundaki * önek eşleşmesi demektir.
     *
     * @return array
     */
    public static function all() {
        static $list = null;

        if ( null !== $list ) {
            return $list;
        }

        $google = 'Google LLC';
        $meta   = 'Meta Platforms Ireland Ltd.';
        $ms     = 'Microsoft Corporation';

        $list = array(
            'kvkk-cerez'              => array(
                'name'     => __( 'Çerez tercihleri', 'kvkk-cerez' ),
                'category' => 'zorunlu',
                'party'    => 1,
                'purpose'  => __( 'Çerez tercihinizi saklar; böylece her sayfada yeniden sorulmaz.', 'kvkk-cerez' ),
                'detect'   => 'KVKKC_VERSION',
                'cookies'  => array(
                    array( 'kvkkc_consent', __( '{gun} gün', 'kvkk-cerez' ), __( 'Çerez tercihinizi, tercih tarihini ve rıza numaranızı saklar.', 'kvkk-cerez' ) ),
                ),
            ),
            'wordpress'               => array(
                'name'     => 'WordPress',
                'category' => 'zorunlu',
                'party'    => 1,
                'purpose'  => __( 'Sitenin altyapısı. Giriş yapan kullanıcıların oturumunu ve yorum formu tercihlerini yönetir.', 'kvkk-cerez' ),
                'detect'   => 'ABSPATH',
                'cookies'  => array(
                    array( 'wordpress_logged_in_*', __( 'Oturum veya 14 gün', 'kvkk-cerez' ), __( 'Giriş yapmış kullanıcının oturumunu sürdürür.', 'kvkk-cerez' ) ),
                    array( 'wordpress_sec_*', __( 'Oturum veya 14 gün', 'kvkk-cerez' ), __( 'Giriş yapmış kullanıcının kimlik doğrulama bilgisini güvenli biçimde tutar.', 'kvkk-cerez' ) ),
                    array( 'wordpress_test_cookie', __( 'Oturum', 'kvkk-cerez' ), __( 'Tarayıcınızın çerezleri kabul edip etmediğini kontrol eder.', 'kvkk-cerez' ) ),
                    array( 'wp-settings-*', __( '1 yıl', 'kvkk-cerez' ), __( 'Yönetim paneli görünüm tercihlerini saklar.', 'kvkk-cerez' ) ),
                    array( 'comment_author_*', __( '347 gün', 'kvkk-cerez' ), __( 'Yorum formunda adınızı ve e-postanızı hatırlar (yalnızca siz işaretlerseniz).', 'kvkk-cerez' ) ),
                ),
            ),
            'php-oturum'              => array(
                'name'     => __( 'Sunucu oturumu', 'kvkk-cerez' ),
                'category' => 'zorunlu',
                'party'    => 1,
                'purpose'  => __( 'Sitedeki bazı eklentilerin sunucu tarafında oturum bilgisi tutmasını sağlar.', 'kvkk-cerez' ),
                'cookies'  => array(
                    array( 'PHPSESSID', __( 'Oturum', 'kvkk-cerez' ), __( 'Sunucu tarafındaki oturumu tanımlar; tarayıcı kapatılınca silinir.', 'kvkk-cerez' ) ),
                ),
            ),
            'woocommerce'             => array(
                'name'     => 'WooCommerce',
                'category' => 'zorunlu',
                'party'    => 1,
                'purpose'  => __( 'Alışveriş sepetinin ve sipariş sürecinin çalışmasını sağlar.', 'kvkk-cerez' ),
                'detect'   => 'WC_VERSION',
                'cookies'  => array(
                    array( 'woocommerce_cart_hash', __( 'Oturum', 'kvkk-cerez' ), __( 'Sepet içeriğindeki değişiklikleri izler.', 'kvkk-cerez' ) ),
                    array( 'woocommerce_items_in_cart', __( 'Oturum', 'kvkk-cerez' ), __( 'Sepette ürün olup olmadığını tutar.', 'kvkk-cerez' ) ),
                    array( 'wp_woocommerce_session_*', __( '2 gün', 'kvkk-cerez' ), __( 'Sepetinizi oturumunuzla ilişkilendirir.', 'kvkk-cerez' ) ),
                ),
            ),
            'woocommerce-attribution' => array(
                'name'     => __( 'WooCommerce sipariş kaynağı', 'kvkk-cerez' ),
                'category' => 'pazarlama',
                'party'    => 1,
                'purpose'  => __( 'Siparişin hangi kanaldan (reklam, arama, sosyal medya) geldiğini kaydeder.', 'kvkk-cerez' ),
                'detect'   => 'WC_VERSION',
                'cookies'  => array(
                    array( 'sbjs_*', __( 'Oturum ile 6 ay arası', 'kvkk-cerez' ), __( 'Ziyaretin geldiği kaynağı ve kampanya bilgisini tutar.', 'kvkk-cerez' ) ),
                ),
            ),
            'litespeed'               => array(
                'name'     => 'LiteSpeed Cache',
                'category' => 'zorunlu',
                'party'    => 1,
                'purpose'  => __( 'Sayfaların hızlı yüklenmesi için önbellek sisteminin size doğru sayfa sürümünü sunmasını sağlar.', 'kvkk-cerez' ),
                'detect'   => 'LSCWP_V',
                'cookies'  => array(
                    array( '_lscache_vary', __( 'Oturum', 'kvkk-cerez' ), __( 'Giriş yapmış ve yapmamış ziyaretçilere farklı önbellek sürümü sunar.', 'kvkk-cerez' ) ),
                ),
            ),
            'wordfence'               => array(
                'name'     => 'Wordfence',
                'category' => 'zorunlu',
                'party'    => 1,
                'purpose'  => __( 'Siteyi saldırılara karşı koruyan güvenlik duvarı.', 'kvkk-cerez' ),
                'detect'   => 'WORDFENCE_VERSION',
                'cookies'  => array(
                    array( 'wfwaf-authcookie-*', __( '12 saat', 'kvkk-cerez' ), __( 'Giriş yapmış kullanıcıların güvenlik duvarı tarafından tanınmasını sağlar.', 'kvkk-cerez' ) ),
                    array( 'wf_loginalerted_*', __( '1 yıl', 'kvkk-cerez' ), __( 'Yeni bir cihazdan yönetici girişi yapıldığında uyarı gönderilmesine yarar.', 'kvkk-cerez' ) ),
                ),
            ),
            'cloudflare'              => array(
                'name'     => 'Cloudflare',
                'provider' => 'Cloudflare, Inc.',
                'country'  => __( 'ABD', 'kvkk-cerez' ),
                'category' => 'zorunlu',
                'party'    => 3,
                'purpose'  => __( 'Siteyi kötü amaçlı trafiğe karşı koruyan ve içeriği hızlı sunan ağ hizmeti.', 'kvkk-cerez' ),
                'header'   => 'cf-ray',
                'cookies'  => array(
                    array( '__cf_bm', __( '30 dakika', 'kvkk-cerez' ), __( 'Bot trafiğini ayırt ederek siteyi korur.', 'kvkk-cerez' ) ),
                    array( 'cf_clearance', __( '1 yıl', 'kvkk-cerez' ), __( 'Güvenlik doğrulamasını geçtiğinizi hatırlar.', 'kvkk-cerez' ) ),
                    array( '_cfuvid', __( 'Oturum', 'kvkk-cerez' ), __( 'Aynı IP adresini paylaşan kullanıcıları hız sınırlaması için ayırt eder.', 'kvkk-cerez' ) ),
                ),
            ),
            'recaptcha'               => array(
                'name'     => 'Google reCAPTCHA',
                'provider' => $google,
                'country'  => __( 'ABD', 'kvkk-cerez' ),
                'category' => 'zorunlu',
                'party'    => 3,
                'purpose'  => __( 'Formları otomatik saldırılara ve istenmeyen gönderimlere karşı korur.', 'kvkk-cerez' ),
                'markers'  => array( 'google.com/recaptcha', 'gstatic.com/recaptcha', 'recaptcha.net' ), // phpcs:ignore PluginCheck.CodeAnalysis.Offloading.OffloadedContent -- engelleme deseni, dosya yüklemez.
                'cookies'  => array(
                    array( '_GRECAPTCHA', __( '6 ay', 'kvkk-cerez' ), __( 'Gerçek kullanıcı ile otomatik yazılımı ayırt eder.', 'kvkk-cerez' ) ),
                ),
            ),
            'popup-maker'             => array(
                'name'     => 'Popup Maker',
                'category' => 'zorunlu',
                'party'    => 1,
                'purpose'  => __( 'Açılır pencerelerin gösterimini yönetir.', 'kvkk-cerez' ),
                'detect'   => 'Popup_Maker',
                'cookies'  => array(
                    array( 'pum-*', __( 'Ayarlanan süre', 'kvkk-cerez' ), __( 'Kapattığınız açılır pencerenin size tekrar gösterilmemesini sağlar.', 'kvkk-cerez' ) ),
                ),
            ),
            'elementor'               => array(
                'name'     => 'Elementor',
                'category' => 'islevsel',
                'party'    => 1,
                'purpose'  => __( 'Sayfa tasarım altyapısı.', 'kvkk-cerez' ),
                'detect'   => 'ELEMENTOR_VERSION',
                'cookies'  => array(
                    array( 'elementor', __( 'Kalıcı (yerel depolama)', 'kvkk-cerez' ), __( 'Açılır pencerelerin gösterim koşulları için ziyaret sayısı gibi bilgileri tarayıcınızda tutar.', 'kvkk-cerez' ) ),
                ),
            ),
            'joinchat'                => array(
                'name'     => __( 'Joinchat (WhatsApp)', 'kvkk-cerez' ),
                'category' => 'islevsel',
                'party'    => 1,
                'purpose'  => __( 'WhatsApp üzerinden iletişim butonu.', 'kvkk-cerez' ),
                'detect'   => 'JOINCHAT_VERSION',
                'cookies'  => array(
                    array( 'joinchat_*', __( 'Kalıcı (yerel depolama)', 'kvkk-cerez' ), __( 'Sohbet penceresini görüntüleme ve kapatma tercihinizi hatırlar.', 'kvkk-cerez' ) ),
                ),
            ),
            'pixelyoursite'           => array(
                'name'     => 'PixelYourSite',
                'category' => 'pazarlama',
                'party'    => 1,
                'purpose'  => __( 'Reklam ölçüm etiketlerini (Meta, Google Ads) siteye ekler ve ziyaretin kaynağını kaydeder.', 'kvkk-cerez' ),
                'detect'   => 'PYS_FREE_VERSION',
                'cookies'  => array(
                    array( 'pys_*', __( 'Oturum ile 7 gün arası', 'kvkk-cerez' ), __( 'Reklam ölçümü için oturum ve ilk ziyaret bilgisini tutar.', 'kvkk-cerez' ) ),
                    array( 'pysTrafficSource', __( '7 gün', 'kvkk-cerez' ), __( 'Ziyaretin hangi kaynaktan geldiğini tutar.', 'kvkk-cerez' ) ),
                    array( 'last_pys*', __( '7 gün', 'kvkk-cerez' ), __( 'Son ziyaretin kaynağını ve giriş sayfasını tutar.', 'kvkk-cerez' ) ),
                ),
            ),
            'google-tag-manager'      => array(
                'name'     => 'Google Tag Manager',
                'provider' => $google,
                'country'  => __( 'ABD', 'kvkk-cerez' ),
                'category' => 'analitik',
                'party'    => 3,
                'purpose'  => __( 'Sitedeki ölçüm ve reklam etiketlerini tek merkezden yükler. Kendisi çerez bırakmaz; yüklediği etiketler kendi hizmetleri altında listelenir.', 'kvkk-cerez' ),
                'detect'   => 'GTM4WP_VERSION',
                'markers'  => array( 'googletagmanager.com/gtm.js', 'googletagmanager.com/ns.html' ),
                'inline'   => array( 'googletagmanager.com/gtm.js' ),
            ),
            'google-analytics'        => array(
                'name'     => 'Google Analytics',
                'provider' => $google,
                'country'  => __( 'ABD', 'kvkk-cerez' ),
                'category' => 'analitik',
                'party'    => 3,
                'purpose'  => __( 'Ziyaretçi sayısı, ziyaret edilen sayfalar ve ziyaret süresi gibi istatistikleri ölçer.', 'kvkk-cerez' ),
                'markers'  => array( 'googletagmanager.com/gtag/js', 'google-analytics.com', 'analytics.google.com' ),
                'inline'   => array( 'google-analytics.com/analytics.js' ),
                'cookies'  => array(
                    array( '_ga', __( '2 yıl', 'kvkk-cerez' ), __( 'Ziyaretçileri birbirinden ayırt eder.', 'kvkk-cerez' ) ),
                    array( '_ga_*', __( '2 yıl', 'kvkk-cerez' ), __( 'Oturum durumunu ve ziyaret sayısını tutar.', 'kvkk-cerez' ) ),
                    array( '_gid', __( '24 saat', 'kvkk-cerez' ), __( 'Ziyaretçileri gün içinde ayırt eder.', 'kvkk-cerez' ) ),
                    array( '_gat*', __( '1 dakika', 'kvkk-cerez' ), __( 'İstek sıklığını sınırlar.', 'kvkk-cerez' ) ),
                ),
            ),
            'google-ads'              => array(
                'name'     => 'Google Ads',
                'provider' => $google,
                'country'  => __( 'ABD', 'kvkk-cerez' ),
                'category' => 'pazarlama',
                'party'    => 3,
                'purpose'  => __( 'Reklam tıklamalarının siteye dönüşünü ölçer ve ilgi alanına dayalı reklam gösterimini sağlar.', 'kvkk-cerez' ),
                'markers'  => array( 'googletagmanager.com/gtag/js?id=AW-', 'googletagmanager.com/gtag/destination?id=AW-', 'googleadservices.com', 'googleads.g.doubleclick.net', 'doubleclick.net', 'googlesyndication.com', 'google.com/pagead' ),
                'cookies'  => array(
                    array( '_gcl_au', __( '90 gün', 'kvkk-cerez' ), __( 'Reklam tıklamalarını dönüşümlerle eşleştirir.', 'kvkk-cerez' ) ),
                    array( '_gcl_aw', __( '90 gün', 'kvkk-cerez' ), __( 'Reklam tıklama bilgisini saklar.', 'kvkk-cerez' ) ),
                    array( 'IDE', __( '13 ay', 'kvkk-cerez' ), __( 'İlgi alanına dayalı reklam gösterimi için kullanılır (doubleclick.net).', 'kvkk-cerez' ) ),
                    array( 'test_cookie', __( '15 dakika', 'kvkk-cerez' ), __( 'Tarayıcının çerezleri destekleyip desteklemediğini kontrol eder (doubleclick.net).', 'kvkk-cerez' ) ),
                ),
            ),
            'meta-pixel'              => array(
                'name'     => 'Meta Pixel',
                'provider' => $meta,
                'country'  => __( 'İrlanda, ABD', 'kvkk-cerez' ),
                'category' => 'pazarlama',
                'party'    => 3,
                'purpose'  => __( 'Facebook ve Instagram reklamlarının etkinliğini ölçer, ilgi alanına dayalı reklam gösterimini sağlar.', 'kvkk-cerez' ),
                'markers'  => array( 'connect.facebook.net', 'facebook.com/tr' ),
                'inline'   => array( 'fbevents.js' ),
                'scan'     => array( array( 'pysOptions', '"facebook":{' ) ),
                'cookies'  => array(
                    array( '_fbp', __( '90 gün', 'kvkk-cerez' ), __( 'Ziyaretçiyi reklam ölçümü için tanımlar.', 'kvkk-cerez' ) ),
                    array( '_fbc', __( '90 gün', 'kvkk-cerez' ), __( 'Reklam tıklama bilgisini saklar.', 'kvkk-cerez' ) ),
                    array( 'fr', __( '90 gün', 'kvkk-cerez' ), __( 'Reklam gösterimi ve ölçümü için kullanılır (facebook.com).', 'kvkk-cerez' ) ),
                ),
            ),
            'instagram'               => array(
                'name'     => __( 'Instagram gömülü içerikleri', 'kvkk-cerez' ),
                'provider' => $meta,
                'country'  => __( 'İrlanda, ABD', 'kvkk-cerez' ),
                'category' => 'pazarlama',
                'party'    => 3,
                'purpose'  => __( 'Sayfaya gömülü Instagram gönderilerini gösterir.', 'kvkk-cerez' ),
                'markers'  => array( 'instagram.com', 'cdninstagram.com' ),
                'cookies'  => array(
                    array( 'ig_did', __( '1 yıl', 'kvkk-cerez' ), __( 'Cihazınızı tanımlar (instagram.com).', 'kvkk-cerez' ) ),
                    array( 'mid', __( '1 yıl', 'kvkk-cerez' ), __( 'Oturum ve güvenlik için kullanılır (instagram.com).', 'kvkk-cerez' ) ),
                ),
            ),
            'youtube'                 => array(
                'name'     => 'YouTube',
                'provider' => $google,
                'country'  => __( 'ABD', 'kvkk-cerez' ),
                'category' => 'pazarlama',
                'party'    => 3,
                'purpose'  => __( 'Gömülü videoları oynatır; izleme tercihlerini ve istatistiklerini tutar, reklam kişiselleştirmesinde kullanılabilir.', 'kvkk-cerez' ),
                'markers'  => array( 'youtube.com', 'youtube-nocookie.com', 'youtu.be' ),
                'cookies'  => array(
                    array( 'YSC', __( 'Oturum', 'kvkk-cerez' ), __( 'İzlenen videoların istatistiği için oturumu tanımlar.', 'kvkk-cerez' ) ),
                    array( 'VISITOR_INFO1_LIVE', __( '6 ay', 'kvkk-cerez' ), __( 'Bağlantı hızınıza uygun video kalitesini ve tercihleri tutar.', 'kvkk-cerez' ) ),
                    array( 'VISITOR_PRIVACY_METADATA', __( '6 ay', 'kvkk-cerez' ), __( 'Çerez tercih durumunuzu YouTube için saklar.', 'kvkk-cerez' ) ),
                    array( 'yt-remote-*', __( 'Kalıcı (yerel depolama)', 'kvkk-cerez' ), __( 'Oynatıcı ayarlarını tarayıcınızda tutar.', 'kvkk-cerez' ) ),
                ),
            ),
            'vimeo'                   => array(
                'name'     => 'Vimeo',
                'provider' => 'Vimeo.com, Inc.',
                'country'  => __( 'ABD', 'kvkk-cerez' ),
                'category' => 'pazarlama',
                'party'    => 3,
                'purpose'  => __( 'Gömülü Vimeo videolarını oynatır ve izleme istatistiği tutar.', 'kvkk-cerez' ),
                'markers'  => array( 'player.vimeo.com', 'vimeo.com', 'vimeocdn.com' ),
                'cookies'  => array(
                    array( 'vuid', __( '2 yıl', 'kvkk-cerez' ), __( 'İzleyiciyi istatistik için tanımlar.', 'kvkk-cerez' ) ),
                ),
            ),
            'google-maps'             => array(
                'name'     => __( 'Google Haritalar', 'kvkk-cerez' ),
                'provider' => $google,
                'country'  => __( 'ABD', 'kvkk-cerez' ),
                'category' => 'islevsel',
                'party'    => 3,
                'purpose'  => __( 'Sayfaya gömülü haritaları gösterir.', 'kvkk-cerez' ),
                'markers'  => array( 'google.com/maps', 'maps.google.com', 'maps.googleapis.com', 'google.com.tr/maps' ),
                'cookies'  => array(
                    array( 'NID', __( '6 ay', 'kvkk-cerez' ), __( 'Harita tercihlerinizi hatırlar (google.com).', 'kvkk-cerez' ) ),
                ),
            ),
            'clarity'                 => array(
                'name'     => 'Microsoft Clarity',
                'provider' => $ms,
                'country'  => __( 'ABD', 'kvkk-cerez' ),
                'category' => 'analitik',
                'party'    => 3,
                'purpose'  => __( 'Tıklama ve kaydırma gibi etkileşimleri ölçerek ısı haritaları ve oturum kayıtları oluşturur.', 'kvkk-cerez' ),
                'markers'  => array( 'clarity.ms' ),
                'inline'   => array( 'clarity.ms/tag' ),
                'cookies'  => array(
                    array( '_clck', __( '1 yıl', 'kvkk-cerez' ), __( 'Ziyaretçiyi tanımlar.', 'kvkk-cerez' ) ),
                    array( '_clsk', __( '1 gün', 'kvkk-cerez' ), __( 'Sayfa görüntülemelerini tek oturumda birleştirir.', 'kvkk-cerez' ) ),
                    array( 'CLID', __( '1 yıl', 'kvkk-cerez' ), __( 'Ziyaretçiyi Clarity siteleri arasında tanımlar (clarity.ms).', 'kvkk-cerez' ) ),
                ),
            ),
            'bing-uet'                => array(
                'name'     => __( 'Microsoft Advertising (UET)', 'kvkk-cerez' ),
                'provider' => $ms,
                'country'  => __( 'ABD', 'kvkk-cerez' ),
                'category' => 'pazarlama',
                'party'    => 3,
                'purpose'  => __( 'Bing reklamlarının dönüşümlerini ölçer.', 'kvkk-cerez' ),
                'markers'  => array( 'bat.bing.com' ),
                'inline'   => array( 'bat.bing.com/bat.js' ),
                'cookies'  => array(
                    array( '_uetsid', __( '1 gün', 'kvkk-cerez' ), __( 'Oturumu reklam ölçümü için tanımlar.', 'kvkk-cerez' ) ),
                    array( '_uetvid', __( '13 ay', 'kvkk-cerez' ), __( 'Ziyaretçiyi reklam ölçümü için tanımlar.', 'kvkk-cerez' ) ),
                    array( 'MUID', __( '13 ay', 'kvkk-cerez' ), __( 'Microsoft hizmetlerinde tarayıcıyı tanımlar (bing.com).', 'kvkk-cerez' ) ),
                ),
            ),
            'hotjar'                  => array(
                'name'     => 'Hotjar',
                'provider' => 'Hotjar Ltd.',
                'country'  => __( 'Malta', 'kvkk-cerez' ),
                'category' => 'analitik',
                'party'    => 3,
                'purpose'  => __( 'Ziyaretçi davranışını ısı haritaları ve oturum kayıtlarıyla analiz eder.', 'kvkk-cerez' ),
                'markers'  => array( 'hotjar.com', 'hotjar.io' ),
                'inline'   => array( 'static.hotjar.com' ),
                'cookies'  => array(
                    array( '_hjSessionUser_*', __( '1 yıl', 'kvkk-cerez' ), __( 'Ziyaretçiyi tanımlar.', 'kvkk-cerez' ) ),
                    array( '_hjSession_*', __( '30 dakika', 'kvkk-cerez' ), __( 'Geçerli oturumun verisini tutar.', 'kvkk-cerez' ) ),
                ),
            ),
            'yandex-metrica'          => array(
                'name'     => 'Yandex Metrica',
                'provider' => 'YANDEX LLC',
                'country'  => __( 'Rusya', 'kvkk-cerez' ),
                'category' => 'analitik',
                'party'    => 3,
                'purpose'  => __( 'Ziyaretçi istatistiklerini ölçer; oturum kaydı (Webvisor) ile sayfa içi etkileşimleri analiz eder.', 'kvkk-cerez' ),
                'markers'  => array( 'mc.yandex.ru', 'mc.yandex.com', 'mc.yandex.com.tr', 'mc.webvisor.org' ),
                'inline'   => array( 'mc.yandex.ru/metrika', 'mc.yandex.com/metrika' ),
                'cookies'  => array(
                    array( '_ym_uid', __( '1 yıl', 'kvkk-cerez' ), __( 'Ziyaretçiyi tanımlar.', 'kvkk-cerez' ) ),
                    array( '_ym_d', __( '1 yıl', 'kvkk-cerez' ), __( 'İlk ziyaret tarihini tutar.', 'kvkk-cerez' ) ),
                    array( '_ym_isad', __( '2 gün', 'kvkk-cerez' ), __( 'Reklam engelleyici kullanılıp kullanılmadığını belirler.', 'kvkk-cerez' ) ),
                    array( '_ym_visorc', __( '30 dakika', 'kvkk-cerez' ), __( 'Oturum kaydının doğru çalışmasını sağlar.', 'kvkk-cerez' ) ),
                    array( 'yandexuid', __( '1 yıl', 'kvkk-cerez' ), __( 'Ziyaretçiyi Yandex hizmetlerinde tanımlar (yandex.ru).', 'kvkk-cerez' ) ),
                ),
            ),
            'tiktok-pixel'            => array(
                'name'     => 'TikTok Pixel',
                'provider' => 'TikTok Pte. Ltd.',
                'country'  => __( 'Singapur, ABD', 'kvkk-cerez' ),
                'category' => 'pazarlama',
                'party'    => 3,
                'purpose'  => __( 'TikTok reklamlarının etkinliğini ölçer.', 'kvkk-cerez' ),
                'markers'  => array( 'analytics.tiktok.com' ),
                'inline'   => array( 'analytics.tiktok.com/i18n/pixel' ),
                'cookies'  => array(
                    array( '_ttp', __( '13 ay', 'kvkk-cerez' ), __( 'Ziyaretçiyi reklam ölçümü için tanımlar.', 'kvkk-cerez' ) ),
                    array( '_tt_enable_cookie', __( '13 ay', 'kvkk-cerez' ), __( 'Çerez desteğini kontrol eder.', 'kvkk-cerez' ) ),
                ),
            ),
            'linkedin-insight'        => array(
                'name'     => 'LinkedIn Insight Tag',
                'provider' => 'LinkedIn Ireland Unlimited Company',
                'country'  => __( 'İrlanda, ABD', 'kvkk-cerez' ),
                'category' => 'pazarlama',
                'party'    => 3,
                'purpose'  => __( 'LinkedIn reklamlarının dönüşümlerini ölçer.', 'kvkk-cerez' ),
                'markers'  => array( 'snap.licdn.com', 'px.ads.linkedin.com', 'linkedin.com/px' ),
                'inline'   => array( 'snap.licdn.com/li.lms-analytics' ),
                'cookies'  => array(
                    array( 'bcookie', __( '1 yıl', 'kvkk-cerez' ), __( 'Tarayıcıyı tanımlar (linkedin.com).', 'kvkk-cerez' ) ),
                    array( 'lidc', __( '1 gün', 'kvkk-cerez' ), __( 'Yük dengeleme için kullanılır (linkedin.com).', 'kvkk-cerez' ) ),
                    array( 'li_gc', __( '6 ay', 'kvkk-cerez' ), __( 'Çerez tercihinizi LinkedIn için saklar.', 'kvkk-cerez' ) ),
                ),
            ),
            'twitter'                 => array(
                'name'     => 'X (Twitter)',
                'provider' => 'X Corp.',
                'country'  => __( 'ABD', 'kvkk-cerez' ),
                'category' => 'pazarlama',
                'party'    => 3,
                'purpose'  => __( 'Gömülü gönderileri gösterir ve X reklamlarını ölçer.', 'kvkk-cerez' ),
                'markers'  => array( 'platform.twitter.com', 'syndication.twitter.com', 'static.ads-twitter.com', 'analytics.twitter.com', 'platform.x.com' ),
                'inline'   => array( 'static.ads-twitter.com/uwt.js' ),
                'cookies'  => array(
                    array( 'personalization_id', __( '13 ay', 'kvkk-cerez' ), __( 'Reklam kişiselleştirmesi için kullanılır.', 'kvkk-cerez' ) ),
                    array( 'guest_id', __( '13 ay', 'kvkk-cerez' ), __( 'Ziyaretçiyi tanımlar.', 'kvkk-cerez' ) ),
                ),
            ),
            'hubspot'                 => array(
                'name'     => 'HubSpot',
                'provider' => 'HubSpot, Inc.',
                'country'  => __( 'ABD', 'kvkk-cerez' ),
                'category' => 'pazarlama',
                'party'    => 3,
                'purpose'  => __( 'Ziyaretçi takibi, formlar ve pazarlama otomasyonu.', 'kvkk-cerez' ),
                'markers'  => array( 'js.hs-scripts.com', 'js.hs-analytics.net', 'js.hsadspixel.net', 'js.hscollectedforms.net', 'js.usemessages.com', 'js.hs-banner.com' ),
                'cookies'  => array(
                    array( '__hstc', __( '6 ay', 'kvkk-cerez' ), __( 'Ziyaretçiyi tanımlar.', 'kvkk-cerez' ) ),
                    array( 'hubspotutk', __( '6 ay', 'kvkk-cerez' ), __( 'Form gönderimlerini ziyaretçiyle eşleştirir.', 'kvkk-cerez' ) ),
                    array( '__hssc', __( '30 dakika', 'kvkk-cerez' ), __( 'Oturumu tanımlar.', 'kvkk-cerez' ) ),
                    array( '__hssrc', __( 'Oturum', 'kvkk-cerez' ), __( 'Yeni oturum başlayıp başlamadığını belirler.', 'kvkk-cerez' ) ),
                ),
            ),
            'mailchimp'               => array(
                'name'     => 'Mailchimp',
                'provider' => 'Intuit Inc.',
                'country'  => __( 'ABD', 'kvkk-cerez' ),
                'category' => 'pazarlama',
                'party'    => 3,
                'purpose'  => __( 'E-posta bülteni formları ve ziyaretçi takibi.', 'kvkk-cerez' ),
                'markers'  => array( 'chimpstatic.com', 'list-manage.com' ),
            ),
        );

        foreach ( $list as $id => $service ) {
            $list[ $id ] = array_merge(
                array(
                    'provider' => '',
                    'country'  => '',
                    'markers'  => array(),
                    'inline'   => array(),
                    'scan'     => array(),
                    'cookies'  => array(),
                    'detect'   => '',
                    'header'   => '',
                ),
                $service
            );
        }

        return $list;
    }

    /**
     * Tek bir yerleşik hizmet.
     *
     * @param string $id Hizmet kimliği.
     * @return array|null
     */
    public static function get( $id ) {
        $all = self::all();
        return isset( $all[ $id ] ) ? $all[ $id ] : null;
    }

    /**
     * Yerleşik hizmeti envanter kaydı biçimine çevirir.
     *
     * @param string $id Hizmet kimliği.
     * @return array|null
     */
    public static function template( $id ) {
        $service = self::get( $id );
        if ( ! $service ) {
            return null;
        }

        $cookies = array();
        foreach ( $service['cookies'] as $cookie ) {
            $cookies[] = array(
                'name'     => $cookie[0],
                'duration' => $cookie[1],
                'purpose'  => $cookie[2],
            );
        }

        return array(
            'name'     => $service['name'],
            'provider' => $service['provider'],
            'country'  => $service['country'],
            'category' => $service['category'],
            'party'    => $service['party'],
            'purpose'  => $service['purpose'],
            'markers'  => $service['markers'],
            'cookies'  => $cookies,
        );
    }

    /**
     * Kurulumda envantere eklenen hizmetler.
     *
     * @return array
     */
    public static function default_inventory() {
        return array(
            'kvkk-cerez' => self::template( 'kvkk-cerez' ),
            'wordpress'  => self::template( 'wordpress' ),
        );
    }

    /**
     * Kayıtlı envanter.
     *
     * @return array
     */
    public static function inventory() {
        $inventory = get_option( 'kvkkc_inventory', array() );
        return is_array( $inventory ) ? $inventory : array();
    }

    /**
     * Sitede kurulu eklentilerden tanınan hizmetler (tarama sırasında kullanılır).
     *
     * @return string[]
     */
    public static function detect_installed() {
        $found = array();
        foreach ( self::all() as $id => $service ) {
            $probe = $service['detect'];
            if ( '' !== $probe && ( defined( $probe ) || class_exists( $probe, false ) || function_exists( $probe ) ) ) {
                $found[] = $id;
            }
        }
        return $found;
    }

    /**
     * "alanadi.com/yol" işaretini çözer.
     *
     * @param string $marker İşaret.
     * @return array|null array( alan adı, yol öneki ) ya da geçersizse null.
     */
    public static function parse_marker( $marker ) {
        $marker = trim( (string) $marker );
        $slash  = strpos( $marker, '/' );
        $domain = strtolower( false === $slash ? $marker : substr( $marker, 0, $slash ) );
        $path   = false === $slash ? '' : substr( $marker, $slash );

        if ( strlen( $marker ) > 200 || ! preg_match( '/^[a-z0-9-]+(\.[a-z0-9-]+)+$/', $domain ) || preg_match( '/\s/', $path ) ) {
            return null;
        }

        return array( $domain, $path );
    }

    /**
     * Elle girilen kurallar: her satır "desen|kategori". # ile başlayan satırlar yok sayılır.
     *
     * @param string $text Kural metni.
     * @return array array( array( desen, kategori ), ... )
     */
    public static function parse_rules( $text ) {
        $rules = array();

        foreach ( preg_split( '/\r\n|\r|\n/', (string) $text ) as $line ) {
            $line = trim( $line );
            if ( '' === $line || '#' === $line[0] || false === strpos( $line, '|' ) ) {
                continue;
            }

            list( $pattern, $category ) = array_map( 'trim', explode( '|', $line, 2 ) );
            $category                   = strtolower( $category );

            if ( '' !== $pattern && strlen( $pattern ) <= 200 && in_array( $category, kvkkc_categories(), true ) ) {
                $rules[] = array( $pattern, $category );
            }
        }

        return $rules;
    }

    /**
     * Engelleme kuralları: değerler kategoridir.
     *
     * Envanterdeki kategori ve işaretler yerleşik varsayılanları ezer; envanterde olmayan yerleşik
     * hizmetler de varsayılan kategorileriyle engellenir (tarama yapılmamış sitede de korur).
     * Zorunlu kurallar da listede kalır: daha uzun eşleşmeyle genel bir işareti gölgeleyebilirler.
     *
     * @param array|null  $inventory Envanter; verilmezse kayıtlı olan.
     * @param string|null $manual    Elle kurallar; verilmezse ayarlardaki.
     * @return array
     */
    public static function block_rules( $inventory = null, $manual = null ) {
        static $cache = null;

        $use_cache = null === $inventory && null === $manual;
        if ( $use_cache && null !== $cache ) {
            return $cache;
        }

        $inventory = null === $inventory ? self::inventory() : $inventory;
        $manual    = null === $manual ? (string) kvkkc_setting( 'rules' ) : $manual;
        $rules     = self::build_rules(
            $inventory,
            function ( $id, $category ) {
                return $category;
            }
        );

        $rules['manual'] = self::parse_rules( $manual );

        if ( $use_cache ) {
            $cache = $rules;
        }

        return $rules;
    }

    /**
     * Tarama kuralları: değerler hizmet kimliğidir.
     *
     * @param array $inventory Envanter.
     * @return array
     */
    public static function detect_rules( $inventory = array() ) {
        $rules = self::build_rules(
            $inventory,
            function ( $id ) {
                return $id;
            }
        );

        // Yalnızca taramada kullanılan imzalar: başka eklentilerin yapılandırma scriptleri
        // engellenmez ama içlerinden hangi hizmetin kurulu olduğu anlaşılır.
        $rules['scan'] = array();
        foreach ( self::all() as $id => $service ) {
            foreach ( $service['scan'] as $parts ) {
                $rules['scan'][] = array( $parts, $id );
            }
        }

        return $rules;
    }

    /**
     * Kural listesini kurar.
     *
     * @param array    $inventory Envanter.
     * @param callable $value     function( $id, $category ) eşleşmede dönecek değeri üretir.
     * @return array
     */
    private static function build_rules( $inventory, $value ) {
        $rules = array(
            'hosts'  => array(),
            'inline' => array(),
            'manual' => array(),
        );

        $all = self::all();

        foreach ( $all + $inventory as $id => $unused ) {
            $builtin  = isset( $all[ $id ] ) ? $all[ $id ] : null;
            $entry    = isset( $inventory[ $id ] ) && is_array( $inventory[ $id ] ) ? $inventory[ $id ] : array();
            $category = isset( $entry['category'] ) ? $entry['category'] : ( $builtin ? $builtin['category'] : '' );
            $markers  = isset( $entry['markers'] ) ? (array) $entry['markers'] : ( $builtin ? $builtin['markers'] : array() );

            if ( ! in_array( $category, kvkkc_categories(), true ) ) {
                continue;
            }

            foreach ( $markers as $marker ) {
                $parsed = self::parse_marker( $marker );
                if ( $parsed ) {
                    $rules['hosts'][] = array( $parsed[0], $parsed[1], $value( $id, $category ) );
                }
            }

            if ( $builtin ) {
                foreach ( $builtin['inline'] as $signature ) {
                    $rules['inline'][] = array( $signature, $value( $id, $category ) );
                }
            }
        }

        return $rules;
    }

    /**
     * Adresi kurallarla eşleştirir. Elle kurallar önceliklidir, ardından en uzun işaret kazanır.
     *
     * @param string $url   Adres (tam, protokolsüz veya göreli).
     * @param array  $rules block_rules() ya da detect_rules() çıktısı.
     * @return string Eşleşen değer veya boş string.
     */
    public static function match_url( $url, $rules ) {
        $url = trim( (string) $url );
        if ( '' === $url ) {
            return '';
        }

        foreach ( $rules['manual'] as $rule ) {
            if ( false !== stripos( $url, $rule[0] ) ) {
                return $rule[1];
            }
        }

        $parts = wp_parse_url( 0 === strpos( $url, '//' ) ? 'https:' . $url : $url );
        if ( empty( $parts['host'] ) ) {
            return '';
        }

        $host  = strtolower( $parts['host'] );
        $tail  = ( isset( $parts['path'] ) ? $parts['path'] : '/' ) . ( isset( $parts['query'] ) ? '?' . $parts['query'] : '' );
        $best  = '';
        $score = -1;

        foreach ( $rules['hosts'] as $rule ) {
            list( $domain, $path, $result ) = $rule;

            if ( ! self::host_matches( $host, $domain ) || ( '' !== $path && 0 !== strpos( $tail, $path ) ) ) {
                continue;
            }

            $length = strlen( $domain ) + strlen( $path );
            if ( $length > $score ) {
                $score = $length;
                $best  = $result;
            }
        }

        return $best;
    }

    /**
     * Yalnızca alan adına bakan eşleme (preconnect, dns-prefetch gibi yolsuz ipuçları için).
     * Alan adı zorunlu bir hizmetle paylaşılıyorsa (ör. gstatic.com) boş döner.
     *
     * @param string $url   Adres.
     * @param array  $rules block_rules() çıktısı.
     * @return string Kategori veya boş string.
     */
    public static function match_host( $url, $rules ) {
        $url  = trim( (string) $url );
        $host = strtolower( (string) wp_parse_url( 0 === strpos( $url, '//' ) ? 'https:' . $url : $url, PHP_URL_HOST ) );
        if ( '' === $host ) {
            return '';
        }

        $found = '';
        foreach ( $rules['hosts'] as $rule ) {
            if ( self::host_matches( $host, $rule[0] ) ) {
                if ( 'zorunlu' === $rule[2] ) {
                    return '';
                }
                $found = $rule[2];
            }
        }

        return $found;
    }

    /**
     * Alan adı aynı ya da alt alan adı mı? (evil-google.com, google.com ile eşleşmez.)
     *
     * @param string $host   Adresin alan adı.
     * @param string $domain Kuraldaki alan adı.
     * @return bool
     */
    private static function host_matches( $host, $domain ) {
        return $host === $domain || substr( $host, -strlen( $domain ) - 1 ) === '.' . $domain;
    }

    /**
     * Satır içi script içeriğini kurallarla eşleştirir.
     *
     * @param string $text  Script içeriği.
     * @param array  $rules Kurallar.
     * @return string Eşleşen değer veya boş string.
     */
    public static function match_inline( $text, $rules ) {
        $text = (string) $text;
        if ( '' === trim( $text ) ) {
            return '';
        }

        foreach ( $rules['manual'] as $rule ) {
            if ( false !== stripos( $text, $rule[0] ) ) {
                return $rule[1];
            }
        }

        $best  = '';
        $score = -1;

        foreach ( $rules['inline'] as $rule ) {
            if ( strlen( $rule[0] ) > $score && false !== stripos( $text, $rule[0] ) ) {
                $score = strlen( $rule[0] );
                $best  = $rule[1];
            }
        }

        return $best;
    }

    /**
     * Çerez adının ait olduğu hizmet. Önce envanter, sonra yerleşik veritabanı; tam ad
     * eşleşmesi önek eşleşmesinden önce gelir.
     *
     * @param string $name      Çerez adı.
     * @param array  $inventory Envanter.
     * @return string Hizmet kimliği veya boş string.
     */
    public static function cookie_service( $name, $inventory = array() ) {
        $sources = array();
        foreach ( $inventory as $id => $entry ) {
            foreach ( isset( $entry['cookies'] ) ? (array) $entry['cookies'] : array() as $cookie ) {
                if ( isset( $cookie['name'] ) ) {
                    $sources[] = array( $cookie['name'], $id );
                }
            }
        }
        foreach ( self::all() as $id => $service ) {
            foreach ( $service['cookies'] as $cookie ) {
                $sources[] = array( $cookie[0], $id );
            }
        }

        $best  = '';
        $score = -1;
        foreach ( $sources as $source ) {
            list( $pattern, $id ) = $source;

            if ( $pattern === $name ) {
                return $id;
            }

            $prefix = rtrim( $pattern, '*' );
            if ( $prefix !== $pattern && '' !== $prefix && 0 === strpos( $name, $prefix ) && strlen( $prefix ) > $score ) {
                $score = strlen( $prefix );
                $best  = $id;
            }
        }

        return $best;
    }

    /**
     * Envanteri temizler (yönetim panelinden gelen veri).
     *
     * @param mixed $raw Ham envanter.
     * @return array
     */
    public static function sanitize_inventory( $raw ) {
        $clean = array();

        foreach ( (array) $raw as $id => $entry ) {
            $id = sanitize_key( $id );
            if ( '' === $id || ! is_array( $entry ) ) {
                continue;
            }

            $name = self::text( isset( $entry['name'] ) ? $entry['name'] : '', 100 );
            if ( '' === $name ) {
                continue;
            }

            $builtin  = self::get( $id );
            $category = isset( $entry['category'] ) && in_array( $entry['category'], kvkkc_categories(), true )
                ? $entry['category']
                : ( $builtin ? $builtin['category'] : 'islevsel' );
            $party    = isset( $entry['party'] ) && in_array( (int) $entry['party'], array( 1, 3 ), true )
                ? (int) $entry['party']
                : ( $builtin ? $builtin['party'] : 3 );

            $markers = isset( $entry['markers'] ) ? $entry['markers'] : array();
            $markers = is_array( $markers ) ? $markers : preg_split( '/[\r\n]+/', (string) $markers );
            $valid   = array();
            foreach ( $markers as $marker ) {
                $parsed = self::parse_marker( $marker );
                if ( $parsed ) {
                    $valid[] = $parsed[0] . $parsed[1];
                }
            }

            $cookies = array();
            foreach ( isset( $entry['cookies'] ) && is_array( $entry['cookies'] ) ? $entry['cookies'] : array() as $cookie ) {
                if ( ! is_array( $cookie ) ) {
                    continue;
                }
                $cookie_name = preg_replace( '/[^A-Za-z0-9_\-\.\*:@%]/', '', isset( $cookie['name'] ) ? (string) $cookie['name'] : '' );
                if ( '' === $cookie_name ) {
                    continue;
                }
                $cookies[] = array(
                    'name'     => substr( $cookie_name, 0, 100 ),
                    'duration' => self::text( isset( $cookie['duration'] ) ? $cookie['duration'] : '', 60 ),
                    'purpose'  => self::text( isset( $cookie['purpose'] ) ? $cookie['purpose'] : '', 300 ),
                );
            }

            $clean[ $id ] = array(
                'name'     => $name,
                'provider' => self::text( isset( $entry['provider'] ) ? $entry['provider'] : '', 100 ),
                'country'  => self::text( isset( $entry['country'] ) ? $entry['country'] : '', 60 ),
                'category' => $category,
                'party'    => $party,
                'purpose'  => self::text( isset( $entry['purpose'] ) ? $entry['purpose'] : '', 500 ),
                'markers'  => array_slice( array_values( array_unique( $valid ) ), 0, 30 ),
                'cookies'  => array_slice( $cookies, 0, 60 ),
            );
        }

        return $clean;
    }

    /**
     * Tek satır metin temizliği ve uzunluk sınırı.
     *
     * @param mixed $value Değer.
     * @param int   $max   Azami uzunluk.
     * @return string
     */
    private static function text( $value, $max ) {
        $value = sanitize_text_field( is_scalar( $value ) ? (string) $value : '' );
        return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, $max ) : substr( $value, 0, $max );
    }
}
