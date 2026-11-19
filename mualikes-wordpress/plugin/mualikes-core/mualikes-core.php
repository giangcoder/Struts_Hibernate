<?php
/**
 * Plugin Name: MuaLikes Core
 * Description: Hệ thống SMM panel (dịch vụ, server, đơn hàng, ví tiền, cấp bậc, nạp tiền, API) dùng chung với theme "MuaLikes".
 * Version: 1.0.0
 * Author: MuaLikes.Net
 * Text Domain: mualikes
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

define('ML_VERSION', '1.0.0');
define('ML_DB_VERSION', '1.0.0');
define('ML_FILE', __FILE__);
define('ML_DIR', plugin_dir_path(__FILE__));
define('ML_URL', plugin_dir_url(__FILE__));

require_once ML_DIR . 'includes/helpers.php';
require_once ML_DIR . 'includes/class-db.php';
require_once ML_DIR . 'includes/class-settings.php';
require_once ML_DIR . 'includes/class-services.php';
require_once ML_DIR . 'includes/class-users.php';
require_once ML_DIR . 'includes/class-providers.php';
require_once ML_DIR . 'includes/class-orders.php';
require_once ML_DIR . 'includes/class-payments.php';
require_once ML_DIR . 'includes/class-datatables.php';
require_once ML_DIR . 'includes/class-graph-api.php';
require_once ML_DIR . 'includes/class-public-api.php';
require_once ML_DIR . 'includes/class-router.php';
require_once ML_DIR . 'includes/class-cron.php';
require_once ML_DIR . 'includes/class-importer.php';
if (is_admin()) {
    require_once ML_DIR . 'includes/admin/class-admin.php';
}
if (defined('WP_CLI') && WP_CLI) {
    require_once ML_DIR . 'includes/class-cli.php';
}

register_activation_hook(__FILE__, function () {
    ML_DB::install();
    ML_Settings::install_defaults();
    ML_Services::register();
    ML_Router::add_rules();
    ML_Cron::schedule();
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function () {
    ML_Cron::unschedule();
    flush_rewrite_rules();
});

add_action('plugins_loaded', function () {
    if (get_option('ml_db_version') !== ML_DB_VERSION) {
        ML_DB::install();
    }
    ML_Services::init();
    ML_Users::init();
    ML_Router::init();
    ML_Cron::init();
    ML_Payments::init();
    if (is_admin()) {
        ML_Admin::init();
    }
});
