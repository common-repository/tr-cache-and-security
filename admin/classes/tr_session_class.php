<?php

class Tr_Session_Class
{
    static public function reset($box='default')
    {
        unset($_SESSION[__CLASS__.$box]);
    }
    static public function add_error($msg,$box='default')
    {
        self::add($msg,$box,'error');
    }
    
    static public function add($msg,$box='default',$flag='message')
    {
        if(!is_array($_SESSION[__CLASS__.$box]))
        {
            $_SESSION[__CLASS__.$box] = array();
        }
        if(!is_array($_SESSION[__CLASS__.$box][$flag]))
        {
            $_SESSION[__CLASS__.$box][$flag] = array();
        }
        $_SESSION[__CLASS__.$box][$flag][] = $msg;
    }
    
    static public function show($box='default')
    {
        $flags = isset($_SESSION[__CLASS__.$box])? $_SESSION[__CLASS__.$box] : array();

        foreach($flags as $flag => $msg)
        {
            if(count($msg)==0)continue;
            $class = ($flag=='error')? 'error': $flag.' updated';
            echo '<div class="message_alert woocommerce-'.$flag.' '.$class.'">';
            foreach($msg as $m)
            {
                echo '<p>'.$m.'</p>';
            }
            echo '</div>';
        }
        self::reset($box);
    }
    
    static public function count_error($box='default')
    {
        if(!is_array($_SESSION[__CLASS__.$box]['error']))return 0;
        return count($_SESSION[__CLASS__.$box]['error']);
    }
}