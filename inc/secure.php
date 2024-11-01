<?php
add_filter('authenticate',array('Tr_Security_Class','authenticate'),99,3);
add_action('login_init', array('Tr_Security_Class','login_init'));
add_action('login_form',array('Tr_Security_Class','login_form'));
add_action('lostpassword_form',array('Tr_Security_Class','lostpassword_form'));
add_action('lostpassword_post',array('Tr_Security_Class','lostpassword_post'));
add_action('register_form',array('Tr_Security_Class','register_form'));
add_filter('registration_errors', array('Tr_Security_Class','registration_errors'));
add_action('comment_form_after_fields', array('Tr_Security_Class','comment_form'));
add_filter('preprocess_comment', array('Tr_Security_Class', 'preprocess_comment'), 1);

if( defined('FORCE_SSL_FRONT') && FORCE_SSL_FRONT &&  !is_admin() )
{
    Tr_Security_Class::use_ssl(true);
}
