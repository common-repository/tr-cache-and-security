<?php
$wp_htaccess_file = ABSPATH.'/.htaccess';
$wp_config_file = ABSPATH.'/wp-config.php';


if(!wp_is_writable($wp_config_file) )
{
    ?>
    <h4>Your wp-config.php file not writeable. So you need add below content to file</h4>
    <textarea readonly="true" style="width: 100%;height:150px">
    <?php echo trwr_action_change_config_file(true)?>
    </textarea>
    <?php
}
if(!wp_is_writable($wp_htaccess_file))
{
    ?>
    <h4>Your .htaccess file not writeable. So you need add below content to file</h4>
    <textarea readonly="true" style="width: 100%;height:150px">
    <?php echo trwr_action_change_htaccess(true)?>
    </textarea>
    <?php
}