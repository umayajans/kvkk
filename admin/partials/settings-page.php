<?php
/**
 * Ayarlar sayfası: sekmeli form, canlı önizleme ve rehber kontrol listesi
 *
 * @package KVKK_Cerez
 */

// Doğrudan erişimi engelle.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$kvkkc_fields   = KVKKC_Admin::fields();
$kvkkc_tabs     = KVKKC_Admin::tabs();
$kvkkc_settings = array();
foreach ( $kvkkc_fields as $kvkkc_key => $kvkkc_field ) {
    $kvkkc_settings[ $kvkkc_key ] = kvkkc_setting( $kvkkc_key );
}
$kvkkc_checks = KVKKC_Admin::checks( $kvkkc_settings );
$kvkkc_status = KVKKC_Integrations::status();

$kvkkc_intros = array(
    'gorunum'     => __( 'Bandın sayfadaki yeri, biçimi ve butonları. Değişiklikler sağdaki önizlemede anında görünür.', 'umay-cerez-yoneticisi' ),
    'renk'        => __( 'Varsayılan olarak sitenin yazı tipi kullanılır. Renkleri marka paletinize göre ayarlayın; kontrast yetersizse çizelge uyarır.', 'umay-cerez-yoneticisi' ),
    'metinler'    => __( 'Ziyaretçinin gördüğü tüm metinler. Boş bırakılan alanda varsayılan metin kullanılır.', 'umay-cerez-yoneticisi' ),
    'kategoriler' => __( 'Tercihler penceresindeki dört kategori. Hangi çerezin hangi kategoride olduğunu Çerez Envanteri sayfasından yönetin.', 'umay-cerez-yoneticisi' ),
    'sayfalar'    => __( 'Bantta ve tercihler penceresinde bağlantısı verilen yasal metinler.', 'umay-cerez-yoneticisi' ),
    'davranis'    => __( 'Tercihin süresi, iznin geri alınması ve sitedeki diğer eklentilerle iletişim.', 'umay-cerez-yoneticisi' ),
    'gelismis'    => __( 'Elle engelleme kuralları, rıza kayıtlarının saklanması ve özel CSS.', 'umay-cerez-yoneticisi' ),
);

$kvkkc_section_fields = array();
foreach ( $kvkkc_fields as $kvkkc_key => $kvkkc_field ) {
    $kvkkc_section_fields[ $kvkkc_field['section'] ][ $kvkkc_key ] = $kvkkc_field;
}

$kvkkc_pages = get_pages(
    array(
        'post_status' => 'publish',
        'sort_column' => 'post_title',
    )
);

$kvkkc_passed = count(
    array_filter(
        $kvkkc_checks,
        function ( $check ) {
            return 'ok' === $check['state'];
        }
    )
);
?>
<div class="wrap kvkkc-app">
    <h1 class="screen-reader-text"><?php esc_html_e( 'Umay Çerez Yöneticisi', 'umay-cerez-yoneticisi' ); ?></h1>
    <?php KVKKC_Admin::render_header( 'kvkk-cerez' ); ?>
    <?php settings_errors(); ?>

    <form action="options.php" method="post" id="kvkkc-settings-form" class="kvkkc-settings" novalidate>
        <?php settings_fields( 'kvkkc_settings_group' ); ?>

        <div class="kvkkc-layout">
            <div class="kvkkc-tabs" role="tablist" aria-orientation="vertical" aria-label="<?php esc_attr_e( 'Ayar bölümleri', 'umay-cerez-yoneticisi' ); ?>">
                <?php $kvkkc_first = true; ?>
                <?php foreach ( $kvkkc_tabs as $kvkkc_id => $kvkkc_tab ) : ?>
                    <button type="button" role="tab" class="kvkkc-tab" id="kvkkc-tab-<?php echo esc_attr( $kvkkc_id ); ?>" data-tab="<?php echo esc_attr( $kvkkc_id ); ?>" aria-controls="kvkkc-panel-<?php echo esc_attr( $kvkkc_id ); ?>" aria-selected="<?php echo $kvkkc_first ? 'true' : 'false'; ?>" tabindex="<?php echo $kvkkc_first ? '0' : '-1'; ?>">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?php echo wp_kses( $kvkkc_tab['icon'], kvkkc_svg_allowed_html() ); ?></svg>
                        <span><?php echo esc_html( $kvkkc_tab['label'] ); ?></span>
                    </button>
                    <?php $kvkkc_first = false; ?>
                <?php endforeach; ?>
            </div>

            <div class="kvkkc-panels">
                <?php $kvkkc_first = true; ?>
                <?php foreach ( $kvkkc_tabs as $kvkkc_id => $kvkkc_tab ) : ?>
                    <section class="kvkkc-panel" role="tabpanel" id="kvkkc-panel-<?php echo esc_attr( $kvkkc_id ); ?>" aria-labelledby="kvkkc-tab-<?php echo esc_attr( $kvkkc_id ); ?>" tabindex="0"<?php echo $kvkkc_first ? '' : ' hidden'; ?>>
                        <header class="kvkkc-panel-head">
                            <h2><?php echo esc_html( $kvkkc_tab['label'] ); ?></h2>
                            <p><?php echo esc_html( $kvkkc_intros[ $kvkkc_id ] ); ?></p>
                        </header>

                        <?php foreach ( $kvkkc_tab['sections'] as $kvkkc_section => $kvkkc_section_title ) : ?>
                            <?php if ( empty( $kvkkc_section_fields[ $kvkkc_section ] ) ) : ?>
                                <?php continue; ?>
                            <?php endif; ?>
                            <div class="kvkkc-card">
                                <h3 class="kvkkc-card-title"><?php echo esc_html( $kvkkc_section_title ); ?></h3>
                                <?php
                                foreach ( $kvkkc_section_fields[ $kvkkc_section ] as $kvkkc_key => $kvkkc_field ) {
                                    KVKKC_Admin::render_field( $kvkkc_key, $kvkkc_field, $kvkkc_settings[ $kvkkc_key ] );
                                }
                                ?>
                            </div>
                        <?php endforeach; ?>

                        <?php if ( 'sayfalar' === $kvkkc_id ) : ?>
                            <div class="kvkkc-card">
                                <h3 class="kvkkc-card-title"><?php esc_html_e( 'Kısa kodlar', 'umay-cerez-yoneticisi' ); ?></h3>
                                <dl class="kvkkc-codes">
                                    <div>
                                        <dt><button type="button" class="kvkkc-code" data-kvkkc-copy="[kvkk_cerez_tablosu]"><code>[kvkk_cerez_tablosu]</code></button></dt>
                                        <dd><?php esc_html_e( 'Çerez politikası sayfasına ekleyin. Envanterdeki çerezleri kategori, sağlayıcı, amaç ve süreyle listeler; yurt dışındaki sağlayıcıları da gösterir.', 'umay-cerez-yoneticisi' ); ?></dd>
                                    </div>
                                    <div>
                                        <dt><button type="button" class="kvkkc-code" data-kvkkc-copy="[kvkk_cerez_ayarlari]"><code>[kvkk_cerez_ayarlari]</code></button></dt>
                                        <dd><?php esc_html_e( 'Tercihler penceresini açan bağlantı. Alt bilgi veya iletişim sayfası için uygundur.', 'umay-cerez-yoneticisi' ); ?></dd>
                                    </div>
                                    <div>
                                        <dt><button type="button" class="kvkkc-code" data-kvkkc-copy="#kvkkc-ayarlar"><code>#kvkkc-ayarlar</code></button></dt>
                                        <dd><?php esc_html_e( 'Menüye bu adresle "Özel bağlantı" eklerseniz tıklandığında tercihler penceresi açılır.', 'umay-cerez-yoneticisi' ); ?></dd>
                                    </div>
                                </dl>
                            </div>
                        <?php endif; ?>

                        <?php if ( 'davranis' === $kvkkc_id && $kvkkc_status ) : ?>
                            <div class="kvkkc-card">
                                <h3 class="kvkkc-card-title"><?php esc_html_e( 'Sitedeki eklentiler', 'umay-cerez-yoneticisi' ); ?></h3>
                                <ul class="kvkkc-status">
                                    <?php foreach ( $kvkkc_status as $kvkkc_item ) : ?>
                                        <li class="is-<?php echo esc_attr( $kvkkc_item['state'] ); ?>">
                                            <strong><?php echo esc_html( $kvkkc_item['title'] ); ?></strong>
                                            <span><?php echo esc_html( $kvkkc_item['text'] ); ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </section>
                    <?php $kvkkc_first = false; ?>
                <?php endforeach; ?>

                <div class="kvkkc-savebar">
                    <span class="kvkkc-dirty" data-kvkkc-dirty hidden><?php esc_html_e( 'Kaydedilmemiş değişiklikler var', 'umay-cerez-yoneticisi' ); ?></span>
                    <?php submit_button( __( 'Değişiklikleri kaydet', 'umay-cerez-yoneticisi' ), 'primary kvkkc-save', 'submit', false ); ?>
                </div>
            </div>

            <aside class="kvkkc-side" aria-label="<?php esc_attr_e( 'Önizleme ve rehber kontrol listesi', 'umay-cerez-yoneticisi' ); ?>">
                <div class="kvkkc-preview">
                    <div class="kvkkc-preview-tools">
                        <div class="kvkkc-segment" role="group" aria-label="<?php esc_attr_e( 'Önizlenen katman', 'umay-cerez-yoneticisi' ); ?>">
                            <button type="button" data-kvkkc-layer="banner" aria-pressed="true"><?php esc_html_e( 'Bant', 'umay-cerez-yoneticisi' ); ?></button>
                            <button type="button" data-kvkkc-layer="prefs" aria-pressed="false"><?php esc_html_e( 'Tercihler', 'umay-cerez-yoneticisi' ); ?></button>
                            <button type="button" data-kvkkc-layer="float" aria-pressed="false"><?php esc_html_e( 'Simge', 'umay-cerez-yoneticisi' ); ?></button>
                        </div>
                        <div class="kvkkc-segment" role="group" aria-label="<?php esc_attr_e( 'Ekran genişliği', 'umay-cerez-yoneticisi' ); ?>">
                            <button type="button" data-kvkkc-device="desktop" aria-pressed="true" aria-label="<?php esc_attr_e( 'Masaüstü', 'umay-cerez-yoneticisi' ); ?>" title="<?php esc_attr_e( 'Masaüstü', 'umay-cerez-yoneticisi' ); ?>"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="4" width="18" height="12" rx="1.5"/><path d="M9 20h6M12 16v4"/></svg></button>
                            <button type="button" data-kvkkc-device="mobile" aria-pressed="false" aria-label="<?php esc_attr_e( 'Telefon', 'umay-cerez-yoneticisi' ); ?>" title="<?php esc_attr_e( 'Telefon', 'umay-cerez-yoneticisi' ); ?>"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="7" y="3" width="10" height="18" rx="2"/><path d="M11 18h2"/></svg></button>
                            <button type="button" data-kvkkc-expand aria-pressed="false" aria-label="<?php esc_attr_e( 'Önizlemeyi büyüt', 'umay-cerez-yoneticisi' ); ?>" title="<?php esc_attr_e( 'Önizlemeyi büyüt (Esc ile kapanır)', 'umay-cerez-yoneticisi' ); ?>"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M14 4h6v6M10 20H4v-6M20 4l-7 7M4 20l7-7"/></svg></button>
                        </div>
                    </div>
                    <div class="kvkkc-device" data-kvkkc-device-frame="desktop">
                        <div class="kvkkc-device-bar" aria-hidden="true">
                            <span></span><span></span><span></span>
                            <em><?php echo esc_html( wp_parse_url( home_url(), PHP_URL_HOST ) ); ?></em>
                        </div>
                        <div class="kvkkc-device-view" data-kvkkc-view>
                            <iframe id="kvkkc-preview-frame" title="<?php esc_attr_e( 'Çerez bandı önizlemesi', 'umay-cerez-yoneticisi' ); ?>" tabindex="-1"></iframe>
                        </div>
                    </div>
                </div>

                <div class="kvkkc-ledger<?php echo count( $kvkkc_checks ) === $kvkkc_passed ? ' is-sealed' : ''; ?>" data-kvkkc-ledger>
                    <div class="kvkkc-ledger-head">
                        <svg class="kvkkc-seal" viewBox="0 0 120 120" aria-hidden="true">
                            <defs><path id="kvkkc-seal-ring" d="M60 60m-43 0a43 43 0 1 1 86 0a43 43 0 1 1-86 0"/></defs>
                            <circle cx="60" cy="60" r="56" class="kvkkc-seal-outer"/>
                            <circle cx="60" cy="60" r="31" class="kvkkc-seal-inner"/>
                            <text class="kvkkc-seal-ring"><textPath href="#kvkkc-seal-ring" startOffset="0"><?php esc_html_e( 'KVKK ÇEREZ REHBERİ · KONTROL LİSTESİ · ', 'umay-cerez-yoneticisi' ); ?></textPath></text>
                            <text x="60" y="66" text-anchor="middle" class="kvkkc-seal-score" data-kvkkc-score><?php echo esc_html( $kvkkc_passed . '/' . count( $kvkkc_checks ) ); ?></text>
                        </svg>
                        <div>
                            <h2><?php esc_html_e( 'Rehber kontrol listesi', 'umay-cerez-yoneticisi' ); ?></h2>
                            <p><?php esc_html_e( 'Ayarlarınızın KVKK Çerez Uygulamaları Rehberi (Temmuz 2025) önerileriyle karşılaştırması. Sayfa numaraları rehbere aittir. Bu liste hukuki görüş değildir ve uyumu garanti etmez.', 'umay-cerez-yoneticisi' ); ?></p>
                        </div>
                    </div>
                    <ul class="kvkkc-checks" data-kvkkc-checks>
                        <?php foreach ( $kvkkc_checks as $kvkkc_check ) : ?>
                            <li class="is-<?php echo esc_attr( $kvkkc_check['state'] ); ?>">
                                <span class="kvkkc-check-text"><?php echo esc_html( $kvkkc_check['text'] ); ?></span>
                                <span class="kvkkc-check-ref"><?php echo esc_html( $kvkkc_check['ref'] ); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </aside>
        </div>
    </form>

    <datalist id="kvkkc-pages">
        <?php foreach ( $kvkkc_pages as $kvkkc_page ) : ?>
            <option value="<?php echo esc_url( get_permalink( $kvkkc_page ) ); ?>"><?php echo esc_html( $kvkkc_page->post_title ); ?></option>
        <?php endforeach; ?>
    </datalist>
</div>
