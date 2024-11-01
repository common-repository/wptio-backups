<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
if (!defined('ABSPATH')) {
    exit;
}

class wptio_api_client {

    public static function wptio_upload_file($provider, $file_path) {

        $buffer = file_get_contents($file_path);
        $url = WPTIO_UPLOAD_URL . $provider.WPTIO_API_REQ_VERSION;
        if (strpos($provider,'&') !== false) {
         $url = WPTIO_UPLOAD_URL . $provider.WPTIO_API_RES_VERSION;
         }
        
        $options = array(
            'http' => array(
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n" .
                "Content-Type: application/zip",
                'method' => 'POST',
                'content' => $buffer,
            )
        );
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        return $result;
    }

    public function wptio_send_request($provider, $params) {

        $url = $provider. WPTIO_API_REQ_VERSION;
         if (strpos($provider,'&') !== false) {
         $url = $provider. WPTIO_API_RES_VERSION;
         }
        $options = array(
            'http' => array(
                'header' => "Content-Type: application/x-www-form-urlencoded",
                'method' => 'POST',
                'content' => 'requestData=' . json_encode($params),
            )
        );
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        return (object) array(
                    result => $result
        );
    }

    public function wptio_send_request_form($provider, $params) {
       
        $url = $provider. WPTIO_API_REQ_VERSION;
        if (strpos($provider,'&') !== false) {
         $url = $provider. WPTIO_API_RES_VERSION;
         }
        $content = '';
        $subcontent = '';

        if (is_object($params)) {
            $params = (array) $params;

            foreach ($params as $key => $value) {

                $content .= sprintf('%1$s=%2$s&', $key, $value);
            }
        }

        $options = array(
            'http' => array(
                'header' => "Content-Type: application/x-www-form-urlencoded",
                'method' => 'POST',
                'content' => $content,
            )
        );
       
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
       
        return (object) array(
                    result => $result
        );
    }

    public function wptio_create_patch_drives($patch) {
        $connected_storage = get_option('wptio_connected_storage');
        $connected_storage = str_replace('\\', '', $connected_storage);
        if ($connected_storage == false) {

            return false;
        }
        $theAccessToken = $connected_storage['access_token'];
        $folder_id = $connected_storage['folder_id'];
        $provider = WPTIO_PATCH_URL . $connected_storage['provider'];

        $decode_access_token = $connected_storage['decode_access_token'];

        if ($decode_access_token == 'true') {
            $theAccessToken = json_decode($connected_storage['access_token']);
        }

        $obj = new wptio_settings();
        $ac_info = get_option('wptio_activation_info');

        $result = self::wptio_send_request($provider, (object) array(
                            access_token => $theAccessToken,
                            folder_id => $folder_id,
                            server_info => $obj->wptio_data(),
                            activation_info => $ac_info,
                            patch_info => $patch,
                            file_name_for_api => WPTIO_SITE_NAME
        ));
       
        $result = json_decode($result->result);

        if ($result->result == "ok" && isset($result->backup_session_id)) {
            $result->patch_ok = true;
        }

        return $result;
    }

    public static function wptio_regenerate_token() {
        
        $connect_option = get_option('wptio_connected_storage');
        $get_refresh = str_replace("//", '', $connect_option['access_token']);
        $get_refresh = json_decode($get_refresh);
        if ($connect_option['required_refresh'] == "true") {
            $time = date("Y-m-d H:i:s");
            $lasttime=$connect_option['timestamp'];
            $lastdate=date("Y-m-d H:i:s" ,strtotime("$lasttime +15 minutes"));
            if($lastdate < $time){
            $data = (object) array
                        (
                        refresh_token => $get_refresh->refresh_token
            );
            $provider = WPTIO_GENERATE_TOKEN . $connect_option['provider'];
            
            $obejct = new wptio_api_client();
            $new_access_token_json = $obejct->wptio_send_request_form($provider, $data);
            $new_access_token = json_decode($new_access_token_json->result);
           
            $update_access = get_option('wptio_connected_storage');
            $access = str_replace('\\', '', $update_access['access_token']);
            $access_obj = (json_decode($access));
            
            $access_obj->access_token = $new_access_token->access_token;
            $update_access['access_token'] = json_encode($access_obj);
            $update_access['timestamp'] = date("Y-m-d H:i:s");
            update_option('wptio_connected_storage', $update_access);
           
        }
        }
    }

}
