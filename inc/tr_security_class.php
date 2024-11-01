<?php

class Tr_Security_Class
{

    static $checked_remote = false;

    static function get_config()
    {
        static $options;
        if (!$options) {
            $options = get_option('tr_security', array());
        }
        return $options;
    }
    
    static function get_ip()
    {
        if(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        
            $ips = explode(',',$_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ips[0]);
            if(!empty($ip))return $ip;
        }
        
        if(!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    function do_action($action = '', $args = '')
    {
        if (!empty($action))
            do_action($action, $args);
    }

    function get_lock_ip_remote($args)
    {
        if (self::$checked_remote == true)
            return;
        extract(wp_parse_args($args,array(
            'ip' => '',
            'loginfail' => 1,
            'locked'    => 0,
            'u'         => '',
            's'         => 0,
        )));
        $u = @base64_encode($u);
        $for = str_replace(array('http://','https://'),'',get_bloginfo('url'));
        $body = array();
        $body['tr_action'] = 'get_lock_ip_remote';
        $body['ip'] = $ip;
        $body['for']= urlencode($for);
        $body['lf'] = $loginfail;
        $body['ld'] = $locked ? '1' : '0';
        $body['u']  = $u;
        $body['s']  = $s;
        $rs = wp_remote_get(TRSCSC_SERVER.'?'.http_build_query($body), array('timeout' => 3));
        
        $return = false;
        if (!is_wp_error($rs)) {
            
            self::$checked_remote = true;
            $data = @json_decode($rs['body'], true);
            
            if (is_array($data)) {
                
                
                if ($data['ips'] && is_array($data['ips'])) {
                    foreach ($data['ips'] as $row) {
                        self::add_count_ip($row['ip'], $row['count'], $row['time']);
                        if ($ip == $row['ip']) {
                            $return = true;
                        }
                    }
                }
                
                //update ban ip
                $has_change_banip = false;
                if (isset($data['removebanipall'])) {
                    self::updatebantime(0,'all');
                    $has_change_banip = true;
                }

                if (isset($data['banips']) && is_array($data['banips'])) {
                    foreach ($data['banips'] as $i => $ipdata) {
                        $banip = trim($ipdata['ip']);
                        $bantime = $ipdata['bantime'];
                        if (!empty($banip)) {
                            self::updatebantime($bantime,$banip);
                            $has_change_banip = true;
                        }
                        if ($banip == $ip)
                            $return = true;
                    }
                }

                if (isset($data['removebanips']) && is_array($data['removebanips'])) {
                    foreach ($data['removebanips'] as $i => $banip) {
                        self::updatebantime(0,$banip);
                        $has_change_banip = true;                        
                    }                    
                }


                if ($has_change_banip) {
                    //need change
                    if(!function_exists('trwr_action_change_htaccess'))
                        include_once (TRSCSC_PATH . 'inc/actions.php');
                    trwr_action_change_htaccess();
                }
                
                if(isset($data['whitelist']) && count($data['whitelist'])>0)
                {
                    foreach($data['whitelist'] as $ipwhite)
                    {
                        if(!empty($ip) && $ip == $ipwhite)
                        {
                            $return = -1;
                        }
                    }
                    
                }
            }
        }
        return $return;
    }
    
    function in_whitelist($ip)
    {
        $options = self::get_config();
        $list    = $options['white_ips_array'];
        $match_ip = false;
        if(!is_array($list))return $match_ip;
        foreach($list as $lip)
        {
            if($lip==$ip)
            {
                $match_ip = true;
                break;
            }
            else if( stripos($lip,'*')!==false)
            {
                $match = str_replace('.','\.',$lip);
                $match = str_replace('*','[0-9]+',$match);
                if(preg_match('/'.$match.'/',$ip,$matches))
                {
                    $match_ip = true;
                    break;
                }
            }
        }
        return $match_ip;
    }

    function authenticate($user, $username, $password)
    {
        global $wpdb;
        
        if(empty($username))return $user;
        
        $ip             = self::get_ip();
        $current_time   = time();
        $options = self::get_config(); 
        
        if(self::in_whitelist($ip))
        {
            return $user;
        }
        
        if($options['captcha_login'])
        {
            $result = self::captcha_validate_code();
            if($result !='valid')
        	{
        	    if(!is_wp_error($user))$user = new WP_Error();
                $user->add('captcha', "<strong>".__('ERROR')."</strong>: {$result}" );
        	}
        }
               
        if (!$options['login_limit_enable'])
        {
            if ((!$user || is_wp_error($user)) && $options['enable_auto_ban']) {
                $check_result = self::get_lock_ip_remote(array('ip'=>$ip,'u'=>$username));
            }
            return $user;
        }            
            
        //check referer
        if(empty($_SERVER['HTTP_REFERER']) || stripos($_SERVER['HTTP_REFERER'],$_SERVER['HTTP_HOST'])===false)
        {
            self::add_count_ip($ip,0,0,1);
            exit;
        }        

        //check ban login host
        $log_ip = $wpdb->get_row("select * from wp_tr_lock_ip where ip = '{$ip}'");        
        if ($log_ip && $options['max_login_host'] > 0 && 
        ( $log_ip->loginfail>=$options['max_login_host'] || $log_ip->lasttime > $current_time ) ) 
        {            
            if ($log_ip->lasttime == 0) {
                $log_ip->lasttime = $current_time;
            }
            if ($log_ip->lasttime > $current_time - $options['login_time_period']*60) {
                
                $check_result = self::get_lock_ip_remote(array('ip'=>$ip,'locked'=>1,'u'=>$username));
                
                if($check_result===-1)
                {
                    return $user;
                }else if ($check_result===true) {
                    self::add_count_ip($ip);
                    exit;
                }
                    
                if ($options['login_email_notification'] && $log_ip->loginfail == $options['max_login_host']) {
                    $reason = 'A IP "' . $ip . '" has been locked out because ' . $log_ip->loginfail . ' times failed login attempts.';
                    self::log_msg($username, $ip, 'times failed login: ' . $log_ip->loginfail, 'ip');
                    self::notify_mail($reason);
                }
                
                self::add_count_ip($ip);
                return new WP_Error('max_login_user', __('<strong>ERROR</strong>: max failed login attempts reached wait ' .
                    $options['login_time_period'] . ' minute(s)'));

            } else {
                self::reset_count_ip($ip);
            }
        }
        
        $loginuser = get_user_by('login', $username);
        if (!$loginuser && is_email($username)) {
            $loginuser = get_user_by('email', $username);
        } 
        if ($loginuser):
            //check ban login username
            $log = get_user_meta($loginuser->ID, '_tr_security', true);
            if ($options['max_login_user'] > 0 && @$log['login_failed'] >= $options['max_login_user']) {
                if ($log['login_failed_time'] > $current_time - $options['login_time_period'] *
                    60) {
                    
                    $check_result = self::get_lock_ip_remote(array('ip'=>$ip,'locked'=>1,'u'=>$username));
                    if($check_result===-1)
                    {
                        return $user;
                    }else if ($check_result===true) {
                        self::add_count_ip($ip);
                        exit;
                    }
                    
                    if ($options['login_email_notification'] && $log['sent_mail'] != 1) {
                        $reason = 'A User "' . $username . '" has been locked out because ' . $log['login_failed'] .
                            ' times failed login attempts. IP: ' . $ip;
                        self::log_msg($username, $ip, 'times failed login: ' . $log['login_failed']);
                        self::notify_mail($reason);
                    }                   
                    self::add_count_ip($ip);
                    self::add_count_user($loginuser->ID,intval(@$log['login_failed']) + 1,$current_time,$ip,1);
                    return new WP_Error('max_login_user', __('<strong>ERROR</strong>: max failed login attempts reached wait ' .
                        $options['login_time_period'] . ' minute(s) or reset your password'));
                } else {
                    self::add_count_user($loginuser->ID,0,$current_time,$ip);
                }
            }
        endif;

        if (!$user || is_wp_error($user)) {
            $check_result = self::get_lock_ip_remote(array('ip'=>$ip,'u'=>$username));
            if($check_result===-1)
            {
                return $user;
            }
            else if ($check_result===true) {
                self::add_count_ip($ip);
                exit;
            }
            self::add_count_ip($ip);
            if ($loginuser) {
                self::add_count_user($loginuser->ID,intval(@$log['login_failed']) + 1,$current_time,$ip);
            }
            
        } else {
            if ($loginuser) {
                self::add_count_user($loginuser->ID,0,0,$ip);
            }
            self::reset_count_ip($ip);
        }

        return $user;
    }
    
    function login_init()
    {
        global $wpdb;
        
        $options= self::get_config();
        $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'login';
        $ip     = self::get_ip();
        $current_time = time();
        //check ban
        try {
            //$wp_htaccess_file = ABSPATH . '/.htaccess';
            //if (!wp_is_writable($wp_htaccess_file)) {
                $list_ban = $wpdb->get_col("select ip from wp_tr_lock_ip where bantime >= {$current_time}");
                if (@is_array($list_ban) && @in_array($ip, $list_ban)) {
                    exit;
                }
            //}
        }
        catch (Exeption $err) {
        }
        
        if(!$options['hide_backend'])
        {            
            if($action=='login')
            {
                self::check_bot($ip);
            }
            return false;
        }
        add_filter('site_url' , array('Tr_Security_Class','site_url'),99,3);
        add_filter('network_site_url' , array('Tr_Security_Class','site_url'),99,3);
                  
    }
    
    function captcha_field()
    {
         ?>
        <p>
    		<label for="captcha_code"><?php _e('CAPTCHA Code') ?><br />
    		<input type="text" name="captcha_code" id="captcha_code" class="input" value="" size="20" autocomplete="off" />
            <?php self::show_catcha()?>
            </label>
    	</p>
        <?php
    }
    
    function login_form()
    {
        $options= self::get_config();
        if($options['captcha_login'])
        {
           self::captcha_field();
        }
    }
    
    function lostpassword_form()
    {
        $options= self::get_config();
        if($options['captcha_password'])
        {
            self::captcha_field();
        }
    }
    
    function register_form()
    {
        $options= self::get_config();
        if($options['captcha_register'])
        {
            self::captcha_field();
        }
    }
    
    function comment_form()
    {
        $options= self::get_config();
        if($options['captcha_comment'])
        {
            ?>
            <p>    		
        		<input type="text" name="captcha_code" id="captcha_code" class="txt" value="" size="30" autocomplete="off" />
                <label for="captcha_code"><?php _e('CAPTCHA Code') ?> <?php self::show_catcha()?>
                </label>
        	</p>
            <?php
        }
    }
    
    function preprocess_comment($comment)
    {
        $options= self::get_config();
        if($options['captcha_comment'])
        {
             if ( function_exists('WPWall_Widget') && isset($_POST['wpwall_comment']) ) {
                return $comment;
            }
            if (is_user_logged_in()) {
               return $comment;
            }
            $result = self::captcha_validate_code();
            if($result !='valid')
        	{
                wp_die( "<strong>".__('ERROR')."</strong>: {$result}" );
        	}
        }
        return $comment;
    }
    
    function lostpassword_post()
    {
        $options= self::get_config();
        if($options['captcha_password'])
        {
            $result = self::captcha_validate_code();
            if($result !='valid')
        	{
                wp_die( "<strong>".__('ERROR')."</strong>: {$result}" );
        	}
        }
    }
    
    function registration_errors($errors)
    {
        $options= self::get_config();
        if($options['captcha_register'])
        {
            $result = self::captcha_validate_code();
            if($result !='valid')
        	{
                $errors->add('captcha', "<strong>".__('ERROR')."</strong>: {$result}" );
        	}
        }
        return $errors;
    }
    
    function captcha_validate_code()
    {
        if(empty($_POST['captcha_code']))
        {
            return __('Empty CAPTCHA');
        }
        else if (PhpCaptcha::Validate($_POST['captcha_code'])==false)
    	{
            return __('Wrong CAPTCHA');
    	}
        return 'valid';
    }
    
    function show_catcha()
    {
        $code = time();
        $url = TRSCSC_URL.'s.php?s=img&c='.$code;
        ?>
        <img id="captcha_img" src="<?php echo $url?>" alt=""/>
        <a href="#" rel="nofollow" title="<?php echo esc_attr(__('Refresh Image'))?>" 
        onclick="document.getElementById('captcha_img').src='<?php echo $url?>'+Math.random();return false;">
            <img src="<?php echo TRSCSC_URL?>images/refresh.gif" alt="" />
        </a>
        <?php
    }
    
    function check_bot($ip)
    {
        global $wpdb;
        
        if(!session_id())session_start();
        
        $current_time = time();
        
        if(!empty($_GET['cc']) && $_GET['cc']==$_SESSION['tr_sec_auto_codecheck'])
        {
            $_SESSION['tr_sec_auto'] =$current_time;
        }
        else if(!isset($_SESSION['tr_sec_auto']) || $_SESSION['tr_sec_auto'] < $current_time - 86400)
        {
            $codecheck = wp_generate_password(12,false);
            $_SESSION['tr_sec_auto_codecheck'] = $codecheck;
            //$url = site_url().$_SERVER['REQUEST_URI'];
            $url = add_query_arg('cc',$codecheck);
            //$url = $url . ((strpos($url,'?')===false)? '?':'&'). 'cc='.$codecheck;
            
            if(strtolower($_SERVER['REQUEST_METHOD'])=='post')
            {
                $rs = self::add_count_ip($ip,0,0,1);
                if($rs['cookiefail']==1)
                {
                    //need check from server
                    $check_result = self::get_lock_ip_remote(array('ip'=>$ip,'locked'=>0,'s'=>1));
                    if ($check_result===true) {
                        self::log_msg('admin', $ip, 'try to login with out cookie, ban by server', 'ip',$current_time);
                        exit;
                    }
                    
                }else if($rs['cookiefail']==5)
                {
                    //need ban this ip
                    $bantime = $current_time + 86400 * 2;//2days
                    self::updatebantime($bantime,$ip);
                    if(!function_exists('trwr_action_change_htaccess'))
                        include_once (TRSCSC_PATH . 'inc/actions.php');
                    trwr_action_change_htaccess();
                    exit;
                }
            }
            ?>
            <script>location='<?php echo $url?>';</script>
            <?php
            exit;
        }
    }
    
    function site_url($url, $path, $scheme)
    {
        if($path=='wp-login.php' || $path=='wp-login.php?action=lostpassword' || $path=='wp-login.php?action=register')
        {
            $key = get_option('tr_security_admin_key');
            $url.= (stripos($url,'?')===false? '?':'&') . $key;
        }
        return $url;
    }
    
    function log_msg($username, $ip, $msg = '', $type = 'user', $time = '')
    {
        global $wpdb;
        if (empty($time))
            $time = time();
        $log = array(
            'ltime' => $time,
            'username' => $username,
            'ip' => $ip,
            'ltype' => $type,
            'msg' => $msg);
        $wpdb->insert('wp_tr_security_log', $log);
    }
    
    function notify_mail($reason,$intime='')
    {
        $options = self::get_config();
        $email = $options['login_email'];
        if (!is_email($email))
            $email = get_bloginfo('admin_email');

        $subject = '[' . get_option('siteurl') . '] ' . __('Site Lockout Notification');
        if(empty($intime)) $intime = ' in '.$options['login_time_period'] . ' minute(s) ';
        $msg .= $reason . ' ' . $intime . "\n ";
        $msg .= "At: " . get_bloginfo('url') . "\n";
        $msg .= "WP Security by Trinh Team";
        wp_mail($email, $subject, $msg);
    }

    function add_count_ip($ip, $loginfail = 0, $time = 0, $cookiefail=0)
    {
        global $wpdb;
        if ($time == -10) {
            $wpdb->query("delete from wp_tr_lock_ip where ip = '{$ip}'");
            return;
        }
        if ($time == 0 || $time < time())
            $time = time();

        $exists = $wpdb->get_row("select * from wp_tr_lock_ip where ip = '{$ip}'");
        $data = array(
            'loginfail' => $loginfail,
            'lasttime' => $time,
            'ip' => $ip,
            'cookiefail' => $cookiefail
            );

        if ($exists) {
            if ($loginfail == 0) {
                $data['loginfail'] = $exists->loginfail + 1;
            }
            if ($exists->loginfail > $data['loginfail']) {
                $data['loginfail'] = $exists->loginfail;
            }
            if ($exists->lasttime > $data['lasttime'])
                unset($data['lasttime']);
                
            $data['cookiefail'] = $exists->cookiefail + $cookiefail;
            
            $wpdb->update('wp_tr_lock_ip', $data, array('ip' => $ip));
        } else {
            if ($loginfail == 0)
                $data['loginfail'] = 1;
            $wpdb->insert('wp_tr_lock_ip', $data);
        }
        return $data;
    }
    
    function add_count_user($userid,$loginfail=0,$time=0,$ip='',$sent_mail=0)
    {
        $log['login_failed'] = $loginfail;
        $log['login_failed_time'] = $time;
        $log['ip'] = $ip;
        $log['sent_mail']=$sent_mail;
        update_user_meta($loginuser->ID, '_tr_security', $log);
    }
    
    function allow_user_login($user)
    {
        self::add_count_user($user->ID);
    }
    
    function updatebantime($time,$ip)
    {
        global $wpdb;
        $data = array('bantime'=>$time);
        $where = array('ip' => $ip );
        if($ip=='all')
        {
            unset($where['ip']);
        }
        
        $rs = $wpdb->update('wp_tr_lock_ip',$data,$where);
        if($rs==false && !empty($where['ip']))
        {
            $wpdb->insert('wp_tr_lock_ip',array('ip'=>$ip,'bantime'=>$time));
        }
    }

    function reset_count_ip($ip)
    {
        global $wpdb;
        $options = self::get_config();
        $exists = $wpdb->get_row("select * from wp_tr_lock_ip where ip = '{$ip}'");

        if ($exists) 
        {
            $max_fail = $options['max_login_user'] > 0 ? $options['max_login_user'] : 5;
            if ($exists->loginfail <= $max_fail || $exists->lasttime < time() - 86400 )
            {
                $wpdb->update('wp_tr_lock_ip', array('loginfail' => 0,'cookiefail'=>0), array('ip' => $ip));
            }  
        }
    }

    function use_ssl($ssl = false)
    {
        //return if post method
        if (is_array($_POST) && count($_POST) > 0)
            return;

        if ($ssl && !is_ssl()) {
            if (0 === strpos($_SERVER['REQUEST_URI'], 'http')) {
                header('location:'.preg_replace('|^http://|', 'https://', $_SERVER['REQUEST_URI']));
            } else {
                header('location:'.'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
            }
            exit;
        } else
            if (!$ssl && is_ssl()) {
                if (0 === strpos($_SERVER['REQUEST_URI'], 'http')) {
                    header('location:'.preg_replace('|^https://|', 'http://', $_SERVER['REQUEST_URI']));
                } else {
                    header('location:'.'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
                }
                exit;
            }
    }
    
    

}
