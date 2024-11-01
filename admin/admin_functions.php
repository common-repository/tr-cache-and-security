<?php

if(!function_exists('tr_admin_list_page_posts')):
function tr_admin_list_page_posts($post_type='page')
{
    $defaults = array(
        'post_type'=>$post_type,
        'posts_per_page'=>300,
        'orderby'=>'post_title',
        'order' => 'desc'
	);
    $r = wp_parse_args( $args, $defaults );
	$pages = get_posts($r);
    $pagesarray = array();
    $pagesarray[0] = '---select---';
    foreach($pages as $p)
    {
        $pagesarray[$p->ID] =  $p->post_title;
    }
    return $pagesarray;
}
endif;

if(!function_exists('tr_admin_list_roles')):
function tr_admin_list_roles($rolekey = '')
{
    global $wp_roles;
    if ( ! isset( $wp_roles ) )
        $wp_roles = new WP_Roles();
    
    $account_types = array();
    foreach($wp_roles->roles as $key => $role)
    {
        $account_types[$key] = $role['name'];
    }
    if(!empty($rolekey))
    {
        return $account_types[$rolekey];
    }
    return $account_types;
}
endif;


if(!function_exists('tr_admin_list_sidebar_options')):
function tr_admin_list_sidebar_options()
{
    $sidebararray = array();
  global $wp_registered_sidebars;
  $sidebararray[0] = '---select---';
  foreach($wp_registered_sidebars as $name => $sidebar)
  {
    $sidebararray[$name] = $sidebar['name'];
  }
    return $sidebararray;
}
endif;

if(!function_exists('tr_admin_list_menu_options')):
function tr_admin_list_menu_options($getobj = false)
{
    global $wpdb;
    //list menu
  $custom_menu = array();
  
  $menus = $wpdb->get_results( "SELECT term_taxonomy_id,a.term_id,name FROM " . $wpdb->prefix . "term_taxonomy as a," . $wpdb->prefix . "terms as b WHERE a.taxonomy = 'nav_menu' AND a.term_id = b.term_id" );
  if($getobj)return $menus;
	if ( $menus ) {
		foreach( $menus as $menu ) {
			$custom_menu[ $menu->term_id ] = $menu->name;	
		}	
	}
    return $custom_menu;
}
endif;

if(!function_exists('tr_list_post_types')):
function tr_list_post_types()
{
    global $wpdb,$wp_post_types;
    $arr_post_break = array('revision','nav_menu_item','attachment');
    $post_types = array();
    foreach($wp_post_types as $key  => $pt)
    {
        if(in_array($key,$arr_post_break))continue;
        $text = $pt->labels->name;
        $post_types[$key] = $text;
    }
    return $post_types;
}
endif;

if(!function_exists('tr_admin_upload_size_limit')):

function tr_admin_upload_size_limit($limit,$u_bytes, $p_bytes )
{
    if($limit==0)$limit = 1024*10000;//10MB
    return $limit;
}
add_filter('upload_size_limit','tr_admin_upload_size_limit',10,3);
endif;