<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
if (!defined('ABSPATH')) {
    exit;
}
define("WPTIO_SIZE_LIMIT", 50000000);
define("WPTIO_VERSION", '1.2.2');

define("WPTIO_API_REQ_VERSION", "");
define("WPTIO_API_RES_VERSION", "");
define(WPTIO_UPLOAD_URL, 'https://api.wptools.io/backup/upload/');
define(WPTIO_PATCH_URL, 'https://api.wptools.io/backup/patch/');
define(WPTIO_CONNECT_URL, 'https://api.wptools.io/backup/connect/');
define(WPTIO_RESTORE_URL, 'https://api.wptools.io/backup/restore/');
define(WPTIO_DISPATCH_URL, 'https://api.wptools.io/backup/dispatch/');
define(WPTIO_ACTIVATION_URL, 'https://api.wptools.io/backup/license/');
define(WPTIO_GENERATE_TOKEN, 'https://api.wptools.io/backup/accesstoken/');
define(WPTIO_DOWNLOAD_URL, 'https://api.wptools.io/backup/downloadurl/');

