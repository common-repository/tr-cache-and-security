<?php

class Tr_Cache_Class
{
    static private $instance = null;
    
    static $scripts = array();
    static $styles  = array();
    static $script_file = 'cache/site_script.js';
    static $style_file  = 'cache/site_css.css';
    static $option = array();
    static $needupdate = false;
    static $has_run = false;
    
    
    public function __construct()
    {
        if (function_exists('domain_mapping_siteurl')) {
    		define('TR_WP_SITE_URL',domain_mapping_siteurl());
    		define('TR_WP_CONTENT_URL',str_replace(get_original_url(TR_WP_SITE_URL),TR_WP_SITE_URL,content_url()));
    	} else {
    		define('TR_WP_SITE_URL',site_url());
    		define('TR_WP_CONTENT_URL',content_url());
    	}
        self::$option = get_option('tr_cache_optimize_files',array());
    }
    
    public static function instance()
    {
        if(self::$instance == null)
        {
            self::$instance = new Tr_Cache_Class();
        }
        return self::$instance;
    }
    
    public function template_redirect()
    {
        ob_start(array(&$this,'autoptimize_end'));
    }
    
    public function autoptimize_end($content)
    {
        global $tr_cache_options;
        if(self::$has_run)return $content;
        $starttime = microtime(true);
        if($tr_cache_options['optimize_js'])
        {
            $content = $this->_optimize_js($content);
        }
        if($tr_cache_options['optimize_css'])
        {
            $content = $this->_optimize_css($content);
        }
        self::$has_run= true;
        $content .= '<!-- optimize in ' . (microtime(true) - $starttime) .' -->';
        return $content;
    }
    
    function _optimize_js($content)
    {
        global $tr_cache_options;
        if(preg_match_all('/\<script[^\>]*src\=[\'|\"]?([^\'|^\"]*\.js)[\'|\"]?[^\<]*\<\/script\>/',$content,$matches))
        {
            $time_string = '';
            foreach($matches[0] as $i => $m)
            {
                $url     = $matches[1][$i];
                if(strpos($url,'/jquery.js')!==false || strpos($url,'/admin-bar.min.js')!==false)continue;
                
                $path    = $this->getpath($url);
                if(!$path)continue;
                
                $content = str_replace($m,'',$content);
                
                self::$scripts[] = $path;
                $time_string         .= filemtime($path).'|';
            }
            
            $file_md5    = TRSCSC_CACHE_JS.'/'. md5($time_string) . '.js';
            $file_path   = WP_CONTENT_DIR.'/'.$file_md5;
            if(!empty($time_string))
            {
                if(!file_exists($file_path))
                {
                    $data     = '';
                    foreach(self::$scripts as $handle => $path)
                    {                    
                        $data.= 'try{ '.@file_get_contents($path)."\n".' }catch(e){} '."\n";
                    }
                    if(!empty($data))
                    {
                        @file_put_contents($file_path,$data);
                    }
                }
                
                //put script
                $search = ($tr_cache_options['optimize_js_footer'])? '</body>':'</head>';
                $script = '<script type="text/javascript" src="'.content_url($file_md5).'"></script>';
                $content= str_replace($search,$script.$search,$content);
            }
        }
        return $content;
    }
    
    function _optimize_css($content)
    {
        if(preg_match_all('/\<link[^\>]*href\=[\'|\"]?([^\'|^\"]*\.css)[\'|\"]?[^\>]*\>/',$content,$matches))
        {
            $time_string = '';
            foreach($matches[0] as $i => $m)
            {
                $url     = $matches[1][$i];
                if(strpos($url,'/admin-bar.min.css')!==false)continue;
                
                $path    = $this->getpath($url);
                if(!$path)continue;
                
                $content = str_replace($m,'',$content);
                
                self::$styles[] = array($path,$url);
                $time_string         .= filemtime($path).'|';
            }
            
            $file_md5    = TRSCSC_CACHE_CSS.'/'. md5($time_string) . '.css';
            $file_path   = WP_CONTENT_DIR.'/'.$file_md5;
            if(!empty($time_string))
            {
                if(!file_exists($file_path))
                {
                    $data     = '';
                    foreach(self::$styles as $handle => $css)
                    {
                        $csscode = $this->_fix_css(@file_get_contents($css[0]),$css[0],$css[1]);
                        $data.= $csscode."\n";
                    }
                   // return $data;
                    if(!empty($data))
                    {
                        @file_put_contents($file_path,$data);
                    }
                }
                
                //put script
                $search = '<title>';
                $style  = '<link rel="stylesheet" type="text/css"  href="'.content_url($file_md5).'" />';
                $content= str_replace($search,$style.$search,$content);
            }
        }
        return $content;
    }
    
    function _fix_css($css,$path,$url)
    {
        //fix url()
        if(preg_match_all('/url\([\s]*[\'|\"]?([^\'\"\)]+)[\'|\"]?[^\)]*\)/i',$css,$matches))
        {
            foreach($matches[0] as $i => $bg)
            {
                $s_url = $matches[1][$i];
                if(preg_match('/^http/',$s_url) || strpos($s_url,'data:')===0)continue;
                
                $img_url = dirname($url).'/'.$s_url;
                $bg_new  = str_replace($s_url,$img_url,$bg);
                $css     = str_replace($bg,$bg_new,$css);
                //return $bg_new;
                
            }
            
        }
        
        
        //fix import
        if(preg_match_all('/@import[^\'^\"]+[\'|\"]+([^\'^\"]+)[\'|\"]+/',$css,$matches))
        {
            foreach($matches[0] as $i=> $import)
            {
                $import_url = dirname($url) .'/'. $matches[1][$i];
				$import_path = $this->getpath($import_url);
                if($import_path && file_exists($import_path) && is_readable($import_path))
				{
				    $csscode = $this->_fix_css(@file_get_contents($import_path),$import_path,$import_url);
                    $css = str_replace($import,'',$css);
                    $css .= "\n" . $csscode;
				}
            }
        }
        
        
        return $css;
    }
    
    public function getpath($url)
    {
        $url = current(explode('?',$url,2));
    	$path = str_replace(TR_WP_SITE_URL,'',$url);
        if(preg_match('#^((https?|ftp):)?//#i',$path))
    	{
            	/** External script/css (adsense, etc) */
        		return false;
        }
    	$path = str_replace('//','/',ABSPATH.$path);
    	return $path;
    }
    
}