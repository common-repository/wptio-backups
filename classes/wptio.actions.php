<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
if (!defined('ABSPATH')) {
    exit;
}

function wptio_ajax_wptio_api() {

    if (current_user_can('administrator')) {


        $json = $_POST['data'];

        $requestData = json_decode(stripslashes($_POST['data']));



        if (wp_verify_nonce($requestData->params->security_key, 'wptio_nonce_key') != 1) {
            header('Content-Type: application/json');
            $results = (object) array(
                        'message' => "Unauthorization"
            );
            echo json_encode($results);
            return;
        }

        wptio\wptio_engine::wptio_validate_request($requestData);

        wptio\wptio_engine::wptio_process_request($requestData);
    } else {
        header('Content-Type: application/json');
        $results = (object) array(
                    'message' => "Unauthorization"
        );
        echo json_encode($results);
        return;
    }
}

function wptio_setup_menu() {



    add_menu_page('backup-website', 'WPTIO Backups', 'manage_options1', 'backup-website', 'wptio_primary_menu', WPTIO_PLUGIN_DIR_URL . '/icon/icon.png');


    if (get_option("wptio_activation_info") != false) {
        add_submenu_page('backup-website', 'Backup', 'Backup', 'manage_options', 'backup-website-proccess', 'wptio_submenu_backup');
    }
    if (get_option("wptio_activation_info") != false) {
        add_submenu_page('backup-website', 'Backup', 'Restore', 'manage_options', 'restore-website-proccess', 'wptio_submenu_restore');
    }
    add_submenu_page('backup-website', 'Backup', 'Setting', 'manage_options', 'setting-website-proccess', 'wptio_submenu_setting');
}

function wptio_primary_menu() {

    require WPTIO_PLUGIN_DIR_PATH . '/pages/wptio-settings.php';
}

function wptio_submenu_backup() {
    require WPTIO_PLUGIN_DIR_PATH . "/pages/wptio-backup.php";
}

function wptio_submenu_restore() {
    require WPTIO_PLUGIN_DIR_PATH . "/pages/wptio-restore.php";
}

function wptio_submenu_setting() {
    require WPTIO_PLUGIN_DIR_PATH . '/pages/wptio-settings.php';
}

function wptio_scripts() {

    wp_register_style('custom_wp_admin_css', WPTIO_PLUGIN_DIR_URL . '/bootstrap/css/bootstrap.min.css', array(), WPTIO_VERSION);
    wp_enqueue_style('custom_wp_admin_css');
    wp_register_script('wptio.ajax.js', WPTIO_PLUGIN_DIR_URL . '/js/wptio.ajax.js', array(), WPTIO_VERSION);
    wp_enqueue_script('wptio.ajax.js');
    wp_register_script('wptio.drive.js', WPTIO_PLUGIN_DIR_URL . '/js/wptio.drivebtn.js', array(), WPTIO_VERSION);
    wp_enqueue_script('wptio.drive.js');
    wp_enqueue_script('jquery-ui-tooltip');
    //wp_register_script('jquery-3.2.1', "https://code.jquery.com/jquery-3.2.1.min.js", array(), WPTIO_VERSION ,true);
    //wp_enqueue_script('jquery-3.2.1');
    //wp_register_script('bootstrap.min.js', WPTIO_PLUGIN_DIR_URL . '/bootstrap/js/bootstrap.bundle.min.js', array(), WPTIO_VERSION,true);
    //wp_enqueue_script('bootstrap.min.js');
//    wp_register_script('tooltip.js', WPTIO_PLUGIN_DIR_URL . '/js/src/tooltip.js', array('bootstrap.min.js'), WPTIO_VERSION,true);
//    wp_enqueue_script('tooltip.js');
    wp_register_style('font.min.css', "https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css", array(), WPTIO_VERSION);
    wp_enqueue_style('font.min.css');
}

function wptio_create_db_table() {
    global $table_prefix, $wpdb;

    $table_name = $table_prefix . 'wptio_backup';
    $table_name1 = $table_prefix . 'wptio_restore';
    if ($wpdb->get_var("show tables like '$table_name'") != $table_name) {

        $sql = "CREATE TABLE $table_name (
                   id  INT AUTO_INCREMENT PRIMARY KEY,
                   file_path varchar(255),
                   file_size INT,
                   `status` INT ,
                   current_position INT
                  )";

        $wpdb->query($sql);
    }
    if ($wpdb->get_var("show tables like '$table_name1'") != $table_name1) {

        $sql = "CREATE TABLE $table_name1 (
                   id  INT AUTO_INCREMENT PRIMARY KEY,
                   file_id text,
                   name varchar(255),
                  `status` INT 
                   
                  )";

        $wpdb->query($sql);
    }
}

function wptio_delete_db_table() {
    global $table_prefix, $wpdb;

    $table_name = $table_prefix . 'wptio_backup';
    $table_name1 = $table_prefix . 'wptio_restore';
    $sql = "drop table $table_name";
    $wpdb->query($sql);
    $sql = "drop table $table_name1";
    $wpdb->query($sql);
}

function wptio_every_30_seconds($schedules) {
    $schedules['every_30_seconds'] = array(
        'interval' => 30,
        'display' => __('Every 30 Seconds', 'textdomain')
    );
    return $schedules;
}

function wptio_do_backup_step_by_schedule() {
    $backup_patch = get_option('wptio_backup_patch');
    $restore_patch = get_option('wptio_restore_patch');
    $schedule_patch = get_option('wptio_schedule_backup');

    if ($backup_patch == false) {

        //return;
    }
    if ($restore_patch['status'] == 'running') {
        // 

        $restore_patch = new wptio_restore();
        \wptio_api_client::wptio_regenerate_token();
        $restore_patch->wptio_restore_step();
        return;
    }
    if ($backup_patch['status'] == 'finish_db_backup') {
        $backup_patch = new wptio_backup();
        $backup_patch->wptio_clean_up();


        return;
    }


    if ($backup_patch['status'] == 'files_running_on_schedule' || $backup_patch['status'] == 'files_finished_on_schedule') {

        $backup_patch = new wptio_backup();
        \wptio_api_client::wptio_regenerate_token();
        $result = $backup_patch->wptio_do_backup_step();
        return;
    }
    if ($schedule_patch != false) {
        $schedule = new wptio_schedule();
        $schedule->wptio_check_schedule_backup();
        

        return;
    }
}
