<?php
/**
 * Rıza kayıtları: arama, sayfalama, CSV
 *
 * @package KVKK_Cerez
 */

// Doğrudan erişimi engelle.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;

$kvkkc_per_page = 25;
$kvkkc_search   = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- yalnızca okuma.
$kvkkc_paged    = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- yalnızca okuma.
$kvkkc_like     = '%' . $wpdb->esc_like( $kvkkc_search ) . '%';

// phpcs:disable WordPress.DB.DirectDatabaseQuery -- özel tablo, önbelleğe alınacak bir veri değil.
$kvkkc_total = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE consent_id LIKE %s', kvkkc_table(), $kvkkc_like ) );
$kvkkc_rows  = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i WHERE consent_id LIKE %s ORDER BY id DESC LIMIT %d OFFSET %d', kvkkc_table(), $kvkkc_like, $kvkkc_per_page, ( $kvkkc_paged - 1 ) * $kvkkc_per_page ) );
// phpcs:enable

$kvkkc_actions   = KVKKC_Consent_Log::action_labels();
$kvkkc_cat_names = array();
foreach ( kvkkc_categories() as $kvkkc_cat ) {
    $kvkkc_cat_names[ $kvkkc_cat ] = KVKKC_Frontend::category_name( $kvkkc_cat );
}
$kvkkc_csv_url = wp_nonce_url( admin_url( 'admin-post.php?action=kvkkc_export_csv' ), 'kvkkc_export_csv' );
?>
<div class="wrap kvkkc-app">
    <h1 class="screen-reader-text"><?php esc_html_e( 'Rıza Kayıtları', 'kvkk-cerez' ); ?></h1>
    <?php KVKKC_Admin::render_header( 'kvkk-cerez-kayitlar' ); ?>

    <section class="kvkkc-card kvkkc-log">
        <div class="kvkkc-log-head">
            <div>
                <h2 class="kvkkc-card-title"><?php esc_html_e( 'Rıza kayıtları', 'kvkk-cerez' ); ?></h2>
                <p class="kvkkc-card-lead">
                    <?php
                    printf(
                        /* translators: 1: kayıt sayısı, 2: ay sayısı */
                        esc_html__( '%1$s kayıt. Kayıtlar %2$d ay saklanır, süresi dolanlar her gün silinir.', 'kvkk-cerez' ),
                        esc_html( number_format_i18n( $kvkkc_total ) ),
                        (int) kvkkc_setting( 'retention_months' )
                    );
                    ?>
                </p>
            </div>
            <div class="kvkkc-log-tools">
                <form method="get" class="kvkkc-search" role="search">
                    <input type="hidden" name="page" value="kvkk-cerez-kayitlar">
                    <label class="screen-reader-text" for="kvkkc-search"><?php esc_html_e( 'Rıza numarası ara', 'kvkk-cerez' ); ?></label>
                    <input type="search" id="kvkkc-search" name="s" value="<?php echo esc_attr( $kvkkc_search ); ?>" placeholder="<?php esc_attr_e( 'Rıza numarası', 'kvkk-cerez' ); ?>" class="kvkkc-mono">
                    <button type="submit" class="button"><?php esc_html_e( 'Ara', 'kvkk-cerez' ); ?></button>
                </form>
                <a class="button" href="<?php echo esc_url( $kvkkc_csv_url ); ?>"><?php esc_html_e( 'CSV indir', 'kvkk-cerez' ); ?></a>
            </div>
        </div>

        <?php if ( ! $kvkkc_rows ) : ?>
            <div class="kvkkc-empty">
                <?php if ( '' !== $kvkkc_search ) : ?>
                    <p><?php esc_html_e( 'Bu numarayla eşleşen kayıt yok. Ziyaretçinin tercihler penceresinin altında gördüğü numaranın tamamını ya da bir kısmını yazın.', 'kvkk-cerez' ); ?></p>
                <?php else : ?>
                    <p><?php esc_html_e( 'Henüz rıza kaydı yok. Ziyaretçiler bantta seçim yaptıkça kayıtlar burada görünür.', 'kvkk-cerez' ); ?></p>
                <?php endif; ?>
            </div>
        <?php else : ?>
            <div class="kvkkc-table-wrap">
                <table class="kvkkc-table kvkkc-log-table">
                    <thead>
                        <tr>
                            <th scope="col"><?php esc_html_e( 'Tarih', 'kvkk-cerez' ); ?></th>
                            <th scope="col"><?php esc_html_e( 'Rıza No', 'kvkk-cerez' ); ?></th>
                            <th scope="col"><?php esc_html_e( 'Eylem', 'kvkk-cerez' ); ?></th>
                            <th scope="col"><?php esc_html_e( 'İzin verilen kategoriler', 'kvkk-cerez' ); ?></th>
                            <th scope="col"><?php esc_html_e( 'Sürüm', 'kvkk-cerez' ); ?></th>
                            <th scope="col"><?php esc_html_e( 'Sayfa', 'kvkk-cerez' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $kvkkc_rows as $kvkkc_row ) : ?>
                            <tr>
                                <td><?php echo esc_html( get_date_from_gmt( $kvkkc_row->created_at, 'd.m.Y H:i' ) ); ?></td>
                                <td><code><?php echo esc_html( $kvkkc_row->consent_id ); ?></code></td>
                                <td><span class="kvkkc-badge is-<?php echo esc_attr( $kvkkc_row->action ); ?>"><?php echo esc_html( isset( $kvkkc_actions[ $kvkkc_row->action ] ) ? $kvkkc_actions[ $kvkkc_row->action ] : $kvkkc_row->action ); ?></span></td>
                                <td>
                                    <?php if ( '' === $kvkkc_row->categories ) : ?>
                                        <span class="kvkkc-muted"><?php esc_html_e( 'Yalnızca zorunlu', 'kvkk-cerez' ); ?></span>
                                    <?php else : ?>
                                        <?php foreach ( explode( ',', $kvkkc_row->categories ) as $kvkkc_cat ) : ?>
                                            <span class="kvkkc-chip kvkkc-chip--<?php echo esc_attr( $kvkkc_cat ); ?>"><?php echo esc_html( isset( $kvkkc_cat_names[ $kvkkc_cat ] ) ? $kvkkc_cat_names[ $kvkkc_cat ] : $kvkkc_cat ); ?></span>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </td>
                                <td>v<?php echo esc_html( $kvkkc_row->policy_version ); ?></td>
                                <td class="kvkkc-muted"><?php echo esc_html( $kvkkc_row->page ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php
            $kvkkc_links = paginate_links(
                array(
                    'base'      => add_query_arg( 'paged', '%#%' ),
                    'format'    => '',
                    'current'   => $kvkkc_paged,
                    'total'     => (int) ceil( $kvkkc_total / $kvkkc_per_page ),
                    'prev_text' => __( '&lsaquo; Önceki', 'kvkk-cerez' ),
                    'next_text' => __( 'Sonraki &rsaquo;', 'kvkk-cerez' ),
                )
            );
            ?>
            <?php if ( $kvkkc_links ) : ?>
                <nav class="kvkkc-pagination" aria-label="<?php esc_attr_e( 'Sayfalar', 'kvkk-cerez' ); ?>"><?php echo wp_kses_post( $kvkkc_links ); ?></nav>
            <?php endif; ?>
        <?php endif; ?>
    </section>

    <section class="kvkkc-card kvkkc-explain">
        <h2 class="kvkkc-card-title"><?php esc_html_e( 'Kayıtta ne var, ne yok', 'kvkk-cerez' ); ?></h2>
        <div class="kvkkc-explain-grid">
            <div>
                <h3 class="kvkkc-subtitle"><?php esc_html_e( 'Saklanan', 'kvkk-cerez' ); ?></h3>
                <ul>
                    <li><?php esc_html_e( 'Rastgele üretilen rıza numarası (ziyaretçi tercihler penceresinde görür)', 'kvkk-cerez' ); ?></li>
                    <li><?php esc_html_e( 'Tarih, eylem ve izin verilen kategoriler', 'kvkk-cerez' ); ?></li>
                    <li><?php esc_html_e( 'O an geçerli politika sürümü ve sayfa yolu', 'kvkk-cerez' ); ?></li>
                    <li><?php esc_html_e( 'İsteğe bağlı: kırpılmış IP adresinin tek yönlü özeti', 'kvkk-cerez' ); ?></li>
                </ul>
            </div>
            <div>
                <h3 class="kvkkc-subtitle"><?php esc_html_e( 'Saklanmayan', 'kvkk-cerez' ); ?></h3>
                <ul>
                    <li><?php esc_html_e( 'Ham IP adresi', 'kvkk-cerez' ); ?></li>
                    <li><?php esc_html_e( 'Tarayıcı ve cihaz bilgisi', 'kvkk-cerez' ); ?></li>
                    <li><?php esc_html_e( 'Sayfa adresindeki sorgu parametreleri (kampanya kodları, e-posta gibi)', 'kvkk-cerez' ); ?></li>
                    <li><?php esc_html_e( 'Giriş yapmış kullanıcının kimliği', 'kvkk-cerez' ); ?></li>
                </ul>
            </div>
        </div>
    </section>
</div>
