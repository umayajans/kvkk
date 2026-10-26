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
                'name'     => __( 'Çerez tercihleri', 'umay-cerez-yoneticisi' ),
                'category' => 'zorunlu',
                'party'    => 1,
                'purpose'  => __( 'Çerez tercihinizi saklar; böylece her sayfada yeniden sorulmaz.', 'umay-cerez-yoneticisi' ),
                'detect'   => 'KVKKC_VERSION',
                'cookies'  => array(
                    array( 'kvkkc_consent', __( '{gun} gün', 'umay-cerez-yoneticisi' ), __( 'Çerez tercihinizi, tercih tarihini ve rıza numaranızı saklar.', 'umay-cerez-yoneticisi' ) ),
                ),
            ),
            'wordpress'               => array(
                'name'     => 'WordPress',
                'category' => 'zorunlu',
                'party'    => 1,
                'purpose'  => __( 'Sitenin altyapısı. Giriş yapan kullanıcıların oturumunu ve yorum formu tercihlerini yönetir.', 'umay-cerez-yoneticisi' ),
                'detect'   => 'ABSPATH',
                'cookies'  => array(
                    array( 'wordpress_logged_in_*', __( 'Oturum veya 14 gün', 'umay-cerez-yoneticisi' ), __( 'Giriş yapmış kullanıcının oturumunu sürdürür.', 'umay-cerez-yoneticisi' ) ),
                    array( 'wordpress_sec_*', __( 'Oturum veya 14 gün', 'umay-cerez-yoneticisi' ), __( 'Giriş yapmış kullanıcının kimlik doğrulama bilgisini güvenli biçimde tutar.', 'umay-cerez-yoneticisi' ) ),
                    array( 'wordpress_test_cookie', __( 'Oturum', 'umay-cerez-yoneticisi' ), __( 'Tarayıcınızın çerezleri kabul edip etmediğini kontrol eder.', 'umay-cerez-yoneticisi' ) ),
                    array( 'wp-settings-*', __( '1 yıl', 'umay-cerez-yoneticisi' ), __( 'Yönetim paneli görünüm tercihlerini saklar.', 'umay-cerez-yoneticisi' ) ),
                    array( 'comment_author_*', __( '347 gün', 'umay-cerez-yoneticisi' ), __( 'Yorum formunda adınızı ve e-postanızı hatırlar (yalnızca siz işaretlerseniz).', 'umay-cerez-yoneticisi' ) ),
                ),
            ),
            'php-oturum'              => array(
                'name'     => __( 'Sunucu oturumu', 'umay-cerez-yoneticisi' ),
                'category' => 'zorunlu',
                'party'    => 1,
                'purpose'  => __( 'Sitedeki bazı eklentilerin sunucu tarafında oturum bilgisi tutmasını sağlar.', 'umay-cerez-yoneticisi' ),
                'cookies'  => array(
                    array( 'PHPSESSID', __( 'Oturum', 'umay-cerez-yoneticisi' ), __( 'Sunucu tarafındaki oturumu tanımlar; tarayıcı kapatılınca silinir.', 'umay-cerez-yoneticisi' ) ),
                ),
            ),
            'woocommerce'             => array(
                'name'     => 'WooCommerce',
                'category' => 'zorunlu',
                'party'    => 1,
                'purpose'  => __( 'Alışveriş sepetinin ve sipariş sürecinin çalışmasını sağlar.', 'umay-cerez-yoneticisi' ),
                'detect'   => 'WC_VERSION',
                'cookies'  => array(
                    array( 'woocommerce_cart_hash', __( 'Oturum', 'umay-cerez-yoneticisi' ), __( 'Sepet içeriğindeki değişiklikleri izler.', 'umay-cerez-yoneticisi' ) ),
                    array( 'woocommerce_items_in_cart', __( 'Oturum', 'umay-cerez-yoneticisi' ), __( 'Sepette ürün olup olmadığını tutar.', 'umay-cerez-yoneticisi' ) ),
                    array( 'wp_woocommerce_session_*', __( '2 gün', 'umay-cerez-yoneticisi' ), __( 'Sepetinizi oturumunuzla ilişkilendirir.', 'umay-cerez-yoneticisi' ) ),
                ),
            ),
            'woocommerce-attribution' => array(
                'name'     => __( 'WooCommerce sipariş kaynağı', 'umay-cerez-yoneticisi' ),
                'category' => 'pazarlama',
                'party'    => 1,
                'purpose'  => __( 'Siparişin hangi kanaldan (reklam, arama, sosyal medya) geldiğini kaydeder.', 'umay-cerez-yoneticisi' ),
                'detect'   => 'WC_VERSION',
                'cookies'  => array(
                    array( 'sbjs_*', __( 'Oturum ile 6 ay arası', 'umay-cerez-yoneticisi' ), __( 'Ziyaretin geldiği kaynağı ve kampanya bilgisini tutar.', 'umay-cerez-yoneticisi' ) ),
                ),
            ),
            'litespeed'               => array(
                'name'     => 'LiteSpeed Cache',
                'category' => 'zorunlu',
                'party'    => 1,
                'purpose'  => __( 'Sayfaların hızlı yüklenmesi için önbellek sisteminin size doğru sayfa sürümünü sunmasını sağlar.', 'umay-cerez-yoneticisi' ),
                'detect'   => 'LSCWP_V',
                'cookies'  => array(
                    array( '_lscache_vary', __( 'Oturum', 'umay-cerez-yoneticisi' ), __( 'Giriş yapmış ve yapmamış ziyaretçilere farklı önbellek sürümü sunar.', 'umay-cerez-yoneticisi' ) ),
                ),
            ),
            'wordfence'               => array(
                'name'     => 'Wordfence',
                'category' => 'zorunlu',
                'party'    => 1,
                'purpose'  => __( 'Siteyi saldırılara karşı koruyan güvenlik duvarı.', 'umay-cerez-yoneticisi' ),
                'detect'   => 'WORDFENCE_VERSION',
                'cookies'  => array(
                    array( 'wfwaf-authcookie-*', __( '12 saat', 'umay-cerez-yoneticisi' ), __( 'Giriş yapmış kullanıcıların güvenlik duvarı tarafından tanınmasını sağlar.', 'umay-cerez-yoneticisi' ) ),
                    array( 'wf_loginalerted_*', __( '1 yıl', 'umay-cerez-yoneticisi' ), __( 'Yeni bir cihazdan yönetici girişi yapıldığında uyarı gönderilmesine yarar.', 'umay-cerez-yoneticisi' ) ),
                ),
            ),
            'cloudflare'              => array(
                'name'     => 'Cloudflare',
                'provider' => 'Cloudflare, Inc.',
                'country'  => __( 'ABD', 'umay-cerez-yoneticisi' ),
                'category' => 'zorunlu',
                'party'    => 3,
                'purpose'  => __( 'Siteyi kötü amaçlı trafiğe karşı koruyan ve içeriği hızlı sunan ağ hizmeti.', 'umay-cerez-yoneticisi' ),
                'header'   => 'cf-ray',
                'cookies'  => array(
                    array( '__cf_bm', __( '30 dakika', 'umay-cerez-yoneticisi' ), __( 'Bot trafiğini ayırt ederek siteyi korur.', 'umay-cerez-yoneticisi' ) ),
                    array( 'cf_clearance', __( '1 yıl', 'umay-cerez-yoneticisi' ), __( 'Güvenlik doğrulamasını geçtiğinizi hatırlar.', 'umay-cerez-yoneticisi' ) ),
                    array( '_cfuvid', __( 'Oturum', 'umay-cerez-yoneticisi' ), __( 'Aynı IP adresini paylaşan kullanıcıları hız sınırlaması için ayırt eder.', 'umay-cerez-yoneticisi' ) ),
                ),
            ),
            'recaptcha'               => array(
                'name'     => 'Google reCAPTCHA',
                'provider' => $google,
                'country'  => __( 'ABD', 'umay-cerez-yoneticisi' ),
                'category' => 'zorunlu',
                'party'    => 3,
                'purpose'  => __( 'Formları otomatik saldırılara ve istenmeyen gönderimlere karşı korur.', 'umay-cerez-yoneticisi' ),
                'markers'  => array( 'google.com/recaptcha', 'gstatic.com/recaptcha', 'recaptcha.net' ), // phpcs:ignore PluginCheck.CodeAnalysis.Offloading.OffloadedContent -- engelleme deseni, dosya yüklemez.
                'cookies'  => array(
                    array( '_GRECAPTCHA', __( '6 ay', 'umay-cerez-yoneticisi' ), __( 'Gerçek kullanıcı ile otomatik yazılımı ayırt eder.', 'umay-cerez-yoneticisi' ) ),
                ),
            ),
            'popup-maker'             => array(
                'name'     => 'Popup Maker',
                'category' => 'zorunlu',
                'party'    => 1,
                'purpose'  => __( 'Açılır pencerelerin gösterimini yönetir.', 'umay-cerez-yoneticisi' ),
                'detect'   => 'Popup_Maker',
                'cookies'  => array(
                    array( 'pum-*', __( 'Ayarlanan süre', 'umay-cerez-yoneticisi' ), __( 'Kapattığınız açılır pencerenin size tekrar gösterilmemesini sağlar.', 'umay-cerez-yoneticisi' ) ),
                ),
            ),
            'elementor'               => array(
                'name'     => 'Elementor',
                'category' => 'islevsel',
                'party'    => 1,
                'purpose'  => __( 'Sayfa tasarım altyapısı.', 'umay-cerez-yoneticisi' ),
                'detect'   => 'ELEMENTOR_VERSION',
                'cookies'  => array(
                    array( 'elementor', __( 'Kalıcı (yerel depolama)', 'umay-cerez-yoneticisi' ), __( 'Açılır pencerelerin gösterim koşulları için ziyaret sayısı gibi bilgileri tarayıcınızda tutar.', 'umay-cerez-yoneticisi' ) ),
                ),
            ),
            'joinchat'                => array(
                'name'     => __( 'Joinchat (WhatsApp)', 'umay-cerez-yoneticisi' ),
                'category' => 'islevsel',
                'party'    => 1,
                'purpose'  => __( 'WhatsApp üzerinden iletişim butonu.', 'umay-cerez-yoneticisi' ),
                'detect'   => 'JOINCHAT_VERSION',
                'cookies'  => array(
                    array( 'joinchat_*', __( 'Kalıcı (yerel depolama)', 'umay-cerez-yoneticisi' ), __( 'Sohbet penceresini görüntüleme ve kapatma tercihinizi hatırlar.', 'umay-cerez-yoneticisi' ) ),
                ),
            ),
            'pixelyoursite'           => array(
                'name'     => 'PixelYourSite',
                'category' => 'pazarlama',
                'party'    => 1,
                'purpose'  => __( 'Reklam ölçüm etiketlerini (Meta, Google Ads) siteye ekler ve ziyaretin kaynağını kaydeder.', 'umay-cerez-yoneticisi' ),
                'detect'   => 'PYS_FREE_VERSION',
                'cookies'  => array(
                    array( 'pys_*', __( 'Oturum ile 7 gün arası', 'umay-cerez-yoneticisi' ), __( 'Reklam ölçümü için oturum ve ilk ziyaret bilgisini tutar.', 'umay-cerez-yoneticisi' ) ),
                    array( 'pysTrafficSource', __( '7 gün', 'umay-cerez-yoneticisi' ), __( 'Ziyaretin hangi kaynaktan geldiğini tutar.', 'umay-cerez-yoneticisi' ) ),
                    array( 'last_pys*', __( '7 gün', 'umay-cerez-yoneticisi' ), __( 'Son ziyaretin kaynağını ve giriş sayfasını tutar.', 'umay-cerez-yoneticisi' ) ),
                ),
            ),
            'google-tag-manager'      => array(
                'name'     => 'Google Tag Manager',
                'provider' => $google,
                'country'  => __( 'ABD', 'umay-cerez-yoneticisi' ),
                'category' => 'analitik',
                'party'    => 3,
                'purpose'  => __( 'Sitedeki ölçüm ve reklam etiketlerini tek merkezden yükler. Kendisi çerez bırakmaz; yüklediği etiketler kendi hizmetleri altında listelenir.', 'umay-cerez-yoneticisi' ),
                'detect'   => 'GTM4WP_VERSION',
                'markers'  => array( 'googletagmanager.com/gtm.js', 'googletagmanager.com/ns.html' ),
                'inline'   => array( 'googletagmanager.com/gtm.js' ),
            ),
            'google-analytics'        => array(
                'name'     => 'Google Analytics',
                'provider' => $google,
                'country'  => __( 'ABD', 'umay-cerez-yoneticisi' ),
                'category' => 'analitik',
                'party'    => 3,
                'purpose'  => __( 'Ziyaretçi sayısı, ziyaret edilen sayfalar ve ziyaret süresi gibi istatistikleri ölçer.', 'umay-cerez-yoneticisi' ),
                'markers'  => array( 'googletagmanager.com/gtag/js', 'google-analytics.com', 'analytics.google.com' ),
                'inline'   => array( 'google-analytics.com/analytics.js' ),
                'cookies'  => array(
                    array( '_ga', __( '2 yıl', 'umay-cerez-yoneticisi' ), __( 'Ziyaretçileri birbirinden ayırt eder.', 'umay-cerez-yoneticisi' ) ),
                    array( '_ga_*', __( '2 yıl', 'umay-cerez-yoneticisi' ), __( 'Oturum durumunu ve ziyaret sayısını tutar.', 'umay-cerez-yoneticisi' ) ),
                    array( '_gid', __( '24 saat', 'umay-cerez-yoneticisi' ), __( 'Ziyaretçileri gün içinde ayırt eder.', 'umay-cerez-yoneticisi' ) ),
                    array( '_gat*', __( '1 dakika', 'umay-cerez-yoneticisi' ), __( 'İstek sıklığını sınırlar.', 'umay-cerez-yoneticisi' ) ),
                ),
            ),
            'google-ads'              => array(
                'name'     => 'Google Ads',
                'provider' => $google,
                'country'  => __( 'ABD', 'umay-cerez-yoneticisi' ),
                'category' => 'pazarlama',
                'party'    => 3,
                'purpose'  => __( 'Reklam tıklamalarının siteye dönüşünü ölçer ve ilgi alanına dayalı reklam gösterimini sağlar.', 'umay-cerez-yoneticisi' ),
                'markers'  => array( 'googletagmanager.com/gtag/js?id=AW-', 'googletagmanager.com/gtag/destination?id=AW-', 'googleadservices.com', 'googleads.g.doubleclick.net', 'doubleclick.net', 'googlesyndication.com', 'google.com/pagead' ),
                'cookies'  => array(
                    array( '_gcl_au', __( '90 gün', 'umay-cerez-yoneticisi' ), __( 'Reklam tıklamalarını dönüşümlerle eşleştirir.', 'umay-cerez-yoneticisi' ) ),
                    array( '_gcl_aw', __( '90 gün', 'umay-cerez-yoneticisi' ), __( 'Reklam tıklama bilgisini saklar.', 'umay-cerez-yoneticisi' ) ),
                    array( 'IDE', __( '13 ay', 'umay-cerez-yoneticisi' ), __( 'İlgi alanına dayalı reklam gösterimi için kullanılır (doubleclick.net).', 'umay-cerez-yoneticisi' ) ),
                    array( 'test_cookie', __( '15 dakika', 'umay-cerez-yoneticisi' ), __( 'Tarayıcının çerezleri destekleyip desteklemediğini kontrol eder (doubleclick.net).', 'umay-cerez-yoneticisi' ) ),
                ),
            ),
            'meta-pixel'              => array(
                'name'     => 'Meta Pixel',
                'provider' => $meta,
                'country'  => __( 'İrlanda, ABD', 'umay-cerez-yoneticisi' ),
                'category' => 'pazarlama',
                'party'    => 3,
                'purpose'  => __( 'Facebook ve Instagram reklamlarının etkinliğini ölçer, ilgi alanına dayalı reklam gösterimini sağlar.', 'umay-cerez-yoneticisi' ),
                'markers'  => array( 'connect.facebook.net', 'facebook.com/tr' ),
                'inline'   => array( 'fbevents.js' ),
                'scan'     => array( array( 'pysOptions', '"facebook":{' ) ),
                'cookies'  => array(
                    array( '_fbp', __( '90 gün', 'umay-cerez-yoneticisi' ), __( 'Ziyaretçiyi reklam ölçümü için tanımlar.', 'umay-cerez-yoneticisi' ) ),
                    array( '_fbc', __( '90 gün', 'umay-cerez-yoneticisi' ), __( 'Reklam tıklama bilgisini saklar.', 'umay-cerez-yoneticisi' ) ),
                    array( 'fr', __( '90 gün', 'umay-cerez-yoneticisi' ), __( 'Reklam gösterimi ve ölçümü için kullanılır (facebook.com).', 'umay-cerez-yoneticisi' ) ),
                ),
            ),
            'instagram'               => array(
                'name'     => __( 'Instagram gömülü içerikleri', 'umay-cerez-yoneticisi' ),
                'provider' => $meta,
                'country'  => __( 'İrlanda, ABD', 'umay-cerez-yoneticisi' ),
                'category' => 'pazarlama',
                'party'    => 3,
                'purpose'  => __( 'Sayfaya gömülü Instagram gönderilerini gösterir.', 'umay-cerez-yoneticisi' ),
                'markers'  => array( 'instagram.com', 'cdninstagram.com' ),
                'cookies'  => array(
                    array( 'ig_did', __( '1 yıl', 'umay-cerez-yoneticisi' ), __( 'Cihazınızı tanımlar (instagram.com).', 'umay-cerez-yoneticisi' ) ),
                    array( 'mid', __( '1 yıl', 'umay-cerez-yoneticisi' ), __( 'Oturum ve güvenlik için kullanılır (instagram.com).', 'umay-cerez-yoneticisi' ) ),
                ),
            ),
            'youtube'                 => array(
                'name'     => 'YouTube',
                'provider' => $google,
                'country'  => __( 'ABD', 'umay-cerez-yoneticisi' ),
                'category' => 'pazarlama',
                'party'    => 3,
                'purpose'  => __( 'Gömülü videoları oynatır; izleme tercihlerini ve istatistiklerini tutar, reklam kişiselleştirmesinde kullanılabilir.', 'umay-cerez-yoneticisi' ),
                'markers'  => array( 'youtube.com', 'youtube-nocookie.com', 'youtu.be' ),
                'cookies'  => array(
                    array( 'YSC', __( 'Oturum', 'umay-cerez-yoneticisi' ), __( 'İzlenen videoların istatistiği için oturumu tanımlar.', 'umay-cerez-yoneticisi' ) ),
                    array( 'VISITOR_INFO1_LIVE', __( '6 ay', 'umay-cerez-yoneticisi' ), __( 'Bağlantı hızınıza uygun video kalitesini ve tercihleri tutar.', 'umay-cerez-yoneticisi' ) ),
                    array( 'VISITOR_PRIVACY_METADATA', __( '6 ay', 'umay-cerez-yoneticisi' ), __( 'Çerez tercih durumunuzu YouTube için saklar.', 'umay-cerez-yoneticisi' ) ),
                    array( 'yt-remote-*', __( 'Kalıcı (yerel depolama)', 'umay-cerez-yoneticisi' ), __( 'Oynatıcı ayarlarını tarayıcınızda tutar.', 'umay-cerez-yoneticisi' ) ),
                ),
            ),
            'vimeo'                   => array(
                'name'     => 'Vimeo',
                'provider' => 'Vimeo.com, Inc.',
                'country'  => __( 'ABD', 'umay-cerez-yoneticisi' ),
                'category' => 'pazarlama',
                'party'    => 3,
                'purpose'  => __( 'Gömülü Vimeo videolarını oynatır ve izleme istatistiği tutar.', 'umay-cerez-yoneticisi' ),
                'markers'  => array( 'player.vimeo.com', 'vimeo.com', 'vimeocdn.com' ),
                'cookies'  => array(
                    array( 'vuid', __( '2 yıl', 'umay-cerez-yoneticisi' ), __( 'İzleyiciyi istatistik için tanımlar.', 'umay-cerez-yoneticisi' ) ),
                ),
            ),
            'google-maps'             => array(
                'name'     => __( 'Google Haritalar', 'umay-cerez-yoneticisi' ),
                'provider' => $google,
                'country'  => __( 'ABD', 'umay-cerez-yoneticisi' ),
                'category' => 'islevsel',
                'party'    => 3,
                'purpose'  => __( 'Sayfaya gömülü haritaları gösterir.', 'umay-cerez-yoneticisi' ),
                'markers'  => array( 'google.com/maps', 'maps.google.com', 'maps.googleapis.com', 'google.com.tr/maps' ),
                'cookies'  => array(
                    array( 'NID', __( '6 ay', 'umay-cerez-yoneticisi' ), __( 'Harita tercihlerinizi hatırlar (google.com).', 'umay-cerez-yoneticisi' ) ),
                ),
            ),
            'clarity'                 => array(
                'name'     => 'Microsoft Clarity',
                'provider' => $ms,
                'country'  => __( 'ABD', 'umay-cerez-yoneticisi' ),
                'category' => 'analitik',
                'party'    => 3,
                'purpose'  => __( 'Tıklama ve kaydırma gibi etkileşimleri ölçerek ısı haritaları ve oturum kayıtları oluşturur.', 'umay-cerez-yoneticisi' ),
                'markers'  => array( 'clarity.ms' ),
                'inline'   => array( 'clarity.ms/tag' ),
                'cookies'  => array(
                    array( '_clck', __( '1 yıl', 'umay-cerez-yoneticisi' ), __( 'Ziyaretçiyi tanımlar.', 'umay-cerez-yoneticisi' ) ),
                    array( '_clsk', __( '1 gün', 'umay-cerez-yoneticisi' ), __( 'Sayfa görüntülemelerini tek oturumda birleştirir.', 'umay-cerez-yoneticisi' ) ),
                    array( 'CLID', __( '1 yıl', 'umay-cerez-yoneticisi' ), __( 'Ziyaretçiyi Clarity siteleri arasında tanımlar (clarity.ms).', 'umay-cerez-yoneticisi' ) ),
                ),
            ),
            'bing-uet'                => array(
                'name'     => __( 'Microsoft Advertising (UET)', 'umay-cerez-yoneticisi' ),
                'provider' => $ms,
                'country'  => __( 'ABD', 'umay-cerez-yoneticisi' ),
                'category' => 'pazarlama',
                'party'    => 3,
                'purpose'  => __( 'Bing reklamlarının dönüşümlerini ölçer.', 'umay-cerez-yoneticisi' ),
                'markers'  => array( 'bat.bing.com' ),
                'inline'   => array( 'bat.bing.com/bat.js' ),
                'cookies'  => array(
                    array( '_uetsid', __( '1 gün', 'umay-cerez-yoneticisi' ), __( 'Oturumu reklam ölçümü için tanımlar.', 'umay-cerez-yoneticisi' ) ),
                    array( '_uetvid', __( '13 ay', 'umay-cerez-yoneticisi' ), __( 'Ziyaretçiyi reklam ölçümü için tanımlar.', 'umay-cerez-yoneticisi' ) ),
                    array( 'MUID', __( '13 ay', 'umay-cerez-yoneticisi' ), __( 'Microsoft hizmetlerinde tarayıcıyı tanımlar (bing.com).', 'umay-cerez-yoneticisi' ) ),
                ),
            ),
            'hotjar'                  => array(
                'name'     => 'Hotjar',
                'provider' => 'Hotjar Ltd.',
                'country'  => __( 'Malta', 'umay-cerez-yoneticisi' ),
                'category' => 'analitik',
                'party'    => 3,
                'purpose'  => __( 'Ziyaretçi davranışını ısı haritaları ve oturum kayıtlarıyla analiz eder.', 'umay-cerez-yoneticisi' ),
                'markers'  => array( 'hotjar.com', 'hotjar.io' ),
                'inline'   => array( 'static.hotjar.com' ),
                'cookies'  => array(
                    array( '_hjSessionUser_*', __( '1 yıl', 'umay-cerez-yoneticisi' ), __( 'Ziyaretçiyi tanımlar.', 'umay-cerez-yoneticisi' ) ),
                    array( '_hjSession_*', __( '30 dakika', 'umay-cerez-yoneticisi' ), __( 'Geçerli oturumun verisini tutar.', 'umay-cerez-yoneticisi' ) ),
                ),
            ),
            'yandex-metrica'          => array(
                'name'     => 'Yandex Metrica',
                'provider' => 'YANDEX LLC',
                'country'  => __( 'Rusya', 'umay-cerez-yoneticisi' ),
                'category' => 'analitik',
                'party'    => 3,
                'purpose'  => __( 'Ziyaretçi istatistiklerini ölçer; oturum kaydı (Webvisor) ile sayfa içi etkileşimleri analiz eder.', 'umay-cerez-yoneticisi' ),
                'markers'  => array( 'mc.yandex.ru', 'mc.yandex.com', 'mc.yandex.com.tr', 'mc.webvisor.org' ),
                'inline'   => array( 'mc.yandex.ru/metrika', 'mc.yandex.com/metrika' ),
                'cookies'  => array(
                    array( '_ym_uid', __( '1 yıl', 'umay-cerez-yoneticisi' ), __( 'Ziyaretçiyi tanımlar.', 'umay-cerez-yoneticisi' ) ),
                    array( '_ym_d', __( '1 yıl', 'umay-cerez-yoneticisi' ), __( 'İlk ziyaret tarihini tutar.', 'umay-cerez-yoneticisi' ) ),
                    array( '_ym_isad', __( '2 gün', 'umay-cerez-yoneticisi' ), __( 'Reklam engelleyici kullanılıp kullanılmadığını belirler.', 'umay-cerez-yoneticisi' ) ),
                    array( '_ym_visorc', __( '30 dakika', 'umay-cerez-yoneticisi' ), __( 'Oturum kaydının doğru çalışmasını sağlar.', 'umay-cerez-yoneticisi' ) ),
                    array( 'yandexuid', __( '1 yıl', 'umay-cerez-yoneticisi' ), __( 'Ziyaretçiyi Yandex hizmetlerinde tanımlar (yandex.ru).', 'umay-cerez-yoneticisi' ) ),
                ),
            ),
            'tiktok-pixel'            => array(
                'name'     => 'TikTok Pixel',
                'provider' => 'TikTok Pte. Ltd.',
                'country'  => __( 'Singapur, ABD', 'umay-cerez-yoneticisi' ),
                'category' => 'pazarlama',
                'party'    => 3,
                'purpose'  => __( 'TikTok reklamlarının etkinliğini ölçer.', 'umay-cerez-yoneticisi' ),
                'markers'  => array( 'analytics.tiktok.com' ),
                'inline'   => array( 'analytics.tiktok.com/i18n/pixel' ),
                'cookies'  => array(
                    array( '_ttp', __( '13 ay', 'umay-cerez-yoneticisi' ), __( 'Ziyaretçiyi reklam ölçümü için tanımlar.', 'umay-cerez-yoneticisi' ) ),
                    array( '_tt_enable_cookie', __( '13 ay', 'umay-cerez-yoneticisi' ), __( 'Çerez desteğini kontrol eder.', 'umay-cerez-yoneticisi' ) ),
                ),
            ),
            'linkedin-insight'        => array(
                'name'     => 'LinkedIn Insight Tag',
                'provider' => 'LinkedIn Ireland Unlimited Company',
                'country'  => __( 'İrlanda, ABD', 'umay-cerez-yoneticisi' ),
                'category' => 'pazarlama',
                'party'    => 3,
                'purpose'  => __( 'LinkedIn reklamlarının dönüşümlerini ölçer.', 'umay-cerez-yoneticisi' ),
                'markers'  => array( 'snap.licdn.com', 'px.ads.linkedin.com', 'linkedin.com/px' ),
                'inline'   => array( 'snap.licdn.com/li.lms-analytics' ),
                'cookies'  => array(
                    array( 'bcookie', __( '1 yıl', 'umay-cerez-yoneticisi' ), __( 'Tarayıcıyı tanımlar (linkedin.com).', 'umay-cerez-yoneticisi' ) ),
                    array( 'lidc', __( '1 gün', 'umay-cerez-yoneticisi' ), __( 'Yük dengeleme için kullanılır (linkedin.com).', 'umay-cerez-yoneticisi' ) ),
                    array( 'li_gc', __( '6 ay', 'umay-cerez-yoneticisi' ), __( 'Çerez tercihinizi LinkedIn için saklar.', 'umay-cerez-yoneticisi' ) ),
                ),
            ),
            'twitter'                 => array(
                'name'     => 'X (Twitter)',
                'provider' => 'X Corp.',
                'country'  => __( 'ABD', 'umay-cerez-yoneticisi' ),
                'category' => 'pazarlama',
                'party'    => 3,
                'purpose'  => __( 'Gömülü gönderileri gösterir ve X reklamlarını ölçer.', 'umay-cerez-yoneticisi' ),
                'markers'  => array( 'platform.twitter.com', 'syndication.twitter.com', 'static.ads-twitter.com', 'analytics.twitter.com', 'platform.x.com' ),
                'inline'   => array( 'static.ads-twitter.com/uwt.js' ),
                'cookies'  => array(
                    array( 'personalization_id', __( '13 ay', 'umay-cerez-yoneticisi' ), __( 'Reklam kişiselleştirmesi için kullanılır.', 'umay-cerez-yoneticisi' ) ),
                    array( 'guest_id', __( '13 ay', 'umay-cerez-yoneticisi' ), __( 'Ziyaretçiyi tanımlar.', 'umay-cerez-yoneticisi' ) ),
                ),
            ),
            'hubspot'                 => array(
                'name'     => 'HubSpot',
                'provider' => 'HubSpot, Inc.',
                'country'  => __( 'ABD', 'umay-cerez-yoneticisi' ),
                'category' => 'pazarlama',
                'party'    => 3,
                'purpose'  => __( 'Ziyaretçi takibi, formlar ve pazarlama otomasyonu.', 'umay-cerez-yoneticisi' ),
                'markers'  => array( 'js.hs-scripts.com', 'js.hs-analytics.net', 'js.hsadspixel.net', 'js.hscollectedforms.net', 'js.usemessages.com', 'js.hs-banner.com' ),
                'cookies'  => array(
                    array( '__hstc', __( '6 ay', 'umay-cerez-yoneticisi' ), __( 'Ziyaretçiyi tanımlar.', 'umay-cerez-yoneticisi' ) ),
                    array( 'hubspotutk', __( '6 ay', 'umay-cerez-yoneticisi' ), __( 'Form gönderimlerini ziyaretçiyle eşleştirir.', 'umay-cerez-yoneticisi' ) ),
                    array( '__hssc', __( '30 dakika', 'umay-cerez-yoneticisi' ), __( 'Oturumu tanımlar.', 'umay-cerez-yoneticisi' ) ),
                    array( '__hssrc', __( 'Oturum', 'umay-cerez-yoneticisi' ), __( 'Yeni oturum başlayıp başlamadığını belirler.', 'umay-cerez-yoneticisi' ) ),
                ),
            ),
            'mailchimp'               => array(
                'name'     => 'Mailchimp',
                'provider' => 'Intuit Inc.',
                'country'  => __( 'ABD', 'umay-cerez-yoneticisi' ),
                'category' => 'pazarlama',
                'party'    => 3,
                'purpose'  => __( 'E-posta bülteni formları ve ziyaretçi takibi.', 'umay-cerez-yoneticisi' ),
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
