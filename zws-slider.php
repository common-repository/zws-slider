<?php

/*
 * Plugin Name: ZWS Slider
 * Description: A simple image slider plugin for wordpress
 * Version: 1.0
 * Author: Zia Web Solutions
 * Author URI: http://ziawebsolutions.com/
 */
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

define('ZWSSLIDER_VERSION', '1.1');
define('ZWSSLIDER__PLUGIN_DIR', plugin_dir_path(__FILE__));


//Include Plugins own style
wp_register_style('zwsslider_styles.css', plugin_dir_url(__FILE__) . 'css/zwsslider_styles.css', array(), ZWSSLIDER_VERSION);
wp_enqueue_style('zwsslider_styles.css');

add_action('init', 'register_zwsslider_scripts');

function register_zwsslider_scripts() {
    wp_register_script('jquery.flexslider-min.js', plugin_dir_url(__FILE__) . 'js/jquery.flexslider-min.js', array('jquery'), ZWSSLIDER_VERSION);
    wp_enqueue_script('jquery.flexslider-min.js');
}

add_action('wp_footer', 'print_zwsslider_script', 99);

function print_zwsslider_script() {
    global $add_zwsslider_script, $zwsslider_atts;
    if ($add_zwsslider_script) {
        $speed = $zwsslider_atts['slideshowspeed'] * 1000;
        echo "<script type=\"text/javascript\">
jQuery(document).ready(function(jQuery) {
	jQuery('head').prepend(jQuery('<link>').attr({
		rel: 'stylesheet',
		type: 'text/css',
		media: 'screen',
		href: '" . plugin_dir_url(__FILE__) . 'flexslider.css' . "'
	}));
	jQuery('.flexslider').flexslider({
		animation: '" . $zwsslider_atts['animation'] . "',
		slideshowSpeed: " . $speed . ",
		controlNav: true
	});
});
</script>";
        wp_print_scripts('flexslider');
    } else {
        return;
    }
}

add_action('init', 'zws_create_slider_posttype');

function zws_create_slider_posttype() {
    $args = array(
        'public' => false,
        'show_ui' => true,
        'menu_icon' => 'dashicons-images-alt',
        'capability_type' => 'page',
        'rewrite' => array('slider-loc', 'post_tag'),
        'label' => 'ZWS Slider',
        'supports' => array('title', 'editor', 'custom-fields', 'thumbnail', 'page-attributes')
    );
    register_post_type('zwsslider', $args);
}

add_action('wp_insert_post', 'zwsslider_set_slidermeta');

function zwsslider_set_slidermeta($post_ID) {
    add_post_meta($post_ID, 'slider-url', '', true);
    return $post_ID;
}

add_shortcode('zwsslider', 'zwsslider_shortcode');

function zwsslider_shortcode($atts = null) {
    global $add_zwsslider_script, $zwsslider_atts;
    $add_zwsslider_script = true;
    $zwsslider_atts = shortcode_atts(
            array(
        'location' => '',
        'limit' => -1,
        'ulid' => 'flexid',
        'animation' => 'slide',
        'slideshowspeed' => 5
            ), $atts, 'zwsslider'
    );
    $args = array(
        'post_type' => 'zwsslider',
        'posts_per_page' => $zwsslider_atts['limit'],
        'orderby' => 'menu_order',
        'order' => 'ASC'
    );
    if ($zwsslider_atts['location'] != '') {
        $args['tax_query'] = array(
            array('taxonomy' => 'slider-loc', 'field' => 'slug', 'terms' => $zwsslider_atts['location'])
        );
    }
    $the_query = new WP_Query($args);
    $slides = array();
    if ($the_query->have_posts()) {
        while ($the_query->have_posts()) {
            $the_query->the_post();
            $imghtml = get_the_post_thumbnail(get_the_ID(), 'full');
            $url = esc_url(get_post_meta(get_the_ID(), 'slider-url', true));

            $imghtml = '<a href="' . $url . '">' . $imghtml . '</a>';
            $slides[] = '
				<li>
					<div class="slide-media">' . $imghtml . '</div>
					<div class="slide-content">
						<h3 class="slide-title">' . get_the_title() . '</h3>
						<div class="slide-text">' . get_the_content() . '</div>
					</div>
				</li>';
        }
    }
    wp_reset_query();
    return '
	<div class="flexslider" id="' . $zwsslider_atts['ulid'] . '">
		<ul class="slides">
			' . implode('', $slides) . '
		</ul>
	</div>';
}
