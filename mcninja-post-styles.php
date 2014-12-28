<?php
/**
 * McNinja Post Styles
 *
 * @package   McNinja_Post_Styles
 * @author    Tom Harrigan <tom29axp@gmail.com>
 * @license   GPL-2.0+
 * @link      http://thomasharrigan.com/mcninja-post-styles
 *
 * @wordpress-plugin
 * Plugin Name: McNinja Post Styles
 * Version: 1.1
 * Author: Tom Harrigan
 * Plugin URI:  http://thomasharrigan.com/mcninja-post-styles
 * Description: Unleash the creativity of your content with custom post formats.
 * Author URI:  http://thomasharrigan.com
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: Post style
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
} // end if

require_once( plugin_dir_path( __FILE__ ) . 'class-post-styles.php' );

add_action( 'plugins_loaded', array( 'McNinja_Post_Styles', 'get_instance' ) );

function get_post_style() {
	return McNinja_Post_Styles::get_instance()->get_post_style();
}

function has_post_style() {
	return McNinja_Post_Styles::get_instance()->has_post_style();
}

function set_post_style() {
	return McNinja_Post_Styles::get_instance()->set_post_style();
}

function get_post_style_link( $link ) {
	return McNinja_Post_Styles::get_instance()->get_post_style_link( $link );
}

function get_post_style_string( $slug ) {
	return McNinja_Post_Styles::get_instance()->get_post_style_string( $slug );
}
