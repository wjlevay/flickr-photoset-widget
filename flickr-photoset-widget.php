<?php
/*
Plugin Name: Flickr Photoset Widget
Description: A widget that pulls a list of Flickr photosets with thumbnails. This is a modified version of the plugin by David Laietta.
Version: 1.0
Author: W.J. Levay
Author URI: http://wjlevay.net
*/

class OBM_Simple_Flickr_Display extends WP_Widget {

	/*--------------------------------------------------*/
	/* Constructor
	/*--------------------------------------------------*/
	
	/**
	 * Specifies the classname and description, instantiates the widget, 
	 * loads localization files, and includes necessary stylesheets and JavaScript.
	 */
	public function __construct() {
			
		// Hooks fired when the Widget is activated and deactivated
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
		
		parent::__construct(
			'simple-flickr-display',
			'Simple Flickr Display',
			array(
				'classname'		=>	'simple-flickr-display-class',
				'description'	=>	'Display Recent Photos from your Flickr'
			)
		);
	
		// Register site styles and scripts
		if ( is_active_widget( false, false, 'simple-flickr-display', true ) ) {
			wp_register_style( 'simple-flickr-display-widget-styles', plugins_url( 'simple-flickr-display/css/simple-flickr-display.css' ) );
		}
		
	} // end constructor

	/*--------------------------------------------------*/
	/* Widget API Functions
	/*--------------------------------------------------*/
	
	/**
	 * Outputs the content of the widget.
	 *
	 * @param	array	args		The array of form elements
	 * @param	array	instance	The current instance of the widget
	 */
	public function widget( $args, $instance ) {
	
		extract( $args, EXTR_SKIP );
		
		$title = apply_filters('widget_title', $instance['title']);
		$description = $instance['description'];
		$screen_name = $instance['screen_name'];
		$number = $instance['number'];
		$size = $instance['size'];
		
		echo $before_widget;
        
		// Frontend View of Flickr Display

		wp_enqueue_style( 'simple-flickr-display-widget-styles' );
		
		if($title) {
			echo $before_title.$title.$after_title;
		}

		if($description) {
			echo '<p class="flickr-description">' . $description . '</p>';
		}
		
		if($screen_name && $number) {
			// Plugin Developer API Key
			$api_key = '72406b75a4e230f3a08f04bf14c48c0d';
			
			// Retrieve User
			$person = wp_remote_get('https://api.flickr.com/services/rest/?method=flickr.people.findByUsername&api_key='.$api_key.'&username='.$screen_name.'&format=json');
			$person = trim($person['body'], 'jsonFlickrApi()');
			$person = json_decode($person);
		
			if($person->user->id) {
				// Retrieve Photo URL
				$photos_url = wp_remote_get('https://api.flickr.com/services/rest/?method=flickr.urls.getUserPhotos&api_key='.$api_key.'&user_id='.$person->user->id.'&format=json');
				$photos_url = trim($photos_url['body'], 'jsonFlickrApi()');
				$photos_url = json_decode($photos_url);

				// Retrieve Photosets
				$photosets = wp_remote_get('https://api.flickr.com/services/rest/?method=flickr.photosets.getList&api_key='.$api_key.'&user_id='.$person->user->id.'&page=1&per_page='.$number.'&primary_photo_extras=url_'.$size.'&format=json');
				$photosets = trim($photosets['body'], 'jsonFlickrApi()');
				$photosets = json_decode($photosets);

				// Create unordered list of selected photos
				echo '<ul class="flickr-photos">';
					foreach($photosets->photosets->photoset as $photoset):
						$photoset = (array) $photoset;
						$title = $photoset[title]->_content;
						$thumb = (array) $photoset[primary_photo_extras];
						$thumb = array_values($thumb);
						echo '<li class="flickr-photo">';
							echo '<a href="' . $photos_url->user->url . 'sets/'. $photoset['id'] . '">';
								echo '<img src="' . $thumb[0] . '" alt="' . $title . '" height="' . $thumb[1] .'" width="' . $thumb[2] . '" />';
							echo '<br><span class="photoset_title">' . $title . '</span></a>';
						echo '</li>
						';
					endforeach;
				echo '</ul>';
		
			} else { // If username does not exist
				echo '<p class="flickr-error">Invalid Flickr Username</p>';
			}
		}
		
		echo $after_widget;
		
	} // end widget
	
	/**
	 * Processes the widget's options to be saved.
	 *
	 * @param	array	new_instance	The previous instance of values before the update.
	 * @param	array	old_instance	The new instance of values to be generated via the update.
	 */
	public function update( $new_instance, $old_instance ) {
	
		$instance = $old_instance;
		
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['description'] = $new_instance['description'];
		$instance['screen_name'] = $new_instance['screen_name'];
		$instance['number'] = $new_instance['number'];
		$instance['size'] = $new_instance['size'];
    
		return $instance;
		
	} // end widget
	
	/**
	 * Generates the administration form for the widget.
	 *
	 * @param	array	instance	The array of keys and values for the widget.
	 */
	public function form( $instance ) {
	
		$defaults = array(
			'title' => 'Photos from Flickr',
			'description' => '',
			'screen_name' => '',
			'number' => 6,
			'size' => 'q'
		);
		$instance = wp_parse_args((array) $instance, $defaults);
			
		// Display the admin form
		?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">Title:</label><br>
            <input class="widefat" style="width: 100%;" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo $instance['title']; ?>" />
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('description'); ?>">Description:</label><br>
            <input class="widefat" style="width: 100%;" id="<?php echo $this->get_field_id('description'); ?>" name="<?php echo $this->get_field_name('description'); ?>" value="<?php echo $instance['description']; ?>" />
        </p>
        
        <p>
            <label for="<?php echo $this->get_field_id('screen_name'); ?>">Flickr Username:</label><br>
            <input class="widefat" style="width: 100%;" id="<?php echo $this->get_field_id('screen_name'); ?>" name="<?php echo $this->get_field_name('screen_name'); ?>" value="<?php echo $instance['screen_name']; ?>" />
        </p>
        
        <p>
            <label for="<?php echo $this->get_field_id('number'); ?>">Number of Photosets to Display:</label>
            <input class="widefat" style="width: 30px;" id="<?php echo $this->get_field_id('number'); ?>" name="<?php echo $this->get_field_name('number'); ?>" value="<?php echo $instance['number']; ?>" />
        </p>

        <p>
        	<label for="<?php echo $this->get_field_id('size'); ?>">Photo Size:</label><br>
			<select id="<?php echo $this->get_field_id('size'); ?>" name="<?php echo $this->get_field_name('size'); ?>" class="widefat" style="width: 100%;">
			    <option <?php selected( $instance['size'], 'sq'); ?> value="sq">Square</option>
			    <option <?php selected( $instance['size'], 'q'); ?> value="q">Large Square</option> 
			    <option <?php selected( $instance['size'], 't'); ?> value="t">Thumbnail</option>
			    <option <?php selected( $instance['size'], 's'); ?> value="s">Small</option>
			    <option <?php selected( $instance['size'], 'n'); ?> value="n">Small 320</option>
			    <option <?php selected( $instance['size'], 'm'); ?> value="m">Medium</option>
			    <option <?php selected( $instance['size'], 'z'); ?> value="z">Medium 640</option>
			    <option <?php selected( $instance['size'], 'l'); ?> value="l">Large</option>
			</select>
		</p>
        <?php
		
	} // end form
	
} // end class

add_action( 'widgets_init', create_function( '', 'register_widget("OBM_Simple_Flickr_Display");' ) );