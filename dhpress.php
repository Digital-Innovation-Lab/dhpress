<?php
/*
Plugin Name: DH Press | Digital Humanities Toolkit
Plugin URI: http://dhpress.org/
Description: DHPress is a flexible, repurposable, fully extensible digital humanities toolkit designed for non-technical users.
Version: 2.7.2
Author: DHPress Team: Michael Newton, Joe E Hope, Pam Lach
Text Domain: dhpress
Domain Path: /languages
License: GPLv2
*/
/*  Copyright 2016  DHPress Team  (email : info@dhpress.org)

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


define('DHP_NAME', 'Digital Humanities Toolkit');
define('DHP_REQUIRED_PHP_VERSION', '5.2');
define('DHP_REQUIRED_WP_VERSION', '3.5');
define('DHP_PLUGIN_URL', plugins_url('', __FILE__ ));
define('DHP_MAPS_TABLE_VERSION', '0.1');
define('DHP_PLUGIN_VERSION', '2.7.2');


// ================== Localization ===================

add_action('plugins_loaded', 'dhp_load_textdomain');
function dhp_load_textdomain()
{
	load_plugin_textdomain('dhpress', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}



/**
 * Checks if the system requirements are met
 * @return bool True if system requirements are met, false if not
 */
function dhp_requirements_met()
{
	global $wp_version;
	
	if( version_compare( PHP_VERSION, DHP_REQUIRED_PHP_VERSION, '<') )
		return false;
	
	if( version_compare( $wp_version, DHP_REQUIRED_WP_VERSION, "<") )
		return false;
	
	return true;
}

/**
 * Prints an error that the system requirements weren't met.
 */
function dhp_requirements_not_met()
{
	global $wp_version;
	
	echo sprintf('
		<div id="message" class="error">
			<p>
				%s <strong>requires PHP %s</strong> and <strong>WordPress %s</strong> in order to work. You\'re running PHP %s and WordPress %s. You\'ll need to upgrade in order to use this plugin. If you\'re not sure how to <a href="http://codex.wordpress.org/Switching_to_PHP5">upgrade to PHP 5</a> you can ask your hosting company for assistance, and if you need help upgrading WordPress you can refer to <a href="http://codex.wordpress.org/Upgrading_WordPress">the Codex</a>.
			</p>
		</div>',
		DHP_NAME,
		DHP_REQUIRED_PHP_VERSION,
		DHP_REQUIRED_WP_VERSION,
		PHP_VERSION,
		esc_html( $wp_version )
	);
}

// PURPOSE: To register custom post types for projects, markers, and maps
// NOTES:   Called by both dhp_project_init() and dhp_project_activate()
function dhp_register_cpts()
{
  $projectLabels = array(
	'name' => _x('Projects', 'post type general name'),
	'singular_name' => _x('Project', 'post type singular name'),
	'add_new' => _x('Add New', 'project'),
	'add_new_item' => __('Add New Project'),
	'edit_item' => __('Edit Project'),
	'new_item' => __('New Project'),
	'all_items' => __('Projects'),
	'view_item' => __('View Project'),
	'search_items' => __('Search Projects'),
	'not_found' =>  __('No projects found'),
	'not_found_in_trash' => __('No projects found in Trash'), 
	'parent_item_colon' => '',
	'menu_name' => __('Projects'),
	'menu_icon' => plugins_url( 'dhpress/images/dhpress-plugin-icon.png' )  // Icon Path
  );
  $projectArgs = array(
	'labels' => $projectLabels,
	'public' => true,
	'publicly_queryable' => true,
	'show_ui' => true, 
	'show_in_menu' => 'dhp-top-level-handle', 
	'query_var' => true,
	'rewrite' => array('slug' => 'dhp-projects','with_front' => FALSE),
	'capability_type' => 'page',
	'has_archive' => true,
	/* if we want to subclass project types in future (i.e., Entry Points), will need to set 'hierarchical' => true */
	'hierarchical' => false,
	'menu_position' => null,
	/* if hierarchical, then may want to add 'page-attributes' to supports */
	'supports' => array( 'title', 'revisions', 'custom-fields' )
  ); 
  register_post_type('dhp-project',$projectArgs);

  $markerLabels = array(
    'name' => _x('Markers', 'post type general name'),
    'singular_name' => _x('Marker', 'post type singular name'),
    'add_new' => _x('Add New', 'dhp-markers'),
    'add_new_item' => __('Add New Marker'),
    'edit_item' => __('Edit Marker'),
    'new_item' => __('New Marker'),
    'all_items' => __('Markers'),
    'view_item' => __('View Marker'),
     'search_items' => __('Search Markers'),
    'not_found' =>  __('No markers found'),
    'not_found_in_trash' => __('No markers found in Trash'), 
    'parent_item_colon' => '',
    'menu_name' => __('Markers')
  );
  $markerArgs = array(
    'labels' => $markerLabels,
    'public' => true,
    'publicly_queryable' => true,
    'show_ui' => true, 
    'show_in_menu' => 'dhp-top-level-handle', 
    'query_var' => true,
    'rewrite' => true,
    'capability_type' => 'post',
    'has_archive' => true, 
    'hierarchical' => false,
    'menu_position' => null,
    'supports' => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments', 'revisions','custom-fields' )
  ); 
  register_post_type('dhp-markers',$markerArgs);

  $mapLabels = array(
    'name' => _x( 'Maps', 'taxonomy general name' ),
    'singular_name' => _x( 'Map', 'taxonomy singular name' ),
    'add_new' => __('Add New', 'dhp-maps'),
    'add_new_item' => __('Add New Map'),
    'edit_item' => __('Edit Map'),
    'new_item' => __('New Map'),
    'all_items' => __('Map Library'),
    'view_item' => __('View Map'),
    'search_items' => __('Search Maps'),
    'not_found' =>  __('No maps found'),
    'not_found_in_trash' => __('No maps found in Trash'), 
    'parent_item_colon' => '',
    'menu_name' => __('Map Library')
  ); 

  $mapArgs = array(
    'labels' => $mapLabels,
    'public' => true,
    'publicly_queryable' => true,
    'show_ui' => true, 
    'show_in_menu' => 'dhp-top-level-handle', 
    'query_var' => true,
    'rewrite' => false,
    'capability_type' => 'post',
    'has_archive' => true, 
    'hierarchical' => false,
    'menu_position' => null,
    'supports' => array( 'title', 'author', 'excerpt', 'comments', 'revisions','custom-fields' )
	//'supports' => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments', 'revisions','custom-fields' )
  );
  register_post_type('dhp-maps',$mapArgs);
} // dhp_register_project_cpt()


// init action called to initialize a plug-in
add_action( 'init', 'dhp_project_init' );

function dhp_project_init()
{
	dhp_register_cpts();

		// Are there any 'project' custom post types from 2.5.4 or earlier -- if so, change CPT

		// If no version # in DB, definitely old version of DH Press whose data needs checking
	if (get_option('dhp_plugin_version') === false) {
		$args = array('post_type' => 'project', 'posts_per_page' => -1);
		$loop = new WP_Query( $args );
		while ( $loop->have_posts() ) : $loop->the_post();
			$proj_id = get_the_ID();

				// Only does this change if CPT has associated metadata
			$proj_set = get_post_meta($proj_id, 'project_settings', true);
			if(!empty($proj_set)) {
				$update_params = array( 'ID' => $proj_id, 'post_type' => 'dhp-project');
				wp_update_post($update_params);
			}
		endwhile;
		wp_reset_query();
	}
		// store version # in options
	update_option('dhp_plugin_version', DHP_PLUGIN_VERSION);
} // dhp_project_init



register_activation_hook( __FILE__, 'dhp_project_activate');

// PURPOSE: Ensure that custom post types have been registered before we flush rewrite rules
//			See http://solislab.com/blog/plugin-activation-checklist/#flush-rewrite-rules
function dhp_project_activate()
{
	dhp_register_cpts();
	flush_rewrite_rules();
} // dhp_project_activate()


// Check requirements and instantiate
if( dhp_requirements_met() )
{
	include_once( dirname(__FILE__) . '/php/dhp-core.php' );
}
else
	add_action( 'admin_notices', 'dhp_requirements_not_met' );
?>
