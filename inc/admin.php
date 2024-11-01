<?php
add_action('admin_init','tr_cache_security_admin_init');
add_action('save_post', 'tr_cache_invalidate_post', 0);
add_action('publish_post', 'tr_cache_invalidate_post', 0);
add_action('delete_post', 'tr_cache_invalidate_post', 0);
add_action('wp_set_comment_status','tr_cache_invalidate_comment',0,2);
add_action('wp_update_nav_menu','tr_cache_invalidate',0);
add_action('switch_theme', 'tr_cache_invalidate', 0);
add_filter('plugin_action_links', 'tr_cache_security_add_settings_link', 10, 2 );
add_action('pre_current_active_plugins','tr_cache_active_show_message');


$config = array(    
	'menu'=> array('top'=>'trcs_settings'),             //sub page to settings page
    'slug' => 'trcs_settings',
	'page_title' => 'Cache & Security',       //The name of this page 
    //'menu_title' => 'Cache & Security',
	'capability' => 'edit_themes',         // The capability needed to view the page 
	'option_group' => 'trcs_cache',       //the name of the option to create in the database
	'local_images' => true,          // Use local or hosted images (meta box images for add/remove)
   // 'icon_url' => TRJM_URL.'images/mobile.png',
	'use_with_theme' => false ,         //change path if used with theme set to true, false for a plugin or anything else for a custom path(default false).
    'path' => dirname(dirname(__FILE__)),
    'usebackup'     => true,
    'tabs' => array(
        'this'  => array('label'=>'Cache','menu'=>'Cache'),
        'trcs_security' => array('option'=>'tr_security','label'=>'Security','menu'=>true),
    )
);  
  
/**
* Initiate your admin page
*/
new TR_Admin_Page_Class_V6($config);



function tr_cache_security_admin_init()
{    
    if(@$_REQUEST['action']=='activate-plugin' && isset($_GET['success']) && isset($_GET['plugin']))
    {
        //upgrade plugin
        if($_GET['plugin']== plugin_basename(TRSCSC_FILE))
            tr_cache_security_activate();
    }
}


register_activation_hook(TRSCSC_FILE, 'tr_cache_security_activate');
function tr_cache_security_activate()
{
    include(TRSCSC_PATH.'inc/install.php');
}
register_deactivation_hook(TRSCSC_FILE, 'tr_cache_security_deactivate');
function tr_cache_security_deactivate()
{
    wp_clear_scheduled_hook('cache_security');

    // burn the file without delete it so one can rewrite it
    include_once(TRSCSC_PATH.'inc/actions.php');
    trwr_action_generate_config(true);
}




function tr_cache_active_show_message($plugins)
{
    if(get_option('_tr_cache_and_security_first')==1)return;
    ?>
    <div id="message" class="updated"><p><?php _e('Welcome to TR Cache and Security Plugin. <a href="admin.php?page=trcs_settings">You need go to here to config</a>'); ?></p></div>
    <?php
    update_option('_tr_cache_and_security_first',1);
}

function tr_cache_security_add_settings_link($links, $file) 
{
	if ($file == plugin_basename(TRSCSC_FILE)){
		$settings_link = '<a href="admin.php?page=trcs_settings">Settings</a>';
		array_unshift($links, $settings_link);
	}
	return $links;
}

function tr_cache_invalidate()
{
    global $tr_invalidated;
    if($tr_invalidated)return;
    @touch(TRSCSC_CACHE_PATH . '/_global.dat');
    $tr_invalidated = true;
}

function tr_cache_invalidate_post($post_id)
{
    global $tr_invalidated_post_id;
    if ($tr_invalidated_post_id == $post_id)
    {
        return;
    }
    if(false !== (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id))) { return; }
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return $post_id;
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
    $tr_invalidated_post_id = $post_id;
    
    @touch(TRSCSC_CACHE_PATH . '/_archives.dat');
}
function tr_cache_invalidate_comment($comment_id, $comment_status)
{
    if ( !$comment = get_comment($comment_id) )
		return false;
        
    $post_id = $comment->comment_post_ID;
    tr_cache_invalidate_post($post_id);
}

function trcache_alert_button_admin_save_buttons_area($html)
{
    wp_enqueue_script('thickbox');
    wp_enqueue_style('thickbox');
    
    $wp_htaccess_file = ABSPATH.'/.htaccess';
    $wp_config_file = ABSPATH.'/wp-config.php';
    if(!wp_is_writable($wp_config_file) || !wp_is_writable($wp_htaccess_file))
    $button = '<a class=" btn btn-warning thickbox" href="'.admin_url('admin-ajax.php').'?tr_action=need_fix_plugin&action=need_fix_plugin" style="margin-left:20px;background:red">Need Fix</a>';
    if(!empty($button))$html = $button.$html;
    return $html;
}