<?php
if (!session_id())
    session_start();
global $tr_cache_stop;
$tr_cache_stop = false;

// If no-cache header support is enabled and the browser explicitly requests a fresh page, do not cache
if (((!empty($_SERVER['HTTP_CACHE_CONTROL']) && $_SERVER['HTTP_CACHE_CONTROL'] ==
    'no-cache') || (!empty($_SERVER['HTTP_PRAGMA']) && $_SERVER['HTTP_PRAGMA'] ==
    'no-cache')))
    return tr_cache_exit();


// Do not cache post request (comments, plugins and so on)
if ($_SERVER["REQUEST_METHOD"] == 'POST')
    return tr_cache_exit();

if (tr_cache_is_ssl() && $tr_cache_options['disable_ssl']) {
    return tr_cache_exit();
}

if (!tr_cache_is_ssl() && defined('FORCE_SSL_FRONT') && FORCE_SSL_FRONT) {
    return tr_cache_exit();
}

// Try to avoid enabling the cache if sessions are managed with request parameters and a session is active
if (defined('SID') && SID != '')
    return tr_cache_exit();

$tr_cache_uri = $_SERVER['REQUEST_URI'];
$tr_cache_qs = strpos($tr_cache_uri, '?');

if ($tr_cache_qs !== false) {
    if (!$tr_cache_options['cache_qs']) {
        if (!(count($_GET) == 1 && ($_GET['p'] || $_GET['page_id']))) {
            return tr_cache_exit();
        }
    }
}

if (strpos($tr_cache_uri, 'robots.txt') !== false)
    return tr_cache_exit();


// Do not use or cache pages when a wordpress user is logged on
foreach ($_COOKIE as $n => $v) {
    // SHIT!!! This test cookie makes to cache not work!!!
    if ($n == 'wordpress_test_cookie')
        continue;
    // wp 2.5 and wp 2.3 have different cookie prefix, skip cache if a post password cookie is present, also
    if (substr($n, 0, 14) == 'wordpressuser_' || substr($n, 0, 10) == 'wordpress_' ||
        substr($n, 0, 12) == 'wp-postpass_' || strpos($n, 'wordpress_logged_in') === 0) {
        return tr_cache_exit();
    }
}

// Do not cache WP pages, even if those calls typically don't go throught this script
if (strpos($tr_cache_uri, '/wp-') !== false && strpos($tr_cache_uri, '.php') !== false)
    return tr_cache_exit();

// Multisite
if (function_exists('is_multisite') && is_multisite() && strpos($tr_cache_uri,
    '/files/') !== false)
    return tr_cache_exit();


// Prefix host, and for wordpress 'pretty URLs' strip trailing slash (e.g. '/my-post/' -> 'my-site.com/my-post')
$tr_cache_uri = $_SERVER['HTTP_HOST'] . $tr_cache_uri;

// The name of the file with html and other data
$tr_cache_name = md5($tr_cache_uri);
$hc_file = $tr_cache_path . $tr_cache_name . tr_cache_mobile_type() . '.dat';
if (tr_cache_is_ssl()) {
    $hc_file .= 's';
}

if (!file_exists($hc_file)) {
    tr_cache_start(false);
    return;
}

$hc_file_time = @filemtime($hc_file);
$hc_file_age = time() - $hc_file_time;

if ($hc_file_age > $tr_cache_options['timeout']) {
    tr_cache_start();
    return;
}

$hc_invalidation_time = @filemtime($tr_cache_path . '_global.dat');
if ($hc_invalidation_time && $hc_file_time < $hc_invalidation_time) {
    tr_cache_start();
    return;
}

// Load it and check is it's still valid
$tr_cache_data = @unserialize(file_get_contents($hc_file));

if (!$tr_cache_data) {
    tr_cache_start();
    return;
}

if ($tr_cache_data['type'] == 'home' || $tr_cache_data['type'] == 'archive') {

    $hc_invalidation_archive_file = @filemtime($tr_cache_path . '_archives.dat');
    if ($hc_invalidation_archive_file && $hc_file_time < $hc_invalidation_archive_file) {
        tr_cache_start();
        return;
    }
}

if (array_key_exists("HTTP_IF_MODIFIED_SINCE", $_SERVER)) {
    $if_modified_since = strtotime(preg_replace('/;.*$/', '', $_SERVER["HTTP_IF_MODIFIED_SINCE"]));
    if ($if_modified_since >= $hc_file_time) {
        header($_SERVER['SERVER_PROTOCOL'] . " 304 Not Modified");
        flush();
        die();
    }
}
// Valid cache file check ends here

if (!empty($tr_cache_data['location'])) {
    header('Location: ' . $tr_cache_data['location']);
    flush();
    die();
}

// It's time to serve the cached page

if (!$tr_cache_options['browsercache']) {
    // True if browser caching NOT enabled (default)
    header('Cache-Control: no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: Wed, 11 Jan 1984 05:00:00 GMT');
} else {
    $maxage = $tr_cache_options['timeout'] - $hc_file_age;
    header('Cache-Control: max-age=' . $maxage);
    header('Expires: ' . gmdate("D, d M Y H:i:s", time() + $maxage) . " GMT");
}

// True if user ask to NOT send Last-Modified
if (!$tr_cache_options['lastmodified']) {
    header('Last-Modified: ' . gmdate("D, d M Y H:i:s", $hc_file_time) . " GMT");
}

header('Content-Type: ' . $tr_cache_data['mime']);
if (isset($tr_cache_data['status']) && $tr_cache_data['status'] == 404)
    header($_SERVER['SERVER_PROTOCOL'] . " 404 Not Found");

// Send the cached html
if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && strpos($_SERVER['HTTP_ACCEPT_ENCODING'],
    'gzip') !== false && (($tr_cache_options['gzip'] && !empty($tr_cache_data['gz'])) ||
    ($tr_cache_options['gzip_on_fly'] && function_exists('gzencode')))) {
    header('Content-Encoding: gzip');
    header('Vary: Accept-Encoding');
    if (!empty($tr_cache_data['gz'])) {
        echo $tr_cache_data['gz'];
    } else {
        echo gzencode($tr_cache_data['html']);
    }
} else {
    // No compression accepted, check if we have the plain html or
    // decompress the compressed one.
    if (!empty($tr_cache_data['html'])) {
        //header('Content-Length: ' . strlen($tr_cache_data['html']));
        echo $tr_cache_data['html'];
    } else
        if (function_exists('gzinflate')) {
            $buffer = tr_cache_gzdecode($tr_cache_data['gz']);
            if ($buffer === false)
                echo 'Error retrieving the content';
            else
                echo $buffer;
        } else {
            // Cannot decode compressed data, serve fresh page
            return false;
        }
}
flush();
die();


function tr_cache_start($delete = true)
{
    global $hc_file, $tr_render_cache;
    $tr_render_cache = microtime(true);
    if ($delete)
        @unlink($hc_file);

    ob_start('tr_cache_callback');
}

// From here Wordpress starts to process the request

// Called whenever the page generation is ended
function tr_cache_callback($buffer)
{
    global $tr_cache_stop, $tr_cache_options, $tr_cache_redirect, $hc_file, $tr_cache_name,
        $tr_render_cache;

    if (!function_exists('is_home'))
        return $buffer;
    if (!function_exists('is_front_page'))
        return $buffer;

    if (function_exists('apply_filters'))
        $buffer = apply_filters('tr_cache_buffer', $buffer);

    if ($tr_cache_stop)
        return $buffer;

    if (!$tr_cache_options['notfound'] && is_404()) {
        return $buffer;
    }

    if (strpos($buffer, '</body>') === false)
        return $buffer;

    // WP is sending a redirect
    if ($tr_cache_redirect) {
        if ($tr_cache_options['redirects']) {
            $data['location'] = $tr_cache_redirect;
            tr_cache_write($data);
        }
        return $buffer;
    }

    if ((is_home() || is_front_page()) && $tr_cache_options['home']) {
        return $buffer;
    }


    if (is_home() || is_front_page())
        $data['type'] = 'home';
    else
        if (is_feed())
            $data['type'] = 'feed';
        else
            if (is_archive())
                $data['type'] = 'archive';
            else
                if (is_single())
                    $data['type'] = 'single';
                else
                    if (is_page())
                        $data['type'] = 'page';
    $buffer = trim($buffer);

    // Can be a trackback or other things without a body. We do not cache them, WP needs to get those calls.
    if (strlen($buffer) == 0)
        return '';

    if (!$tr_cache_options['charset'])
        $tr_cache_options['charset'] = 'UTF-8';

    if (is_feed()) {
        $data['mime'] = 'text/xml;charset=' . $tr_cache_options['charset'];
    } else {
        $data['mime'] = 'text/html;charset=' . $tr_cache_options['charset'];
    }
    $tr_render_cache = round(microtime(true) - $tr_render_cache, 2);
    $buffer .= '<!-- render in ' . $tr_render_cache .
        ' seconds with TR Cache and Security ' . $tr_cache_name . ' ' . date('y-m-d h:i:s') .
        ' -->';

    //need optimize js & css here $buffer
    if($tr_cache_options['optimize_js'] || $tr_cache_options['optimaze_css'])
    {
        //include(__DIR__.'/inc/tr_cache_class.php');
        //$buffer = 33;
        $cache_obj = Tr_Cache_Class::instance();
        $buffer    = $cache_obj->autoptimize_end($buffer);
    }
    
    $data['html'] = $buffer;

    if (is_404())
        $data['status'] = 404;

    tr_cache_write($data);

    if ($tr_cache_options['browsercache']) {
        header('Cache-Control: max-age=' . $tr_cache_options['timeout']);
        header('Expires: ' . gmdate("D, d M Y H:i:s", time() + $tr_cache_options['timeout']) .
            " GMT");
    }

    // True if user ask to NOT send Last-Modified
    if (!$tr_cache_options['lastmodified']) {
        header('Last-Modified: ' . gmdate("D, d M Y H:i:s", @filemtime($hc_file)) .
            " GMT");
    }

    if (($tr_cache_options['gzip'] && !empty($data['gz'])) || ($tr_cache_options['gzip_on_fly'] &&
        !empty($data['html']) && function_exists('gzencode'))) {
        header('Content-Encoding: gzip');
        header('Vary: Accept-Encoding');
        if (empty($data['gz'])) {
            $data['gz'] = gzencode($data['html']);
        }
        return $data['gz'];
    }

    return $buffer;
}

function tr_cache_write(&$data)
{
    global $hc_file, $tr_cache_options;

    $data['uri'] = $_SERVER['REQUEST_URI'];

    // Look if we need the compressed version
    if ($tr_cache_options['store_compressed'] && !empty($data['html']) &&
        function_exists('gzencode')) {
        $data['gz'] = gzencode($data['html']);
        if ($data['gz'])
            unset($data['html']);
    }
    $file = fopen($hc_file, 'w');
    fwrite($file, serialize($data));
    fclose($file);
}

function tr_cache_mobile_type()
{
    global $tr_cache_options;
    
    if(defined('WP_MOBILE_SITE') && WP_MOBILE_SITE)
    {
        return '_mobile';
    }
    if ($tr_cache_options['detect_mobile'] && $tr_cache_options['reject_agents']) {
        $tr_cache_agent = strtolower($_SERVER['HTTP_USER_AGENT']);
        foreach ($tr_cache_options['reject_agents'] as $tr_cache_a) {
            if (stripos($tr_cache_agent, $tr_cache_a) !== false)
                return '_mobile';
        }
    }

    return '';
}

function tr_cache_gzdecode($data)
{

    $flags = ord(substr($data, 3, 1));
    $headerlen = 10;
    $extralen = 0;

    $filenamelen = 0;
    if ($flags & 4) {
        $extralen = unpack('v', substr($data, 10, 2));

        $extralen = $extralen[1];
        $headerlen += 2 + $extralen;
    }
    if ($flags & 8) // Filename

        $headerlen = strpos($data, chr(0), $headerlen) + 1;
    if ($flags & 16) // Comment

        $headerlen = strpos($data, chr(0), $headerlen) + 1;
    if ($flags & 2) // CRC at end of file

        $headerlen += 2;
    $unpacked = gzinflate(substr($data, $headerlen));
    return $unpacked;
}

function tr_cache_is_ssl()
{
    if (isset($_SERVER['HTTPS'])) {
        if ('on' == strtolower($_SERVER['HTTPS']))
            return true;
        if ('1' == $_SERVER['HTTPS'])
            return true;
    } elseif (isset($_SERVER['SERVER_PORT']) && ('443' == $_SERVER['SERVER_PORT'])) {
        return true;
    }
    return false;
}
function tr_cache_exit()
{
    global $tr_cache_options;

    if ($tr_cache_options['gzip_on_fly'] && stripos($_SERVER['HTTP_ACCEPT_ENCODING'],
        'gzip') !== false && extension_loaded('zlib') && @ini_get('output_handler') !=
        'ob_gzhandler')
        ob_start('ob_gzhandler');
    return false;
}
