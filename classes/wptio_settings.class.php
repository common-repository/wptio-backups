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
 * Description of wptio_settings
 *
 * @author Ebrahim Qnaibi
 */
class wptio_settings {

    //put your code here
    public function wptio_data() {
        $data = (object) array(
                    home_url => home_url(),
                    url => $_SERVER["REQUEST_URI"],
                    host => $_SERVER['HTTP_HOST'],
                    root => $_SERVER['DOCUMENT_ROOT'],
                    user_agent => $_SERVER['HTTP_USER_AGENT'],
                    server_software => $_SERVER['SERVER_SOFTWARE'],
                    server_signature => $_SERVER['SERVER_SIGNATURE'],
                    remote_addr => $_SERVER['REMOTE_ADDR'],
                    request_scheme => $_SERVER['REQUEST_SCHEME'],
                    server_port => $_SERVER['SERVER_PORT'],
        );

        return $data;
    }

    public function wptio_send_key($params) {
       
        $data = $this->wptio_data();
        $data->key = $params->params;

        $endpoint = WPTIO_ACTIVATION_URL . "activate/";
        
        $result = \wptio_api_client::wptio_send_request_form($endpoint, $data);
        
        $value = json_decode($result->result);

        
        if ($value->result == "ok") {
            delete_option('wptio_activation_info');
            add_option('wptio_activation_info', $value->client_key);
            delete_option('wptio_client_info');
            add_option('wptio_client_info', array('expiry_date' => $value->expiry_date, 'license_type' => $value->license_type, 'licensed_to' => $value->licensed_to));
            return $value;
        } else {

            return $value;
        }
    }

    public function wptio_send_email($params) {
        $data = $this->wptio_data();
        $data->email = $params->params;

        $endpoint = WPTIO_ACTIVATION_URL . "create_user/";


        $result = \wptio_api_client::wptio_send_request($endpoint, $data);
       
        $value = json_decode($result->result);



        return $value;
    }

}
