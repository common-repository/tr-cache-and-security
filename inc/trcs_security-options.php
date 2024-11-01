<?php
global $wpdb;

wp_enqueue_script('cache-admin',TRSCSC_URL.'js/admin.js',array('jquery'),true);
add_action('admin_saved_option_tr_security','admin_saved_option_tr_security');
add_filter('admin_save_buttons_area','trcache_alert_button_admin_save_buttons_area',11);
function admin_saved_option_tr_security($options_panel)
{    
    $options = get_option($options_panel->option_group);
    $options['white_ips_array'] = trcsc_validate_list_ip($options['white_ips']);
    update_option($options_panel->option_group,$options);
    
    include_once(TRSCSC_PATH.'inc/actions.php');
    trwr_action_generate_config();
}

function trcsc_validate_list_ip($ips)
{
    if(!is_array($ips))
    {
        $ips = explode("\n",$ips);
    }
    $return = array();
    
    foreach($ips as $ip)
    {
        $ip = trim($ip);
        $count = count(explode('.',$ip));
       
        if(empty($ip) || $count != 4)continue;
        $return[] = $ip;
    }
    return $return;
}


////----opens tabs
$options_panel->OpenTabs_container();
$options_panel->TabsListing(array(
    'links' => array(
    'status_tab' => __('Status'),
    'ban_tab' => __('Ban'),
    'login_tab' => __('Login'),
    'hide_backend_tab' => __('Hide Backend'),
    'ssl_tab' => __('SSL'),
    'captcha_tab' => __('Captcha'),
    'logs_tab' => __('Logs'),
    )
  ));  
  
$options_panel->OpenTab('status_tab'); 
$options_panel->Title("Status");  
$options_panel->addCustom('status',array('name'=>''));
$options_panel->CloseTab();


$options_panel->OpenTab('ban_tab'); 
$options_panel->Title("Ban");  
$options_panel->addCheckbox('enable_auto_ban',array('name'=>'Enable Auto Ban IP','target'=>'autoban','std'=>true,'desc'=>' Only disable when if it does not work'));
$options_panel->addCustom('autoban',array('name'=>'Auto Ban IP (IPs auto add if that is server try to login, DDos...)'));
$options_panel->addCheckbox('ban_users',array('name'=>'Enable Banned Users (Hosts and User Agents)','std'=>true,'target'=>'ban_host,ban_user_agents','desc'=>'Check this box to enable the banned users feature.'));

$mypip = Tr_Security_Class::get_ip();
$options_panel->addTextarea('white_ips',array('name'=>'White IPs','std'=>'','desc'=>'Add my IP: <a onclick="jQuery(\'#white_ips\').val(jQuery(\'#white_ips\').val()+\'\n'.$mypip.'\')">'.$mypip.'</a>'));

$options_panel->addTextarea('ban_host',array('name'=>'Ban Hosts','std'=>'','desc'=>'se the guidelines below to enter hosts that will not be allowed access to your site. Note you cannot ban yourself.<br>
    You may ban users by individual IP address or IP address range.<br>
    Individual IP addesses must be in IPV4 standard format (i.e. ###.###.###.###). Wildcards (*) are allowed to specify a range of ip addresses.<br>
    If using a wildcard (*) you must start with the right-most number in the ip field. For example ###.###.###.* and ###.###.*.* are permitted but ###.###.*.### is not.
    Lookup IP Address.<br>
    Enter only 1 IP address or 1 IP address range per line.
'));


$options_panel->addTextarea('ban_user_agents',array('name'=>'Ban User Agents','std'=>'','desc'=>'
Use the guidelines below to enter user agents that will not be allowed access to your site.<br>
Enter only 1 user agent per line.
'));
$options_panel->CloseTab();



$options_panel->OpenTab('login_tab'); 
$options_panel->Title("Login");  
$options_panel->addCheckbox('login_limit_enable',array('name'=>'Enable Login Limits','std'=>true,'desc'=>'Check this box to enable login limits on this site.'));
$options_panel->addText('max_login_user',array('name'=>'Max Login Attempts Per User','std'=>'10','desc'=>'The number of login attempts a user has before their username is locked out of the system. Note that this is different from hosts in case an attacker is using multiple computers. In addition, if they are using your login name you could be locked out yourself'));
$options_panel->addText('max_login_host',array('name'=>'Max Login Attempts Per Host','std'=>'5','desc'=>'The number of login attempts a user has before their host or computer is locked out of the system.'));
$options_panel->addText('login_time_period',array('name'=>'Login Time Period (minutes)','std'=>'30','desc'=>'The number of minutes in which bad logins should be remembered.'));
$options_panel->addCheckbox('login_email_notification',array('name'=>'Email Notifications','std'=>false,'target'=>'login_email','desc'=>'Enabling this feature will trigger an email to be sent to the specified email address whenever a  user is locked out of the system.'));
$options_panel->addText('login_email',array('name'=>'Email Address','std'=>get_bloginfo('admin_email'),'desc'=>'The email address lockout notifications will be sent to.'));
$options_panel->CloseTab();


$options_panel->OpenTab('hide_backend_tab'); 
$options_panel->Title("Hide Backend");
$options_panel->addCheckbox('hide_backend',array('name'=>'Enable Hide Backend','std'=>false,'target'=>'login_slug,register_slug,admin_slug','desc'=>'Check this box to enable the hide backend.'));
$options_panel->addText('login_slug',array('name'=>'Login Slug','std'=>'adminlogin','desc'=>'Login URL: '.get_bloginfo('url').'/login'));
$options_panel->addText('register_slug',array('name'=>'Register Slug','std'=>'adminregister','desc'=>'Login URL: '.get_bloginfo('url').'/register'));
$options_panel->addText('admin_slug',array('name'=>'Admin Slug','std'=>'adminpanel','desc'=>'Login URL: '.get_bloginfo('url').'/adminpanel'));
$options_panel->CloseTab();


$https_url = get_bloginfo('url');
$https_url = str_replace('http://','https://',$https_url);

$options_panel->OpenTab('ssl_tab'); 
$options_panel->Title("SSL");  
$options_panel->addParagraph("Please <a target=_blank href='{$https_url}'>click here</a> to make sure your server support SSL before enable SSL");
$options_panel->addCheckbox('front_ssl',array('name'=>'Enforce Front end SSL','std'=>false,'desc'=>'Forces all of the WordPress frontend to be served only over a secure SSL connection.'));
$options_panel->addCheckbox('login_ssl',array('name'=>'Enforce Login SSL','std'=>false,'desc'=>'Forces all logins to be served only over a secure SSL connection.'));
$options_panel->addCheckbox('admin_ssl',array('name'=>'Enforce Admin SSL','std'=>false,'desc'=>'Forces all of the WordPress backend to be served only over a secure SSL connection.'));
$options_panel->CloseTab();

$options_panel->OpenTab('captcha_tab'); 
$options_panel->Title("Captcha");  
$options_panel->addCheckbox('captcha_login',array('name'=>'Enable CAPTCHA on the login form','std'=>false));
$options_panel->addCheckbox('captcha_register',array('name'=>'Enable CAPTCHA on the register form','std'=>false));
$options_panel->addCheckbox('captcha_password',array('name'=>'Enable CAPTCHA on the lost password form','std'=>false));
$options_panel->addCheckbox('captcha_comment',array('name'=>'Enable CAPTCHA on the comment form','std'=>false));
$options_panel->CloseTab();


$options_panel->OpenTab('logs_tab'); 
$options_panel->Title("Logs");  
$options_panel->addCustom('logs',array('name'=>''));
$options_panel->CloseTab();



