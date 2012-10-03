<?php
/*
Plugin Name: WP FlexSlider
Plugin URI: 
Description: 
Author: 
Version: 0.2
Author URI:
*/

/* --------------------------------------------------------------------------- */
/* Theme must support post-thumbnails.
/* Add to functions.php file in your theme folder:
/* add_theme_support( 'post-thumbnails' );
/* --------------------------------------------------------------------------- */

/*Some Set-up*/
define('WPFS_PATH', WP_PLUGIN_URL . '/' . plugin_basename( dirname(__FILE__) ) . '/' );
define('WPFS_NAME', "WP FlexSlider");
define ("WPFS_VERSION", "0.1");

class wp_flexslider {
     
    public $options;
     
    function __construct()
    {
	add_action('admin_init', array($this, 'wpfs_init'));
	add_action('init', array($this, 'wpfs_register'));
	add_action('admin_menu', array($this, 'add_pages'));
	// add_action('plugins_loaded', array($this, 'wpfs_translate'));

	// Plugin functions here
	add_shortcode('wpfs', array($this, 'WPFS_insert_slider'));
    add_action( 'after_setup_theme', array($this, 'add_thumbnails_for_wpfs'));

	add_action('delete_wpfsliders', array($this, 'on_delete_slider_options'));

	// Customize Images table
	add_filter('manage_wpfs-slides_posts_columns', array($this, 'wpfs_columns_head'));  
	add_action('manage_wpfs-slides_posts_custom_column', array($this, 'wpfs_columns_content'), 10, 2);
    }
    
    function wpfs_init()
    {
	//delete_option('wp_flexslider_options'); // use to clear previous data if needed
	$this->options = get_option('wp_flexslider_options');
	$this->reg_settings_and_fields();
	}

    function add_thumbnails_for_wpfs() 
    {

        global $_wp_theme_features;

        if( empty($_wp_theme_features['post-thumbnails']) )
            $_wp_theme_features['post-thumbnails'] = array( array('wpfs-slides') );

        elseif( true === $_wp_theme_features['post-thumbnails'])
            return;

        elseif( is_array($_wp_theme_features['post-thumbnails'][0]) )
            $_wp_theme_features['post-thumbnails'][0][] = 'wpfs-slides';
    }

    function wpfs_translate()
    {
    // Create 'languages' subdir in plugin dir and put translation files there. File name should be wp_flexslider-xx_XX.po ex. wp_flexslider-en_US.po 
    load_plugin_textdomain( 'wp_flexslider', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );	
    }

    function add_pages()
    {
	// add_options_page('Page Title', 'Menu Title', 'administrator', __FILE__, array('wp_flexslider', 'display_options_page'));
    // add_menu_page('WP FlexSlider', 'WP FlexSlider', 'administrator', dirname(__FILE__), array('wp_flexslider', 'display_options_page'), '', 25);
	
	$page = add_submenu_page(/*dirname(__FILE__)*/'edit.php?post_type=wpfs-slides', __('Settings'), __('Settings'), 'administrator', __FILE__, array('wp_flexslider', 'display_options_page'));
	add_action('admin_print_styles-' . $page, array($this, 'wpfs_admin_scripts'));
    }
    
    function wpfs_admin_scripts()
    {
	wp_enqueue_script('jquery'); // Disable if not needed
	wp_enqueue_script('jquery-ui-core'); // Disable if not needed
    wp_enqueue_script('jquery-ui-accordion'); // Disable if not needed
    if ( 'classic' == get_user_option( 'admin_color' ) ) {
        wp_register_style ( 'wootweak-jquery-ui-css', plugin_dir_url( __FILE__ ) . 'css/jquery-ui-classic.css' );
    } else {
        wp_register_style ( 'wootweak-jquery-ui-css', plugin_dir_url( __FILE__ ) . 'css/jquery-ui-fresh.css' );
    }
    wp_enqueue_style('wootweak-jquery-ui-css');
	// wp_register_style('examplestyle', plugins_url('/css/examplestyle.css', __FILE__) );
	// wp_enqueue_style('examplestyle');
    }
     
    
    function display_options_page()
    {
    ?>
    
    <div class="wrap">
	<?php //screen_icon();
	$o = get_option('wp_flexslider_options');
	?>
	
	<h2><?php echo __('Settings'); ?></h2>
	<form method="post" action="options.php" enctype="multipart/form-data">
	<?php settings_fields('wp_flexslider_plugin_options_group'); ?>
	<?php do_settings_sections(__FILE__); ?>

	<p class="submit">
	    <input type="submit" name="submit" value="<?php echo __('Save Changes'); ?>" class="button-primary">
	</p>
	</form>
    </div>
    <script>
    	jQuery(document).ready(function() {
            jQuery('table.form-table').prev('h3').andSelf().wrapAll('<div class="accordion" />');
    		jQuery('form h3').each(function(){
                jQuery(this).wrapInner('<a href="#" />');
                jQuery(this).next('table.form-table').andSelf().wrapAll('<div />');
            });
            jQuery('.accordion').accordion({ header: "h3" });

    		// jQuery('.accordion').hide();
    		// jQuery('h3').css('cursor', 'pointer').live('click', function(){
    		// 	jQuery(this).next().slideToggle();
    		// });
    	});
    </script>
    <?php
    }
     
    function reg_settings_and_fields()
    {
    register_setting('wp_flexslider_plugin_options_group', 'wp_flexslider_options', array($this, 'wp_flexslider_validate_settings')); //3rd param optional callback func
    add_settings_section('wp_flexslider_main_section', __('Settings'), array($this, 'wp_flexslider_main_section_cb'), __FILE__); //id, title, callback, page
    
    // ADD ALL add_settings_field FUNCTIONS HERE
    add_settings_field('wpfs_responsive_images', __('Disable responsive images'), array($this,'wpfs_responsive_images_generate_field'), __FILE__, 'wp_flexslider_main_section', null); // id, title, cb func, page , section
    add_settings_field('wpfs_animation_select', __('Animation type'), array($this,'wpfs_animation_select_generate_field'), __FILE__, 'wp_flexslider_main_section', null); // id, title, cb func, page , section
    add_settings_field('wpfs_slider_animation_direction', __('Animation direction'), array($this,'wpfs_slider_animation_direction_generate_field'), __FILE__, 'wp_flexslider_main_section', null); // id, title, cb func, page , section
    add_settings_field('wpfs_show_nav_dots', __('Show navigation dots'), array($this,'wpfs_show_nav_dots_generate_field'), __FILE__, 'wp_flexslider_main_section', null); // id, title, cb func, page , section
    add_settings_field('wpfs_show_nav_arrows', __('Show navigation arrows on the sides'), array($this,'wpfs_show_nav_arrows_generate_field'), __FILE__, 'wp_flexslider_main_section', null); // id, title, cb func, page , section
    add_settings_field('wpfs_is_slideshow', __('Slideshow'), array($this,'wpfs_is_slideshow_generate_field'), __FILE__, 'wp_flexslider_main_section', null); // id, title, cb func, page , section
    add_settings_field('wpfs_slideshow_speed', __('Slideshow speed (ms)'), array($this,'wpfs_slideshow_speed_generate_field'), __FILE__, 'wp_flexslider_main_section', null); // id, title, cb func, page , section
    add_settings_field('wpfs_animation_speed', __('Animation speed (ms)'), array($this,'wpfs_animation_speed_generate_field'), __FILE__, 'wp_flexslider_main_section', null); // id, title, cb func, page , section
    add_settings_field('wpfs_carousel_navigation', __('Carousel slider as navigation'), array($this,'wpfs_carousel_navigation_generate_field'), __FILE__, 'wp_flexslider_main_section', null); // id, title, cb func, page , section
    add_settings_field('wpfs_carousel_img_width', __('Carousel image width'), array($this,'wpfs_carousel_img_width_generate_field'), __FILE__, 'wp_flexslider_main_section', null); // id, title, cb func, page , section
    add_settings_field('wpfs_force_use_margin', __('Use margin for carousel images below (or set it is your CSS)'), array($this,'wpfs_force_use_margin_generate_field'), __FILE__, 'wp_flexslider_main_section', null); // id, title, cb func, page , section
    add_settings_field('wpfs_carousel_img_margin', __('Carousel image margin'), array($this,'wpfs_carousel_img_margin_generate_field'), __FILE__, 'wp_flexslider_main_section', null); // id, title, cb func, page , section
    add_settings_field('wpfs_orderby', __('Order slides by'), array($this,'wpfs_orderby_generate_field'), __FILE__, 'wp_flexslider_main_section', null); // id, title, cb func, page , section
    add_settings_field('wpfs_order', __('Order in'), array($this,'wpfs_order_generate_field'), __FILE__, 'wp_flexslider_main_section' , null); // id, title, cb func, page , section


    $args = array(
		'hide_empty' => 0,
    	'taxonomy' => 'wpfsliders'
    	);

    $taxonomies = get_categories( $args );

    if(count($taxonomies) > 0)
    {
	    foreach ($taxonomies as $taxonomy)
	    {
	    	// echo $taxonomy->cat_ID;
    		add_settings_section('wp_flexslider_slider_'.$taxonomy->slug.'_section', __('Slider - ').$taxonomy->name, array($this, 'wp_flexslider_main_section_cb'), __FILE__); //id, title, callback, page

            add_settings_field('wpfs_responsive_images_'.$taxonomy->slug, __('Disable responsive images'), array($this,'wpfs_responsive_images_generate_field'), __FILE__, 'wp_flexslider_slider_'.$taxonomy->slug.'_section', $taxonomy->slug); // id, title, cb func, page , section
	    	add_settings_field('wpfs_animation_select_'.$taxonomy->slug, __('Animation type: '), array($this,'wpfs_animation_select_generate_field'), __FILE__, 'wp_flexslider_slider_'.$taxonomy->slug.'_section', $taxonomy->slug); // id, title, cb func, page , section
            add_settings_field('wpfs_slider_animation_direction_'.$taxonomy->slug, __('Animation direction'), array($this,'wpfs_slider_animation_direction_generate_field'), __FILE__, 'wp_flexslider_slider_'.$taxonomy->slug.'_section', $taxonomy->slug); // id, title, cb func, page , section
		    add_settings_field('wpfs_show_nav_dots_'.$taxonomy->slug, __('Show navigation dots'), array($this,'wpfs_show_nav_dots_generate_field'), __FILE__, 'wp_flexslider_slider_'.$taxonomy->slug.'_section', $taxonomy->slug); // id, title, cb func, page , section
		    add_settings_field('wpfs_show_nav_arrows_'.$taxonomy->slug, __('Show navigation arrows on the sides'), array($this,'wpfs_show_nav_arrows_generate_field'), __FILE__, 'wp_flexslider_slider_'.$taxonomy->slug.'_section', $taxonomy->slug); // id, title, cb func, page , section
		    add_settings_field('wpfs_is_slideshow_'.$taxonomy->slug, __('Slideshow'), array($this,'wpfs_is_slideshow_generate_field'), __FILE__, 'wp_flexslider_slider_'.$taxonomy->slug.'_section', $taxonomy->slug); // id, title, cb func, page , section
		    add_settings_field('wpfs_slideshow_speed_'.$taxonomy->slug, __('Slideshow speed (ms)'), array($this,'wpfs_slideshow_speed_generate_field'), __FILE__, 'wp_flexslider_slider_'.$taxonomy->slug.'_section', $taxonomy->slug); // id, title, cb func, page , section
		    add_settings_field('wpfs_animation_speed_'.$taxonomy->slug, __('Animation speed (ms)'), array($this,'wpfs_animation_speed_generate_field'), __FILE__, 'wp_flexslider_slider_'.$taxonomy->slug.'_section', $taxonomy->slug); // id, title, cb func, page , section
		    add_settings_field('wpfs_carousel_navigation_'.$taxonomy->slug, __('Carousel slider as navigation'), array($this,'wpfs_carousel_navigation_generate_field'), __FILE__, 'wp_flexslider_slider_'.$taxonomy->slug.'_section', $taxonomy->slug); // id, title, cb func, page , section
		    add_settings_field('wpfs_carousel_img_width_'.$taxonomy->slug, __('Carousel image width'), array($this,'wpfs_carousel_img_width_generate_field'), __FILE__, 'wp_flexslider_slider_'.$taxonomy->slug.'_section', $taxonomy->slug); // id, title, cb func, page , section
		    add_settings_field('wpfs_force_use_margin_'.$taxonomy->slug, __('Use margin for carousel images below (or set it is your CSS)'), array($this,'wpfs_force_use_margin_generate_field'), __FILE__, 'wp_flexslider_slider_'.$taxonomy->slug.'_section', $taxonomy->slug); // id, title, cb func, page , section
		    add_settings_field('wpfs_carousel_img_margin_'.$taxonomy->slug, __('Carousel image margin'), array($this,'wpfs_carousel_img_margin_generate_field'), __FILE__, 'wp_flexslider_slider_'.$taxonomy->slug.'_section', $taxonomy->slug); // id, title, cb func, page , section
    		add_settings_field('wpfs_orderby_'.$taxonomy->slug, __('Order slides by'), array($this,'wpfs_orderby_generate_field'),  __FILE__, 'wp_flexslider_slider_'.$taxonomy->slug.'_section', $taxonomy->slug); // id, title, cb func, page , section
    		add_settings_field('wpfs_order_'.$taxonomy->slug, __('Order in'), array($this,'wpfs_order_generate_field'), __FILE__, 'wp_flexslider_slider_'.$taxonomy->slug.'_section', $taxonomy->slug); // id, title, cb func, page , section

	    }
	}

    //delete_option('myplugin_used_option');
    }
     
    function wp_flexslider_main_section_cb()
    {
    	// Optional
	}
   
    function wp_flexslider_validate_settings($plugin_options)
    {
	return $plugin_options;
    }
     
    /* --------------------------------------------------------------------------- */
    /* Input functions
    /* --------------------------------------------------------------------------- */

    function wpfs_animation_select_generate_field($id=null)
    {
    	if($id != null) $id = '_'.$id;
		$items = array('slide', 'fade');
		echo "<select name='wp_flexslider_options[wpfs_animation_select".$id."]'>";
		foreach($items as $item)
		{
			$selected = ( $this->options['wpfs_animation_select'.$id] === $item ) ? ' selected="selected"' : '' ;
			echo "<option value='$item'$selected>$item</option>";
		}
		echo "</select>";
    }

    function wpfs_slider_animation_direction_generate_field($id=null)
    {
        if($id != null) $id = '_'.$id;
        $items = array('horizontal', 'vertical');
        echo "<select name='wp_flexslider_options[wpfs_slider_animation_direction".$id."]'>";
        foreach($items as $item)
        {
            $selected = ( $this->options['wpfs_slider_animation_direction'.$id] === $item ) ? ' selected="selected"' : '' ;
            echo "<option value='$item'$selected>$item</option>";
        }
        echo "</select>";
    }

    function wpfs_orderby_generate_field()
    {
		$items = array('title', 'date');
		echo "<select name='wp_flexslider_options[wpfs_orderby]'>";
		foreach($items as $item)
		{
			$selected = ( $this->options['wpfs_orderby'] === $item ) ? ' selected="selected"' : '' ;
			echo "<option value='$item'$selected>$item</option>";
		}
		echo "</select>";
    }

    function wpfs_order_generate_field()
    {
    	$items = array('ASC', 'DESC');
    	echo "<select name='wp_flexslider_options[wpfs_order]'>";
    	foreach($items as $item)
    	{
    		$selected = ( $this->options['wpfs_order'] === $item ) ? ' selected="selected"' : '' ;
    		echo "<option value='$item'$selected>$item</option>";
    	}
    	echo "</select>";
    }

    function wpfs_show_nav_dots_generate_field($id=null)
    {
    	if($id != null) $id = '_'.$id;
    	$checked = ( 1 == $this->options['wpfs_show_nav_dots'.$id] ) ? 'checked="checked"' : '' ;
    	echo '<input name="wp_flexslider_options[wpfs_show_nav_dots'.$id.']" type="checkbox" value="1" '.$checked.'>';
    }
    
    function wpfs_show_nav_arrows_generate_field($id=null)
    {
    	if($id != null) $id = '_'.$id;
    	$checked = ( 1 == $this->options['wpfs_show_nav_arrows'.$id] ) ? 'checked="checked"' : '' ;
    	echo '<input name="wp_flexslider_options[wpfs_show_nav_arrows'.$id.']" type="checkbox" value="1" '.$checked.'>';
    }

    function wpfs_is_slideshow_generate_field($id=null)
    {
    	if($id != null) $id = '_'.$id;
    	$checked = ( 1 == $this->options['wpfs_is_slideshow'.$id] ) ? 'checked="checked"' : '' ;
    	echo '<input name="wp_flexslider_options[wpfs_is_slideshow'.$id.']" type="checkbox" value="1" '.$checked.'>';
    }

    function wpfs_slideshow_speed_generate_field($id=null)
    {
    	if($id != null) $id = '_'.$id;
    	echo '<input name="wp_flexslider_options[wpfs_slideshow_speed'.$id.']" type="number" value="'.$this->options[wpfs_slideshow_speed.$id].'" >';
    }

    function wpfs_animation_speed_generate_field($id=null)
    {
    	if($id != null) $id = '_'.$id;
    	echo '<input name="wp_flexslider_options[wpfs_animation_speed'.$id.']" type="number" value="'.$this->options[wpfs_animation_speed.$id].'" >';
    }

    function wpfs_carousel_navigation_generate_field($id=null)
    {
    	if($id != null) $id = '_'.$id;
    	$checked = ( 1 == $this->options['wpfs_carousel_navigation'.$id] ) ? 'checked="checked"' : '' ;
    	echo '<input name="wp_flexslider_options[wpfs_carousel_navigation'.$id.']" type="checkbox" value="1" '.$checked.'>';
    }

    function wpfs_carousel_img_width_generate_field($id=null)
    {
    	if($id != null) $id = '_'.$id;
    	echo '<input name="wp_flexslider_options[wpfs_carousel_img_width'.$id.']" type="number" value="'.$this->options[wpfs_carousel_img_width.$id].'" >';
    }

    function wpfs_force_use_margin_generate_field($id=null)
    {
    	if($id != null) $id = '_'.$id;
    	$checked = ( 1 == $this->options['wpfs_force_use_margin'.$id] ) ? 'checked="checked"' : '' ;
    	echo '<input name="wp_flexslider_options[wpfs_force_use_margin'.$id.']" type="checkbox" value="1" '.$checked.'>';
    }

    function wpfs_carousel_img_margin_generate_field($id=null)
    {
    	if($id != null) $id = '_'.$id;
    	echo '<input name="wp_flexslider_options[wpfs_carousel_img_margin'.$id.']" type="number" value="'.$this->options[wpfs_carousel_img_margin.$id].'" >';
    }

    function wpfs_responsive_images_generate_field()
    {
        $checked = ( 1 == $this->options['wpfs_responsive_images'] ) ? 'checked="checked"' : '' ;
        echo '<input name="wp_flexslider_options[wpfs_responsive_images]" type="checkbox" value="1" '.$checked.'>';
    }

    /* --------------------------------------------------------------------------- */
    /* Customize Images table
    /* --------------------------------------------------------------------------- */

    function wpfs_get_featured_image($post_ID) {  
    $post_thumbnail_id = get_post_thumbnail_id($post_ID);  
	    if ($post_thumbnail_id) {  
	        $post_thumbnail_img = wp_get_attachment_image_src($post_thumbnail_id, 'featured_preview');  
	        return $post_thumbnail_img[0];  
	    }  
	}

	// Add new column  
	function wpfs_columns_head($defaults) {
		$defaults2['cb'] = '<input type="checkbox" />';
 		$defaults2['title'] = __('Image Name', 'column name'); 
	    $defaults2['featured_image'] = __('Featured Image');
	    $defaults2['slider_name'] = __('Used in slider:'); 
	    $defaults2['date'] = _x('Date', 'column name');
	    return $defaults2;  
	}  
	  
	// Show the featured image  
	function wpfs_columns_content($column_name, $post_ID) {  
	    if ($column_name == 'featured_image') {  
	        $post_featured_image = $this->wpfs_get_featured_image($post_ID);  
		    if ($post_featured_image) {  
	            // Has a featured image  
	            echo '<img width="100" src="' . $post_featured_image . '" />';  
	        }  
	        else {  
	            // No featured image, show the default one  
	            echo '<img src="' . get_bloginfo('wpurl') . '/wp-includes/images/wlw/wp-watermark.png" />'; 
	        }    
	    }
	    if ($column_name == 'slider_name')
	    {
	    	$terms = get_the_terms($post_ID, 'wpfsliders');
	    	if($terms)
	    	{
		    	foreach ($terms as $term)
		    	{
		    		echo $term->name.'<br>';
		    	}	
	    	}
	    }  
	}

    /* --------------------------------------------------------------------------- */
    /* Plugin functions
    /* --------------------------------------------------------------------------- */

    function on_delete_slider_options( $id )
    {
    	// clear options
    }

	function WPFS_get_slider($wpfsliders=null)
	{
	$o = get_option('wp_flexslider_options');

	if($wpfsliders != null && $wpfsliders != '')
	{
		$spesific = '_'.$wpfsliders;
		$selector = '.'.$wpfsliders;
	}
	else
	{
		$spesific = '';	
	} 

	if($o['wpfs_orderby'.$spesific]) $orderby = $o['wpfs_orderby'.$spesific];
	else $orderby = 'title';

	if($o['wpfs_order'.$spesific]) $order = $o['wpfs_orderby'.$spesific];
	else $order = 'ASC';

	if($wpfsliders != null && $wpfsliders != '')
	{
		

		$args = array(
			'post_type' => 'wpfs-slides',
			'wpfsliders' => $wpfsliders,
			'orderby' => $orderby,
			'order' => $order
		);
		query_posts( $args );

		$slider = '<div class="flexslider '.$wpfsliders.'" id="'.$wpfsliders.'">
		<ul class="slides">';
		if($o['wpfs_carousel_navigation'.$spesific]) $carousel = '<div class="flexslider '.$wpfsliders.'" id="carousel_'.$wpfsliders.'">
		<ul class="slides">';
	}
	else
	{
		$WPFS_query= "post_type=wpfs-slides&orderby=".$orderby."&order=".$order;
		query_posts($WPFS_query);
		$slider = '<div class="flexslider" id="flexslider">
		<ul class="slides">';
		if($o['wpfs_carousel_navigation']) $carousel = '<div class="flexslider" id="carousel_flexslider">
		<ul class="slides">';
	}

		if (have_posts()) : while (have_posts()) : the_post(); 
		
		if(get_the_post_thumbnail( $post->ID, 'large' ))
        {
            $img = get_the_post_thumbnail( $post->ID, 'large' );
            $img = $this->remove_thumbnail_dimensions($img);    
        }
        else
        {
            $img = get_the_content();
        }
        
		
		$slider .= '<li>'.$img.'</li>';
		if($o['wpfs_carousel_navigation'.$spesific]) $carousel .= '<li>'.$img.'</li>';
			
		endwhile; 
		endif; 
		wp_reset_query();

		$slider .= '</ul>
		</div>';
		if($o['wpfs_carousel_navigation'.$spesific])
		{
			$carousel .= '</ul>
			</div>';
			$slider .= $carousel;
		}

		if($o['wpfs_animation_select'.$spesific]) $anime = $o['wpfs_animation_select'.$spesific];
		else $anime = 'slide';

        if($o['wpfs_slider_animation_direction'.$spesific]) $sliderdirection = $o['wpfs_slider_animation_direction'.$spesific];
        else $sliderdirection = 'horizontal';

		if($o['wpfs_show_nav_dots'.$spesific] && !$o['wpfs_carousel_navigation'.$spesific]) $dots = 'true';
		else $dots = 'false';

		if($o['wpfs_show_nav_arrows'.$spesific]) $arrows = 'true';
		else $arrows = 'false';

		if($o['wpfs_is_slideshow'.$spesific] && !$o['wpfs_carousel_navigation'.$spesific]) $slideshow = 'true';
		else $slideshow = 'false';

		if(!$o['wpfs_slideshow_speed'.$spesific]) $sspeed = 5000;
		else $sspeed = $o['wpfs_slideshow_speed'.$spesific];

		if(!$o['wpfs_animation_speed'.$spesific]) $aspeed = 600;
		else $aspeed = $o['wpfs_animation_speed'.$spesific];

		if(!$o['wpfs_carousel_img_width'.$spesific]) $carouselimgwidht = 210;
		else $carouselimgwidht = $o['wpfs_carousel_img_width'.$spesific];

		if(!$o['wpfs_carousel_img_margin'.$spesific]) $carouselimgmargin = 5;
		else $carouselimgmargin = $o['wpfs_carousel_img_margin'.$spesific];

		if($o['wpfs_force_use_margin'.$spesific]) $cssmargin = '
			jQuery("#carousel_'.$spesific.' li").css("margin-right", "'.$carouselimgmargin.'px")

			';
		else $cssmargin = '';
	
	

	$output = '<script type="text/javascript" charset="utf-8">
	  jQuery(window).load(function() {';

	if($o['wpfs_carousel_navigation'.$spesific])
	{
		$output .= '
		 jQuery("#carousel_'.$wpfsliders.$selector.'").flexslider({
		    animation: "slide",
		    controlNav: false,
		   	directionNav: '.$arrows.',
		    animationLoop: false,
		    slideshow: false,
		    itemWidth: '.$carouselimgwidht.',
		    itemMargin: '.$carouselimgmargin.',
		    asNavFor: "#'.$wpfsliders.'"
		  });
		';
	}

	$output .= $cssmargin;

	

	$output .= 'jQuery(\'.flexslider'.$selector.'\').flexslider({
			pauseOnAction: true,
			pauseOnHover: true,
            direction: "'.$sliderdirection.'",
	    	controlNav: '.$dots.',
	    	directionNav: '.$arrows.',
	    	slideshow: '.$slideshow.',
	    	slideshowSpeed: '.$sspeed.',
	    	animationSpeed: '.$aspeed.',';

	if($o['wpfs_carousel_navigation'.$spesific])
	{
		$output .= '
		sync: "#carousel_'.$wpfsliders.$selector.'",
		';	
	}

	$output .= '
			animation: "'.$anime.'"
		});';

	$output .= '});
	</script>';

    if($o['wpfs_responsive_images'])
    {
        $output .= '<style>
        .flexslider .slides img {
            max-width: auto;
            width: auto;
            }
        </style>';
    }

	$slider .= $output;

	return $slider;
	}

	function WPFS_insert_slider($atts, $content=null)
	{
	if(!isset($atts['slider'])) $atts['slider'] = null;
	$slider = $this->WPFS_get_slider($atts['slider']);
	return $slider;
	}

	function wpfs_register() 
	{  
    $args = array(  
    	'labels' => array(
    			'name' => 'WP FlexSlider',
				'add_new' => __('Add new image'),
				'all_items' => __('All images'),
				'add_new_item' => __('Add new item'),
				'edit_item' => __('Edit image'),
				'new_item' => '',
				'view_item' => __('View images'),
				'search_items' => __('Search images'),
				'not_found' => __('Images not fount'),
				'not_found_in_trash' => __('Images not found in trash'),
				// 'parent_item_colon' => '',
				'menu_name' => 'WP FlexSlider'
    			),
        // 'label' =>__(CPT_NAME),  
        // 'singular_label' => __(CPT_SINGLE),  
        'public' => true,  
        'show_ui' => true,  
        //'show_in_menu' => dirname(__FILE__),
        'capability_type' => 'post',  
        'hierarchical' => false,  
        'rewrite' => true,  
        'supports' => array('title', 'editor', 'thumbnail'), 
        'taxonomies' => array('wpfsliders')
       );  
  
    register_post_type('wpfs-slides' , $args );
    $this->reg_new_taxonomies();    
	}

	function reg_new_taxonomies()
    {
    	$taxonomies = array();

    	$taxonomies['wpfsliders'] = array(
    			'hierarchical' => true,
    			'query_var' => true, //'ads_type',
    			'rewrite' => true, // path for mod_rewrite
    			'show_ui' => true,
    			'public' => true,

    			'labels' => array(
    			'name' => 'Sliders',
				'add_new' => 'Add new Slider',
				'all_items' => 'All Sliders',
				'add_new_item' => 'Add new item',
				'edit_item' => 'Edit Slider',
				'new_item' => 'New item',
				'view_item' => 'View Sliders',
				'search_items' => 'Search Sliders',
				'not_found' => 'Sliders not fount',
				'not_found_in_trash' => 'Sliders not found in trash',
				// 'parent_item_colon' => '',
				'menu_name' => 'Sliders'
    			)
    		);

    	$this->reg_all_taxonomies($taxonomies);
    }

    function reg_all_taxonomies($taxonomies)
    {
    	foreach($taxonomies as $name => $arr)
    	{
    		register_taxonomy($name, array('wpfs-slides'), $arr);
    	}
    }

    function remove_thumbnail_dimensions( $html ) {
	    $html = preg_replace( '/(width|height)=\"\d*\"\s/', "", $html );
	    return $html;
	}  
}

    
add_action('wp_enqueue_scripts', 'wpfs_styles');
function wpfs_styles()
    {
	// wp_register_style('wp_flexslider', plugins_url('/css/wp_flexslider.css', __FILE__) );
	// wp_enqueue_style('wp_flexslider');
	wp_enqueue_style('flexslider_css', WPFS_PATH.'flexslider.css');
	wp_enqueue_style('flexslider_wpfix_css', WPFS_PATH.'flexslider.wpfix.css');
    }
add_action('wp_enqueue_scripts', 'wpfs_scripts');
function wpfs_scripts()
    {
	wp_enqueue_script('flexslider', WPFS_PATH.'jquery.flexslider-min.js', array('jquery'));
    }

$copy = new wp_flexslider();

// Function for outside access
function WPFS_slider($wpfsliders=null)
	{
		$copy = new wp_flexslider();
		print $copy->WPFS_get_slider($wpfsliders);
	}

?>
