<?php
/**
 * AbzarWP Auto Update Plugin Uninstall
 */

use AbzarWP\App\Cache;
use AbzarWP\App\Uninstall;

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

require_once plugin_dir_path( __FILE__ ) . 'App/App.php';

get_theme_root();

Uninstall::run();