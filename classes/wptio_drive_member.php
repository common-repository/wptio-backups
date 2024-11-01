<?php

if (!defined('ABSPATH')) {
    exit;
}
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of wptio_drive_member
 *
 * @author Ebrahim Qnaibi
 */
class wptio_drive_member {

    static function wptio_save_access_token($post_info) {
        delete_option("wptio_connected_storage");
        add_option("wptio_connected_storage", array(
            'access_token' => $post_info['access_token'],
            'folder_id' => $post_info['folder_id'],
            'provider' => $post_info['provider'],
            'decode_access_token' => $post_info['decode_access_token'],
            'timestamp' => date("Y-m-d H:i:s"),
            'required_refresh' => $post_info['required_refresh'],
        ));
    }
}
