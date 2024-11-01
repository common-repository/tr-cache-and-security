<?php
/**
Plugin Name: TR Cache and Security
Plugin URI: http://ngoctrinh.net/
Description: Cache and security your site
Version: 1.2.3
Author: Trinh
Author URI: http://ngoctrinh.net/
License: GPL2
*/

define('TRSCSC_FILE', __FILE__);
define('TRSCSC_URL', plugins_url('/',__FILE__));
define('TRSCSC_PATH',plugin_dir_path(__FILE__).'/');
define('TRSCSC_CACHE_PATH',WP_CONTENT_DIR . '/cache/tr-cache');
define('TRSCSC_CACHE_JS','/cache/js');
define('TRSCSC_CACHE_CSS','/cache/css');
define('TRSCSC_SERVER','http://secu.ngoctrinh.net/wp-load.php');

include_once(TRSCSC_PATH.'admin/init.php');
include_once(TRSCSC_PATH.'inc/init.php');
