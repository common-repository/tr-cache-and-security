<?php
global $wpdb;
include_once (TRSCSC_PATH . 'inc/actions.php');


//clear cache
$timeout30 = 86400 * 30;//30days
$current_time   = time();
$cache_options  = get_option('trcs_cache');
$timeout = $cache_options['timeout']*60;
if ($timeout >0 && $cache_options['on'])
{
    tr_cache_clear_by_dir(TRSCSC_CACHE_PATH,$timeout,$current_time);
}

//clear cache js 
$path = WP_CONTENT_DIR .'/'. TRSCSC_CACHE_JS;
$timeout   = ($timeout < $timeout30)? $timeout30 : $timeout;
tr_cache_clear_by_dir($path,$timeout,$current_time);

//clear cache css
$path = WP_CONTENT_DIR .'/'. TRSCSC_CACHE_CSS;
$timeout   = ($timeout < $timeout30)? $timeout30 : $timeout;
tr_cache_clear_by_dir($path,$timeout,$current_time);

//end clear cache



//update security ban list
$need_update_htacess = false;
$secure_options = get_option('tr_security',array());
if($secure_options['enable_auto_ban'])
{
    $banipexp_count = $wpdb->get_var("select count(*) from wp_tr_lock_ip where bantime > 0 and bantime < {$current_time}");
    if(count($banipexp_count)>0)
    {
        $wpdb->query("update wp_tr_lock_ip set bantime=0 where bantime>0 and bantime<{$current_time}");
        
        $need_update_htacess = true;
    }
}

//update log cookie need ban somte time

$bantime = $current_time + 86400 * 5;
$rows = $wpdb->get_results("select * from wp_tr_lock_ip where cookiefail >= 5 ");
if(is_array($rows) && count($rows)>0)
{
    $reason_notify = '';
    $ips= array();
    foreach($rows as $row)
    {        
        $ips[] = $row->ip;
        Tr_Security_Class::log_msg('admin', $row->ip, 'try to login with out cookie: ' . 
                                    $row->cookiefail.' times.', 'ip',$current_time);
        
        $reason_notify .= 'A IP "' . $row->ip . '" has been banned because try login with out cookie in ' . 
                    $row->cookiefail . ' times.'."\n\n";
                        
        
        
        //reset cookiefail
        $bantime_row = $bantime;
        if($row->bantime > $bantime)
        {
            $bantime_row = $row->bantime;
        }
        $wpdb->update('wp_tr_lock_ip',array('cookiefail'=>0,'bantime'=>$bantime_row),array('ip'=>$row->ip));
        $need_update_htacess = true;
    }
    
    if(count($ips)>0)
    {
        $check_result = Tr_Security_Class::get_lock_ip_remote(array('ip'=>$ips,'locked'=>1,'s'=>1));
    }
    
    if($secure_options['login_email_notification'] && !empty($reason_notify))
    {
        Tr_Security_Class::notify_mail($reason_notify,'  ');
    }
        
}



if($need_update_htacess)
{    
    trwr_action_change_htaccess();
}
