<?php

if (!defined('ABSPATH')) {
    exit;
}

class wptio_backup {

    public function wptio_create_patch($params) {

        global $wpdb;
        
        $result = (object) array(
                    number_of_files => 0,
                    total_size => 0
        );

        if ($params->type == 'full') {
           
            $result = $this->wptio_create_files_list('', array());
           
        } else {
            $date_range = array();

            if ($params->allDate == false) {
                $date_range = array(
                    fromDate => $params->fromDate,
                    toDate => $params->toDate
                );
            }

            if ($params->plugin == true) {
                $temp = $this->wptio_create_files_list('plugin_root', $date_range);
                $result->number_of_files += $temp->number_of_files;
                $result->total_size += $temp->total_size;
            }

            if ($params->theme == true) {
                $temp = $this->wptio_create_files_list('themes_root', $date_range);
                $result->number_of_files += $temp->number_of_files;
                $result->total_size += $temp->total_size;
            }
            if ($params->uploadfile == true) {
                $temp = $this->wptio_create_files_list('upload_root', $date_range);
                $result->number_of_files += $temp->number_of_files;
                $result->total_size += $temp->total_size;
            }
            if ($params->database == true) {

                $temp = $this->wptio_create_files_list('dataBase', $date_range);
            }
        }
         
        $patch = array(
            date_time => date("Y-m-d H:i:s"),
            status => 'patch_ready',
            params => $params,
            total_expected_files_steps => $result->number_of_files,
            current_files_step => $result->total_size,
            total_byte_size => $result->total_size,
            root => ABSPATH//get_home_path()
        );


        $result = \wptio_api_client::wptio_create_patch_drives($patch);
       
        if ($result->result == 'not-ok') {
            return $result;
        }
        if (isset($result->error)) {

            delete_option("wptio_activation_info");
            return (object) array(
                        url => admin_url('admin.php?page=setting-website-proccess'),
            );
        }
        if ($result->patch_ok == true) {
            $patch['backup_session_id'] = $result->backup_session_id;
            add_option("wptio_backup_patch", $patch);

            $result->backup_patch = $patch;
            $result->patch_name = "wptio_backup_patch";
            $result->bytesize = $result->total_size;
            $result->total_size = $result->total_size / 1024 / 1024;

            if ($result->total_size > 1024) {
                $result->total_size = ($result->total_size / 1024);
                $result->total_size = number_format($result->total_size, 2) . "GB";
            } else {
                $result->total_size = number_format($result->total_size, 2) . "MB";
            }
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
            return $result;
        }

        //delete content table
        $prefix_table = $wpdb->prefix;
        $table_name = $prefix_table . "wptio_backup";
        $wpdb->query("delete from $table_name");
        return (object) array(
                    result => "big_fuck"
        );
    }

    private function wptio_create_files_list($root_dir, $date_range = array()) {
        //$total_size=array();
       
        global $wpdb, $table_prefix;
        $table_name = $table_prefix . "wptio_backup";
        $source = '';
        if ($root_dir == 'themes_root') {
            $source = get_theme_root() . "/";
        } elseif ($root_dir == 'upload_root') {
            $uploads = wp_get_upload_dir();
            $source = $uploads['basedir'] . "/";
        } elseif ($root_dir == 'plugin_root') {
            $source = WP_PLUGIN_DIR . "/";
        } elseif ($root_dir == 'dataBase') {
            return;
        } else {
           
            $source = ABSPATH; // get_home_path();
           
           
        }



        


        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);

        
        $doRangeCheck = false;
        $fromDate = null;
        $toDate = null;

        if (sizeof($date_range) > 0) {
            $doRangeCheck = true;
            $fromDate = strtotime($date_range['fromDate']);
            $toDate = strtotime($date_range['toDate']);
        }



        foreach ($files as $file) {
            $file = str_replace('\\', '/', $file);
            if (strpos(realpath($file), WPTIO_PLUGIN_NAME) || strpos(realpath($file), 'wp-config.php')) {
                continue;
            }
            if (is_file($file)) {
                $fileDate = strtotime(date("m/d/Y ", filemtime($file)));

                if (!$doRangeCheck || ($doRangeCheck && $fileDate >= $fromDate && $fileDate <= $toDate)) {
                    $wpdb->insert($table_name, array(
                        file_path => realpath($file),
                        file_size => filesize($file),
                        status => 0,
                        current_position => 0
                    ));
                    $file_num ++;
                    $total_size = $total_size + filesize($file);
                }
            }
        }



        return (object) array(
                    number_of_files => $file_num,
                    total_size => $total_size
        );
    }

    private function wptio_backup_files() {

        $uploads = wp_get_upload_dir();
        if (!is_dir($uploads['basedir'])) {
            mkdir($uploads['basedir']);
        }

        $backup_patch = get_option('wptio_backup_patch');
        $old_file_status = $backup_patch['status'];
        $backup_patch['status'] = 'compressing_files';
        update_option('wptio_backup_patch', $backup_patch);

        global $wpdb, $table_prefix;

        $table_name = $table_prefix . 'wptio_backup';

        $file_url = $wpdb->get_results(
                "
                    SELECT * FROM $table_name
                    order by id
                "
        );
        $zip = new ZipArchive();
        $backup_file_name = sprintf(WPTIO_SITE_NAME . "-wptio-files-backup-%s.zip", date("Ymdhis"));
        $zip->open($uploads['basedir'] . "/$backup_file_name", ZipArchive::CREATE);
        $file_size = (int) $file_size;

        $zip->addEmptyDir("wptio");
        $zip->open($uploads['basedir'] . "/$backup_file_name");
        $max_file_id = 0;
        $file_size_reached_tell_the_moment = 0;


        foreach ($file_url as $file) {

            if ($file->file_size > WPTIO_SIZE_LIMIT && $file_size_reached_tell_the_moment > 0) {
                // If the file is large and there are others have been added to the ZIP. 
                // Break the look to close the ZIP file before handling the large file
                // self::$partial_file_index = 0;
                break;
            } elseif ($file->file_size > WPTIO_SIZE_LIMIT) {
                // Handle The Large Single File


                $zip->close();
                unlink($uploads['basedir'] . "/$backup_file_name");
                $patch = get_option('wptio_create_patch');
                $patch['step'] = $patch['step'] + 1;
                update_option('wptio_create_patch', $patch);
                $step = get_option('wptio_create_patch');
                $backup_file_name = sprintf(WPTIO_SITE_NAME . "-wptio-files-backup-parts-%d-%d-%s.zip", $file->id, $step['step'], date("Ymdhis"));
                $zip->open($uploads['basedir'] . "/$backup_file_name", ZipArchive::CREATE);
                $zip->addEmptyDir("wptio");
                $zip->open($uploads['basedir'] . "/$backup_file_name");

                $current_position = $file->current_position;

                $fp = fopen($file->file_path, 'r');


                fseek($fp, $current_position);


                $data = fread($fp, WPTIO_SIZE_LIMIT);


                $temp_file_name = $uploads['basedir'] . '/wptio.json';
                $name = basename($file->file_path);
                $json_file_name = $uploads['basedir'] . '/' . $name . 'json';


                file_put_contents($json_file_name, $data, FILE_TEXT);

                fclose($fp);

                file_put_contents($temp_file_name, json_encode((object) array(
                                    filename => $file->file_path,
                                    current_position => $current_position
                        ))
                        , FILE_TEXT);


                $replace = str_replace('\\', '/', $file->file_path);
                $root = get_option('wptio_backup_patch');
                $path_in_zip_file = str_replace($root['root'], '', $replace);
                //$zip->addFile($temp_file_name);
                $zip->addFile($json_file_name, $path_in_zip_file);



                $current_position += WPTIO_SIZE_LIMIT;

                if ($current_position >= $file->file_size) {
                    $max_file_id = $file->id;
                }

                $wpdb->update($table_name, array(current_position => $current_position), array(id => $file->id));

                break;
            } else {
                // self::$partial_file_index = 0;
                // Small Files
                $file_size_reached_tell_the_moment += $file->file_size;
                $replace = str_replace('\\', '/', $file->file_path);

                $root = get_option('wptio_backup_patch');
                $path_in_zip_file = str_replace($root['root'], '', $replace);


                $zip->addFile($file->file_path, $path_in_zip_file);
                $max_file_id = $file->id;

                if ($file_size_reached_tell_the_moment > WPTIO_SIZE_LIMIT) {
                    break;
                }
            }
        }

        $zip->close();
        $file_path = $uploads['basedir'] . "/" . $backup_file_name;
        $this->wptio_move_file_to_cloud($file_path, $backup_file_name);
        unlink($uploads['basedir'] . "/" . $backup_file_name);
        unlink($temp_file_name);
        unlink($json_file_name);
        $wpdb->query($wpdb->prepare("delete from $table_name where id <= %d", $max_file_id));



        $backup_patch['current_files_step'] = $wpdb->get_var("SELECT (sum(file_size)-sum(current_position)) from $table_name");




        if ($wpdb->get_var("SELECT count(id) from $table_name") == 0) {
            $backup_patch['status'] = ($old_file_status == 'files_running_on_schedule' ? 'files_finished_on_schedule' : 'files_finished');
        } else {
            $backup_patch['status'] = $old_file_status;
        }

        update_option('wptio_backup_patch', $backup_patch);

        return (object) array(
                    result => 'ok',
                    backup_patch => (object) $backup_patch
        );
    }

    private function wptio_db_backup() {
        $backup_patch = get_option('wptio_backup_patch');

        if ($backup_patch['params']->type == 'partial' && $backup_patch['params']->database == false) {
            $backup_patch['status'] = 'finish_db_backup';
            update_option('wptio_backup_patch', $backup_patch);
            return;
        }

        $backup_patch['status'] = 'doing_db_backup';
        update_option('wptio_backup_patch', $backup_patch);



        global $wpdb, $table_prefix;
        $in_obj = 'Tables_in_' . DB_NAME;
        $uploads = wp_get_upload_dir();
        $tables = $wpdb->get_results("SHOW TABLES");
        $in_structure = 'Create Table';
        $obj_save = (object) array(
                    structure => array(),
                    data => array()
        );
        foreach ($tables as $table) {
            $table_name = $table->$in_obj;
            $obj_save->structure[$table_name] = $wpdb->get_row("SHOW CREATE TABLE $table_name")->$in_structure;
            $results = $wpdb->get_results("SELECT * FROM $table_name");
            if ($table_name == $table_prefix . 'options') {
                $results = $wpdb->get_results("SELECT * FROM $table_name where option_name not in ('wptio_client_info','wptio_backup_patch','wptio_restore_patch','wptio_create_patch','siteurl','home')");
            }
            foreach ($results as $row) {
                $row_array = (array) $row;
                $row_array_keys = array_keys($row_array);
                $row_array_values = array_values($row_array);
                $insert_query = sprintf('INSERT INTO %s (%s) VALUES (%s) ON DUPLICATE KEY UPDATE %s=%s'
                        , $table_name
                        , implode(',', $row_array_keys)
                        , implode(',', array_map(function($item) {
                                    return sprintf("'%s'", $item);
                                }, $row_array_values))
                        , $row_array_keys[0]
                        , $row_array_values[0]);

                $obj_save->data[$table_name][] = $insert_query;
            }
        }


        file_put_contents($uploads['basedir'] . '/wptio-db-backup.json', json_encode($obj_save), FILE_TEXT);
        $zip = new ZipArchive();
        $backup_filename = sprintf(WPTIO_SITE_NAME . "-wptio-db-backup-%s.zip", date("Ymdhis"));
        $zip->open($uploads['basedir'] . "/$backup_filename", ZipArchive::CREATE);
        //$ss= $zip->open($uploads['basedir'].'/db.json');
        $zip->addFile($uploads['basedir'] . '/wptio-db-backup.json', 'wptio-db-backup.json');
        $zip->close();
        unlink($uploads['basedir'] . '/wptio-db-backup.json');
        //print_r($uploads['basedir']);
        $path_file = $uploads['basedir'] . "/$backup_filename";
        $this->wptio_move_file_to_cloud($path_file, $backup_filename);
        unlink($path_file);
        $backup_patch['status'] = 'finish_db_backup';
        update_option('wptio_backup_patch', $backup_patch);

        return (object) array(
                    result => 'ok'
        );
    }

    // Move to another class

    private function wptio_move_file_to_cloud($file_path, $filename) {

        $backup_patch = get_option('wptio_backup_patch');
        $backup_session_id = $backup_patch['backup_session_id'];
        $connected_storage = get_option('wptio_connected_storage');
        $provider = sprintf($connected_storage['provider'] . '/?backup_session_id=%s&filename=%s', $backup_session_id, $filename);
        wptio_api_client::wptio_upload_file($provider, $file_path);
    }

    // Automaticcly called every 30 second
    function wptio_do_backup_step() {
        $backup_patch = get_option('wptio_backup_patch');

        if ($backup_patch == false || $backup_patch['status'] == 'compressing_files' || $backup_patch['status'] == 'doing_db_backup') {
            return (object) array(
                        result => 'ok',
                        backup_patch => (is_array($backup_patch) ? (object) $backup_patch : false)
            );
        } elseif ($backup_patch['status'] == 'patch_ready') {
            $this->wptio_start_buckup_step();
        } elseif ($backup_patch['status'] == 'files_running' || $backup_patch['status'] == 'files_running_on_schedule') {
            $this->wptio_backup_files();
        }

        if ($backup_patch['status'] == 'files_finished' || $backup_patch['status'] == 'files_finished_on_schedule') {
            $this->wptio_db_backup();
        }
        if ($backup_patch['status'] == 'finish_db_backup') {
            $this->wptio_clean_up();
            //delete_option('wptio_create_patch');
            delete_option('wptio_backup_patch');
        }

        $backup_patch = get_option('wptio_backup_patch');

        return (object) array(
                    result => 'ok',
                    backup_patch => $backup_patch
        );
    }

    function wptio_start_buckup_step($params) {
        $backup_patch = get_option('wptio_backup_patch');

        if ($backup_patch['status'] != 'patch_ready') {
            return (object) array(
                        result => 'ok',
                        backup_patch => (object) $backup_patch
            );
        }

        $backup_patch['status'] = 'files_running_on_schedule';
        update_option('wptio_backup_patch', $backup_patch);

        $backup_patch = get_option('wptio_backup_patch');
        return (object) array(
                    result => 'ok',
                    backup_patch => (object) $backup_patch
        );
    }

    function wptio_clean_up() {
        $backup_patch = get_option('wptio_backup_patch');
        $backup_session_id = $backup_patch['backup_session_id'];

        $provider = WPTIO_DISPATCH_URL . 'clear_session';

        wptio_api_client::wptio_send_request_form($provider, (object) array
                    (
                    backup_session_id => $backup_session_id,
        ));
        delete_option('wptio_backup_patch');
        delete_option('wptio_last_backup_time');
        delete_option('wptio_create_patch');
        $provider = get_option('wptio_connected_storage');
        add_option('wptio_last_backup_time', array(
            "date" => date('Y-m-d H:i:s'),
            "provider" => $provider['provider'],
        ));
    }

    function wptio_check_backup_status() {
        $backup_patch = get_option('wptio_backup_patch');
        global $wpdb, $table_prefix;

        $table_name = $table_prefix . 'wptio_backup';
        $the_count = $wpdb->get_var("SELECT COUNT(id) from $table_name");

        return (object) array(
                    result => 'ok',
                    backup_patch => $backup_patch,
                    the_count => $the_count
        );
    }

}
