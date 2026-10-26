<?php
/**
 * Eklenti kaldırma dosyası
 *
 * Eklenti WordPress'ten silindiğinde çalışır. Ayarlar ve geçici veriler silinir; rıza kayıtları
 * ispat amacıyla yalnızca "Eklenti silinince rıza kayıtlarını da sil" seçeneği açıksa silinir.
 *
 * @package KVKK_Cerez
 */

// WordPress tarafından çağrılmadıysa çık.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

$kvkkc_settings = get_option( 'kvkkc_settings', array() );
$kvkkc_drop     = is_array( $kvkkc_settings ) && ! empty( $kvkkc_settings['delete_on_uninstall'] );

foreach ( array( 'kvkkc_settings', 'kvkkc_inventory', 'kvkkc_policy_version', 'kvkkc_db_version', 'kvkkc_scan' ) as $kvkkc_option ) {
    delete_option( $kvkkc_option );
}

// Hız sınırı sayaçları ve tarama anahtarı (transient).
$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- kaldırma sırasında toplu silme.
    $wpdb->prepare(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
        $wpdb->esc_like( '_transient_kvkkc_' ) . '%',
        $wpdb->esc_like( '_transient_timeout_kvkkc_' ) . '%'
    )
);

wp_clear_scheduled_hook( 'kvkkc_cleanup' );

if ( $kvkkc_drop ) {
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}kvkkc_consents" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- eklentinin kendi tablosu kaldırılıyor.
}
