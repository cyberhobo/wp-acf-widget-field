<?php

/*
 *	Advanced Custom Fields - Widget Relationship Field
 *
 */
 
 
class acf_Widget extends acf_Relationship
{
	//creating a unique string we can use for inheritance
	const INHERIT_STRING = '--INHERIT--';

	/*--------------------------------------------------------------------------------------
	*
	*	Constructor
	*	- This function is called when the field class is initalized on each page.
	*	- Here you can add filters / actions and setup any other functionality for your field
	*
	*	@author Dallas Johnson
	* 
	*-------------------------------------------------------------------------------------*/
	
	function __construct($parent)
	{
		// do not delete!
    	parent::__construct($parent);
    	
    	// set name / title
    	$this->name = 'widget_field'; // variable name (no spaces / special characters / etc)
		$this->title = __("Widget Filter",'acf'); // field label (Displayed in edit screens)
		
   	}

	
	/*--------------------------------------------------------------------------------------
	*
	*	create_options
	*	- this function is called from core/field_meta_box.php to create extra options
	*	for your field
	*
	*	@params
	*	- $key (int) - the $_POST object key required to save the options to the field
	*	- $field (array) - the field object
	*
	*	@author Dallas Johnson
	* 
	*-------------------------------------------------------------------------------------*/
	
	function create_options($key, $field)
	{
		global $wp_registered_sidebars;
		
		// defaults
		$field['sidebar'] = isset($field['sidebar']) ? $field['sidebar'] : '';
		$field['inherit_from'] = isset($field['inherit_from']) ? $field['inherit_from'] : '';
		?>
		<tr class="field_option field_option_<?php echo $this->name; ?>">
			<td class="label">
				<label for=""><?php _e("Sidebar",'acf'); ?></label>
			</td>
			<td>
				<?php 
				$sidebars = array();
				
				foreach ((array) $wp_registered_sidebars as $sidebar) {
					if(!is_active_sidebar($sidebar['id']))
						continue;
						
					$sidebars[$sidebar['id']] = $sidebar['name'];
				}
				$this->parent->create_field(array(
					'type'	=>	'select',
					'name'	=>	'fields['.$key.'][sidebar]',
					'value'	=>	$field['sidebar'],
					'choices'	=>	$sidebars,
					'multiple'	=>	'0',
				));
				?>
			</td>
		</tr>
		<tr class="field_option field_option_<?php echo $this->name; ?>">
			<td class="label">
				<label for=""><?php _e("Inherit From",'acf'); ?></label>
			</td>
			<td>
				<?php 
				$options = array();
                $options[''] = 'None';
				$options['page'] = 'Page Structure';
				$options['menu'] = 'Menu Structure';
				
				$this->parent->create_field(array(
					'type' => 'select',
					'name' => 'fields['.$key.'][inherit_from]',
					'value'	=> $field['inherit_from'],
					'choices' => $options
				));
				?>
			</td>
		</tr>
		
		<?php
		
		
	}

		
	/*--------------------------------------------------------------------------------------
	*
	*	get_widgets
	*	- this function retrieves all widgets for the specified sidebar
	*
	*	@author Dallas Johnson
	* 
	*-------------------------------------------------------------------------------------*/
	function get_widgets($field)
	{
		global $wp_registered_sidebars, $wp_registered_widgets;

        if(is_int($field['sidebar'])):
			$index = 'sidebar-' . $field['sidebar'];
		else:
			$index = sanitize_title($field['sidebar']);
			foreach ( (array) $wp_registered_sidebars as $key => $value ):
		        if ( sanitize_title($value['name']) == $index ):
		                $index = $key;
		                break;
		        endif;
		    endforeach;
		endif;

		$sidebars_widgets = wp_get_sidebars_widgets();

		if ( empty($wp_registered_sidebars[$index]) || !array_key_exists($index, $sidebars_widgets) || !is_array($sidebars_widgets[$index]) || empty($sidebars_widgets[$index]) )
		    return $posts;

		//set our default
		$posts = array();

        if(isset($field['inherit_from']) and !($field['inherit_from']==''))
            $posts[] = (object)array(
                'ID' => self::INHERIT_STRING,
                'title' => '-------- Inherit From Parent --------'
            );


        //loop through widgets in sidebar, add them to posts
		foreach ( (array) $sidebars_widgets[$index] as $id ) :

		    if ( !isset($wp_registered_widgets[$id]) )
		        continue;

		    $classname = $wp_registered_widgets[$id]['callback'][0]->id_base;
		    $instance = $wp_registered_widgets[$id]['params'][0]['number'];

		    if(!$option_list = get_option($classname))
		        $option_list = get_option('widget_'.$classname);

		    $post = array(
			    'ID' => $id,
			    'title' =>  ((strlen($option_list[$instance]['title']) > 0) ? $option_list[$instance]['title'] : 'No Title') . ' (' . $wp_registered_widgets[$id]['name'] . ')'
		    );

		    $posts[] = (object)$post;

		endforeach;

		return $posts;
	}
	
	
	/*--------------------------------------------------------------------------------------
	*
	*	create_field
	*	- this function is called on edit screens to produce the html for this field
	*
	*	@author Dallas Johnson
	* 
	*-------------------------------------------------------------------------------------*/
	
	function create_field($field)
	{
		
		$field['max'] = isset($field['max']) ? $field['max'] : '-1';
		$field['sidebar'] = isset($field['sidebar']) ? $field['sidebar'] : '';
		
		//get widget list
        $posts = $this->get_widgets($field);

		$values_array = array();
		if($field['value'] != ""):
			//get current values
			$values_array = explode(',', $field['value']);
			
			//get available widgets, we're making sure our set widgets
			//haven't be deactivated. if they have, we're removing them
			$all_widgets = wp_get_sidebars_widgets();				
			$sidebar_widgets = (array)$all_widgets[$field['sidebar']];
			foreach($values_array as $key => $value):
				if(!($value == self::INHERIT_STRING) and !in_array($value,$sidebar_widgets))
					unset($values_array[$key]);
			endforeach;
		endif;
		?>
		<div class="acf_relationship" data-max="<?php echo $field['max']; ?>">
			
			<input type="hidden" name="<?php echo $field['name']; ?>" value="<?php echo implode(',', $values_array); ?>" />
			
			<div class="relationship_left">
				<table class="widefat">
					<thead>
						<tr>
							<th>
								<label class="relationship_label" for="relationship_<?php echo $field['name']; ?>">Search...</label>
								<input class="relationship_search" type="text" id="relationship_<?php echo $field['name']; ?>" />
								<div class="clear_relationship_search"></div>
							</th>
						</tr>
					</thead>
				</table>
				<div class="relationship_list">
				<?php
				if($posts)
				{
					foreach($posts as $post)
					{
						if(!$post->title) continue;
						
						$class = in_array($post->ID, $values_array) ? 'hide' : '';
						echo '<a href="javascript:;" class="' . $class . '" data-post_id="' . $post->ID . '">' . $post->title . '<span class="add"></span></a>';
					}
				}
				?>
				</div>
			</div>
			
			<div class="relationship_right">
				<div class="relationship_list">
				<?php
				$temp_posts = array();
				
				if($posts)
				{
					foreach($posts as $post)
					{
						$temp_posts[$post->ID] = $post;
					}
				}
				
				if($temp_posts)
				{
					foreach($values_array as $value)
					{
						echo '<a href="javascript:;" class="" data-post_id="' . $temp_posts[$value]->ID . '">' . $temp_posts[$value]->title . '<span class="remove"></span></a>';
						unset($temp_posts[$value]);
					}
					
					foreach($temp_posts as $id => $post)
					{
						echo '<a href="javascript:;" class="hide" data-post_id="' . $post->ID . '">' . $post->title . '<span class="remove"></span></a>';
					}
				}
					
				?>
				</div>
			</div>
			
			
		</div>
		<?php
	}


    /*--------------------------------------------------------------------------------------
     *
     *	update_value
     *	- this function is called when saving a post object that your field is assigned to.
     *	the function will pass through the 3 parameters for you to use.
     *
     *	@params
     *	- $post_id (int) - useful if you need to save extra data or manipulate the current
     *	post object
     *	- $field (array) - useful if you need to manipulate the $value based on a field option
     *	- $value (mixed) - the new value of your field.
     *
     *	@author Dallas Johnson
     *
     *-------------------------------------------------------------------------------------*/

    function update_value($post_id, $field, $value)
    {
        // do stuff with value

        // save value
        parent::update_value($post_id, $field, $value);
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
     *	@author Dallas Johnson
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
     *	get_value_for_api
     *	- called from your template file when using the API functions (get_field, etc).
     *	This function is useful if your field needs to format the returned value
     *
     *	@params
     *	- $post_id (int) - the post ID which your value is attached to
     *	- $field (array) - the field object.
     *
     *	@author Dallas Johnson
     *
     *-------------------------------------------------------------------------------------*/

    function get_value_for_api($post_id, $field)
    {
        // vars
        $value = parent::get_value($post_id, $field);

        if(!$value || $value == "")
        {
            return false;
        }

        $value = explode(',', $value);

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

                    $parent = '';

                    if(isset($field['inherit_from'])):

                        //menu inheritance
                        if($field['inherit_from'] == 'menu')
                            $parent = 'menu';

                        //page inheritance
                        else
                            $parent = 'page';

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
     *	getWidgetsFromParent
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