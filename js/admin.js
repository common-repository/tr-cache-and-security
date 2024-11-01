jQuery(function($){
    $('a#clear_cache').bind('click',function(){
        $this = $(this);
        if($this.html()=='Clearing...')return false;
        $this.html('Clearing...');
        $.post(ajaxurl,{'tr_action':'clear_cache','action':'clear_cache'},function($rs){
            $this.html('Clear Cache');
            $('.count_cache').html('0');
        });
         return false;      
    });
})