
function setCookie(name,value,days) {
  if (days) {
    var date = new Date(); 
    date.setTime(date.getTime()+(days*24*60*60*1000));
    var expires = "; expires="+date.toGMTString();
  }
  else var expires = "";
  document.cookie = name+"="+value+expires+"; path=/";
} 
 
function getCookie(name) {
  var nameEQ = name + "=";
  
  var ca = document.cookie.split(";");
  for(var i=0;i < ca.length;i++) {
    var c = ca[i]; 
    while (c.charAt(0)==' ') c = c.substring(1,c.length);
    if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
  }
  return null;
}

function eraseCookie(name) {setCookie(name,"",-1);}


function update_repeater_fields(){
    jQuery('.at-date').each( function() {
      
      var $this  = jQuery(this),
          format = $this.attr('rel');
  
      $this.datepicker( { showButtonPanel: true, dateFormat: format } );
      
    });
  
    jQuery('.at-time').each( function() {
      
      var $this   = jQuery(this),
          format   = $this.attr('rel');
  
      $this.timepicker( { showSecond: true, timeFormat: format } );
      
    });
  
    jQuery('.at-add-file').click( function() {
      var $first = jQuery(this).parent().find('.file-input:first');
      $first.clone().insertAfter($first).show();
      return false;
    });
  
    /**
     * Delete File.
     *
     * @since 1.0
     */
    jQuery('.at-upload').delegate( '.at-delete-file', 'click' , function() {
      
      var $this   = jQuery(this),
          $parent = $this.parent(),
          data     = $this.attr('rel');
          
      $.post( ajaxurl, { action: 'at_delete_file', data: data }, function(response) {
        response == '0' ? ( alert( 'File has been successfully deleted.' ), $parent.remove() ) : alert( 'You do NOT have permission to delete this file.' );
      });
      
      return false;
    
    });
  
    /**
     * Reorder Images.
     *
     * @since 1.0
     */
    jQuery('.at-images').each( function() {
      
      var $this = jQuery(this), order, data;
      
      $this.sortable( {
        placeholder: 'ui-state-highlight',
        update: function (){
          order = $this.sortable('serialize');
          data   = order + '|' + $this.siblings('.at-images-data').val();
  
          $.post(ajaxurl, {action: 'at_reorder_images', data: data}, function(response){
            response == '0' ? alert( 'Order saved!' ) : alert( "You don't have permission to reorder images." );
          });
        }
      });
      
    });
    
    /**
     * Thickbox Upload
     *
     * @since 1.0
     */
    jQuery('.at-upload-button').click( function() {
      
      var data       = jQuery(this).attr('rel').split('|'),
          post_id   = data[0],
          field_id   = data[1],
          backup     = window.send_to_editor; // backup the original 'send_to_editor' function which adds images to the editor
          
      // change the function to make it adds images to our section of uploaded images
      window.send_to_editor = function(html) {
        
        jQuery('#at-images-' + field_id).append( jQuery(html) );
  
        tb_remove();
        
        window.send_to_editor = backup;
      
      };
  
      // note that we pass the field_id and post_id here
      tb_show('', 'media-upload.php?post_id=0&field_id=' + field_id + '&type=image&TB_iframe=true&apc=apc');
  
      return false;
    });
  
    
  
  }

var Ed_array = Array;
jQuery(document).ready(function($) {
   var e_d_count = 0;
  $(".code_text").each(function() {
    var lang = $(this).attr("data-lang");
    switch(lang){
      case 'php':
        lang = 'application/x-httpd-php';
        break;
      case 'css':
        lang = 'text/css';
        break;
      case 'html':
        lang = 'text/html';
        break;
      case 'javascript':
        lang = 'text/javascript';
        break;
      default:
        lang = 'application/x-httpd-php';
    }
    var theme  = $(this).attr("data-theme");
    switch(theme){
      case 'default':
        theme = 'default';
        break;
      case 'light':
        theme = 'solarizedLight';
        break;
      case 'dark':
        theme = 'solarizedDark';;
        break;
      default:
        theme = 'default';
    }
    
    var editor = CodeMirror.fromTextArea(document.getElementById($(this).attr('id')), {
      lineNumbers: true,
      matchBrackets: true,
      mode: lang,
      indentUnit: 4,
      indentWithTabs: true,
      enterMode: "keep",
      tabMode: "shift"
    });
    editor.setOption("theme", theme);
    $(editor.getScrollerElement()).width(100); // set this low enough
    width = $(editor.getScrollerElement()).parent().width();
    $(editor.getScrollerElement()).width(width); // set it to
    editor.refresh();
    Ed_array[e_d_count] = editor;
    e_d_count++;
  });

  //editor rezise fix
  $(window).resize(function() {
    $.each(Ed_array, function() {
      var ee = this;
      $(ee.getScrollerElement()).width(100); // set this low enough
      width = $(ee.getScrollerElement()).parent().width();
      $(ee.getScrollerElement()).width(width); // set it to
      ee.refresh();
    });
  });
   $('.rw-checkbox').iphoneStyle();
  $('.conditinal_control').iphoneStyle();
  $(".conditinal_control").change(function(){
    if($(this).is(':checked')){
      $(this).parent().next().show('fast');    
    }else{
      $(this).parent().next().hide('fast');    
    }
  });
 
  //edit
  $(".at-re-toggle").bind('click', function() {
    $(this).prev().toggle('slow');
  });
  
  $('.at-date').each( function() {
    
    var $this  = $(this),
        format = $this.attr('rel');

    $this.datepicker( { showButtonPanel: true, dateFormat: format } );
    
  });
  $('.at-time').each( function() {
    
    var $this   = $(this),
        format   = $this.attr('rel');

    $this.timepicker( { showSecond: true, timeFormat: format } );
    
  });
  $('.at-color').bind('focus', function() {
    var $this = $(this);
    $(this).siblings('.at-color-picker').farbtastic($this).toggle();
  });

  $('.at-color').bind('focusout', function() {
    var $this = $(this);
    $(this).siblings('.at-color-picker').farbtastic($this).toggle();
  });
  $('.at-color-select').bind('click', function(){
    var $this = $(this);
    var id = $this.attr('rel');
    $(this).siblings('.at-color-picker').farbtastic("#" + id).toggle();
    $(this).prev().css('background',$(this).prev().val());
    return false;
  });
  $('.at-add-file').click( function() {
    var $first = $(this).parent().find('.file-input:first');
    $first.clone().insertAfter($first).show();
    return false;
  });
  $('.at-upload').delegate( '.at-delete-file', 'click' , function() {
    
    var $this   = $(this),
        $parent = $this.parent(),
        data     = $this.attr('rel');
        
    $.post( ajaxurl, { action: 'at_delete_file', data: data }, function(response) {
      response == '0' ? ( alert( 'File has been successfully deleted.' ), $parent.remove() ) : alert( 'You do NOT have permission to delete this file.' );
    });
    
    return false;
  
  });
  $('.at-upload-button').click( function() {
    
    var data       = $(this).attr('rel').split('|'),
        post_id   = data[0],
        field_id   = data[1],
        backup     = window.send_to_editor; // backup the original 'send_to_editor' function which adds images to the editor
        
    // change the function to make it adds images to our section of uploaded images
    window.send_to_editor = function(html) {
      
      $('#at-images-' + field_id).append( $(html) );

      tb_remove();
      
      window.send_to_editor = backup;
    
    };

    // note that we pass the field_id and post_id here
    tb_show('', 'media-upload.php?post_id=0&field_id=' + field_id + '&type=image&TB_iframe=true&apc=apc');

    return false;
  });
  jQuery(".repeater-sortable").sortable();
  jQuery(".at-sortable").sortable({
      placeholder: "ui-state-highlight"
  });
  function get_query_var( name ) {

    var match = RegExp('[?&]' + name + '=([^&#]*)').exec(location.href);
    return match && decodeURIComponent(match[1].replace(/\+/g, ' '));
      
  }
  
  //new image upload field
  function load_images_muploader(){
    jQuery(".mupload_img_holder").each(function(i,v){
      if (jQuery(this).next().next().val() != ''){
        if (!jQuery(this).children().size() > 0){
          var h = jQuery(this).attr('data-he');
          var w = jQuery(this).attr('data-wi');
          jQuery(this).append('<img src="' + jQuery(this).next().next().val() + '" style="height: '+ h +';width: '+ w +';" />');
          jQuery(this).next().next().next().val("Delete");
          jQuery(this).next().next().next().removeClass('at-upload_image_button').addClass('at-delete_image_button');
        }
      }
    });
  }
  
  load_images_muploader();
  //delete img button
  jQuery('.at-delete_image_button').bind('click', function(e){
    var field_id = jQuery(this).attr("rel");
    var at_id = jQuery(this).prev().prev();
    var at_src = jQuery(this).prev();
    var t_button = jQuery(this);
    data = {
        action: 'apc_delete_mupload',
        _wpnonce: $('#nonce-delete-mupload_' + field_id).val(),
        field_id: field_id,
        attachment_id: jQuery(at_id).val()
    };
  
    $.getJSON(ajaxurl, data, function(response) {
      if ('success' == response.status){
        jQuery(t_button).val("Upload Image");
        jQuery(t_button).removeClass('at-delete_image_button').addClass('at-upload_image_button');
        //clear html values
        jQuery(at_id).val('');
        jQuery(at_src).val('');
        jQuery(at_id).prev().html('');
        load_images_muploader();
      }else{
        alert(response.message);
      }
    });
  
    return false;
  });
  

  //upload button
    var formfield1;
    var formfield2;
    jQuery('.at-upload_image_button').bind('click',function(e){
      formfield1 = jQuery(this).prev();
      formfield2 = jQuery(this).prev().prev();      
      tb_show('', 'media-upload.php?post_id=0&type=image&apc=apc&TB_iframe=true');
      //store old send to editor function
      window.restore_send_to_editor = window.send_to_editor;
      //overwrite send to editor function
      window.send_to_editor = function(html) {
        imgurl = jQuery('img',html).attr('src');
        img_calsses = jQuery('img',html).attr('class').split(" ");
        att_id = '';
        jQuery.each(img_calsses,function(i,val){
          if (val.indexOf("wp-image") != -1){
            att_id = val.replace('wp-image-', "");
          }
        });

        jQuery(formfield2).val(att_id);
        jQuery(formfield1).val(imgurl);
        load_images_muploader();
        tb_remove();
        //restore old send to editor function
        window.send_to_editor = window.restore_send_to_editor;
      }
      return false;
    });
  
  function microtime(get_as_float) { 
    var now = new Date().getTime() / 1000; 
    var s = parseInt(now); 
    return (get_as_float) ? now : (Math.round((now - s) * 1000) / 1000) + " " + s; 
  }
  function do_ajax_import_export(which){
    before_ajax_import_export(which);
    var group = jQuery("#option_group_name").val();
    var seq_selector = "#apc_" + which + "_nonce";
    var action_selctor = "apc_" + which+'_tr';
    jQuery.ajaxSetup({ cache: false });
    if (which == 'export')
      export_ajax_call(action_selctor,group,seq_selector,which);
    else
      import_ajax_call(action_selctor,group,seq_selector,which);
    jQuery.ajaxSetup({ cache: true });
  }

  function export_ajax_call(action,group,seq_selector,which){
    jQuery.getJSON(ajaxurl,
      {
        group: group,
        rnd: microtime(false), //hack to avoid request cache
        action: action,
        seq: jQuery(seq_selector).val()
      },
      function(data) {
        if (data){
          export_response(data);
        }else{
          alert("Something Went Wrong, try again later");
        }
        after_ajax_import_export(which);
      }
    );
  }
  function import_ajax_call(action,group,seq_selector,which){
    jQuery.post(ajaxurl,
      {
        group: group,
        rnd: microtime(false), //hack to avoid request cache
        action: action,
        seq: jQuery(seq_selector).val(),
        imp: jQuery("#export_code").val(),
      },
      function(data) {
        if (data){
           import_response(data);
        }else{
          alert("Something Went Wrong, try again later");
        }
        after_ajax_import_export(which);
      },
       "json"
    );
  }

  function before_ajax_import_export(which){
    jQuery(".export_status").hide("fast");
    jQuery(".export_results").html('').removeClass('alert-success').hide();
    jQuery(".export_status").show("fast");      
  }

  function after_ajax_import_export(which){
      jQuery(".export_status").hide("fast");
  }
  function export_response(data){
    if (data.code)
      jQuery('#export_code').val(data.code);
    if (data.nonce)
      jQuery("#apc_export_nonce").val(data.nonce);
    if(data.err)
      jQuery(".export_results").html(data.err).show('slow');
  }
  function import_response(data){
    if (data.nonce)
      jQuery("#apc_import_nonce").val(data.nonce);
    if(data.err)
      jQuery(".export_results").html(data.err).show();
    if (data.success)
      jQuery(".export_results").html(data.success).addClass('alert-success').show('slow');
  }

  jQuery("#apc_import_b").bind("click",function(){
    if($('#export_code').val()=='')
    {
        jQuery(".export_results").html('Please Add Code below to Import').show();
        return false;
    }    
    do_ajax_import_export('import');
  });

  jQuery("#apc_export_b").bind("click",function(){
    do_ajax_import_export('export');
  });

  jQuery("#apc_refresh_page_b").bind("click",function(){
    refresh_page();
  });
  $('form#admin_options_form').submit(function(){
    var $this = $(this);
    $('.msg_alert_success',$this).html('Saving...').css('display','block').addClass('saving');
     $.ajax({
        url: location.href,
        data: $(this).serialize() + '&is_ajax=true',
        type:'post',
        success:function(rs){
            $('.msg_alert_success',$this).html('The options are been saved!').css('display','block').removeClass('saving');
            setTimeout(function(){$('.msg_alert_success',$this).slideUp()},5000);
        }
     })
    return false;
  });
  $('input.restore_button').bind('click',function(){
    $this = $('form#admin_options_form');
    if(confirm("Do you want restore Default options?")==false)return false;
    $('.msg_alert_success',$this).html('Restoring...').css('display','block').addClass('saving');
     $.ajax({
        url: location.href,
        data: $this.serialize() + '&is_ajax=true&restore=default',
        type:'post',
        success:function(rs){
            $('.msg_alert_success',$this).html('The options are been restored!').css('display','block').removeClass('saving');
            refresh_page();
        }
     })
    return false;
  })
  $('.msg_alert_success').hover(function(){
    if($(this).hasClass('saving'))return;
    $(this).slideUp()
  })

  /**
   * refresh_page 
   * @since 0.8
   * @return void
   */
  function refresh_page(){
    location = location.href;
  }
  $('input[type="checkbox"]').each(function(){
    if($(this).attr('target') && $(this).attr('target').length>0)
    {
        $(this).change(function(){
            var $tg = $(this).attr('target');
            $tg = $tg.split(',');
            var show = ($(this).is(':checked'))? true: false;
            for(i in $tg)
            {
                if(i=='in_array')continue;
                if(show)
                    $("#field_"+$tg[i]).slideDown();
                else 
                    $("#field_"+$tg[i]).slideUp();
            }
        }).change();
    }
  });
  
    
    var last_tab = getCookie("apc_last_tab");
    if (last_tab) {
       var last_tab = last_tab;
    }else{
       var last_tab = null;
    } 
  function show_tab(li){
    if (!$(li).hasClass("active_tab")){
      //hide all
      $(".setingstab").hide("slow");
      $(".panel_menu li").removeClass("active_tab");
      $(".panel_menu li").removeClass("active_parent_tab");
      tab  = $(li).find("a").attr("href");
      $(li).addClass("active_tab");
      $(tab).show("fast");
      setCookie("apc_last_tab",tab);
      li.parents("li").addClass("active_parent_tab");
      $("li:first-child a",li).click();
    }
  }
  //hide all
  if($(".setingstab").length==0)return;
  $(".setingstab").hide();

  //set first_tab as active if no cookie found
  if (last_tab == null){
    $(".panel_menu li:first").addClass("active_tab");
    var tab  = $(".panel_menu li:first a").attr("href");
    $(tab).show();
  }else{
    var la = $('[href="' + last_tab + '"]');
    if(la.hasClass('nav_tab_link'))
        show_tab(la.parent());
    else{$(".panel_menu li:first").addClass("active_tab");
    var tab  = $(".panel_menu li:first a").attr("href");
    $(tab).show();}
  }

  //bind click on menu action to show the right tab.
  $(".panel_menu li a").bind("click", function(event){
    event.preventDefault()
    show_tab($(this).parent("li"));

  });
  
  function load_images_muploader(){
    jQuery(".mupload_img_holder").each(function(i,v){
      if (jQuery(this).next().next().val() != ""){
        jQuery(this).append("<img src=\"" + jQuery(this).next().next().val() + "\" style=\"height: 100px;width: 100px;\" />");
        jQuery(this).next().next().next().val("Delete");
        jQuery(this).next().next().next().removeClass("apc_upload_image_button").addClass("apc_delete_image_button");
      }
    });
  }
  //upload button
  var formfield1;
  var formfield2;
  jQuery("#image_button").click(function(e){
    if(jQuery(this).hasClass("apc_upload_image_button")){
      formfield1 = jQuery(this).prev();
      formfield2 = jQuery(this).prev().prev();
      tb_show("", "media-upload.php?type=image&amp;apc=insert_file&amp;TB_iframe=true");
      return false;
    }else{
      var field_id = jQuery(this).attr("rel");
      var at_id = jQuery(this).prev().prev();
      var at_src = jQuery(this).prev();
      var t_button = jQuery(this);
      data = {
        action: "apc_delete_mupload",
        _wpnonce: $("#nonce-delete-mupload_" + field_id).val(),
        field_id: field_id,
        attachment_id: jQuery(at_id).val()
      };

      $.post(ajaxurl, data, function(response) {
        if ("success" == response.status){
          jQuery(t_button).val("Upload Image");
          jQuery(t_button).removeClass("apc_delete_image_button").addClass("apc_upload_image_button");
          //clear html values
          jQuery(at_id).val("");
          jQuery(at_src).val("");
          jQuery(at_id).prev().html("");
          load_images_muploader();
        }else{
          alert(response.message);
        }
      }, "json");

      return false;
    }
    
  });
  


  //store old send to editor function
  window.restore_send_to_editor = window.send_to_editor;
  //overwrite send to editor function
  window.send_to_editor = function(html) {
    imgurl = jQuery("img",html).attr("src");
    img_calsses = jQuery("img",html).attr("class").split(" ");
    att_id = "";
    jQuery.each(img_calsses,function(i,val){
      if (val.indexOf("wp-image") != -1){
        att_id = val.replace("wp-image-", "");
      }
    });

    jQuery(formfield2).val(att_id);
    jQuery(formfield1).val(imgurl);
    load_images_muploader();
    tb_remove();
    //restore old send to editor function
    window.send_to_editor = window.restore_send_to_editor;
  }
});