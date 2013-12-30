<?php
/*
Plugin Name: ILAB Rare Book Search
Plugin URI: http://www.vldhs.com/ILAB/search_plugin_description.php
Description: Search for old and rare books for sale by the world's foremost antiquarian booksellers.
Version: 1.0.1
Author: viaLibri
Author URI: http://www.vialibri.net/
License: GPL2
*/
?>
<?php
/*
Copyright 2013 Jim Hinck (email : mail@vialibri.net)  **

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

include_once 'results-parser-class.php';

class ViaLibri_ILAB_Widget extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'vialibri_ilab_widget', // Base ID
			'ILAB Rare Book Search', // Name
			array( 'description' => __( "Search the world's rare books from your sidebar", 'text_domain' ), ) // Args
		);
	}

	public function widget( $args, $instance ) {
		// outputs the content of the widget
		extract($args);
		include_once 'widget-content.php';
	}

 	public function form( $instance ) {
 		$currency = 'USD';
 		if ( isset( $instance[ 'currency' ] ) ) {
			$currency = $instance[ 'currency' ];
		}
		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'currency' ); ?>">Default currency:</label> 
		<select name="<?php echo $this->get_field_name( 'currency' ); ?>" id="<?php echo $this->get_field_id( 'currency' ); ?>">
			<?php foreach(IlabResults::$valid_currencies as $code => $cur) { ?>
				<option value="<?php echo $code; ?>"
					<?php if ($code == $currency) { ?>
						selected="selected"
					<?php } ?>>
					<?php echo esc_html("$code - $cur"); ?>
				</option>
			<?php } ?>
		</select>
		</p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['currency'] = ( ! empty( $new_instance['currency'] ) ) ? $new_instance['currency'] : 'USD';

		return $instance;
	}
}

function vialibri_ilab_register_widget() {
	register_widget('ViaLibri_ILAB_Widget');
}

add_action('widgets_init', 'vialibri_ilab_register_widget');

// Set up the results page 
// - http://wordpress.org/support/topic/how-do-i-create-a-new-page-with-the-plugin-im-building
function vialibri_ilab_install() {
	delete_option("vialibri_ilab_page_id");
	add_option("vialibri_ilab_page_id", '0', '', 'yes');

	$page_title = 'ILAB Search Results';
	$the_page = get_page_by_title( $page_title );

	if ( ! $the_page ) {
		$post = array(
			//'page_template' => [ <template file> ] //Sets the template for the page.
			'comment_status' => 'closed',
			'post_author' => 1,
			'post_content' => 'Some text',
			'post_name' => 'ilab-search-results',
			'post_status' => 'publish',
			'post_title' => $page_title,
			'post_type' => 'page'
		);  

		// Insert the post into the database
		$the_page_id = wp_insert_post( $post );
	} else {
		// the plugin may have been previously active and the page may just be trashed...
		$the_page_id = $the_page->ID;

		//make sure the page is not trashed...
		$the_page->post_status = 'publish';
		$the_page_id = wp_update_post( $the_page );
	}

	delete_option( 'vialibri_ilab_page_id' );
	add_option( 'vialibri_ilab_page_id', $the_page_id );
}

register_activation_hook(__FILE__, 'vialibri_ilab_install');

function vialibri_ilab_remove() {

	//  the id of our page...
	$the_page_id = get_option('vialibri_ilab_page_id');
	if( $the_page_id ) {
		wp_delete_post( $the_page_id, true ); // this will delete, not trash
	}

	delete_option("vialibri_ilab_page_id");
}

register_deactivation_hook(__FILE__, 'vialibri_ilab_remove');

// Replace results page content.
function vialibri_ilab_result_page_content($content) {
	global $wp_query;
	$post_id = $wp_query->post->ID;

	if ($post_id == get_option('vialibri_ilab_page_id')) {
		include_once 'results.php';
	} else {
		return $content;
	}
}

add_filter("the_content", "vialibri_ilab_result_page_content");

// Make sure JS and CSS is included for our results page and the placeholder
// shim for all pages.
function vialibri_ilab_page_scripts() {
	global $wp_query;
	$post_id = $wp_query->post->ID;
	$plugin_url = plugins_url().'/ilab-rare-book-search/';

	wp_enqueue_script('jquery');
	wp_enqueue_script('jquery.placeholder', $plugin_url.'jquery.placeholder.min.js', array('jquery'), '1.0.0', true);
	wp_enqueue_script('vialibri_run_placeholder', $plugin_url.'run-placeholder.js', array('jquery', 'jquery.placeholder'), '1.0.0', true);

	if ($post_id == get_option('vialibri_ilab_page_id')) {
		wp_enqueue_script('jquery.shorten', $plugin_url.'jquery.shorten.js', array('jquery'), '1.0.0', true);
		wp_enqueue_script('vialibri_ilab_js', $plugin_url.'results.js', array('jquery', 'jquery.shorten'), '1.0.0', true);
		wp_enqueue_style('vialibri_ilab_css', $plugin_url.'results.css');
	}
}
add_action('wp_enqueue_scripts', 'vialibri_ilab_page_scripts');

// Make sure the results page doesn't show up in menus.
function vialibri_ilab_page_filter($pages) {
	$new_pages = array();
	$the_page_id = get_option('vialibri_ilab_page_id');

	foreach($pages as $page) {
		if($page->ID != $the_page_id) {
			array_push($new_pages, $page);
		}
	}

	return $new_pages;
}
add_filter('get_pages', 'vialibri_ilab_page_filter');

add_action('init', 'vialibri_ilab_start_session', 1);
add_action('wp_logout', 'vialibri_ilab_end_session');
add_action('wp_login', 'vialibri_ilab_end_session');

function vialibri_ilab_start_session() {
    if(!session_id()) {
        session_start();
    }
}

function vialibri_ilab_end_session() {
    session_destroy ();
}

?>