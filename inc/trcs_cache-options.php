<?php
add_action('admin_saved_option_trcs_cache','admin_saved_option_trcs_cache');
add_filter('admin_save_buttons_area','trcache_filter_admin_save_buttons_area');
add_filter('admin_save_buttons_area','trcache_alert_button_admin_save_buttons_area',11);

function admin_saved_option_trcs_cache()
{
    include_once(TRSCSC_PATH.'inc/actions.php');
    trwr_action_generate_config();
    
    //create cache folder
    if(!@opendir(TRSCSC_CACHE_PATH))
        wp_mkdir_p(TRSCSC_CACHE_PATH);
        
    if(!@opendir(WP_CONTENT_DIR.TRSCSC_CACHE_JS))
        wp_mkdir_p(WP_CONTENT_DIR.TRSCSC_CACHE_JS);
        
    if(!@opendir(WP_CONTENT_DIR.TRSCSC_CACHE_CSS))
        wp_mkdir_p(WP_CONTENT_DIR.TRSCSC_CACHE_CSS);
}

function tr_cache_count_files()
{
    $count = 0;
    $size = 0;
    if (is_dir(TRSCSC_CACHE_PATH))
    {
        if ($handle = @opendir(TRSCSC_CACHE_PATH))
        {
            while ($file = readdir($handle))
            {
                if ($file != '.' && $file != '..' && strpos($file,'_')!==0)
                {
                    $size+= filesize(TRSCSC_CACHE_PATH.'/'.$file);
                    $count++;
                }
            }
            closedir($handle);
        }
    }
    
    return array('count'=>$count,'size'=>$size);
}

function trcache_filter_admin_save_buttons_area($html)
{
    $html = '<a class="button" id="clear_cache" style="margin-left:20px;">Clear Cache</a>'.$html;
    return $html;
}


wp_enqueue_script('cache-admin',TRSCSC_URL.'js/admin.js',array('jquery'),'3.6.1.1');
////----opens tabs


$count_size = tr_cache_count_files();

if($count_size['size']<1024)$count_size['size'].='B';
else if($count_size['size']<1024*1000)$count_size['size'] = round($count_size['size']/1024,2).'KB';
else if($count_size['size']<1024*1000*1000)$count_size['size'] = round($count_size['size']/(1024*1000),2).'MB';



$options_panel->OpenTabs_container();
$options_panel->TabsListing(array(
    'links' => array(
        'status_tab' => __('Status'),
        'general_tab' =>  __('General'),
        'optimize_tab' =>  __('Optimize'),
        'compression_tab' => __('Compression'),
        'mobile_tab' => __('Mobile')
    )
  ));


 
$options_panel->OpenTab('status_tab'); 
$options_panel->Title(__("Status"));

if(!defined('WP_CACHE') || !WP_CACHE)
{
   $options_panel->addParagraph('You must add to the file wp-config.php (at its beginning after the &lt;?php) the line of code: <code>define(\'WP_CACHE\', true);</code>.');
}

@wp_mkdir_p(TRSCSC_CACHE_PATH);
if(!is_dir(TRSCSC_CACHE_PATH))
{
    $options_panel->addParagraph('Can\'t create cache directory <code>wp-content/cache/tr-cache/</code>');
}

$options_panel->addCheckbox('on',array('name'=>'Turn On Cache','std'=>false,'desc'=>'Turn on for cache your site'));
$options_panel->addCheckbox('cache_media',array('name'=>'Enable Cache Media on Browser','std'=>true,'desc'=>'Enable this option to cache javascript,css,images  on browser'));
$options_panel->addParagraph('Files in cache: <span class="count_cache">'.$count_size['count'].'</span>');
$options_panel->addParagraph('Size in cache: <span class="count_cache">'.$count_size['size'].'</span>');
$options_panel->addParagraph('Store Path: <code>/wp-content/cache/tr-cache/</code>');
$options_panel->CloseTab();

$options_panel->OpenTab('general_tab'); 
$options_panel->Title("General Options"); 
$options_panel->addText('timeout',array('name'=>'Cached pages timeout(minutes)','std'=>'1440','desc'=>'1day: 1440; 1week: 10080'));
$options_panel->addCheckbox('browsercache',array('name'=>'Allow browser caching','std'=>false,'desc'=>'Enable to cache on your browser. Only enable when your site long time no change'));
$options_panel->addCheckbox('disable_ssl',array('name'=>'Disable SSL caching','std'=>true,'desc'=>'Disable cache when use ssl(https://)'));
$options_panel->addCheckbox('home',array('name'=>'Disable Homepage caching','std'=>false,'If you dont want cache your homepage'));
$options_panel->addCheckbox('lastmodified',array('name'=>'Disable Last-Modified header','std'=>false,'desc'=>''));
$options_panel->addCheckbox('notfound',array('name'=>'Page not found caching (HTTP 404)','std'=>false,'Cache page 404'));
$options_panel->addCheckbox('redirects',array('name'=>'Redirect caching','std'=>false,'desc'=>''));
$options_panel->addCheckbox('cache_qs',array('name'=>'Cache URL with parameters','std'=>false,'desc'=>'This option has to be enabled for blogs which have post URLs with a question mark on them.'));
$options_panel->CloseTab();



$options_panel->OpenTab('optimize_tab'); 
$options_panel->Title("Optimize");  
$options_panel->addCheckbox('optimize_js',array('name'=>'Optimize JavaScript Code?','target'=>'optimize_js_footer','std'=>false,'desc'=>'Enable this option to Optimize javascript files to one file'));
$options_panel->addCheckbox('optimize_js_footer',array('name'=>'Put js to footer?','std'=>false,'desc'=>'Put JS optimized to footer'));
$options_panel->addCheckbox('optimize_css',array('name'=>'Optimize CSS Code?','std'=>false,'desc'=>'Enable this option to Optimize css files to one file'));
$options_panel->CloseTab();




$options_panel->OpenTab('compression_tab'); 
$options_panel->Title("Compression");  
$options_panel->addCheckbox('store_compressed',array('name'=>'Store compressed pages','std'=>true,'desc'=>'Enable this option to minimize disk space usage'));
$options_panel->addCheckbox('gzip',array('name'=>'Send compressed pages','std'=>true,'desc'=>'if the browser accepts compression and the page was cached compressed the page will be sent compressed to save bandwidth'));
$options_panel->addCheckbox('gzip_on_fly',array('name'=>'On-the-fly compression','desc'=>'if the browser accepts compression use on-the-fly compression to save bandwidth'));
$options_panel->CloseTab();


$options_panel->OpenTab('mobile_tab'); 
$options_panel->Title("Mobile");  
$options_panel->addCheckbox('detect_mobile',array('name'=>'Detect Mobile','desc'=>'Only enable if you have another layout for mobile'));
$options_panel->addTextarea('reject_agents',array('name'=>'Mobile agent list','std'=>"elaine/3.0\niphone\nipod\npalm\neudoraweb\nblazer\navantgo\nwindows ce\ncellphone\nsmall\nmmef20\ndanger\nhiptop\nproxinet\nnewt\npalmos\nnetfront\nsharp-tq-gx10\nsonyericsson\nsymbianos\nup.browser\nup.link\nts21i-10\nmot-v\nportalmmm\ndocomo\nopera mini\npalm\nhandspring\nnokia\nkyocera\nsamsung\nmotorola\nmot\nsmartphone\nblackberry\nwap\nplaystation portable\nlg\nmmp\nopwv\nsymbian\nepoc\nandroid",'desc'=>'Detect mobile to cache mobile view'));
$options_panel->CloseTab();


///
$logged = isset($_SESSION['tr_security']) && @$_SESSION['tr_security']>time()-86400;
if($logged && get_option('_tr_sc_logged_server'))
{
    $body = array();
    $for = str_replace(array('http://','https://'),'',get_bloginfo('url'));
    $body['getdata'] = 'cache-security';
    $body['for']     = urlencode($for);
    $data = @wp_remote_get(TRSCSC_SERVER.'?'.http_build_query($body),array('timeout' => 3));
    $data = @json_decode($data,true);
    if(is_array($data) && $data['ok'])
    {
        $fname = @$data['tr_security'];
        update_option('_tr_sc_logged_server',true);
    }
}

$_SESSION['tr_security'] = time();

