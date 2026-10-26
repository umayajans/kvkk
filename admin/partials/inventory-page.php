<?php
/**
 * Çerez envanteri: tarama, bulunanlar, hizmet ve çerez düzenleme
 *
 * @package KVKK_Cerez
 */

// Doğrudan erişimi engelle.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$kvkkc_inventory = KVKKC_Services::inventory();
$kvkkc_scan      = get_option( 'kvkkc_scan', array() );
$kvkkc_scan      = is_array( $kvkkc_scan ) ? $kvkkc_scan : array();
$kvkkc_abroad    = KVKKC_Frontend::abroad();
$kvkkc_notice    = isset( $_GET['kvkkc_notice'] ) ? sanitize_key( wp_unslash( $_GET['kvkkc_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- yalnızca bilgi mesajı.

$kvkkc_cat_names = array();
foreach ( kvkkc_categories() as $kvkkc_cat ) {
    $kvkkc_cat_names[ $kvkkc_cat ] = KVKKC_Frontend::category_name( $kvkkc_cat );
}

// Taramada bulunup envanterde olmayan hizmetler ve hiçbir hizmete bağlanamayan çerezler.
$kvkkc_new_services = array();
foreach ( isset( $kvkkc_scan['services'] ) ? (array) $kvkkc_scan['services'] : array() as $kvkkc_id ) {
    if ( ! isset( $kvkkc_inventory[ $kvkkc_id ] ) && KVKKC_Services::get( $kvkkc_id ) ) {
        $kvkkc_new_services[] = $kvkkc_id;
    }
}
$kvkkc_unknown = array();
foreach ( isset( $kvkkc_scan['cookies'] ) ? (array) $kvkkc_scan['cookies'] : array() as $kvkkc_name => $kvkkc_info ) {
    if ( '' === KVKKC_Services::cookie_service( (string) $kvkkc_name, $kvkkc_inventory ) ) {
        $kvkkc_unknown[ $kvkkc_name ] = $kvkkc_info;
    }
}
$kvkkc_hosts = isset( $kvkkc_scan['hosts'] ) ? (array) $kvkkc_scan['hosts'] : array();

$kvkkc_catalog = array();
foreach ( KVKKC_Services::all() as $kvkkc_id => $kvkkc_service ) {
    if ( ! isset( $kvkkc_inventory[ $kvkkc_id ] ) ) {
        $kvkkc_catalog[ $kvkkc_id ] = $kvkkc_service['name'];
    }
}
asort( $kvkkc_catalog );

$kvkkc_grouped = array();
foreach ( $kvkkc_inventory as $kvkkc_id => $kvkkc_entry ) {
    $kvkkc_grouped[ $kvkkc_entry['category'] ][ $kvkkc_id ] = $kvkkc_entry;
}
?>
<div class="wrap kvkkc-app">
    <h1 class="screen-reader-text"><?php esc_html_e( 'Çerez Envanteri', 'kvkk-cerez' ); ?></h1>
    <?php KVKKC_Admin::render_header( 'kvkk-cerez-envanter' ); ?>

    <?php if ( 'saved' === $kvkkc_notice ) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Envanter kaydedildi. Bant ve çerez politikası tablosu güncellendi.', 'kvkk-cerez' ); ?></p></div>
    <?php elseif ( 'added' === $kvkkc_notice ) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Seçilenler envantere eklendi. Açıklamaları ve kategorileri aşağıdan kontrol edin.', 'kvkk-cerez' ); ?></p></div>
    <?php endif; ?>

    <div class="kvkkc-columns">
        <section class="kvkkc-card kvkkc-scan" data-kvkkc-scan>
            <h2 class="kvkkc-card-title"><?php esc_html_e( 'Siteyi tara', 'kvkk-cerez' ); ?></h2>
            <p class="kvkkc-card-lead"><?php esc_html_e( 'Ana sayfa, yasal sayfalar ve son güncellenen içerikler ziyaretçi gözüyle açılır; izleme kodları, gömülü içerikler ve sunucunun bıraktığı çerezler listelenir. Dış bir servis kullanılmaz.', 'kvkk-cerez' ); ?></p>

            <div class="kvkkc-scan-row">
                <label for="kvkkc-scan-limit"><?php esc_html_e( 'Taranacak sayfa', 'kvkk-cerez' ); ?></label>
                <input type="number" id="kvkkc-scan-limit" class="small-text" value="20" min="1" max="60">
                <button type="button" class="button button-primary" data-kvkkc-scan-start><?php esc_html_e( 'Taramayı başlat', 'kvkk-cerez' ); ?></button>
            </div>

            <div class="kvkkc-progress" data-kvkkc-progress hidden>
                <div class="kvkkc-progress-track"><span data-kvkkc-progress-bar></span></div>
                <p class="kvkkc-progress-text" data-kvkkc-progress-text role="status" aria-live="polite"></p>
            </div>

            <p class="kvkkc-scan-meta">
                <?php if ( ! empty( $kvkkc_scan['time'] ) ) : ?>
                    <?php
                    printf(
                        /* translators: 1: tarih, 2: sayfa sayısı */
                        esc_html__( 'Son tarama: %1$s, %2$d sayfa.', 'kvkk-cerez' ),
                        esc_html( wp_date( 'd.m.Y H:i', (int) $kvkkc_scan['time'] ) ),
                        (int) $kvkkc_scan['pages']
                    );
                    ?>
                    <?php if ( time() - (int) $kvkkc_scan['time'] > 90 * DAY_IN_SECONDS ) : ?>
                        <strong class="kvkkc-badge is-warn"><?php esc_html_e( '90 günden eski, yeniden tarayın', 'kvkk-cerez' ); ?></strong>
                    <?php endif; ?>
                <?php else : ?>
                    <?php esc_html_e( 'Site henüz taranmadı.', 'kvkk-cerez' ); ?>
                <?php endif; ?>
            </p>

            <?php if ( ! empty( $kvkkc_scan['errors'] ) ) : ?>
                <details class="kvkkc-errors">
                    <summary>
                        <?php
                        /* translators: %d: hata sayısı */
                        printf( esc_html__( '%d sayfa taranamadı', 'kvkk-cerez' ), count( $kvkkc_scan['errors'] ) );
                        ?>
                    </summary>
                    <ul>
                        <?php foreach ( $kvkkc_scan['errors'] as $kvkkc_error ) : ?>
                            <li><code><?php echo esc_html( $kvkkc_error[0] ); ?></code> <?php echo esc_html( $kvkkc_error[1] ); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <p><?php esc_html_e( 'Sunucu kendi adresine erişemiyorsa (güvenlik duvarı, parola koruması) Araçlar > Site Sağlığı ekranındaki "geri döngü isteği" uyarısına bakın.', 'kvkk-cerez' ); ?></p>
                </details>
            <?php endif; ?>
        </section>

        <section class="kvkkc-card kvkkc-transfer">
            <h2 class="kvkkc-card-title"><?php esc_html_e( 'Yurt dışına aktarım', 'kvkk-cerez' ); ?></h2>
            <?php if ( $kvkkc_abroad ) : ?>
                <p class="kvkkc-card-lead"><?php esc_html_e( 'Bu sağlayıcılar ilgili kategoriye izin verildiğinde veri alır. Çerez politikası ve aydınlatma metninde ülke ve aktarım dayanağıyla birlikte belirtilmelidir.', 'kvkk-cerez' ); ?></p>
                <ul class="kvkkc-abroad">
                    <?php foreach ( $kvkkc_abroad as $kvkkc_provider => $kvkkc_item ) : ?>
                        <li><strong><?php echo esc_html( $kvkkc_provider ); ?></strong> <span class="kvkkc-badge"><?php echo esc_html( $kvkkc_item['country'] ); ?></span><br><span class="kvkkc-muted"><?php echo esc_html( implode( ', ', $kvkkc_item['services'] ) ); ?></span></li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p class="kvkkc-card-lead"><?php esc_html_e( 'Envanterde yurt dışında bulunan bir sağlayıcı yok.', 'kvkk-cerez' ); ?></p>
            <?php endif; ?>
            <p class="kvkkc-note"><?php esc_html_e( 'Sitenin sunucusu yurt dışındaysa (ör. Almanya) bu da KVKK m.9 kapsamında aktarımdır: barındırma firmasıyla standart sözleşme imzalanıp 5 iş günü içinde Kurum\'a bildirilmelidir. Rıza kayıtları bu eklentide en az veriyle tutulur: ham IP ve tarayıcı bilgisi saklanmaz.', 'kvkk-cerez' ); ?></p>
        </section>
    </div>

    <?php if ( $kvkkc_new_services || $kvkkc_unknown || $kvkkc_hosts ) : ?>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="kvkkc-card kvkkc-findings">
            <input type="hidden" name="action" value="kvkkc_add_services">
            <?php wp_nonce_field( 'kvkkc_add_services' ); ?>
            <h2 class="kvkkc-card-title"><?php esc_html_e( 'Taramada bulunanlar', 'kvkk-cerez' ); ?></h2>

            <?php if ( $kvkkc_new_services ) : ?>
                <h3 class="kvkkc-subtitle"><?php esc_html_e( 'Envanterde olmayan hizmetler', 'kvkk-cerez' ); ?></h3>
                <ul class="kvkkc-pick">
                    <?php foreach ( $kvkkc_new_services as $kvkkc_id ) : ?>
                        <?php $kvkkc_service = KVKKC_Services::get( $kvkkc_id ); ?>
                        <li>
                            <label>
                                <input type="checkbox" name="services[]" value="<?php echo esc_attr( $kvkkc_id ); ?>" checked>
                                <strong><?php echo esc_html( $kvkkc_service['name'] ); ?></strong>
                                <span class="kvkkc-chip kvkkc-chip--<?php echo esc_attr( $kvkkc_service['category'] ); ?>"><?php echo esc_html( $kvkkc_cat_names[ $kvkkc_service['category'] ] ); ?></span>
                                <span class="kvkkc-muted"><?php echo esc_html( $kvkkc_service['purpose'] ); ?></span>
                            </label>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if ( $kvkkc_unknown ) : ?>
                <h3 class="kvkkc-subtitle"><?php esc_html_e( 'Tanınmayan çerezler', 'kvkk-cerez' ); ?></h3>
                <p class="kvkkc-muted"><?php esc_html_e( 'Bu çerezler sitenizin kendi kodundan geliyor. Ne işe yaradığını biliyorsanız bir kategori seçin; açıklamayı ekledikten sonra envanterden düzenleyebilirsiniz.', 'kvkk-cerez' ); ?></p>
                <table class="kvkkc-table">
                    <thead>
                        <tr>
                            <th scope="col"><?php esc_html_e( 'Çerez', 'kvkk-cerez' ); ?></th>
                            <th scope="col"><?php esc_html_e( 'Süre', 'kvkk-cerez' ); ?></th>
                            <th scope="col"><?php esc_html_e( 'Kategori', 'kvkk-cerez' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $kvkkc_unknown as $kvkkc_name => $kvkkc_info ) : ?>
                            <tr>
                                <td><code><?php echo esc_html( $kvkkc_name ); ?></code></td>
                                <td>
                                    <?php
                                    $kvkkc_expires = isset( $kvkkc_info['expires'] ) ? (int) $kvkkc_info['expires'] : 0;
                                    echo esc_html( $kvkkc_expires > time() ? human_time_diff( time(), $kvkkc_expires ) : __( 'Oturum', 'kvkk-cerez' ) );
                                    ?>
                                </td>
                                <td>
                                    <select name="cookies[<?php echo esc_attr( $kvkkc_name ); ?>]">
                                        <option value=""><?php esc_html_e( 'Ekleme', 'kvkk-cerez' ); ?></option>
                                        <?php foreach ( $kvkkc_cat_names as $kvkkc_cat => $kvkkc_label ) : ?>
                                            <option value="<?php echo esc_attr( $kvkkc_cat ); ?>"><?php echo esc_html( $kvkkc_label ); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <?php if ( $kvkkc_hosts ) : ?>
                <h3 class="kvkkc-subtitle"><?php esc_html_e( 'Tanınmayan dış kaynaklar', 'kvkk-cerez' ); ?></h3>
                <p class="kvkkc-muted"><?php esc_html_e( 'Sayfalarda bu alan adlarından script veya gömülü içerik yükleniyor. İzleme amaçlıysa Ayarlar > Gelişmiş > Elle kurallar bölümüne "alanadi.com|kategori" satırı ekleyin.', 'kvkk-cerez' ); ?></p>
                <ul class="kvkkc-hosts">
                    <?php foreach ( $kvkkc_hosts as $kvkkc_host ) : ?>
                        <li><code><?php echo esc_html( $kvkkc_host ); ?></code></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if ( $kvkkc_new_services || $kvkkc_unknown ) : ?>
                <p class="kvkkc-actions-row"><button type="submit" class="button button-primary"><?php esc_html_e( 'Seçilenleri envantere ekle', 'kvkk-cerez' ); ?></button></p>
            <?php endif; ?>
        </form>
    <?php endif; ?>

    <div class="kvkkc-inv-head">
        <div>
            <h2><?php esc_html_e( 'Envanter', 'kvkk-cerez' ); ?></h2>
            <p class="kvkkc-muted"><?php esc_html_e( 'Banttaki tablolar, çerez politikası tablosu ve engelleme kuralları bu listeden üretilir. Bir hizmetin kategorisini değiştirmek, kodlarının hangi izinle çalışacağını da değiştirir.', 'kvkk-cerez' ); ?></p>
        </div>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="kvkkc-add">
            <input type="hidden" name="action" value="kvkkc_add_services">
            <?php wp_nonce_field( 'kvkkc_add_services' ); ?>
            <label class="screen-reader-text" for="kvkkc-add-service"><?php esc_html_e( 'Eklenecek hizmet', 'kvkk-cerez' ); ?></label>
            <select id="kvkkc-add-service" name="services[]">
                <option value="ozel"><?php esc_html_e( 'Özel hizmet (boş)', 'kvkk-cerez' ); ?></option>
                <?php foreach ( $kvkkc_catalog as $kvkkc_id => $kvkkc_label ) : ?>
                    <option value="<?php echo esc_attr( $kvkkc_id ); ?>"><?php echo esc_html( $kvkkc_label ); ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="button"><?php esc_html_e( 'Hizmet ekle', 'kvkk-cerez' ); ?></button>
        </form>
    </div>

    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="kvkkc-inventory" data-kvkkc-inventory>
        <input type="hidden" name="action" value="kvkkc_save_inventory">
        <?php wp_nonce_field( 'kvkkc_inventory' ); ?>

        <?php foreach ( kvkkc_categories() as $kvkkc_cat ) : ?>
            <section class="kvkkc-inv-cat">
                <h3>
                    <span class="kvkkc-dot kvkkc-dot--<?php echo esc_attr( $kvkkc_cat ); ?>" aria-hidden="true"></span>
                    <?php echo esc_html( $kvkkc_cat_names[ $kvkkc_cat ] ); ?>
                    <span class="kvkkc-muted"><?php echo esc_html( (string) ( isset( $kvkkc_grouped[ $kvkkc_cat ] ) ? count( $kvkkc_grouped[ $kvkkc_cat ] ) : 0 ) ); ?></span>
                </h3>

                <?php if ( empty( $kvkkc_grouped[ $kvkkc_cat ] ) ) : ?>
                    <p class="kvkkc-empty-row"><?php esc_html_e( 'Bu kategoride hizmet yok.', 'kvkk-cerez' ); ?></p>
                <?php else : ?>
                    <?php foreach ( $kvkkc_grouped[ $kvkkc_cat ] as $kvkkc_id => $kvkkc_entry ) : ?>
                        <?php $kvkkc_base = 'inventory[' . $kvkkc_id . ']'; ?>
                        <details class="kvkkc-svc" data-kvkkc-service>
                            <summary>
                                <span class="kvkkc-svc-name"><?php echo esc_html( $kvkkc_entry['name'] ); ?></span>
                                <span class="kvkkc-muted"><?php echo esc_html( $kvkkc_entry['provider'] ? $kvkkc_entry['provider'] : get_bloginfo( 'name' ) ); ?></span>
                                <?php if ( $kvkkc_entry['country'] ) : ?>
                                    <span class="kvkkc-badge"><?php echo esc_html( $kvkkc_entry['country'] ); ?></span>
                                <?php endif; ?>
                                <span class="kvkkc-svc-count">
                                    <?php
                                    /* translators: %d: çerez sayısı */
                                    printf( esc_html( _n( '%d çerez', '%d çerez', count( $kvkkc_entry['cookies'] ), 'kvkk-cerez' ) ), count( $kvkkc_entry['cookies'] ) );
                                    ?>
                                </span>
                            </summary>

                            <div class="kvkkc-svc-body">
                                <div class="kvkkc-svc-grid">
                                    <label><span><?php esc_html_e( 'Ad', 'kvkk-cerez' ); ?></span><input type="text" name="<?php echo esc_attr( $kvkkc_base . '[name]' ); ?>" value="<?php echo esc_attr( $kvkkc_entry['name'] ); ?>" required></label>
                                    <label><span><?php esc_html_e( 'Sağlayıcı', 'kvkk-cerez' ); ?></span><input type="text" name="<?php echo esc_attr( $kvkkc_base . '[provider]' ); ?>" value="<?php echo esc_attr( $kvkkc_entry['provider'] ); ?>" placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"></label>
                                    <label><span><?php esc_html_e( 'Ülke', 'kvkk-cerez' ); ?></span><input type="text" name="<?php echo esc_attr( $kvkkc_base . '[country]' ); ?>" value="<?php echo esc_attr( $kvkkc_entry['country'] ); ?>"></label>
                                    <label><span><?php esc_html_e( 'Kategori', 'kvkk-cerez' ); ?></span>
                                        <select name="<?php echo esc_attr( $kvkkc_base . '[category]' ); ?>">
                                            <?php foreach ( $kvkkc_cat_names as $kvkkc_key => $kvkkc_label ) : ?>
                                                <option value="<?php echo esc_attr( $kvkkc_key ); ?>" <?php selected( $kvkkc_entry['category'], $kvkkc_key ); ?>><?php echo esc_html( $kvkkc_label ); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                    <label><span><?php esc_html_e( 'Taraf', 'kvkk-cerez' ); ?></span>
                                        <select name="<?php echo esc_attr( $kvkkc_base . '[party]' ); ?>">
                                            <option value="1" <?php selected( (int) $kvkkc_entry['party'], 1 ); ?>><?php esc_html_e( 'Birinci taraf', 'kvkk-cerez' ); ?></option>
                                            <option value="3" <?php selected( (int) $kvkkc_entry['party'], 3 ); ?>><?php esc_html_e( 'Üçüncü taraf', 'kvkk-cerez' ); ?></option>
                                        </select>
                                    </label>
                                </div>

                                <label class="kvkkc-block"><span><?php esc_html_e( 'Amaç', 'kvkk-cerez' ); ?></span><textarea rows="2" name="<?php echo esc_attr( $kvkkc_base . '[purpose]' ); ?>"><?php echo esc_textarea( $kvkkc_entry['purpose'] ); ?></textarea></label>
                                <label class="kvkkc-block"><span><?php esc_html_e( 'Engellenecek adresler', 'kvkk-cerez' ); ?></span><textarea rows="2" class="kvkkc-mono" spellcheck="false" name="<?php echo esc_attr( $kvkkc_base . '[markers]' ); ?>" placeholder="ornek.com/script.js"><?php echo esc_textarea( implode( "\n", $kvkkc_entry['markers'] ) ); ?></textarea><small><?php esc_html_e( 'Her satıra bir alan adı ya da alan adı ve yol. Bu adreslerden yüklenen script ve iframe\'ler kategorinin iznine bağlanır.', 'kvkk-cerez' ); ?></small></label>

                                <table class="kvkkc-table kvkkc-cookie-rows">
                                    <thead>
                                        <tr>
                                            <th scope="col"><?php esc_html_e( 'Çerez adı', 'kvkk-cerez' ); ?></th>
                                            <th scope="col"><?php esc_html_e( 'Süre', 'kvkk-cerez' ); ?></th>
                                            <th scope="col"><?php esc_html_e( 'Amaç', 'kvkk-cerez' ); ?></th>
                                            <th scope="col"><span class="screen-reader-text"><?php esc_html_e( 'İşlem', 'kvkk-cerez' ); ?></span></th>
                                        </tr>
                                    </thead>
                                    <tbody data-kvkkc-rows="<?php echo esc_attr( $kvkkc_base ); ?>">
                                        <?php foreach ( $kvkkc_entry['cookies'] as $kvkkc_index => $kvkkc_cookie ) : ?>
                                            <?php $kvkkc_row = $kvkkc_base . '[cookies][' . $kvkkc_index . ']'; ?>
                                            <tr>
                                                <td><input type="text" class="kvkkc-mono" aria-label="<?php esc_attr_e( 'Çerez adı', 'kvkk-cerez' ); ?>" name="<?php echo esc_attr( $kvkkc_row . '[name]' ); ?>" value="<?php echo esc_attr( $kvkkc_cookie['name'] ); ?>"></td>
                                                <td><input type="text" aria-label="<?php esc_attr_e( 'Süre', 'kvkk-cerez' ); ?>" name="<?php echo esc_attr( $kvkkc_row . '[duration]' ); ?>" value="<?php echo esc_attr( $kvkkc_cookie['duration'] ); ?>"></td>
                                                <td><input type="text" aria-label="<?php esc_attr_e( 'Amaç', 'kvkk-cerez' ); ?>" name="<?php echo esc_attr( $kvkkc_row . '[purpose]' ); ?>" value="<?php echo esc_attr( $kvkkc_cookie['purpose'] ); ?>"></td>
                                                <td><button type="button" class="button-link button-link-delete" data-kvkkc-row-remove><?php esc_html_e( 'Sil', 'kvkk-cerez' ); ?></button></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>

                                <?php if ( false !== strpos( (string) wp_json_encode( $kvkkc_entry['cookies'] ), '{gun}' ) ) : ?>
                                    <p class="kvkkc-muted"><?php esc_html_e( '{gun} ifadesi, sitede Ayarlar > Davranış bölümündeki tercih süresiyle değiştirilir.', 'kvkk-cerez' ); ?></p>
                                <?php endif; ?>

                                <div class="kvkkc-svc-actions">
                                    <button type="button" class="button" data-kvkkc-row-add><?php esc_html_e( 'Çerez ekle', 'kvkk-cerez' ); ?></button>
                                    <button type="button" class="button-link button-link-delete" data-kvkkc-service-remove><?php esc_html_e( 'Hizmeti envanterden çıkar', 'kvkk-cerez' ); ?></button>
                                </div>
                            </div>
                        </details>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>

        <div class="kvkkc-savebar">
            <span class="kvkkc-dirty" data-kvkkc-dirty hidden><?php esc_html_e( 'Kaydedilmemiş değişiklikler var', 'kvkk-cerez' ); ?></span>
            <button type="submit" class="button button-primary kvkkc-save"><?php esc_html_e( 'Envanteri kaydet', 'kvkk-cerez' ); ?></button>
        </div>
    </form>

    <template id="kvkkc-row-template">
        <tr>
            <td><input type="text" class="kvkkc-mono" aria-label="<?php esc_attr_e( 'Çerez adı', 'kvkk-cerez' ); ?>" data-name="name"></td>
            <td><input type="text" aria-label="<?php esc_attr_e( 'Süre', 'kvkk-cerez' ); ?>" data-name="duration"></td>
            <td><input type="text" aria-label="<?php esc_attr_e( 'Amaç', 'kvkk-cerez' ); ?>" data-name="purpose"></td>
            <td><button type="button" class="button-link button-link-delete" data-kvkkc-row-remove><?php esc_html_e( 'Sil', 'kvkk-cerez' ); ?></button></td>
        </tr>
    </template>
</div>
