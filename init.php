<?php

/*
 * Plugin Name: WPtools.io Cloud Backup & Restore plugin
 * Plugin URI: https://wordpress.org/plugins/BackUps-wptools
 * Description: The WPtool.io Cloud Backup & Restore WordPress Plugin is a trusted, reliable solution when it comes to backing up your sites into your preferred cloud storage location. No matter the size of your website (or the amount of data) this plugin is simple to use and start implementing immediately. In fact, we’ve made it powerful enough to handle even the biggest of sites and file sizes – benchmarked on 20GB websites – something unheard of for plugins of this nature. 
 * Version: 1.2.2
 * Author: WPtools.io
 * Author URI: http://wptools.io/wptoolsio-cloud-backup-restore-plugin/
 */
if (!defined('ABSPATH')) {
    exit;
}
//init must contain this define to get plugin real path

//include APACHE.'/wp-admin/includes/file.php';
define('WPTIO_PLUGIN_DIR_PATH', dirname(__FILE__));
define('WPTIO_PLUGIN_DIR_URL', plugins_url(basename(dirname(__FILE__))));
define('WPTIO_PLUGIN_NAME', basename(dirname(__FILE__)));
define('WPTIO_SITE_NAME', basename(home_url()));
ini_set('memory_limit', '2048M');
if (!function_exists('wptio_include_directory')) {

    function wptio_include_directory($path) {
        $files = glob(sprintf('%s/*.php', $path));

        foreach ($files as $filename) {
            include_once $filename;
        }
    }

}
wptio_include_directory(__DIR__ . "/classes");
add_action('wp_ajax_wptio_api', 'wptio_ajax_wptio_api');
add_action('admin_menu', 'wptio_setup_menu');
add_action('admin_enqueue_scripts', 'wptio_scripts');
add_action("plugins_loaded", "wptio_create_db_table");
register_deactivation_hook(__FILE__, 'wptio_delete_db_table');
add_filter('cron_schedules', 'wptio_every_30_seconds');
if (!wp_next_scheduled('wptio_every_30_seconds')) {
    wp_schedule_event(time(), 'every_30_seconds', 'wptio_every_30_seconds');
}
add_action("wptio_every_30_seconds", "wptio_do_backup_step_by_schedule");

// function log_to_file($content) {
//		$content = var_export ( $content, TRUE );
//		
//		file_put_contents ( 'E:\wamp64\www\tntn.txt', "\n--------------\n$content", FILE_APPEND );
//	}