# Umay Çerez Yöneticisi

Umay Ajans'ın WordPress müşterileri için geliştirdiği, KVKK Çerez Uygulamaları Rehberi'ne (Temmuz 2025) göre çalışan çerez rıza eklentisi. Ziyaretçiye çerezleri kategori bazında gösterir, seçimine göre izleme kodlarını açar veya kapalı tutar, verilen rızayı ispat için kaydeder ve sitedeki çerezleri dış servis kullanmadan tarar.

- Gereksinim: WordPress 6.7+, PHP 7.4+
- Dil: Türkçe (kaynak metinler Türkçe). WordPress.org dizin kuralı gereği `readme.txt` İngilizcedir; Türkçe readme translate.wordpress.org üzerinden çevrilir.
- Bağımlılık: yok (jQuery, React, derleme adımı yok)
- Ön ek: PHP, seçenek, tablo, REST, çerez, CSS ve JS adlarının tamamında `kvkkc` / `KVKKC`.

Müşteriye özel kurulum notları yalnızca yerelde, `docs/` klasöründe tutulur; bu klasör repoya ve dağıtım paketine girmez.

## Kurulum

1. Repoyu `wp-content/plugins/kvkk-cerez` klasörüne kopyalayın (klasör adı önemli değil, ana dosya `kvkk-cerez.php`).
2. Eklentiler ekranından **Umay Çerez Yöneticisi**'ni etkinleştirin. Kurulumda yaygın adlı yasal sayfalar (`/kvkk-aydinlatma-metni/`, `/cerez-politikasi/`, `/gizlilik-politikasi/`, `/acik-riza-metni/`) bulunursa otomatik bağlanır.
3. **Umay Çerez > Çerez Envanteri** ekranında **Taramayı başlat**'a basın, bulunan hizmetleri envantere ekleyin.
4. **Umay Çerez > Ayarlar** ekranında görünümü ve metinleri düzenleyin. Sağdaki **Rehber kontrol listesi** ayarları rehber maddeleriyle karşılaştırır; tüm maddeler yeşil olduğunda mühür basılır. Liste hukuki görüş değildir.
5. Çerez politikası sayfasına `[kvkk_cerez_tablosu]` kısa kodunu ekleyin.

## Nasıl çalışır

**Rıza öncesi engelleme.** Sayfa HTML'i sunucuda tamponlanır ve WordPress'in `WP_HTML_Tag_Processor` sınıfıyla taranır. Rıza gerektiren script'lerin tipi `text/plain` yapılır, iframe ve noscript piksellerinin adresi `data-kvkkc-src` özniteliğine taşınır, preconnect ipuçları kaldırılır. Çıktı her ziyaretçi için aynıdır; bu yüzden sayfa önbelleğiyle uyumludur. Ana sayfa (374 KB) yaklaşık 16 ms'de işlenir.

**PixelYourSite.** Piksellerin açık olup olmadığı sayfa HTML'ine her zaman "kapalı" olarak yazılır, gerçek durum PYS'nin GDPR AJAX isteğinde ziyaretçinin rıza çerezine göre verilir. Böylece önbelleğe giren bir sayfa başka bir ziyaretçinin iznini taşımaz. PYS Google etiketlerini kendisi yüklediğinde gönderdiği İzin Modu varsayılanı da "denied" olur; sunucu tarafı olaylar (Meta Conversions API) her istekte rıza çerezine göre kontrol edilir.

**Sonradan eklenen kodlar.** `<head>`'in en başına basılan küçük koruma scripti `document.createElement` üzerinden oluşturulan script ve iframe'lerin `src` atamasını yakalar. Elementor'un YouTube API'si, PixelYourSite'ın Meta yükleyicisi ve GTM içinden eklenen etiketler bu yolla izin gelene kadar yüklenmez.

**Google İzin Modu v2.** Sayfa başında tüm izinler `denied` bildirilir (`wait_for_update: 500`), kayıtlı tercih varsa hemen `update` gönderilir. Kategori eşlemesi:

| Kategori | Consent Mode | WP Consent API |
|---|---|---|
| Zorunlu | security_storage (her zaman granted) | functional |
| İşlevsel | functionality_storage, personalization_storage | preferences |
| Analitik | analytics_storage | statistics, statistics-anonymous |
| Pazarlama | ad_storage, ad_user_data, ad_personalization | marketing |

**Tarayıcı.** "Siteyi Tara" seçilen sayfaları sunucudan, anonim ziyaretçi gibi ve engellemeyi atlayan tek kullanımlık bir anahtarla ister. Script, iframe, piksel ve `data-*` özniteliklerindeki adresleri yerleşik hizmet veritabanıyla, `Set-Cookie` başlıklarını çerez listesiyle eşleştirir. Anahtarlı istekler `DONOTCACHEPAGE` ile işaretlenir, önbelleğe girmez.

**Yerleşik hizmet veritabanı.** Google Tag Manager, Google Analytics, Google Ads, Meta Pixel, YouTube, Vimeo, Google Haritalar, reCAPTCHA, Microsoft Clarity, Bing UET, Hotjar, Yandex Metrica, TikTok, LinkedIn, X, HubSpot, Mailchimp, Instagram ve sitedeki yaygın eklentiler (WordPress, WooCommerce, LiteSpeed, Wordfence, Elementor, Joinchat, Popup Maker, PixelYourSite, Cloudflare). Kaynak: `includes/class-services.php`.

## Rıza kayıtları

Her seçimde `wp-json/kvkkc/v1/consent` ucuna bir kayıt gider ve tablo `{önek}kvkkc_consents`'e yazılır.

| Saklanan | Saklanmayan |
|---|---|
| Sunucuda üretilen rıza numarası (UUID) | Ham IP adresi |
| Tarih (UTC), eylem, izin verilen kategoriler | Tarayıcı ve cihaz bilgisi |
| Politika sürümü | Sayfa adresindeki sorgu parametreleri |
| Sayfa yolu | Giriş yapmış kullanıcının kimliği |
| İsteğe bağlı: kırpılmış IP'nin (IPv4 /24, IPv6 /48) HMAC özeti | |

Ziyaretçi rıza numarasını tercihler penceresinin altında görür; yönetici Rıza Kayıtları ekranında bu numarayla arar ve CSV olarak dışa aktarır (CSV formül enjeksiyonuna karşı korunur). Saklama süresi varsayılan 24 ay, süresi dolan kayıtlar günlük cron ile silinir. Kayıtların yazıldığı tek nokta `kvkkc_store_consent()` fonksiyonudur.

## Kısa kodlar ve geliştirici notları

| Kullanım | Açıklama |
|---|---|
| `[kvkk_cerez_tablosu]` | Envanterdeki çerezleri Rehber EK-4 sütunlarıyla (ad, sağlayıcı, tip, amaç, süre) ve yurt dışındaki sağlayıcılarla listeler. `kategori="analitik"` tek kategori, `aktarim="hayir"` aktarım listesini gizler. |
| `[kvkk_cerez_ayarlari text="..."]` | Tercihler penceresini açan bağlantı. |
| Menüde `#kvkkc-ayarlar` | Bu adresli bir menü bağlantısı tercihler penceresini açar. |
| `document` üzerinde `kvkkc:consent` olayı | `detail.categories` izin verilen kategoriler. |
| `dataLayer` içinde `kvkkc_consent` olayı | `kvkkc_islevsel`, `kvkkc_analitik`, `kvkkc_pazarlama` değişkenleriyle. |
| `data-kvkkc-skip` özniteliği | Script'i engellemeden muaf tutar. |
| `.kvkkc` ve `.kvkkc-*` sınıfları | Görünüm ayarlardaki form alanlarıyla değişir; eklentide serbest CSS alanı yoktur (WordPress.org kuralı). Ötesi için temanın Ek CSS alanında seçicilere `.kvkkc` ile başlayın. |
| Ayarlar > Gelişmiş > Elle kurallar | `adres veya kod parçası|kategori` satırları; `zorunlu` yazılan kural kodu engellemeden çıkarır. |

## Geliştirme

```bash
php tests/run.php                                   # WordPress gerektirmeyen 277 kontrol
KVKKC_WP_DIR=/yol/wordpress php tests/run.php       # HTML API dosyaları başka bir kurulumdan
phpcs --standard=phpcs.xml.dist                     # WordPress kodlama standardı (girinti 4 boşluk)
```

## WordPress.org paketi

Dağıtım paketi yalnızca git'teki dosyalardan üretilir; `.gitattributes` içindeki export-ignore satırları `tests/`, `docs/`, `phpcs.xml.dist` ve gizli dosyaları dışarıda bırakır.

```bash
git archive --format=zip --prefix=umay-cerez-yoneticisi/ -o umay-cerez-yoneticisi-1.0.0.zip HEAD
```

Gönderimden önce paket açılır ve Plugin Check, WordPress.org'un gönderimde kullandığı parametrelerle çalıştırılır. Hata (ERROR) kalmamalıdır:

```bash
wp plugin check /yol/umay-cerez-yoneticisi --slug=umay-cerez-yoneticisi --categories=plugin_repo \
  --error-severity=7 --warning-severity=6 --include-low-severity-errors --exclude-checks=prefixing
```

Dizindeki slug ve text domain `umay-cerez-yoneticisi`'dir. İnceleme ekibi "KVKK" ile başlayan adları resmi kurumla bağlantı ima ettiği için kabul etmez; görünen ad değişse bile KVKK başa alınmamalıdır. Slug onaydan sonra değiştirilemez. Ana dosyanın adı (`kvkk-cerez.php`), kısa kodlar, yönetim sayfası adresleri ve `kvkkc` ön eki iç adlardır ve slug'dan bağımsızdır. Her sürümde `Version` (ana dosya), `KVKKC_VERSION` ve `Stable tag` (readme) birlikte artar.

Testler HTML API sınıflarını bir WordPress kurulumundan alır (varsayılan `../tdsite`). `tests/fixtures/` klasörüne koyulan gerçek sayfa HTML'leri varsa engelleyici süre ölçümüyle birlikte onlarla da çalışır; bu dosyalar müşteri içeriği taşıdığı için repoya girmez.

## Bilinen sınırlar

- Sonradan `innerHTML` ile eklenen satır içi script'ler engellenemez; dış adresli script ve iframe'ler engellenir.
- Tag Manager içindeki Google dışı etiketler GTM tarafında `kvkkc_consent` olayına bağlanmalıdır.
- Rıza verildikten sonra PixelYourSite gibi sunucu tarafında karar veren eklentiler bir sonraki sayfa yüklemesinde devreye girer.
- Tarayıcı, sunucunun kendi adresine erişebilmesini gerektirir (Araçlar > Site Sağlığı'ndaki "geri döngü isteği").
