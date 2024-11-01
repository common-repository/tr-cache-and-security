<?php
add_action('init','tr_cache_plugin_init');
add_action('cache_security', 'tr_cache_security_clean');
add_action('wp_insert_comment','tr_wp_insert_comment',10,2);
add_action('validate_password_reset','tr_validate_password_reset',10,2);
add_action('save_post', 'tr_wp_insert_comment', 10,1);



function tr_cache_plugin_init()
{    
    if(isset($_REQUEST['tr_action']))
    {
        include_once(TRSCSC_PATH.'inc/actions.php');
    }
    if(!is_admin())
    {        
        if(!is_user_logged_in())
            add_filter( 'show_admin_bar', '__return_false' ,99); 
            
        global $tr_cache_options;
        if(!is_array($tr_cache_options))
            $tr_cache_options = get_option('trcs_cache',array());
            
        if($tr_cache_options['optimize_js'] || $tr_cache_options['optimaze_css'])
        {
            $cache_obj = Tr_Cache_Class::instance();
            if(!Tr_Cache_Class::$has_run)
                add_action('template_redirect',array(&$cache_obj,'template_redirect'),1);
        }
        
    }
}

function tr_cache_security_clean()
{
    include(TRSCSC_PATH.'inc/cache_security_clean.php');
}

function tr_wp_insert_comment($id, $comment='')
{
    if($comment)
    {
        $post_id = $comment->comment_post_ID;
    }else
    {
        $post_id = $id;
    }
    
    $link = get_permalink($post_id);
    $link = preg_replace( '~^.*?://~', '', $link );
    $file = md5($link);
    $filename = $file.'.dat';
    $mobilefile = $file.'_mobile.dat';
    @unlink(TRSCSC_CACHE_PATH.'/'.$filename);
    @unlink(TRSCSC_CACHE_PATH.'/'.$mobilefile);
    @unlink(TRSCSC_CACHE_PATH.'/'.$filename.'s');
    @unlink(TRSCSC_CACHE_PATH.'/'.$mobilefile.'s');
    
    @touch(TRSCSC_CACHE_PATH . '/_archives.dat');
    
}

function tr_validate_password_reset($errors, $user)
{
    if($errors->get_error_code()=='')
    {
        Tr_Security_Class::allow_user_login($user);
    }
}

if(!function_exists('wp_is_writable'))
{
    function wp_is_writable( $path ) {
    	if ( 'WIN' === strtoupper( substr( PHP_OS, 0, 3 ) ) )
    		return win_is_writable( $path );
    	else
    		return @is_writable( $path );
    }
}

include(TRSCSC_PATH.'inc/secure.php');

