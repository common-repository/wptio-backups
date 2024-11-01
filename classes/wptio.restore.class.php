<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
if (!defined('ABSPATH')) {
    exit;
}

class wptio_restore {

    public function wptio_restore_list() {
        $connected_storage = get_option('wptio_connected_storage');
        $connected_storage = str_replace('\\', '', $connected_storage);
        if ($connected_storage == false) {

            return false;
        }
        $theAccessToken = $connected_storage['access_token'];

        if ($connected_storage['decode_access_token'] == 'true') {
            $theAccessToken = json_decode($connected_storage['access_token']);
            $theAccessToken = $theAccessToken;
        }
        $provider = WPTIO_RESTORE_URL . $connected_storage['provider'];
        $obj = new wptio_settings();



        $ac_info = get_option('wptio_activation_info');
        $result = wptio_api_client::wptio_send_request($provider, (object) array(
                            access_token => $theAccessToken,
                            folder_id => $connected_storage['folder_id'],
                            status => 'list',
                            server_info => $obj->wptio_data(),
                            activation_info => $ac_info,
        ));



        $folder_name = array();
        $result = json_decode($result->result);

        if (isset($result->access_token) && isset($result->refresh_token)) {
            //update connected storage
            $update_access = get_option('wptio_connected_storage');
            $access = str_replace('\\', '', $update_access['access_token']);
            $access_obj = (json_decode($access));
            $access_obj->refresh_token = $result->refresh_token;
            $access_obj->access_token = $result->access_token;
            $update_access['access_token'] = json_encode($access_obj);
            $update_access['timestamp'] = date("Y-m-d H:i:s");

            update_option('wptio_connected_storage', $update_access);
        }

        if (isset($result->error)) {

            delete_option("wptio_activation_info");
            return (object) array(
                        url => admin_url('admin.php?page=setting-website-proccess'),
            );
        }
        if ($result->files == null) {
            return;
        }
        if ($result->details == null) {
            return;
        }
        foreach ($result->details as $key => $val) {
            $details[$key] = $val->params->type;
        }
        foreach ($result->files as $rslt) {

            if (!$details[$rslt->id]) {
                continue;
            }
            $folder_name[$rslt->id] = $rslt->name;
        }

        return (object)
                array(
                    folder => $folder_name,
                    details => $details
        );
    }

    public function wptio_restore_process($params) {
       
        global $wpdb, $table_prefix;
        $table_name = $table_prefix . 'wptio_restore';
        $connected_storage = get_option('wptio_connected_storage');
        $connected_storage = str_replace('\\', '', $connected_storage);
        if ($connected_storage == false) {

            return false;
        }
        $theAccessToken = $connected_storage['access_token'];

        if ($connected_storage['decode_access_token'] == 'true') {
            $theAccessToken = json_decode($connected_storage['access_token']);
            $theAccessToken = $theAccessToken;
        }
       
        $provider = WPTIO_RESTORE_URL . $connected_storage['provider'];
        $obj = new wptio_settings();
        $ac_info = get_option('wptio_activation_info');
        $result = wptio_api_client::wptio_send_request($provider, (object) array(
                            access_token => $theAccessToken,
                            folder_id => $connected_storage['folder_id'],
                            status => 'get',
                            server_info => $obj->wptio_data(),
                            activation_info => $ac_info,
                            folder_restore_name => $params->restorefilename,
                            folder_restore_id => $params->restoreid
        ));



        $data = json_decode($result->result);


        foreach ($data->result as $name => $id) {


            $wpdb->insert($table_name, array(
                'file_id' => $id,
                'name' => $name,
                'status' => 0
            ));
        }
        add_option("wptio_restore_patch", array('status' => 'running', 'root' => get_home_path(), 'filename_to_restore' => $params->restorefilename));
        return (object) array(
                    result => json_decode($url),
        );
    }

    private function wptio_restore_clean($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (filetype($dir . "/" . $object) == "dir")
                        rmdir($dir . "/" . $object);
                    else
                        unlink($dir . "/" . $object);
                }
            }
            reset($objects);
            rmdir($dir);
        }
    }

    private function wptio_download_file($access_token, $file_id, $filename) {
        //create dir in uploads and get path
        $uploads_root = wp_get_upload_dir();
        if (!is_dir($uploads_root['basedir'] . '/wptio_restore/')) {
            mkdir($uploads_root['basedir'] . '/wptio_restore/');
        }

        $root = $uploads_root['basedir'] . '/wptio_restore/';
        $download_root = $root;
        //api get url
        $connected_storage = get_option('wptio_connected_storage');
        $theAccessToken = $connected_storage['access_token'];
        if ($connected_storage['decode_access_token'] == 'true') {
            $theAccessToken = json_decode($connected_storage['access_token']);
            $theAccessToken = $theAccessToken->access_token;
        }
        $data_to_get_url = (object) array(
                    file_name => $filename,
                    file_id => $file_id,
                    access_token => $theAccessToken,
                    folder_id => $connected_storage['folder_id']
        );

        $get_download_url = \wptio_api_client::wptio_send_request_form(WPTIO_DOWNLOAD_URL . $connected_storage['provider'], $data_to_get_url);


        $download_url_api = json_decode($get_download_url->result);
        if ($download_url_api->result == 'ok') {
            $url = str_replace("\\", "", $download_url_api->url);


            $options = array(
                'http' => array(
                    'header' => "Authorization: Bearer " . $access_token . "",
                    'method' => 'GET',
                )
            );




            $context = stream_context_create($options);
            $result = file_get_contents($url, false, $context);

            file_put_contents($download_root . $filename, $result);

            return $download_root;
        } else {
            return $download_url_api;
        }
    }

    private function wptio_handle_files_zip($file_path, $filename) {

        $zip = new ZipArchive;
        $zip->open($file_path . $filename);

        $restore_patch = get_option('wptio_restore_patch');

        $zip->extractTo($restore_patch['root']);


        $zip->close();

        unlink($file_path . $filename);
    }

    private function wptio_handle_files_parts_zip($file_path, $filename) {
        $uploads_root = wp_get_upload_dir();
        if (!is_dir($uploads_root['basedir'] . '/wptio_restore_part/')) {
            mkdir($uploads_root['basedir'] . '/wptio_restore_part/');
        }

        $root = $uploads_root['basedir'] . '/wptio_restore_part/';
        $zip = new ZipArchive;
        $zip->open($file_path . $filename);
        $zip->extractTo($root);

        $zip->close();
        
        unlink($file_path . $filename);
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root), RecursiveIteratorIterator::SELF_FIRST);
        foreach ($files as $file) {
            if (is_file($file)) {

                $content_file = realpath($file);
                $replace11 = str_replace('\\', '/', $content_file);
                $replace22 = str_replace('\\', '/', $root);
                $replace33 = str_replace($replace22, '', $replace11);
                $restore_patch = get_option('wptio_restore_patch');
                $real_root = $restore_patch['root'] . $replace33;

                $get_content = file_get_contents($file);
                file_put_contents($real_root, $get_content, FILE_APPEND);
                unlink($content_file);
                
                
               
            }
        }
        $this->wptio_del_tree_dir_emp($root);
    }

    private function wptio_handle_db_zip($file_path, $filename) {
        global $wpdb;


        $zip = new ZipArchive;
        $zip->open($file_path . $filename);
        $zip->extractTo($file_path, 'wptio-db-backup.json');
        $zip->close();
        unlink($file_path . $filename);


        $data = file_get_contents($file_path . 'wptio-db-backup.json');

        $files_to_run_query = json_decode($data);

        $sructure = (array) $files_to_run_query->structure;
        foreach ($sructure as $key => $value) {
            if ($wpdb->get_var("show tables like '$key'") != $key) {
                $sql = $value;
                $wpdb->query($sql);
            }
        }

        $data_array = (array) $files_to_run_query->data;

        foreach ($data_array as $table_name) {
            foreach ($table_name as $query) {

                $sql = $query;
                $wpdb->query($sql);
            }
        }


        unlink($file_path . 'wptio-db-backup.json');
        return 'local_path';
    }

    public function wptio_restore_step() {
        // $restore_patch = get_option('wptio_restore_patch');
//        if ($restore_patch ['status'] == "running-step") {
//            return;
//        }
//        $restore_patch['status'] = 'running-step';
//        update_option("wptio_restore_patch", $restore_patch);

        global $wpdb, $table_prefix;
        $table_name = $table_prefix . "wptio_restore";

        $connected_storage = get_option('wptio_connected_storage');
        $connected_storage = str_replace('\\', '', $connected_storage);
        if ($connected_storage == false) {

            return false;
        }
        $theAccessToken = $connected_storage['access_token'];

        if ($connected_storage['decode_access_token'] == 'true') {
            $theAccessToken = json_decode($connected_storage['access_token']);
            $theAccessToken = $theAccessToken->access_token;
        }

        $restore_patch = get_option('wptio_restore_patch');

        if (!isset($restore_patch['config'])) {
            $data = $wpdb->get_row("select * from $table_name where `name`='patch_info.json'");


            $file_name = $this->wptio_download_file($theAccessToken, $data->file_id, $data->name);

            $patch_config = file_get_contents($file_name . $data->name);

            $restore_patch['config'] = json_decode($patch_config);
            update_option('wptio_restore_patch', $restore_patch); //json_decode($patch_config);


            $wpdb->delete($table_name, array(name => 'patch_info.json'));
            unlink($file_name . $data->name);
        } else {
            $data = $wpdb->get_row("select * from $table_name where `name` like '%-wptio-db-%' order by name");

            if ($data != null) {
                $this->wptio_update_option($data->name);

                $file_name = $this->wptio_download_file($theAccessToken, $data->file_id, $data->name);

                $this->wptio_handle_db_zip($file_name, $data->name);

                $wpdb->delete($table_name, array(id => $data->id));
            } else {

                $data = $wpdb->get_row("select * from $table_name where `name` not like '%-wptio-files-backup-parts-%' order by name");




                if ($data != null) {
                    $this->wptio_update_option($data->name);

                    $file_name = $this->wptio_download_file($theAccessToken, $data->file_id, $data->name);

                    $this->wptio_handle_files_zip($file_name, $data->name);

                    $wpdb->delete($table_name, array(id => $data->id));
                } else {

                    $data = $wpdb->get_row("select * from $table_name where `status` = 1 order by name");

                    if ($data != null) {
                        $this->wptio_update_option($data->name);

                        $file_name = $this->wptio_download_file($theAccessToken, $data->file_id, $data->name);
                        $wpdb->update($table_name, array(status => 2), array(id => $data->id));
                        $this->wptio_handle_files_parts_zip($file_name, $data->name);
                        if ($wpdb->get_var("select count(*)from $table_name where status= 1") == 0) {
                            $wpdb->delete($table_name, array(status => 2));
                            //$this->wptio_handle_files_parts_zip($file_path, $filename);
                        }
                        // Download this fucken file.
                        // change status to 2
                        // SELECT COUNT(ID) FROM $TABLENAME WEHERE STATUS =1
                        // IF COUNT == 0
                        // THEN STAART EXTRACT THE FILE
                    } else {
                        $data = $wpdb->get_row("select * from $table_name where `name`  like '%-wptio-files-backup-parts-%' order by name");
                        if ($data != null) {
                            $this->wptio_update_option($data->name);

                            $name_parts = explode('-', $data->name);
                            $filename = implode('-', array_slice($name_parts, 0, 5)) . '-%';

                            $the_query = sprintf("select * from $table_name where status=0 and `name`  like '%s' order by name", $filename);

                            $data = $wpdb->get_results($the_query);



                            if (sizeof($data) == 0) {

                                $wpdb->query("delete from $table_name");
                                
                            }
                            foreach ($data as $array_data) {
                                $wpdb->update($table_name, array(status => 1), array(id => $array_data->id));
                                //$url_to_download[$array_data->url] = $array_data->name;
                                //$file_name_to_download[] = $array_data->name;
                                //$id_to_delete[] = $array_data->id;
                            }


                            // DONT DOWNLAD ALL THE FILES, JUST CHANGE THE STATUS AND DONWLOAD THE FIRST FILE ONLY
                            //$file_name = $this->wptio_download_file($theAccessToken, $url_to_download, $file_name_to_download);
                            //$this->wptio_handle_files_parts_zip($file_name, $file_name_to_download);
                        }
                    }
                }
            }
        }


        // Clear Restore Patch
        $count = $wpdb->get_var("select count(id) from $table_name");
       
        if ($count == 0) {
            delete_option('wptio_restore_patch');
            $uploads_root = wp_get_upload_dir();
           
            
            if (is_dir($uploads_root['basedir'] . '/wptio_restore')) {
                $this->wptio_del_tree_dir_emp($uploads_root['basedir'] . '/wptio_restore');
               
            }
             
           
            
        }

    }

    private function wptio_update_option($name) {

        $option = get_option('wptio_restore_patch');
        $option['currentfilename'] = $name;
        update_option('wptio_restore_patch', $option);
    }

    public function wptio_check_restore_status() {
        $restore_patch = get_option('wptio_restore_patch');

        if ($restore_patch !== false) {
            global $wpdb, $table_prefix;
            $table_name = $table_prefix . "wptio_restore";

            $restore_patch['remaining_files'] = $wpdb->get_var("SELECT count(id) from $table_name where status = 0 or status = 1");
        }
        
        return(object) array(
                    result => 'ok',
                    restore_patch => $restore_patch
        );
    }

    private function wptio_del_tree_dir_emp($dir) {
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? wptio_del_tree_dir_emp("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }

}
