<?php

class Tr_Email{
    
    var $subject;
    var $message;
    var $headers;
    
    public function Tr_Email()
    {
        $this->headers = array(
            'from: ' . get_bloginfo('name'). ' <'. get_option('admin_email').'>'
        );
    }
    
    public function parse_tag($message,$array)
    {
        foreach($array as $tag => $text)
        {
            $message = str_replace("[$tag]",$text,$message);
        }
        return $message;
    }
    
    public function addSubject($subject,$tags=false)
    {
        if(is_array($tags))
        {
            $subject = $this->parse_tag($subject,$tags);
        }
        $this->subject = $subject;   
    }
    
    public function addBody($message,$tags=false)
    {
        if(is_array($tags))
        {
            $message = $this->parse_tag($message,$tags);
        }
        $this->message = $message;   
    }
    
    public function setHeaders($headers)
    {
        $this->headers = $headers;
    }
    
    public function send($to)
    {
        return wp_mail($to , $this->subject, $this->message ,$this->headers);
    }
}