<?php
class Tr_Base_Class{
    
    static public function link($args,$echo=true)
    {
        static $query, $path;
        
        if(empty($path))
        {
            $url        = $_SERVER['REQUEST_URI'];
            $parserurl  = parse_url($url);
            $output     = array();
            parse_str($parserurl['query'], $output);
            $query = $output; 
            $path  = $parserurl['path'];
        }
         
        $query_output = $query;
        foreach($args as $k => $vl)
        {
            if(empty($vl))
            {
                unset($query_output[$k]);
            }else
            {
                $query_output[$k] = $vl;
            }
            unset($query_output['_wpnonce']);
            if(!isset($args['tr_action']))
                unset($query_output['tr_action']);
        }
        $link = $path.'?'.http_build_query($query_output);
        if($echo)
            echo $link;
        return $link;
    }
    
    static public function getShortcodepage($shortcode,$return_url = false)
    {
        global $wpdb;
        
        $page_id = $wpdb->get_var("select ID from {$wpdb->posts} where post_content like '%{$shortcode}%' and post_status='publish' limit 1");
        if($page_id==0)return 0;
        if($return_url)
        {
            return get_permalink($page_id);
        }
        return $page_id;
    }
}

