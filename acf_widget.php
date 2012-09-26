<?php

class acf_Widget extends acf_Field
{

    //creating a unique string we can use for inheritance
    const INHERIT_STRING = '--INHERIT--';
	
	/*--------------------------------------------------------------------------------------
	*
	*	Constructor
	*
	*	@author Elliot Condon
	*	@since 1.0.0
	*	@updated 2.2.0
	* 
	*-------------------------------------------------------------------------------------*/
	
	function __construct($parent)
	{
    	parent::__construct($parent);
    	
    	$this->name = 'widget_field';
		$this->title = __("Widget List",'acf');

		add_action('wp_ajax_acf_get_widget_results', array($this, 'acf_get_widget_results'));
   	}


    /*--------------------------------------------------------------------------------------
     *
     *	get_widget_name
     *	- this function is called by get_widgets and create_field on edit screens to produce the html for this field
     *
     *	@author Dallas Johnson
     *
     *-------------------------------------------------------------------------------------*/
    function get_widget_name($id)
    {
        if($id == self::INHERIT_STRING)
            return '-------- Inherit From Parent --------';

        global $wp_registered_widgets;

        if ( !isset($wp_registered_widgets[$id]) )
            return false;

        $classname = $wp_registered_widgets[$id]['callback'][0]->id_base;
        $instance = $wp_registered_widgets[$id]['params'][0]['number'];

        if(!$option_list = get_option($classname))
            $option_list = get_option('widget_'.$classname);

        return ((strlen($option_list[$instance]['title']) > 0) ? $option_list[$instance]['title'] : 'No Title') . ' (' . $wp_registered_widgets[$id]['name'] . ')';
    }


    /*--------------------------------------------------------------------------------------
     *
     *	get_widgets
     *	- this function is called by create_field on edit screens to produce the html for this field
     *
     *	@author Dallas Johnson
     *
     *-------------------------------------------------------------------------------------*/
    function get_widgets($options)
    {
        global $wp_registered_sidebars, $wp_registered_widgets;

        if(is_int($options['sidebar'])):
            $index = 'sidebar-' . $options['sidebar'];
        else:
            $index = sanitize_title($options['sidebar']);
            foreach ( (array) $wp_registered_sidebars as $key => $value ):
                if ( sanitize_title($value['name']) == $index ):
                    $index = $key;
                    break;
                endif;
            endforeach;
        endif;

        $sidebars_widgets = wp_get_sidebars_widgets();

        //set our default
        $posts = array();

        if ( empty($wp_registered_sidebars[$index]) || !array_key_exists($index, $sidebars_widgets) || !is_array($sidebars_widgets[$index]) || empty($sidebars_widgets[$index]) )
            return $posts;

        if(isset($options['inherit_from']) and !($options['inherit_from']==''))
            $posts[] = (object)array(
                'ID' => self::INHERIT_STRING,
                'title' => $this->get_widget_name(self::INHERIT_STRING)
            );

        //loop through widgets in sidebar, add them to posts
        foreach ( (array) $sidebars_widgets[$index] as $id ) :

            $post = array(
                'ID' => $id,
                'title' =>  $this->get_widget_name($id)
            );

            $posts[] = (object)$post;

        endforeach;

        return $posts;
    }
   	
   	
   	/*--------------------------------------------------------------------------------------
	*
	*	acf_get_widget_results
	*
	*	@author Elliot Condon
	*   @description: Generates HTML for Left column relationship results
	*   @created: 5/07/12
	* 
	*-------------------------------------------------------------------------------------*/
	
   	function acf_get_widget_results()
   	{
   		// vars
		$options = array(
			'sidebar'	    => '',
            'inherit_from'  => ''
		);

		$ajax = isset( $_POST['action'] ) ? true : false;

		// override options with posted values
		if( $ajax )
		{
			$options = array_merge($options, $_POST);
		}

		// load the widget list
		$posts = $this->get_widgets( $options );
		
		if( $posts )
		{
			foreach( $posts  as $post )
			{
                if(!$post->title) continue;

				// find title. Could use get_the_title, but that uses get_post(), so I think this uses less Memory
				$title = apply_filters( 'the_title', $post->title, $post->ID );

				echo '<li><a href="javascript:;" data-post_id="' . $post->ID . '">' . $title . '<span class="add"></span></a></li>';
			}
		}
		
		// die?
		if( $ajax )
		{
			die();
		}
	}


    /*--------------------------------------------------------------------------------------
     *
     *	admin_print_scripts / admin_print_styles
     *
     *	@author Elliot Condon
     *	@since 3.0.0
     *
     *-------------------------------------------------------------------------------------*/

    function admin_print_scripts()
    {
        wp_enqueue_script(array(
            'jquery-ui-sortable',
        ));
    }

    function admin_print_styles()
    {

    }


    /*--------------------------------------------------------------------------------------
     *
     *	create_field
     *
     *	@author Elliot Condon
     *	@since 2.0.5
     *	@updated 2.2.0
     *
     *-------------------------------------------------------------------------------------*/
	
	function create_field($field)
	{
		// vars
		$defaults = array(
			'sidebar'	    =>	'',
			'max' 		    =>	-1,
			'inherit_from' 	=>	'',
		);
		
		$field = array_merge($defaults, $field);
		
		// validate types
		$field['max'] = (int) $field['max'];
		
		
		// row limit <= 0?
		if( $field['max'] <= 0 )
		{
			$field['max'] = 9999;
		}

		?>
<div class="acf_relationship" data-max="<?php echo $field['max']; ?>" data-action="acf_get_widget_results" data-s="" data-paged="1" data-sidebar="<?php echo $field['sidebar']; ?>" data-inherit_from="<?php echo $field['inherit_from']; ?>">
	
	<!-- Hidden Blank default value -->
	<input type="hidden" name="<?php echo $field['name']; ?>" value="" />
	
	<!-- Template for value -->
	<script type="text/html" class="tmpl-li">
	<li>
		<a href="#" data-post_id="{post_id}">{title}<span class="remove"></span></a>
		<input type="hidden" name="<?php echo $field['name']; ?>[]" value="{post_id}" />
	</li>
	</script>
	<!-- / Template for value -->
	
	<!-- Left List -->
	<div class="relationship_left">
		<table class="widefat">
			<thead>
				<tr>
					<th>
						<label class="relationship_label" for="relationship_<?php echo $field['name']; ?>"><?php _e("Search",'acf'); ?>...</label>
						<input class="relationship_search" type="text" id="relationship_<?php echo $field['name']; ?>" />
						<div class="clear_relationship_search"></div>
					</th>
				</tr>
			</thead>
		</table>
		<ul class="bl relationship_list">
			<li class="load-more">
				<div class="acf-loading"></div>
			</li>
		</ul>
	</div>
	<!-- /Left List -->
	
	<!-- Right List -->
	<div class="relationship_right">
		<ul class="bl relationship_list">
		<?php

		if( $field['value'] )
		{
			foreach( $field['value'] as $widget )
			{
                $post = array(
                    'ID'    => $widget,
                    'title' => $this->get_widget_name($widget)
                );

				echo '<li>
					<a href="javascript:;" class="" data-post_id="' . $post['ID'] . '">' . $post['title'] . '<span class="remove"></span></a>
					<input type="hidden" name="' . $field['name'] . '[]" value="' . $post['ID'] . '" />
				</li>';
			}
		}
			
		?>
		</ul>
	</div>
	<!-- / Right List -->
	
</div>
		<?php

	
	}
	
	
	/*--------------------------------------------------------------------------------------
	*
	*	create_options
	*
	*	@author Elliot Condon
	*	@since 2.0.6
	*	@updated 2.2.0
	* 
	*-------------------------------------------------------------------------------------*/
	
	function create_options($key, $field)
	{
        global $wp_registered_sidebars;

		// vars
		$defaults = array(
			'sidebar'	    =>	'',
			'max' 		    =>	'',
			'inherit_from' 	=>	''
		);
		
		$field = array_merge($defaults, $field);
		
		?>
        <tr class="field_option field_option_<?php echo $this->name; ?>">
            <td class="label">
                <label for=""><?php _e("Sidebar",'acf'); ?></label>
            </td>
            <td>
            <?php
            $sidebars = array();

            foreach ((array) $wp_registered_sidebars as $sidebar){
                if(!is_active_sidebar($sidebar['id']))
                    continue;

                $sidebars[$sidebar['id']] = $sidebar['name'];
            }

            $this->parent->create_field(array(
                'type'	    =>	'select',
                'name'	    =>	'fields['.$key.'][sidebar]',
                'value'	    =>	$field['sidebar'],
                'choices'	=>	$sidebars,
                'multiple'	=>	'0',
            ));

            ?>
			</td>
		</tr>
        <tr class="field_option field_option_<?php echo $this->name; ?>">
            <td class="label">
                <label><?php _e("Inherit From",'acf'); ?></label>
            </td>
            <td>
            <?php
            $options = array(
                ''          => 'None',
                'page'      => 'Page Structure',
                'menu'      => 'Menu Structure'
            );

            $this->parent->create_field(array(
                'type'      => 'select',
                'name'      => 'fields['.$key.'][inherit_from]',
                'value'     => $field['inherit_from'],
                'choices'   => $options
            ));
            ?>
            </td>
        </tr>
		<?php
	}


    /*--------------------------------------------------------------------------------------
     *
     *	get_value
     *	- called from the edit page to get the value of your field. This function is useful
     *	if your field needs to collect extra data for your create_field() function.
     *
     *	@params
     *	- $post_id (int) - the post ID which your value is attached to
     *	- $field (array) - the field object.
     *
     *	@author Elliot Condon
     *	@since 2.2.0
     *
     *-------------------------------------------------------------------------------------*/

    function get_value($post_id, $field)
    {
        // get value
        $value = parent::get_value($post_id, $field);

        // format value

        // return value
        return $value;
    }



    /*--------------------------------------------------------------------------------------
    *
    *	STATIC FUNCTIONS
    *
    *-------------------------------------------------------------------------------------*/


    /*--------------------------------------------------------------------------------------
     *
     *	dynamic_widgets
     *	- this function is called by sidebar.php and retrieves filtered widget list for page
     *
     *	@author Dallas Johnson
     *
     *-------------------------------------------------------------------------------------*/
    static function dynamic_widgets($index = 1)
    {
        global $wp_registered_sidebars, $wp_registered_widgets;

        if ( is_int($index) ) {
            $index = "sidebar-$index";
        } else {
            $index = sanitize_title($index);
            foreach ( (array) $wp_registered_sidebars as $key => $value ) {
                if ( sanitize_title($value['name']) == $index ) {
                    $index = $key;
                    break;
                }
            }
        }

        $sidebars_widgets = wp_get_sidebars_widgets();

        if ( empty($wp_registered_sidebars[$index]) || !array_key_exists($index, $sidebars_widgets) || !is_array($sidebars_widgets[$index]) || empty($sidebars_widgets[$index]) )
            return false;

        $sidebar = $wp_registered_sidebars[$index];

        /*--------------------------------------------
          * dynamic_widgets (like dynamic_sidebars) uses the sidebar index.
          * the sidebar option isn't pulled by default from acf's get_field so
          * we need to loop through our acf fields and find the fields with "sidebar" options.
          * the field with the sidebar option that matches our index is what we're after.
          * we can use get_field from that point to retrieve our widget list
          * and remove the widgets that aren't in our list.
          * everything else in this function is default wp dynamic_sidebar function
          *---------------------------------------------*/
        global $acf;
        $post = get_queried_object();

        //set defaults
        $acf_field = '';
        $include_list = array();

        //get acf fields for loop

        $acf_fields = get_fields($post->ID);

        //loop acf fields to get our field key
        if($acf_fields):
            foreach($acf_fields as $key => $field):

                //get acf field key
                $field_key = get_post_meta($post->ID, '_' . $key, true);

                if($field_key != ""):

                    //if it's an acf field, get the field's acf structure
                    $field = $acf->get_acf_field($field_key);

                    //see if it has a "sidebar" option and if it matches our index
                    if(isset($field['sidebar']) and $field['sidebar'] == $index):

                        //this field matches, set $acf_field to this one
                        $acf_field = $key;

                        //quit the loop, we have our match
                        break;
                    endif;

                endif;

            endforeach;

            if($acf_field):
                if(get_field($acf_field, $post->ID)):

                    isset($field['inherit_from'])):

                        //menu inheritance
                        if($field['inherit_from'] == 'menu')
                            $parent = 'menu';

                        //page inheritance
                        elseif($field['inherit_from'] == 'page')
                            $parent = 'page';

                        //no inheritance
                        else
                            $parent = false;

                    endif;

                    if($parent)
                        $page_widgets = self::getWidgetsFromParent($post, $parent, $field['name'], $include_list);

                    if(is_array($page_widgets))
                        $include_list = $page_widgets;

                endif;
            endif;
            $sidebars_widgets[$index] = $include_list;

        endif;
        /*--------------------------------------------
          * end acf custom
          *---------------------------------------------*/

        $did_one = false;
        foreach ( (array) $sidebars_widgets[$index] as $id ) {

            if ( !isset($wp_registered_widgets[$id]) ) continue;

            $params = array_merge(
                array( array_merge( $sidebar, array('widget_id' => $id, 'widget_name' => $wp_registered_widgets[$id]['name']) ) ),
                (array) $wp_registered_widgets[$id]['params']
            );

            // Substitute HTML id and class attributes into before_widget
            $classname_ = '';
            foreach ( (array) $wp_registered_widgets[$id]['classname'] as $cn ) {
                if ( is_string($cn) )
                    $classname_ .= '_' . $cn;
                elseif ( is_object($cn) )
                    $classname_ .= '_' . get_class($cn);
            }
            $classname_ = ltrim($classname_, '_');
            $params[0]['before_widget'] = sprintf($params[0]['before_widget'], $id, $classname_);

            $params = apply_filters( 'dynamic_sidebar_params', $params );

            $callback = $wp_registered_widgets[$id]['callback'];

            do_action( 'dynamic_sidebar', $wp_registered_widgets[$id] );

            if ( is_callable($callback) ) {
                call_user_func_array($callback, $params);
                $did_one = true;
            }
        }

        return $did_one;
    }


    /*--------------------------------------------------------------------------------------
     *
     *	getWidgetsFromCommunity
     *	- this function retrieves all inherited widgets for postID
     *
     *	@author Dallas Johnson
     *
     *-------------------------------------------------------------------------------------*/
    static function getWidgetsFromParent($post,$parent,$field,&$include_list)
    {
        $widgets = get_field($field,$post->ID);

        if($widgets):
            $include_list = array_merge($include_list,(array)$widgets);

            //see if this list inherits
            if( ($i = array_search(self::INHERIT_STRING,$include_list)) !== false ):

                //it does, remove our inherit string
                unset($include_list[$i]);

                switch($parent):

                    case 'page':

                        //get post ID for parent and run again
                        $parentPost = ($post->post_parent) ? get_post($post->post_parent) : false;
                        break;

                    case 'menu':

                        //get the menu id so we can pull menu parent
                        $menuID = self::getMenuIDFromPostID($post->ID);

                        //get parent
                        $parentMenu = ($menuID) ? get_post_meta($menuID, '_menu_item_menu_item_parent', true) : false;

                        if($parentMenu):
                            //we have a parent menu, get the post ID for it
                            $parentID = self::getPostIDFromMenuID($parentMenu);
                            $parentPost = get_post($parentID);
                        else:
                            $parentPost = false;
                        endif;
                        break;

                endswitch;

                if($parentPost)
                    self::getWidgetsFromParent($parentPost,$parent,$field,$include_list);

            endif;

        endif;
    }


    /*--------------------------------------------------------------------------------------
     *
     *	getMenuIDFromPost
     *	- this function retrieves menu ID for post
     *
     *	@author Dallas Johnson
     *
     *-------------------------------------------------------------------------------------*/
    static function getMenuIDFromPostID($postID = 0){
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT m.post_id FROM $wpdb->postmeta m INNER JOIN $wpdb->posts p ON m.post_id = p.id " .
                "WHERE m.meta_key = '_menu_item_object_id' AND m.meta_value = %d " .
                "AND p.post_status = 'publish' AND p.post_type='nav_menu_item'", $postID));
    }


    /*--------------------------------------------------------------------------------------
    *
    *	getPostIDFromMenu
    *	- this function retrieves post ID for menu
    *
    *	@author Dallas Johnson
    *
    *-------------------------------------------------------------------------------------*/
    static function getPostIDFromMenuID($menuID = 0){
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT m.meta_value FROM $wpdb->postmeta m INNER JOIN $wpdb->posts p ON m.post_id = p.id " .
                "WHERE m.meta_key = '_menu_item_object_id' AND m.post_id = %d " .
                "AND p.post_status = 'publish' AND p.post_type='nav_menu_item'", $menuID));
    }

}

?>