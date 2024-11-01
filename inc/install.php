<?php
global $wpdb;

if(get_option('tr_security')===false)
{   
    //default security
    $config = array(    
    	'option_group' => 'tr_security',   
        'path' => dirname(dirname(__FILE__)),
    ); 
    $secu = new TR_Admin_Page_Class_V6($config);
    $secu->loadconfig('trcs_security');
    $secu->restore();
    
}

if(get_option('trcs_cache')===false)
{
    //default cache
    $config = array(    
    	'option_group' => 'trcs_cache',   
        'path' => dirname(dirname(__FILE__)),
    );  
    $secu = new TR_Admin_Page_Class_V6($config);
    $secu->loadconfig('trcs_cache');
    $secu->restore();
}


wp_clear_scheduled_hook('cache_security');
wp_schedule_event(time()+300, 'hourly', 'cache_security');

if(!@opendir(TRSCSC_CACHE_PATH))
    wp_mkdir_p(TRSCSC_CACHE_PATH);

include_once(TRSCSC_PATH.'inc/actions.php');
trwr_action_generate_config();


require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
//create tables


$tbl_fields = 'wp_tr_lock_ip';

$sql_fields = "CREATE TABLE `{$tbl_fields}` (
  `ip` varchar(50) DEFAULT NULL,
  `loginfail` int(11) default 0,
  `cookiefail` int(11) default 0,
  `lasttime` int(11) default 0,
  `bantime` int(11) default 0  
) ENGINE=InnoDB ;";

$rs = @dbDelta($sql_fields);

//add primary key for ip
$rs = $wpdb->get_row("show columns from {$tbl_fields} where field='ip'");
if($rs->Key!='PRI')
{
    $wpdb->query("ALTER TABLE {$tbl_fields} ADD PRIMARY KEY(`ip`)");
}

$old_name = $wpdb->prefix . 'tr_security_log';
$new_name = 'wp_tr_security_log';
if($old_name != $new_name)
{
    @dbDelta("RENAME TABLE {$old_name} TO {$new_name}");
}

$sql_fields = "CREATE TABLE `{$new_name}` (
  `msg` text,
  `username` varchar(50),
  `ip` varchar(50),
  `ltype` varchar(10) not null,
  `ltime` int(11)
) ENGINE=InnoDB ;";

$rs = dbDelta($sql_fields);
