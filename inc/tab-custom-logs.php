<?php 
global $wpdb;
$table = 'wp_tr_security_log';

if($_GET['clearlog'] > time() -500)
{
    $wpdb->query("delete from {$table}");
}

$logs = $wpdb->get_results("select * from {$table} order by ltime desc limit 100");
if(count($logs)==0)
{
    echo 'no logs yet';
    return;
}
?>

<table id="tbl_logs" style="width: 100%;" class="wp-list-table widefat fixed posts">
<thead>
    <tr>
        <th style="width: 110px;">Time</th>
        <th>Msg</th>
        <th>Log</th>
        <th width="120">Lock/Ban To</th>
        <th style="width: 100px;">IP</th>
    </tr>
</thead>
<tbody>
<?php
$current_offset = get_option('gmt_offset')*3600;
foreach($logs as $log)
{
    $ip_row = $wpdb->get_row("select * from wp_tr_lock_ip where ip = '{$log->ip}'");
    $msg = '';
    switch($log->ltype)
    {
        
        case 'ip':
            $msg = "This IP locked. when try to login: '{$log->username}'";
            break;
        default:
            $msg = "User Locked: '{$log->username}'";
            break;
        
    }
    $log->ltime += $current_offset;
    if($ip_row->bantime > $ip_row->lasttime)
    {
        $ip_row->lasttime = $ip_row->bantime;
    }
    $ip_row->lasttime += $current_offset;
    ?>
    <tr>
        <td><?php echo date('Y-m-d/ H:i',$log->ltime)?></td>
        <td><?php echo $msg?></td>
        <td><?php echo $log->msg?></td>
        <td><?php echo date('Y-m-d/ H:i',$ip_row->lasttime)?></td>
        <td><?php echo $log->ip?></td>
    </tr>
    <?php    
    }
    ?>
</tbody>
</table><br />
<br />
<a class="button" href="admin.php?page=trcs_settings&pagetab=trcs_security&clearlog=<?php echo time()?>">Clear Log</a>

<style>
#tbl_logs tr:hover,#tbl_logs tr.selected{background:#FFFF99;}
</style>
