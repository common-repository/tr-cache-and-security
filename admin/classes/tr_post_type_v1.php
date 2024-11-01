<?php
class Tr_Post_Type_V1{
    
    public $post_types;
    public $taxonomies;
    public $slug_base_none;
    
    public function __construct()
    {
        $this->post_types = array();
        $this->taxonomies = array();
        $this->slug_base_none = array();
        
        if(is_admin())
        {
            add_action('admin_init',array($this,'admin_init'));
        }else 
        {
            add_filter('request',array($this,'request'));
        }
    }
    
    static public function create()
    {
        static $tr_post_type;
        
        if(!$tr_post_type)
        {
            $tr_post_type = new Tr_Post_Type_V1();
        }
        return $tr_post_type;
    }
    
    public function request($query)
    {
        if(count($this->slug_base_none)>0)
        {
            global $wp_query;
            $name = !empty($query['category_name'])? $query['category_name']: $query['name'];
            if(empty($name))$name = $query['pagename'];
            foreach($this->slug_base_none as $type)
            {
                $term = get_term_by('slug',$name,$type);
                if($term)
                {
                    unset($query['name']);
                    unset($query['category_name']);
                    unset($query['pagename']);
                    unset($query['post_type']);
                    $query[$type] = $name;
                }
            }
        }
        return $query;
    }
    
    public function admin_init()
    {
        foreach($this->taxonomies as $tax => $labels)
        {            
            $name = 'tax_'.$tax.'_base';
            if(isset($_POST[$name]))
            {
                update_option($name,$_POST[$name]);
            }
            add_settings_field($tax.'_base',$labels['name'],array($this,'tax_field'),'permalink','optional',$name);
        }
    }
    
    public function tax_field($name)
    {
        
        ?>
        <input type="text" class="regular-text code" value="<?php echo get_option($name)?>" id="<?php echo $name?>" name="<?php echo $name?>"/>
        <?php
    }
    public function register_post_type($type,$name,$names,$menu_name,$supports = null,$rewrite=true,$others = null)
    {
        $labels = array(
        'name' => $name,
        'singular_name' => $name,
        'add_new' => _x('Add New', 'entity'),
        'add_new_item' => __('Add New '.$name),
        'edit_item' => __('Edit '.$name),
        'new_item' => __('New '.$name),
        'all_items' => __('All '.$names),
        'view_item' => __('View '.$name),
        'search_items' => __('Search '.$names),
        'not_found' =>  __('No '.$names.' found'),
        'not_found_in_trash' => __('No '.$names.' found in Trash'), 
        'parent_item_colon' => '',
        'menu_name' => $menu_name
        
        );
        
        if($supports==null)
        {
            $supports = array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments' );
        }
        
        $args = array(
        'labels' => $labels,
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true, 
        'show_in_menu' => true, 
        'query_var' => true,
        'rewrite' => $rewrite,
        'has_archive' => true, 
        'hierarchical' => false,
        'menu_position' => null,
        'supports' => $supports
        ); 
        if(is_array($others))
        {
            $args = array_merge($args,$others);
        }
        register_post_type($type,$args);    
        $this->post_types[$type] = $args;
    }
    
    public function create_taxonomies($type,$name,$names,$menu,$post_types = null,$rewrite = false,$hierarchical = true) 
    {
      $labels = array(
        'name' => __( $names, 'taxonomy general name' ),
        'singular_name' => __( $name, 'taxonomy singular name' ),
        'search_items' =>  __( 'Search '.$names ),
        'all_items' => __( 'All '.$names ),
        'parent_item' => __( 'Parent '.$name ),
        'parent_item_colon' => __( 'Parent '.$name.':' ),
        'edit_item' => __( 'Edit '.$name ), 
        'update_item' => __( 'Update '.$name ),
        'add_new_item' => __( 'Add New '.$name ),
        'new_item_name' => __( 'New '.$name.' Name' ),
        'menu_name' => __( $menu ),
      ); 	
      if($rewrite)
      {
        $slugbase = get_option('tax_'.$type.'_base');
        if($slugbase)
        {
            if(!is_array($rewrite))$rewrite = array();
            $rewrite['slug']  = $slugbase;
            if($slugbase=='.' || ($slugbase==$post_types) || (is_array($post_types) && in_array($slugbase,$post_types)))
            {
                $this->slug_base_none[] = $type;
            }
        }
      }
    
      register_taxonomy($type,$post_types, array(
        'hierarchical' => $hierarchical,
        'labels' => $labels,
        'show_ui' => true,
        'query_var' => true,
        'rewrite' => $rewrite,
        '_builtin'=> true,        
      ));
      $this->taxonomies[$type] = $labels;
    }
}