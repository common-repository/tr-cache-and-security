
jQuery(function($){
    var file_frame;
    $('.upload_image_button').live('click', function( event ){
        objnow = $(this).attr('rel');
        filetype = $(this).data('filetype');
        multiple = $(this).data('multiple')=='1'? true: false;
        event.preventDefault();
         
        // If the media frame already exists, reopen it.
        if ( file_frame ) {
            file_frame.open();
            return;
        }
         
        // Create the media frame.
        file_frame = wp.media.frames.file_frame = wp.media({
            title: $( this ).data( 'title' ),
            button: {
                text: $( this ).data( 'button_text' ),
            },
             library: {
               type: filetype//type of file
           },
        multiple: multiple // Set to true to allow multiple files to be selected
        });
         
        // When an image is selected, run a callback.
        file_frame.on( 'select', function() {
            var selection = file_frame.state().get('selection');             
            selection.map( function( attachment ) {             
                attachment = attachment.toJSON();   
                var fname =  objnow;
                if(multiple)
                {
                    fname += '[]';
                }else{
                    $('#imagelist_'+objnow).html('');
                }     
                 if(attachment.type=='image')
                    file = '<img src="'+attachment.url+'" width="50" height="50"/>';
                 else
                    file = '<span class="filename"><a href="'+attachment.url+'" target="_blank">'+attachment.title+'</a></span>';
                newob = $('<span class="imagelist listimg"><input type="hidden" name="'+fname+'" value="'+attachment.id+'" />'+file+'<a class="removeimg">Remove</a></span>');
                $('#imagelist_'+objnow).append(newob);
                
        });
        });
         
        // Finally, open the modal
        file_frame.open();
    });
    $('.listimg a.removeimg').live('click',function(){
        if(confirm('Do you want remove?')==false)return false;
        $(this).parents('.listimg').remove();
    })
    $('.single-image a.removeimg').live('click',function(){
        if(confirm('Do you want remove?')==false)return false;
        pr = $(this).parents('.single-image');
        $('input',pr).val('');
        $('img',pr).attr('src','');
        $(this).hide();
    })
})