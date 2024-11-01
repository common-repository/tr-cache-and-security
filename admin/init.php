<?php
if(!session_id())session_start();


spl_autoload_register(create_function('$classname',
'if(class_exists($classname))return;
    
    $dir = dirname(__FILE__);
    $classname = strtolower($classname);
    $filenames = array();
    $filenames[] = $dir . "/classes/" . $classname;
    $filenames[] = dirname($dir) . "/inc/" . $classname;

    foreach($filenames as $fn)
    {
        $full_path = $fn . ".php";

        if(is_file($full_path))
        {
            include_once $full_path;
            break;
        }
    }'));


if(!function_exists('tr_get_option'))
{
    function tr_get_option($key,$name,$default = false)
    {
        global $option_key_cache;
        if(isset($option_key_cache[$key]))
        {
            $data = $option_key_cache[$key];
        }else
        {
            $data = get_option($key);
        
            if(!is_array($option_key_cache))$option_key_cache= array();
            $option_key_cache[$key] = $data;
        }   
        
        return (isset($data[$name]))? $data[$name]: $default;
    }
}

if(is_admin())
{      
    @ini_set('post_max_size','50M');
    @ini_set('upload_max_filesize','50M');
    
    include_once(dirname(__FILE__)."/admin_functions.php");
    
    //custom admin.php
    $custom_admin = dirname(dirname(__FILE__)).'/inc/admin.php';
    
    if(is_file($custom_admin))
    {
        include_once($custom_admin);
    }
}