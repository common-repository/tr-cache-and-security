<?php
global $wpdb;
$current_time = time();
$current_offset = get_option('gmt_offset')*3600;

$banips = $wpdb->get_results("select * from wp_tr_lock_ip where bantime >= {$current_time}");
if(count($banips)>0):
?>
<div style="overflow: auto;max-height:200px;">
<table id="tbl_logs" style="width: 100%;" class="wp-list-table widefat fixed posts">
<thead>
    <tr>
        <th>IP</th>
        <th>Ban To</th>
        <th>Actions</th>
    </tr>
</thead>
<tbody>
<?php 

foreach($banips as $banip):
$banip->bantime += $current_offset;
?>
<tr>
    <td><?php echo $banip->ip ?></td>
    <td><?php echo date('Y-m-d/H:i',$banip->bantime )?></td>
    <td><a class="removeban" data-ip="<?php echo $banip->ip?>">Remove</a></td>
</tr>
<?php endforeach;?>
</tbody>
</table>
</div>
<style>a{cursor: pointer;}</style>
<script>
jQuery(function($){
    $('a.removeban').live('click',function(){
        $this = $(this);
        $.post(ajaxurl,{'tr_action':'removeban','action':'removeban','ip':$this.data('ip')},function(rs){
            $this.parents('tr').css('background','red');
            $this.animate({opacity:'0.3'},function(){$this.parents('tr').remove()});
        })
    })
})
</script>
<?php else:?>
No IP to Ban now
<?php endif;?>