<?php
/**
 * Create meta boxes
 * 
    $meta_boxs = array();
    $meta_boxs[]=array(
    	'id'=>'page-meta-box-3',
    	'title'=>'Meta Data',
    	'page'=> 'cpt_showcase',
    	'context'=>'normal',
    	'priority'=>'high',
    	'fields'=>array(                
                    array(
    					'id'=>"_otherimages",
    					'label'=>"Images:",
    					'type'=>"imgbox",
                        'desc' => ''
    				),
    		)
    );
    
    foreach($meta_boxs as $meta_box)
        new TR_Meta_Box_V3($meta_box);
 */
 
class TR_Meta_Box_V3 {

	protected $_meta_box;

	// create meta box based on given data
	function __construct($meta_box) {
		if (!is_admin()) return;

		$this->_meta_box = $meta_box;
		
        $this->add();

		add_action('save_post', array(&$this, 'save'));
        
        $this->folder_root = dirname(dirname(__FILE__)).'/';
        if (stripos(__FILE__,'wp-content/themes') !==false){
            $this->SelfPath = get_stylesheet_directory_uri() . '/admin';
        }
        else{
            $this->SelfPath = plugins_url( 'admin', plugin_basename( $this->folder_root ) );
        }
	}
    
	/// Add meta box for multiple post types
	function add() {
		$this->_meta_box['context'] = empty($this->_meta_box['context']) ? 'normal' : $this->_meta_box['context'];
		$this->_meta_box['priority'] = empty($this->_meta_box['priority']) ? 'high' : $this->_meta_box['priority'];
        if(!is_array($this->_meta_box['page']))
        {
            $this->_meta_box['page'] = array($this->_meta_box['page']);
        }
		foreach ($this->_meta_box['page'] as $page) {
			add_meta_box($this->_meta_box['id'], $this->_meta_box['title'], array(&$this, 'show'), $page, $this->_meta_box['context'], $this->_meta_box['priority']);
		}
	}

	// Callback function to show fields in meta box
	function show() {
		global $post;
        
        //add_css
        wp_enqueue_style( 'Admin_Page_Class', $this->SelfPath . '/css/admin.css',array(),'3.0');
        wp_enqueue_script( 'admin_post_meta', $this->SelfPath . '/js/post.js',array(),'3.0' );
        if (! did_action( 'wp_enqueue_media' ) )
        {            
            add_thickbox();
            wp_enqueue_media(  array('post' => $post->ID) );
        }

		// Use nonce for verification
		echo '<input type="hidden" name="wp_meta_box_nonce" value="', wp_create_nonce(basename(__FILE__)), '" />';

		echo '<table class="form-table custom-meta-tbl">';

		foreach ($this->_meta_box['fields'] as $field) {
			// get current post meta data
			$meta = get_post_meta($post->ID, $field['id'], true);
            if(empty($field['name']))$field['name'] = $field['id'];
            
            if(empty($field['label']))
            {
                echo '<tr><td colspan="2">';
            }else
            {
                echo '<tr>',
					'<th style="width:20%"><label for="', $field['id'], '">', $field['label'], '</label></th>',
					'<td>';
            }
            
            $field['field_data'] = '';
            if($field['filetype'])
            {
                if($field['filetype']=='pdf')$field['filetype']='application/pdf';
                $field['field_data'].=' data-filetype="'.$field['filetype'].'" ';
            }
			
			switch ($field['type']) {
				case 'text':
					echo '<input type="text" name="', $field['id'], '" id="', $field['id'], '" value="',  $meta ? esc_attr($meta) : esc_attr($field['std']), '" size="30" style="width:97%" />',
						'<br />', $field['desc'];
					break;
				case 'textarea':
					echo '<textarea name="', $field['id'], '" id="', $field['id'], '" cols="60" rows="6" style="width:97%;'.(!empty($field['height'])?'height:'.$field['height']:'').'">', $meta ? $meta : $field['std'], '</textarea>',
						'<br />', $field['desc'];
					break;
				case 'select':
					echo '<select name="', $field['id'], '" id="', $field['id'], '">';
					foreach ($field['options'] as $id => $opt) {
					    $option['value'] = isset($opt['value'])? $opt['value'] : $id;
                        $option['name'] = isset($opt['name'])? $opt['name'] : $opt;
						echo '<option value="', $option['value'], '"', $meta == $option['value'] ? ' selected="selected"' : '', '>', $option['name'], '</option>';
					}
					echo '</select>';
					break;
				case 'radio':
					foreach ($field['options'] as $option) {
						echo '<input type="radio" name="', $field['id'], '" value="', $option['value'], '"', $meta == $option['value'] ? ' checked="checked"' : '', ' />', $option['name'];
					}
					break;
				case 'checkbox':
					echo '<input type="checkbox" name="', $field['id'], '" id="', $field['id'], '"', $meta ? ' checked="checked"' : '', ' />';
					break;
				case 'wysiwyg':
                    echo $field['std'];
                    echo '<div class="postarea">';
                    $text = ($meta ? $meta : $field['std']);
                    the_editor($text,$field['id']);                    
					echo '</div>';
                    echo $field['desc'];
					break;
                case 'file':
				case 'image':
                    if(!empty($meta))
                    {
                        $img_link = wp_get_attachment_image_src($meta);
                        $img_link = $img_link[0];
                        
                        if(empty($img_link))
                         {
                            $file = wp_get_attachment_url($meta);
                            $name = get_post($meta);
                            $name = $name->post_title;
                         }
                    }
                    $title = $field['title']? $field['title'] : 'Upload Image';
                     
                    echo '<span id="imagelist_'.$field['id'].'">';
                    if(!empty($meta))
                    {
                        if(intval($meta)==0)continue;
                        
                         $gThumb = wp_get_attachment_image_src($meta);
                         if(empty($gThumb[0]))
                         {
                            $file = wp_get_attachment_url($meta);
                            $name = get_post($meta);
                            $name = $name->post_title;
                         }
                    ?>
                    <span class="imagelist listimg">
                        <input type="hidden" name="<?php echo $field['id']?>" value="<?php echo $meta?>" />
                        <?php if($gThumb[0]):?>
                            <img src="<?php echo $gThumb[0]?>" width="50" height="50"/>
                        <?php else:?>
                            <span class="filename"><a href="<?php echo $file?>" target="_blank"><?php echo $name?></a></span>
                        <?php endif;?>
                        <a rel="<?php echo $meta?>" pid="<?php echo $post->ID ?>" class="removeimg">Remove</a>
                    </span>
                    <?php
                    }
                    echo '</span>';
                    ?>
                    <a rel="<?php echo $field['id']?>" class="upload_image_button button" <?php echo $field['field_data']?>  data-title="<?php echo $field['label']? $field['label'] : 'Insert Media'?>" href="#upload"><?php echo $title?></a>
                    
                    <?php
					echo '<br>', $field['desc'];
                    break;
                //boxes
                case 'box':
                    $this->creatboxes($meta,$field);
                    break;
                    
                case 'images':
                    $this->creatImages($meta,$field);
                    break;  
                
                case 'optionbox':
                    $this->creatOptionboxs($meta,$field);
                    break;
                
               
			}
			echo 	'<td>',
				'</tr>';
		}

		echo '</table>';
        if($this->_meta_box['richbox'] == 'hide')echo '<style type="text/css">#postdivrich {display:none;}</style>';
	}
    
    function creatImages($meta,$field)
    {
        global $post;
        if(!is_array($meta))$meta = array();
        $meta = (array)$meta;
        $title = $field['title']? $field['title'] : 'Upload Image';
        echo '<div id="imagelist_'.$field['id'].'" class="ui-sortable">';
        foreach($meta as $att_id)
        {
            if(intval($att_id)==0)continue;
            
             $gThumb = wp_get_attachment_image_src($att_id);
             if(empty($gThumb[0]))
             {
                $file = wp_get_attachment_url($att_id);
                $name = get_post($att_id);
                $name = $name->post_title;
             }
        ?>
        <span class="imagelist listimg">
            <input type="hidden" name="<?php echo $field['id']?>[]" value="<?php echo $att_id?>" />
            <?php if($gThumb[0]):?>
                <img src="<?php echo $gThumb[0]?>" width="50" height="50"/>
            <?php else:?>
                <span class="filename"><a href="<?php echo $file?>" target="_blank"><?php echo $name?></a></span>
            <?php endif;?>
            <a rel="<?php echo $att_id?>" pid="<?php echo $post->ID ?>" class="removeimg">Remove</a>
        </span>
        <?php
        }
        echo '</div><div class="clear">&nbsp;</div>';
        ?>
        <a rel="<?php echo $field['id']?>" class="upload_image_button button" <?php echo $field['field_data']?> data-multiple="1"  data-title="<?php echo $field['label']? $field['label'] : 'Insert Media'?>" href="#upload"><?php echo $title?></a>
        <script>
        jQuery(function(){
            jQuery("#<?php echo 'imagelist_'.$field['id']?>").sortable({
                cursor: 'move'
            });
        })
        </script>
        <?php
    }
    
    function creatOptionboxs($meta,$field)
    {
        global $post;
        ?>
        <div class="list_option" id="options_<?php echo $field['id']?>">
            <?php 
            $listoptions = get_option('tr_option_'.$field['id'],array());
            if(!is_array($meta))$meta = array();
            foreach($listoptions as $id=>$text)
            {
                $value = @$meta[$id]['vl'];
                ?>
                <div class="option">
                    <label><input type="checkbox" <?php if($meta[$id]['id'])echo 'checked'?> name="<?php echo $field['id']?>[<?php echo $id?>][id]" value="<?php echo $id?>" /> <?php echo $text?></label>
                    <?php tr_get_value_options_type($field,$id,$value)?>                    
                    <a class="removeoption" oid="<?php echo $id?>" fid="<?php echo $field['id']?>">Remove</a>
                </div>
                <?php
            }
            ?>
        </div>
        <div class="boxaddnewoption">
            <input type="text" id="createnewoption" />
            <a class="button createnewoptionbutton" opt="<?php echo base64_encode(json_encode($field['valueoptions']))?>" rel="<?php echo $field['id']?>" pid="<?php echo $post->ID ?>">Add New</a>
        </div>
                
        <?php
        global $show_script_for_option_abc;
        if(!$show_script_for_option_abc)
        {
            $show_script_for_option_abc = true;
        ?>
        <script>
        (function($){
            $('a.createnewoptionbutton').live('click',function(){
               var prnew= $(this).parents('.boxaddnewoption');
               var fid = $(this).attr('rel');
               var pid = $(this).attr('pid');
               var opt= $(this).attr('opt');
               var vl = prnew.find('input#createnewoption').val();
               if(vl==''){alert('Please enter text');return false};
               $.ajax({
                    url: ajaxurl,
                    type: 'post',
                    data:{'action':'trcreatenewoption','fid':fid,'pid':pid,'text':vl,'opt':opt},
                    success:function(rs){
                        $('#options_'+fid).append(rs);
                        prnew.find('input#createnewoption').val('')
                    }
               })
            });
            $('input#createnewoption').live('keypress',function(e){
                if(e.keyCode==13){
                    $(this).parents('.boxaddnewoption').find('a.button').click();
                    return false;
                };
            })
            $('.list_option .option a.removeoption').live('click',function(){
                if(confirm('Do you want remove?')==false)return false;
                var fth =$(this);
                $.ajax({
                    url: ajaxurl,
                    type: 'post',
                    data:{'action':'trremoveoption','fid':fth.attr('fid'),'id':fth.attr('oid')},
                    success:function(rs){
                        if(rs=='ok')
                        {
                            fth.parents('.option').remove();
                        }
                    }
               })
            })
        })(jQuery)
        </script>
        <?php
        }
    }
    
    function creatboxes($meta,$field)
    {
        global $showed_script_for_boxs;
        if(!$showed_script_for_boxs)
        {
        
        $showed_script_for_boxs = true;
        ?>
        <div class="box_bank" style="display: none;">
                <div class="row">
                    <label><span class="tt">Title</span> <span class="idrow"></span></label>
                    <input type="text" id="title" />
                </div>
                <div class="row">
                    <label class="vl">Value</label>
                    <input type="text" id="value" />
                </div>
                <div class="remove"><a class="removebox">Remove</a></div>
        </div>
        <script>
        var $ = jQuery;
        function tradd_box(title,value,fieldname)
        {        
            var prdiv =$('#boxes'+fieldname);            
            var countbox = prdiv.attr('countbox');
            if(countbox==undefined )countbox = 0;
            countbox = parseInt(countbox);
            var fname = fieldname+'['+countbox+']';
            var newrow= $('<div class="box"></div>');
            newrow.append($('.box_bank').html());
            prdiv.append(newrow);
            newrow.find('#title').attr('name',fname+'[title]').val(title);
            newrow.find('#value').attr('name',fname+'[value]').val(value);
            newrow.find('.idrow').html(countbox+1);
            countbox ++;
            prdiv.attr('countbox',countbox);
        }
        
        (function($){
            $('a.removebox').live('click',function(){
                $(this).parents('.box').remove();
            });            
        })(jQuery)
        </script>
        <?php
        }
        $meta = (array)$meta;
        
        $script_addbox='';
        //print_r($meta);
        if(is_array($meta) && count($meta)>0)
        {
            foreach($meta as $data)
            {
                foreach((array)$data as $k => $vl)
                {
                    $vl       = trim($vl);
                    $vl       = str_replace(array("\n","\r"),array('[br]',' '),$vl);
                    $data[$k] = addslashes ($vl);                
                }
                if(empty($data['title']))continue;
                
                $script_addbox.='tradd_box("'.$data['title'].'","'.$data['value'].'","'.$field['id'].'");'."\n";
            }
        }
        ?>
        <div class="boxes" id="boxes<?php echo $field['id']?>"></div>        
        <a class="button" id="addbox<?php echo $field['id']?>" >Add Row</a>        
        <script>
        (function($){
            $('a#addbox<?php echo $field['id']?>').live('click',function(){
                tradd_box('','','<?php echo $field['id']?>');
                return false;
            });
            <?php echo $script_addbox?>
        })(jQuery)
        </script>
        
        <?php
        
    }

	// Save data from meta box
	function save($post_id) {
		// verify nonce
		if (!wp_verify_nonce($_POST['wp_meta_box_nonce'], basename(__FILE__))) {
			return $post_id;
		}

		// check autosave
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return $post_id;
		}

		// check permissions
		if ('page' == $_POST['post_type']) {
			if (!current_user_can('edit_page', $post_id)) {
				return $post_id;
			}
		} elseif (!current_user_can('edit_post', $post_id)) {
			return $post_id;
		}

		foreach ($this->_meta_box['fields'] as $field) {
			$name = $field['id'];

			$old = get_post_meta($post_id, $name, true);
			$new = $_POST[$field['id']];

			if ($field['type'] == 'wysiwyg') {
				//$new = wpautop($new);
			}

			if ($field['type'] == 'textarea') {
				$new = htmlspecialchars($new);
			}

			// validate meta value
			if (isset($field['validate_func'])) {
				$ok = call_user_func(array('Ant_Meta_Box_Validate', $field['validate_func']), $new);
				if ($ok === false) { // pass away when meta value is invalid
					continue;
				}
			}

			if ($new && $new != $old) {
				update_post_meta($post_id, $name, $new);
			} elseif ('' == $new && $old && $field['type'] != 'file' && $field['type'] != 'image') {
				delete_post_meta($post_id, $name, $old);
			}
		}
	}
	
}


