<?php
/**
 * Plugin Name:       Umay Çerez Yöneticisi
 * Description:       Cookie consent for Turkish websites based on the KVKK Cookie Guidelines: consent banner, blocking before consent, site scan and consent records.
 * Version:           1.0.0
 * Requires at least: 6.7
 * Requires PHP:      7.4
 * Author:            Umay Ajans
 * Author URI:        https://www.umayajans.com
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       umay-cerez-yoneticisi
 * Domain Path:       /languages
 *
 * @package KVKK_Cerez
 */

// Doğrudan erişimi engelle.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Eklenti sabitleri.
define( 'KVKKC_VERSION', '1.0.0' );
define( 'KVKKC_DIR', plugin_dir_path( __FILE__ ) );
define( 'KVKKC_URL', plugin_dir_url( __FILE__ ) );
define( 'KVKKC_BASENAME', plugin_basename( __FILE__ ) );

require_once KVKKC_DIR . 'includes/functions.php';
require_once KVKKC_DIR . 'includes/class-activator.php';
require_once KVKKC_DIR . 'includes/class-services.php';
require_once KVKKC_DIR . 'includes/class-blocker.php';
require_once KVKKC_DIR . 'includes/class-frontend.php';
require_once KVKKC_DIR . 'includes/class-consent-log.php';
require_once KVKKC_DIR . 'includes/class-scanner.php';
require_once KVKKC_DIR . 'includes/class-integrations.php';

register_activation_hook( __FILE__, array( 'KVKKC_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'KVKKC_Activator', 'deactivate' ) );

/**
 * Sürüm değiştiyse tabloyu güncelle (dosya değiştirilerek yapılan güncellemelerde
 * aktivasyon kancası çalışmaz).
 */
function kvkkc_maybe_upgrade() {
    if ( get_option( 'kvkkc_db_version' ) !== KVKKC_VERSION ) {
        KVKKC_Activator::create_tables();
    }
}
add_action( 'plugins_loaded', 'kvkkc_maybe_upgrade' );

KVKKC_Integrations::init();

/**
 * Eklentiyi başlat. Çeviriler WordPress tarafından otomatik yüklenir (dil paketleri).
 */
function kvkkc_init() {
    KVKKC_Blocker::init();
    KVKKC_Frontend::init();
    KVKKC_Consent_Log::init();
    KVKKC_Scanner::init();

    if ( is_admin() ) {
        require_once KVKKC_DIR . 'includes/class-admin.php';
        new KVKKC_Admin();
    }
}
add_action( 'init', 'kvkkc_init', 0 );
