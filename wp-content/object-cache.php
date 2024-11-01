<?php
if (!defined('ABSPATH')) 
    die();
    
if (!defined('TRSCSC_PATH')) {
    define('TRSCSC_PATH', WP_CONTENT_DIR . '/plugins/ci-cache-and-security/');
}

if (!file_exists(TRSCSC_PATH . 'inc/tr_wp_object_cache.php')) {
    if (!defined('WP_ADMIN')) { 
        require_once (ABSPATH . WPINC . '/cache.php');
    }
}else
{
    require_once TRSCSC_PATH . 'inc/tr_wp_object_cache.php';
    
    function wp_cache_add( $key, $data, $group = '', $expire = 0 ) {
    	global $wp_object_cache;
    
    	return $wp_object_cache->add( $key, $data, $group, (int) $expire );
    }
    
    function wp_cache_close() {
    	return true;
    }
    
    function wp_cache_decr( $key, $offset = 1, $group = '' ) {
    	global $wp_object_cache;
    
    	return $wp_object_cache->decr( $key, $offset, $group );
    }
    
    function wp_cache_delete($key, $group = '') {
    	global $wp_object_cache;
    
    	return $wp_object_cache->delete($key, $group);
    }
    
    function wp_cache_flush() {
    	global $wp_object_cache;
    
    	return $wp_object_cache->flush();
    }
    
    function wp_cache_get( $key, $group = '', $force = false, &$found = null ) {
    	global $wp_object_cache;
    
    	return $wp_object_cache->get( $key, $group, $force, $found );
    }
    
    function wp_cache_incr( $key, $offset = 1, $group = '' ) {
    	global $wp_object_cache;
    
    	return $wp_object_cache->incr( $key, $offset, $group );
    }
    
    function wp_cache_init() {
    	$GLOBALS['wp_object_cache'] = new TR_WP_Object_Cache();
    }
    
    function wp_cache_replace( $key, $data, $group = '', $expire = 0 ) {
    	global $wp_object_cache;
    
    	return $wp_object_cache->replace( $key, $data, $group, (int) $expire );
    }
    
    function wp_cache_set( $key, $data, $group = '', $expire = 0 ) {
    	global $wp_object_cache;
    
    	return $wp_object_cache->set( $key, $data, $group, (int) $expire );
    }
    
    function wp_cache_switch_to_blog( $blog_id ) {
    	global $wp_object_cache;
    
    	return $wp_object_cache->switch_to_blog( $blog_id );
    }
    
    function wp_cache_add_global_groups( $groups ) {
    	global $wp_object_cache;
    
    	return $wp_object_cache->add_global_groups( $groups );
    }
    
    function wp_cache_add_non_persistent_groups( $groups ) {
    	// Default cache doesn't persist so nothing to do here.
    	return;
    }
    
    function wp_cache_reset() {
    	_deprecated_function( __FUNCTION__, '3.5' );
    
    	global $wp_object_cache;
    
    	return $wp_object_cache->reset();
    }
}

