<?php
$action = isset($_REQUEST['tr_action'])? $_REQUEST['tr_action'] : '';
$function_action = 'trwr_action_'.$action;
if(function_exists($function_action))
{
    call_user_func($function_action);
}

function tr_security_can_write_file($file)
{
    $can_write = false;  
    $filetime = @filemtime($file);
    if($filetime && $filetime > time() - 5)
    {
        return false;
    }
    
    if(!file_exists($file))
    {
        $f = @fopen( $file, 'a' );
    	$can_write = ( $f !== false );
    	@fclose( $f );
        @unlink( $file );
    } 
    
    if ($can_write==true || wp_is_writable($file) ) {
		$can_write  = true;        
	}else
    {
        @chmod( $file, 0644 );
        if (wp_is_writable($file) ) {
            $can_write  = true; 
        }
    }    
    
    if($can_write)
    {        
        @touch($file);
    }
    
    return $can_write;
}

function trwr_action_need_fix_plugin()
{
    include_once(TRSCSC_PATH.'inc/need_fix_plugin.php');
    exit;
}

function trwr_action_removeban()
{
    $ip = $_POST['ip'];
    Tr_Security_Class::updatebantime(0,$ip);
    trwr_action_change_htaccess();
    echo 'ok';exit;
}

function trwr_action_clear_cache($path='')
{
    if(empty($path))
    {
        $path = TRSCSC_CACHE_PATH;
    }
    tr_cache_clear_by_dir($path,0,time() + 999999);
    
    //clear cache js 
    $path = WP_CONTENT_DIR .'/'. TRSCSC_CACHE_JS;
    tr_cache_clear_by_dir($path,0,time() + 999999);
    
    $path = WP_CONTENT_DIR .'/'. TRSCSC_CACHE_CSS;
    tr_cache_clear_by_dir($path,0,time() + 999999);
    
    
    echo 'ok';
    exit();
}

function tr_cache_clear_by_dir($path,$timeout,$current_time)
{
    $handle = @opendir($path);
    if ($handle) {
        while ($file = readdir($handle)) {
            if ($file == '.' || $file == '..' || $file[0] == '_') continue;
    
            $t = @filemtime($path . '/' . $file);
            if ($current_time - $t > $timeout ) {
                @unlink($path . '/' . $file);
            }
        }
    }
    @closedir($handle);
}


function trwr_action_generate_config($uninstall=false)
{
    global $saved_trwr_action_generate_config;
    if($saved_trwr_action_generate_config===true)return;
    $saved_trwr_action_generate_config  = true;
    
    //change wp-config.php    
    @trwr_action_change_config_file(false,$uninstall);
    
    //change .htaccess 
    @trwr_action_change_htaccess(false,$uninstall);
   
    @trwr_action_change_advanced_cache(false,$uninstall);
    //change advanced-cache.php   
}

function trwr_action_change_config_file($onlycontent=false,$uninstall=false)
{
    $cache_options  = get_option('trcs_cache',array());
    $secure_options = get_option('tr_security',array());
    $secure_options['wp_cache'] = $cache_options['on'];
    
    $need = '';
    $begin = "// BEGIN CACHE SECURITY";
    $end = "// END CACHE SECURITY";
    $defines = array(
        'admin_ssl' => 'FORCE_SSL_ADMIN',
        'login_ssl' => 'FORCE_SSL_LOGIN',
        'front_ssl' => 'FORCE_SSL_FRONT',
        'wp_cache'  => 'WP_CACHE',
        //'login_limit_enable' => 'LOGIN_LIMIT'
    );        
    foreach($defines as $k =>$vl)
    {
        if($secure_options[$k] && strpos($data,$vl)===false)
        {
            $need.= "define( '{$vl}', true );\n";
        }
    } 
    if(!empty($need))
    {
        $need = $begin ."\n". $need .$end ."\n";
    }
    if($onlycontent) return $need;
    
    $wp_config_file = ABSPATH.'/wp-config.php';
    
    if(tr_security_can_write_file($wp_config_file))
    {
        if($uninstall==true)$need = '';
        $data = @file_get_contents($wp_config_file);
        if($data)
        {
            $old_data = $data;
            if(strpos($data,$begin)===false)
            {
                $data = str_replace('<?php','<?php'."\n".$need,$data);
            }else
            {
                $data = preg_replace("%".$begin."(.*)".$end."%s",$need,$data);
            }
            if(strlen($old_data) != strlen($data) && !empty($data))
            {            
                $rs   = file_put_contents($wp_config_file,$data);            
            }
        }    
        
    }
}

function trwr_action_change_htaccess($onlycontent=false, $uninstall=false)
{
    global $wpdb;
    
    $current_time = time();
    
    if ( strstr( strtolower( filter_var( $_SERVER['SERVER_SOFTWARE'], FILTER_SANITIZE_STRING ) ), 'apache' ) ) {
			
		$bwpsserver = 'apache';
		
	} else if ( strstr( strtolower( filter_var( $_SERVER['SERVER_SOFTWARE'], FILTER_SANITIZE_STRING ) ), 'nginx' ) ) {
	
		$bwpsserver = 'nginx';
		
	} else if ( strstr( strtolower( filter_var( $_SERVER['SERVER_SOFTWARE'], FILTER_SANITIZE_STRING ) ), 'litespeed' ) ) {
	
		$bwpsserver = 'litespeed';
		
	} else { //unsupported server
	
		return false;
	
	}
    
    
    $cache_options  = get_option('trcs_cache',array());
    $secure_options = get_option('tr_security',array());
    
    $need = 'IndexIgnore *'."\n";
    $need_rewrite = '';
    $begin = "# BEGIN CACHE SECURITY";
    $end = "# END CACHE SECURITY";
    if ( $bwpsserver == 'apache' || $bwpsserver == 'litespeed' ) {
        $before_rewrite.= "<IfModule mod_rewrite.c>\nRewriteEngine On".PHP_EOL;
    }else
    {
        $before_rewrite.= "\tset \$susquery 0;" . PHP_EOL .
						"\tset \$rule_2 0;" . PHP_EOL .
						"\tset \$rule_3 0;" . PHP_EOL;
    }
    $end_rewrite.="</IfModule>\n";
    
    $secure_options['ban_host'] = str_replace(array(' ',"\r"),'',$secure_options['ban_host']);
    $host_rows = array();
    if(!empty($secure_options['ban_host']))
    {
        $host_rows = explode("\n", $secure_options['ban_host'] );
    }
    
    //ban host
    if( $secure_options['ban_users'] && count($host_rows)>0 )
    {
        if ( $bwpsserver == 'apache' || $bwpsserver == 'litespeed' ) {
            $need .="Order Allow,Deny\nDeny from env=DenyAccess\nAllow from all\n";
        }
        foreach($host_rows as $row)
        {
            $row = trim($row);
            if(empty($row))continue;            
            
            if ( $bwpsserver == 'apache' || $bwpsserver == 'litespeed' ) {
                $par = "^{$row}$";
                $par = str_replace(array('.','*'),array('\.','[0-9]+'),$par);
                $need.= "SetEnvIF REMOTE_ADDR \"{$par}\" DenyAccess\n";
                $need.= "SetEnvIF X-FORWARDED-FOR \"{$par}\" DenyAccess\n";
                $need.= "SetEnvIF X-CLUSTER-CLIENT-IP \"{$par}\" DenyAccess\n";
            }else
            {
                $need .= "\tdeny ".$row .";".PHP_EOL;
            }
        }
    }
    
    //auto ban
    if($secure_options['enable_auto_ban'])
    {
        $auto_ban_ips = $wpdb->get_col("select ip from wp_tr_lock_ip where bantime >= {$current_time}");
        if( is_array($auto_ban_ips) && count($auto_ban_ips)>0)
        {
            if ( $bwpsserver == 'apache' || $bwpsserver == 'litespeed' ) {
                $need .="Order Allow,Deny\nAllow from all".PHP_EOL;
            }
            foreach($auto_ban_ips as $banip)
            {
                if(!in_array($banip,$host_rows))
                {
                    if ( $bwpsserver == 'apache' || $bwpsserver == 'litespeed' ) {
                        $need .= 'Deny from '.$banip .PHP_EOL;
                    }else
                    {
                        $need .= "\tdeny ".$banip .";".PHP_EOL;
                    }
                }
            }
        }
    }
    
    //ban user agents
    $secure_options['ban_user_agents'] = trim($secure_options['ban_user_agents']);
    $ban_user_agents = explode("\n", $secure_options['ban_user_agents'] );
    $count_ban_agents = count($ban_user_agents);
    if( $secure_options['ban_users'] && $count_ban_agents>0 && !empty($secure_options['ban_user_agents']))
    {        
        $count = 1;
        $user_agents = '';
        $agents_list = array();
        foreach($ban_user_agents as  $row)
        {
            $row = trim($row);
            if(empty($row))continue;
            
            if ( $bwpsserver == 'apache' || $bwpsserver == 'litespeed' ) {
                $nc = ($count<$count_ban_agents)? '[NC,OR]':'[NC]';
                $user_agents.= "RewriteCond %{HTTP_USER_AGENT} ^".$row." ".$nc."\n";
                $count++;
            }else
            {
                $agents_list[] = $row;
            }
        }
        if(!empty($user_agents))
        {
            $user_agents.= "RewriteRule ^(.*)$ - [F,L]\n";
            $need_rewrite.= $user_agents;
        }else if(count($agents_list)>0)
        {
            $agents_list = implode('|',$agents_list);
            $need_rewrite.= "\tif (\$http_user_agent ~* " . $agents_list . ") { return 403; }" . PHP_EOL;
        }
    }
    
    //hide admin
    if($secure_options['hide_backend'])
    {
        $siteurl = explode( '/', get_option( 'siteurl' ) );

		if ( isset ( $siteurl[3] ) ) {

			$dir = '/' . $siteurl[3] . '/';

		} else {

			$dir = '/';

		}
        $key = get_option('tr_security_admin_key');
        if(!$key)
        {
            $key = wp_generate_password(12,false);
            update_option('tr_security_admin_key',$key); 
        }
        
        $reDomain = '(.*)';
        $login = $secure_options['login_slug'];
        $admin = $secure_options['admin_slug'];
        $register = $secure_options['register_slug'];
        
        if ( $bwpsserver == 'apache' || $bwpsserver == 'litespeed' ) {
            $need_rewrite .= "RewriteRule ^" . $login . "/?$ " . $dir . "wp-login.php?" . $key . " [R,L]" . PHP_EOL . PHP_EOL .
			"RewriteCond %{HTTP_COOKIE} !^.*wordpress_logged_in_.*$" . PHP_EOL .
			"RewriteRule ^" . $admin . "/?$ " . $dir . "wp-login.php?" . $key . "&redirect_to=" . $dir . "wp-admin/ [R,L]" . PHP_EOL . PHP_EOL .
			"RewriteRule ^" . $admin . "/?$ " . $dir . "wp-admin/?" . $key . " [R,L]" . PHP_EOL . PHP_EOL .
			"RewriteRule ^" . $register . "/?$ " . $dir . "wp-login.php?" . $key . "&action=register [R,L]" . PHP_EOL .
            "RewriteCond %{QUERY_STRING} !" . $key . PHP_EOL .
            "RewriteCond %{QUERY_STRING} !^action=logout" . PHP_EOL .
			"RewriteCond %{QUERY_STRING} !^action=rp" . PHP_EOL .
			"RewriteCond %{QUERY_STRING} !^action=postpass" . PHP_EOL .    
            "RewriteCond %{QUERY_STRING} !^action=resetpass" . PHP_EOL .
            "RewriteCond %{QUERY_STRING} !^checkemail=" . PHP_EOL .                     
			"RewriteCond %{HTTP_COOKIE} !^.*wordpress_logged_in_.*$" . PHP_EOL .
			"RewriteRule ^.*wp-login\.php$ " . $dir . " [R,L]" . PHP_EOL . PHP_EOL;
        }else
        {
            $need_rewrite .= "\trewrite ^" . $dir . $login . "/?$ " . $dir . "wp-login.php?" . $key . " redirect;" . PHP_EOL .
						"\tif (\$rule_2 = 1) { rewrite ^" . $dir . $admin . "/?$ " . $dir . "wp-login.php?" . $key . "&redirect_to=/wp-admin/ redirect; }" . PHP_EOL .
						"\tif (\$rule_2 = 0) { rewrite ^" . $dir . $admin . "/?$ " . $dir . "wp-admin/?" . $key . " redirect; }" . PHP_EOL .
						"\trewrite ^" . $dir . $register . "/?$ " . $dir . "wp-login.php?" . $key . "&action=register redirect;" . PHP_EOL .
					
						"\tif (\$args !~ \"^" . $key . "\") {" . PHP_EOL .
						"\t\trewrite ^(.*/)?wp-login.php " . $dir . " redirect;" . PHP_EOL .
						"\t}" . PHP_EOL;
        }
    }
    
    if(!empty($need_rewrite))
    {
        $need .= $before_rewrite . $need_rewrite . $end_rewrite;
    }
    
    
    
    //cache cache_media
    if($cache_options['cache_media'])
    {
        $need.="# 1 WEEK\n<FilesMatch \"\.(jpg|jpeg|png|gif|swf|ico)$\">\n";
        $need.="Header set Cache-Control \"max-age=604800, public\"\n";
        $need.="</FilesMatch>\n";
        
        $need.="# 1 WEEK\n<FilesMatch \"\.(xml|txt|css|js)$\">\n";
        $need.="Header set Cache-Control \"max-age=604800, public\"\n";
        $need.="</FilesMatch>\n";
    }
    
    
    
    //check empty
    if(!empty($need))
    {
        $need = $begin."\n".$need."\n".$end;
    }
    if($onlycontent) return $need;
    
    $wp_htaccess_file = ABSPATH.'/.htaccess';
    if(tr_security_can_write_file($wp_htaccess_file))
    {
        if($uninstall==true)$need = '';
        
        $data = @file_get_contents($wp_htaccess_file);
       
        if($data!==false || !file_exists($wp_htaccess_file))
        {
            $old_data = $data;
            
            if(strpos($data,$begin)===false)
            {
                $data = $need."\n".$data;
            }else
            {
                $begin = str_replace('#','\#',$begin);
                $end = str_replace('#','\#',$end);
                $data = preg_replace("%".$begin."(.*)".$end."%s",$need,$data);
            }
            
            if($old_data != $data)
            {            
                $rs   = @file_put_contents($wp_htaccess_file,$data);   
                if(function_exists('flush_rewrite_rules'))
                {
                    flush_rewrite_rules();
                }         
            }
        }
    }
}

function trwr_action_change_advanced_cache($onlycontent=false, $uninstall=false)
{
    $cache_options  = get_option('trcs_cache',array());   
      
        
    $cache_args = array();
    if($cache_options['on'] && $uninstall==false)
    {
        $plugin_slug = basename(dirname(TRSCSC_FILE));
        
        foreach($cache_options as $k => $vl)
        {            
            if($k == 'reject_agents')
            {
                $vl = explode("\n",$vl);
                $vl = implode('","',$vl);
                $vl = 'array("'.$vl.'")';
                $vl = str_replace(array("\n","\r"),'',$vl);
            }
            else if(is_string($vl) || empty($vl))$vl = '"'.$vl.'"';
            $cache_args[] = '"'.$k.'" => '.$vl;
        }
        $cache_args = implode(', ',$cache_args);
        $need = "<?php\n";
        $need .= apply_filters('cache_advance_content','');
        $need .= '$tr_cache_path = \'' . TRSCSC_CACHE_PATH . '/\'' . ";\n";
        $need .= '$tr_cache_options = array(' . $cache_args. ");\n";
        
        $need .= "include(WP_CONTENT_DIR . '/plugins/{$plugin_slug}/cache.php');\n";
    }else
    {
        $need = '';   
    }
    
    if($onlycontent)
    {
        return $need;
    }
    
    $file_cache = WP_CONTENT_DIR . '/advanced-cache.php';
    
    if(tr_security_can_write_file($file_cache))
    {
        @file_put_contents ($file_cache,$need);   
        @chmod( $file_cache, 0444 );  
    }
}