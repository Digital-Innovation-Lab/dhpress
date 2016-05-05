<?php 

/**
	 * Registers and handles DHPress Project functions
	 *
	 * @package DHPress Toolkit
	 * @author DHPress Team
	 * @link http://dhpress.org/download/
	 */


// ================== Global Constants and Variables ===================

define('DHP_HTML_ADMIN_EDIT',  'dhp-html-admin-edit.php');
define('DHP_SCRIPT_SERVICES',  'dhp-script-services.php');
define('DHP_SCRIPT_TAX',  	'dhp-script-tax-trans.php');


// ================== Initialize Plug-in ==================

	// Hook into delete of posts so that all data associated with Project gets deleted
add_action('admin_init', 'dhp_admin_init');

function dhp_admin_init()
{
    if (current_user_can('delete_posts')) {
        add_action('before_delete_post', 'dhp_deleting_post', 10);
    }
} // dhp_admin_init()


	// PURPOSE: Catch the process of deleting a Project so other housekeeping can be done
function dhp_deleting_post($postID)
{
	$post = get_post($postID);

	if ($post->post_type != 'dhp-project')
		return;

	dhp_delete_all_terms($postID);
	dhp_delete_all_project_markers($postID);
} // dhp_deleting_project()


	// PURPOSE: Delete all Markers associated with a Project
function dhp_delete_all_project_markers($postID)
{
	$projObj = new DHPressProject($postID);

		// Go through all of the Project's Markers and gather data
	$loop = $projObj->setAllMarkerLoop();
	if($loop->have_posts()){
		foreach($loop->posts as $markerPost){
			$markerID = $markerPost->ID;
			wp_delete_post($markerID, false);
		}
	}
} // dhp_delete_all_project_markers()


	// PURPOSE:	Delete all taxonomic terms associated with Project when deleted
function dhp_delete_all_terms($postID)
{
	$rootTaxName = DHPressProject::ProjectIDToRootTaxName($postID);

		// NOTE: Need to re-register before delete, as WP may not have flushed cache
	register_taxonomy($rootTaxName, 'dhp-markers');

	$args = array('hide_empty' => false);
	$projTerms = get_terms($rootTaxName, $args);

	if (!is_wp_error($projTerms) && !empty($projTerms))
	{
		foreach ($projTerms as $term) {
			wp_delete_term(intval($term->term_id), $rootTaxName);
		}
	} // if !error

		// NOTE: There does not seem to be a formal method for un-registering taxonomies
		// 	This method taken from http://w4dev.com/wp/unregister-wordpress-taxonomy
	global $wp_taxonomies;
	if (taxonomy_exists($rootTaxName) ) {
		unset($wp_taxonomies[$rootTaxName]);
	}
} // dhp_delete_all_terms()


// add support for theme-specific feature
if (function_exists('add_theme_support')) {
		// enable use of thumbnails
	add_theme_support('post-thumbnails');
		// default Post Thumbnail dimensions
	set_post_thumbnail_size(32, 37);
}


// PURPOSE: Ensure that txt and png files are able to be added to the Media Library
function dhp_add_mime_types($mime_types)
{
	$mime_types['txt'] = 'text/plain';
	$mime_types['png'] = 'image/png';
	$mime_types['csv'] = 'text/csv';

	return $mime_types;
} // dhp_add_mime_types()

add_filter('upload_mimes', 'dhp_add_mime_types', 1, 1);


// ================== Produce Admin Panel Header ==================

// admin_head action called to create header for admin panel
add_action('admin_head', 'dhp_plugin_header');

// PURPOSE: Insert DH Press icon into top of administration panel

function dhp_plugin_header()
{ ?>
		<style>
			#icon-dhp-top-level-handle { background:transparent url('<?php echo DHP_PLUGIN_URL .'/images/dhpress-plugin-icon.png';?>') no-repeat; }     
		</style>
<?php
} // plugin_header()


// PURPOSE: Increase maximum number of custom fields that can show up in the Dashboard dropdown menus

function dhp_cf_limit_increase($limit)
{
	$limit = 100;
	return $limit;
} // dhp_cf_limit_increase()

add_filter('postmeta_form_limit', 'dhp_cf_limit_increase');

// ================== DHP Maps =====================

	// PURPOSE: Return list of map attributes, given list of items to load
	// INPUT: 	$mapID = custom post ID of map in DH Press library
	//			$mapMetaList = hash [key to use in resulting array : custom field name]
	//			$bounds = true if swBounds and neBounds values need to be constructed
function dhp_get_map_metadata($mapID, $mapMetaList, $bounds)
{
	$thisMetaSet = array();

	foreach ($mapMetaList as $arrayKey => $metaName) {
		$thisMetaData = get_post_meta($mapID, $metaName, true);
		$thisMetaSet[$arrayKey] = $thisMetaData;
		if ($bounds) {
			$south = get_post_meta($mapID, 'dhp_map_s_bounds', true);
			$west = get_post_meta($mapID, 'dhp_map_w_bounds', true);
			$north = get_post_meta($mapID, 'dhp_map_n_bounds', true);
			$east = get_post_meta($mapID, 'dhp_map_e_bounds', true);
			$thisMetaSet['swBounds'] = array(floatval($south), floatval($west));
			$thisMetaSet['neBounds'] = array(floatval($north), floatval($east));
		}
	}
	return $thisMetaSet;
} // dhp_get_map_metadata()


	// PURPOSE: Return list of all dhp-maps in DHP site (for Project Admin)
	// RETURNS: array [id, sname]
function dhp_get_map_layer_list()
{
	$layers = array();
	$theMetaSet = array('id' => 'dhp_map_id', 'sname' => 'dhp_map_sname');

	$args = array('post_type' => 'dhp-maps', 'posts_per_page' => -1);
	$loop = new WP_Query( $args );

	if($loop->have_posts()){
		foreach($loop->posts as $layerPost){
			$layer_id = $layerPost -> ID;
			$mapMetaData = dhp_get_map_metadata($layer_id, $theMetaSet, false);
			array_push($layers, $mapMetaData);
		}
	}
	wp_reset_query();

		// Sort array according to map IDs
	usort($layers, 'cmp_map_ids');

	return $layers;
} // dhp_get_map_layer_list()


//============================ Customize for Project Posts ============================

// post_updated_messages enables us to customize the messages for our custom post types
add_filter( 'post_updated_messages', 'dhp_project_updated_messages' );

// PURPOSE:	Supply strings specific to Project custom type
// ASSUMES:	Global variables $post, $post_ID set

function dhp_project_updated_messages( $messages )
{
  global $post, $post_ID;

  $messages['dhp-project'] = array(
	0 => '', // Unused. Messages start at index 1.
	1 => sprintf( __('Project updated. <a href="%s">View project</a>'), esc_url( get_permalink($post_ID) ) ),
	2 => __('Custom field updated.'),
	3 => __('Custom field deleted.'),
	4 => __('Project updated.'),
	/* translators: %s: date and time of the revision */
	5 => isset($_GET['revision']) ? sprintf( _x('Project restored to revision from %s', 'translators: %s: date and time of the revision'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
	6 => sprintf( __('Project published. <a href="%s">View project</a>'), esc_url( get_permalink($post_ID) ) ),
	7 => __('Project saved.'),
	8 => sprintf( __('Project submitted. <a target="_blank" href="%s">Preview project</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
	9 => sprintf( __('Project scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview project</a>'),
	  // translators: Publish box date format, see http://php.net/date
	  date_i18n( _x( 'M j, Y @ G:i', 'translators: Publish box date format, see http://php.net/date' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
	10 => sprintf( __('Project draft updated. <a target="_blank" href="%s">Preview project</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
  );

  return $messages;
} // dhp_project_updated_messages()


// post_row_actions enables us to modify the hover links in the Dashboard directories
add_filter( 'post_row_actions', 'dhp_export_post_link', 10, 2 );

// PURPOSE: Add a "CSV Export" and "Export to Prospect" hover link to listing of DH Press Projects

function dhp_export_post_link( $actions, $post )
{
	if ($post->post_type != 'dhp-project') {
		return $actions;
	}

	if (current_user_can('edit_posts')) {
		$actions['CSV_Export'] = '<a href="admin.php?action=dhp_export_as_csv&amp;post='.$post->ID.'" title="Export this item as CSV" rel="permalink">CSV Export</a>';
		$actions['Prospect_Export'] = '<a href="admin.php?action=dhp_export_to_prospect&amp;post='.$post->ID.'" title="Export this project for use in Prospect" rel="permalink">'. __('Export to Prospect', 'dhpress'). '</a>';
	}
	return $actions;
} // dhp_export_post_link()


// =========================== Customize handling of taxonomies ============================

// add custom taxonomies for each project when plugin is initialized
add_action( 'init', 'create_tax_for_projects', 0 );

	// PURPOSE: Create custom taxonomies for all existing DHP Projects if they don't exist (head term for Project)
function create_tax_for_projects()
{
	$args = array('post_type' => 'dhp-project', 'posts_per_page' => -1);
	$projects = get_posts($args);
	if ($projects) {
			// Go through all currently existing Projects
		foreach ( $projects as $project ) {
			$projectTax = DHPressProject::ProjectIDToRootTaxName($project->ID);
			$projectName = $project->post_title;
			$projectSlug = $project->post_name;
			$taxonomy_exist = taxonomy_exists($projectTax);
			//returns true
			if (!$taxonomy_exist) {
				dhp_create_tax($projectTax,$projectName,$projectSlug);
			}
		}
	}
} // create_tax_for_projects()


	// PURPOSE: Create custom taxonomy for a specific Project in WP
	// INPUT:	$taxID = taxonomy root name, $taxName = project title, $taxSlug = project slug
function dhp_create_tax($taxID, $taxName, $taxSlug)
{
	// Add new taxonomy, make it hierarchical (like categories)
  $labels = array(
	'name' => _x( $taxName, 'taxonomy general name' ),
	'singular_name' => _x( $taxName, 'taxonomy singular name' ),
	'search_items' =>  __( 'Search Terms' ),
	'all_items' => __( 'All Terms' ),
	'parent_item' => __( 'Parent Term' ),
	'parent_item_colon' => __( 'Parent Term:' ),
	'edit_item' => __( 'Edit Term' ), 
	'update_item' => __( 'Update Term' ),
	'add_new_item' => __( 'Add New Term' ),
	'new_item_name' => __( 'New Term Name' ),
	'menu_name' => __( 'Term' ),
  );

  register_taxonomy($taxID, 'dhp-markers', array(
	'hierarchical' => true,
	'public' => true,
	'labels' => $labels,
	'show_ui' => true,
	'show_in_nav_menus' => false,
	'query_var' => true,
	'rewrite' => array('hierarchical' => true, 'slug' => 'dhp-projects/'.$taxSlug, 'with_front' => false)
  ));
} // dhp_create_tax()

// admin_head called when compiling header of admin panel
add_action( 'admin_head' , 'show_tax_on_project_markers' );

// PURPOSE: Called when creating admin panel for Markers to remove the editing boxes for
//			all taxonomies other than those connected to this project
// ASSUMES:	$post is set to a project (i.e., that we are editing or viewing project)

function show_tax_on_project_markers()
{
	global $post;

	if ($post && $post->post_type == 'dhp-markers') {
		$projectID = get_post_meta($post->ID, 'project_id', true);
		$project = get_post($projectID);
		$projectRootTaxName = DHPressProject::ProjectIDToRootTaxName($project->ID);
		$dhpTaxs = get_taxonomies();

		foreach ($dhpTaxs as $key => $value) {
			if ($value != $projectRootTaxName) {
				remove_meta_box($value.'div', 'dhp-markers', 'side');
			}
		}
	}
} // show_tax_on_project_markers()


// add_meta_boxes called when Edit Post runs
add_action('add_meta_boxes_dhp-project', 'add_dhp_project_admin_edit');

// PURPOSE: Called when Project is edited in admin panel to create Project-specific GUI

function add_dhp_project_admin_edit()
{
	add_meta_box(
		'dhp_settings_box', 			// id of edit box
		'Project Details',				// textual title of box
		'show_dhp_project_admin_edit', 			// name of callback function
		'dhp-project',					// name of custom post type
		'normal',						// part of page to add box
		'high'); 						// priority

		// Hide Custom Fields meta box
	remove_meta_box('postcustom', 'dhp-project', 'normal');
} // add_dhp_project_settings_box()


// PURPOSE:	Called by WP to create all needed HTML for admin panel
// ASSUMES:	Global $post is set to point to post for current project
// SIDE-FX: Creates hidden fields for storing data   
// NOTE: 	Data that is generated via WP queries (like looking for custom fields and map entries)
//				must be passed this way, as this happens last and does not overwrite WP globals (like $post)

function show_dhp_project_admin_edit()
{
	global $post;

		// BUG -- Post does not have appropriate value
	$projObj = new DHPressProject($post->ID);
	$project_settings = $projObj->getAllSettings();

		// must handle case that project has just been created and does not have settings yet
	if (is_null($project_settings)) {
		$project_settings = '';
	} else {
		$project_settings = json_encode($project_settings);
	}
	
		// Info about DH Press and this project
	echo '<p><b>'.__('DH Press version ', 'dhpress').DHP_PLUGIN_VERSION.'</b>&nbsp;&nbsp;'.__('Project ID ', 'dhpress').$post->ID.'</p>';
	echo '<p><a href="'.get_bloginfo('wpurl').'/wp-admin/edit-tags.php?taxonomy='.$projObj->getRootTaxName().'" >'.__('Category Manager', 'dhpress').'</a></p>';

		// Insert Edit Panel's HTML
	dhp_include_script(DHP_HTML_ADMIN_EDIT);

		// Use nonce for verification
	echo '<input type="hidden" name="dhp_nonce" id="dhp_nonce" value="'.wp_create_nonce('dhp_nonce'.$post->ID).'" />';

		// Insert HTML for special Project Settings
	echo '<table class="project-form-table">';
	echo '<tr><th><label for="project_settings">'.__('Project Settings', 'dhpress').'</label></th>';
	echo '<td><textarea name="project_settings" id="project_settings" cols="60" rows="4">'.$project_settings.'</textarea>
		<br /><span class="description">'.__('Stores the project_settings as JSON object', 'dhpress').'</span>';
	echo '</td></tr>';
		// Icons not currently used
	// echo '<input type="hidden" name="project_icons" id="project_icons" value="'.get_post_meta($post->ID, 'project_icons', true).'" />';
	echo '</table>'; // end table

		// Insert list of custom fields -- NOTE! getAllCustomFieldNames() will reset WP globals
	$dhp_custom_fields = $projObj->getAllCustomFieldNames();
	echo '<div style="display:none" id="custom-fields">'.json_encode($dhp_custom_fields).'</div>';

		// Insert list of map layers from loaded library -- NOTE! dhp_get_map_layer_list() will reset WP globals
	echo '<div style="display:none" id="map-layers">'.json_encode(dhp_get_map_layer_list()).'</div>';
} // show_dhp_project_admin_edit()


// 'save_post' is called after post is created or updated
add_action('save_post', 'save_dhp_project_settings');

// PURPOSE: Save data posted to WP for project
//				(Could be invoked by Auto-Save feature of WP)
// INPUT:	$post_id is the id of the post updated
// NOTE:    Complication is for new Project that does not yet have ID?
// ASSUMES:	$_POST is set to post data

function save_dhp_project_settings($post_id)
{
	if (!isset($_POST['dhp_nonce']))
		return $post_id;

	// verify nonce
	if (!wp_verify_nonce($_POST['dhp_nonce'], 'dhp_nonce'.$post_id))
		return $post_id;

	// check autosave
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
		return $post_id;

	// check permissions
	if ($_POST['post_type'] == 'page') {
		if (!current_user_can('edit_page', $post_id)) {
			return $post_id;
		}
	}

		// is this an update of existing Project post?
	$parent_id = wp_is_post_revision( $post_id );

		// If there was a previous version (not a new Project)
	if ($parent_id) {
		// loop through fields and save the data
		$parent  = get_post( $parent_id );
		$srcToCheck = $parent->ID;
	} else {
		$srcToCheck = $post_id;
	}

		// Check to see if Project settings from custom metabox are different from saved version
	$projObj = new DHPressProject($srcToCheck);
	$old = $projObj->getAllSettings();
	$new = $_POST['project_settings'];
	if ($new && $new != $old) {
		update_metadata('post', $post_id, 'project_settings', $new);
	} elseif ($new == '' && $old) {
		delete_metadata('post', $post_id, 'project_settings', $old);
	}
} // save_dhp_project_settings()


add_action( 'admin_action_dhp_export_as_csv', 'dhp_export_as_csv' );

// PURPOSE: Return all of the marker data associated with Project in CSV format
// NOTES:   This is invoked by URL added to Project Dashboard by dhp_export_post_link()
//			CSV file will start with row names:
//				csv_post_title, csv_post_type, project_id, <custom-fields>...
//			Followed by one row per marker, with column values corresponding to above
//				<post title>, 'dhp-marker', <Project ID>, ...

function dhp_export_as_csv()
{
	if (! ( isset( $_GET['post']) || isset( $_POST['post'])  || ( isset($_REQUEST['action']) && 'rd_duplicate_post_as_draft' == $_REQUEST['action'] ) ) ) {
		wp_die(__('No post to export has been supplied!', 'dhpress'));
	}

		// ensure that this URL has not been faked by non-admin user
	if (!current_user_can('edit_posts')) {
		wp_die(__('Invalid request', 'dhpress'));
	}
 
		// Get post ID and associated Project Data
	$postID = (isset($_GET['post']) ? $_GET['post'] : $_POST['post']);
	$projObj = new DHPressProject($postID);

		// Create appropriate filename
	$date = new DateTime();
	$dateFormatted = $date->format("Y-m-d");

	$filename = "csv-$dateFormatted.csv";

		// Tells the browser to expect a csv file and bring up the save dialog in the browser
	header('Content-Type: text/csv; charset=utf-8');
	header('Content-Disposition: attachment;filename='.$filename);

		// This opens up the output buffer as a "file"
	$fp = fopen('php://output', 'w');
		// Hack to write as UTF-8 format
	fwrite($fp, pack("CCC",0xef,0xbb,0xbf));

	$cfs = $projObj->getAllCustomFieldNames();
	$firstLine = array_merge(array('csv_post_title', 'csv_post_type' ), $cfs);
	array_push($firstLine, 'csv_post_post');

		// Output the names of columns first
	fputcsv($fp, $firstLine);

		// Go through all of the Project's Markers and gather data
	$loop = $projObj->setAllMarkerLoop();
	while ( $loop->have_posts() ) : $loop->the_post();
		$markerID = get_the_ID();

		$values = array(get_the_title(), 'dhp-markers' );

		foreach ($cfs as $theCF) {
			$content_val = get_post_meta($markerID, $theCF, true);
			array_push($values, $content_val);
		} // foreach

		array_push($values, get_the_content());

		fputcsv($fp, $values);
	endwhile;

		// Close the output buffer
	fclose($fp);
 
	exit();
} // dhp_export_as_csv()



// PURPOSE: Return all of the marker data associated with Project in Prospect CSV format
// NOTES:   This is similar to dhp_export_as_csv(), but converts the CSV file so it can be 
//			imported into Prospect.

function dhp_export_as_prospect_csv($postID, $tmplt_id)
{
		// Mote id map
	$get_mote_id = function($key) {
		$val = remove_accents($key);
		$val = sanitize_title($val);
		return $val;
	};
	
		// Get associated Project Data
	$projObj = new DHPressProject($postID);

	$csvFile = tempnam(sys_get_temp_dir(), "");

	$fp = fopen($csvFile, 'w');
		// Hack to write as UTF-8 format
	fwrite($fp, pack("CCC",0xef,0xbb,0xbf));

	$cfs = $projObj->getAllCustomFieldNames();
		// Prospect attribute IDs
	$attIDs = array_map($get_mote_id, $cfs);
	
	$firstLine = array_merge(array('csv_post_type', 'csv_post_title', 'tmplt-id', 'record-id'), $attIDs);
	array_push($firstLine, 'csv_post_post');

		// Output the names of columns first
	fputcsv($fp, $firstLine);

		// Go through all of the Project's Markers and gather data
	$loop = $projObj->setAllMarkerLoop();
	while ( $loop->have_posts() ) : $loop->the_post();
		$markerID = get_the_ID();

		$values = array('prsp-record', get_the_title(), $tmplt_id, basename(get_permalink()) );

		foreach ($cfs as $theCF) {
			$content_val = get_post_meta($markerID, $theCF, true);
			array_push($values, $content_val);
		} // foreach

		array_push($values, get_the_content());

		fputcsv($fp, $values);
	endwhile;

	return $csvFile;
} // dhp_export_as_prospect_csv()


	// PURPOSE: Returns array of short text mote legend values for Prospect export
	// NOTES: Derived from dhp_get_legend_vals() but does not use $_POST and uses Prospect data structure
function get_legend_vals($mote_name, $mote_delim, $custom_field, $projectID)
{

		// Nullify empty string or space
	if ($mote_delim == '' || $mote_delim == ' ') { $mote_delim = null; }

	$projObj      = new DHPressProject($projectID);
	$rootTaxName  = $projObj->getRootTaxName();

		// Does term have to be created? -- Do all the work if so
	if (!term_exists($mote_name, $rootTaxName)) {
		wp_insert_term($mote_name, $rootTaxName);
		$parent_term = get_term_by('name', $mote_name, $rootTaxName);
		$parent_id = $parent_term->term_id;

			// Get unique values used by the related custom field
		$mArray = $projObj->getCustomFieldUniqueDelimValues($custom_field, $mote_delim);

			// Initialize terms with mArray
		dhp_initialize_taxonomy($mArray, $parent_id, $rootTaxName);

			// Bind project's markers to the taxonomic terms
		dhp_bind_tax_to_markers($projObj, $custom_field, $parent_id, $rootTaxName, $mote_delim);
	} else {
		$parent_term = get_term_by('name', $mote_name, $rootTaxName);
		$parent_id = $parent_term->term_id;
	}

		// Find all of the terms derived from mote (parent/head term) in the Project's taxonomy
	$parent_terms_to_exclude = get_terms($rootTaxName, 'parent=0&orderby=term_group&hide_empty=0');

		// Create comma-separated string listing terms derived from other motes
	$exclude_string='';
	$initial = true;
	foreach ( $parent_terms_to_exclude as $term ) {
		if($term->term_id != $parent_id) {
			if(!$initial) {
				$exclude_string.=',';
			}
			$exclude_string.= $term->term_id;
			$initial = false;
		}
	}

		// Get all taxonomic terms for project, excluding all other motes
	$terms_loaded = get_terms($rootTaxName, 'exclude_tree='.$exclude_string.'&orderby=term_group&hide_empty=0');
	$t_count = count($terms_loaded);

	$prospectLegend = array();

		// Parse icon_url data from the description metadata
	if ($t_count > 0) {
		foreach ($terms_loaded as $term) {
			$term->icon_url = $term->description;
			if (substr($term->icon_url, 0, 1) != '#') {
				$term->icon_url = "#888888";
			}
		}

		// Get ID of root term
		$root_id = $terms_loaded[0]->term_id;
		array_shift($terms_loaded);	

		while(!empty($terms_loaded)) {
			foreach ($terms_loaded as $key => $term) {
				// Find all terms with no parent
				if ($term->parent == $root_id) {
					$prospectLegend[$term->term_id]->l = $term->name;
					$prospectLegend[$term->term_id]->v = $term->icon_url;
					$prospectLegend[$term->term_id]->z = array();
					unset($terms_loaded[$key]);
				}
				else if (array_key_exists($term->parent, $prospectLegend)) {
					$child = null;
					$child->l = $term->name;
					$child->v = $term->icon_url;
					$prospectLegend[$term->parent]->z[] = $child;
					unset($terms_loaded[$key]);
				}
			}
		}
		
		// Remove parent keys from array
		$prospectLegend = array_values($prospectLegend);
	}

	return $prospectLegend;
} // get_legend_vals()





add_action( 'admin_action_dhp_export_to_prospect', 'dhp_export_to_prospect' );

// PURPOSE: Return all of the settings associated with Project in a Prospect JSON configuration file
// NOTES:   

function dhp_export_to_prospect()
{
	if (! ( isset( $_GET['post']) || isset( $_POST['post'])  || ( isset($_REQUEST['action']) && 'rd_duplicate_post_as_draft' == $_REQUEST['action'] ) ) ) {
		wp_die(__('No post to export has been supplied!', 'dhpress'));
	}

		// ensure that this URL has not been faked by non-admin user
	if (!current_user_can('edit_posts')) {
		wp_die(__('Invalid request', 'dhpress'));
	}
 
		// Get post ID and associated Project Data
	$postID = (isset($_GET['post']) ? $_GET['post'] : $_POST['post']);
	$projSlug = get_post_field("post_name", $postID);
	$projTitle = get_post_field("post_title", $postID);
	$projObj = new DHPressProject($postID);
	$projSettings = $projObj->getAllSettings();

		// Create appropriate filename
	$date = new DateTime();
	$dateFormatted = $date->format("Y-m-d");

	$filename = "$projSlug-prospect-$dateFormatted";

		// Array to be returned as JSON-formatted Prospect archive
	$archive = array("type" => "Archive");

	$motes = $projSettings->motes;
	$attributes = array();
	$st_legend = array();
	
		// Store original mote ids to determine if they have changed
	$original_mote_ids = array();
    	// map for mote name to mote id
    $mote_id = array();
	

	$xhbtInspect = array("sc" => array(), "yt" => array(), "transcripts" => array(), "timestamps" => array());

	$i = 0;
	// Convert DH Press Motes to Prospect Attributes
	foreach($motes as $mote) {
		$st_legend = array();
		
		$original_mote_ids[$mote->name] = $mote->cf;
		
			// DH Press is more lenient with custom field names, so ensure they consist of lowercase alphanumeric characters, underscores and hyphens
		$mote->cf = remove_accents($mote->cf);
		$mote->cf = sanitize_title($mote->cf);
		
        $mote_id[$mote->name] = $mote->cf;

		switch ($mote->type) {
			case "Short Text" :
				$type =  'V';
				$st_legend = get_legend_vals($mote->name, $mote->delim, $mote->cf, $postID);
				break;
			case "Long Text" :
				$type = 'T';
				break;
			case "Lat/Lon Coordinates" :
				$type = 'L';
				break;
			case "X-Y Coordinates" :
				$type = 'X';
				break;
			case "Date" :
				$type = 'D';
				break;
			case "Pointer" :
				$type = 'P';
				break;
			case "Image" :
				$type = 'I';
				break;
			case "Link To" :
				$type = 'l';
				break;
			case "SoundCloud" :
				$type = 'S';
				$xhbtInspect["sc"][] = $mote->cf;
				break;
			case "YouTube" :
				$type = 'Y';
				$xhbtInspect["yt"][] = $mote->cf;
				break;
			case "Transcript" :
				$type = 'x';
				$xhbtInspect["transcripts"][] = $mote->cf;
				break;
			case "Timestamp" :
				$type = 't';
				$xhbtInspect["timestamps"][] = $mote->cf;
				break;
		}

		//$att_id = strtolower(str_replace(' ', '_', trim($mote->name)));

		$attributes[$i] = array("type" => "Attribute",
								"att-id" => $mote->cf,
								"att-privacy" => 'o',
								"att-def" => array('l' => $mote->name,
												   't' => $type,
												   'd' => $mote->delim,
												   'h' => ""),
								"att-range" => array(),
								"att-legend" => $st_legend
							);
		$tmplt["a"][] = $mote->cf;
		$i++;
	}

	$get_mote_id = function($key) use ($mote_id) {
		return $mote_id[$key];
	};


	$projTranscript = $projSettings->views->transcript;

	foreach ($projTranscript as &$t) {
		if ($t != "disable") {
			$t = $mote_id[$t];
		}
	}

	$xhbtModal = array();
	$xhbtModal["audio"] = ($projTranscript->audio == "disable" ? false : true);
	$xhbtModal["video"] = ($projTranscript->video == "disable" ? false : true);
	$xhbtModal["transcript"] = ($projTranscript->transcript == "disable" ? false : true);
	$xhbtModal["transcript2"] = ($projTranscript->transcript2 == "disable" ? false : true);

	$projSettings->views->post->content = array_map($get_mote_id, $projSettings->views->post->content);
	$projSettings->views->select->content = array_map($get_mote_id, $projSettings->views->select->content);


	$template = array(array("type" => "Template",
					  "tmplt-id" => "tmplt-".$projSlug,
					  "tmplt-def" => array("l" => $projTitle,
					  					   "d" => false,
					  					   "t" => $mote_id[$projSettings->general->mTitle],
					  					   "a" => $tmplt["a"]),
					  "tmplt-joins" => array(),
					  "tmplt-view" => array("sc" => $projTranscript->audio,
					  						"yt" => $projTranscript->video,
					  						"t" => array("t1Att" => $projTranscript->transcript,
					  									 "t2Att" => $projTranscript->transcript2,
					  									 "tcAtt" => $projTranscript->timecode),
					  						"cnt" => $projSettings->views->post->content)
				));



	$eps = $projSettings->eps;
	$xhbt_views = array();

	$maps = array();

	// Convert DH Press Entry Points to a Prospect Exhibit
	foreach ($eps as $ep) {

		$xhbt_c = array();

		switch ($ep->type) {
			case "time" :
				$type = "T";
                
                $lgnds = ($ep->settings->color == "disable") ? "" : $mote_id[$ep->settings->color];

				$xhbt_c = array("bHt" => $ep->settings->bandHt,
								"xLbl" => $ep->settings->wAxisLbl,
								"from" => $ep->settings->from,
								"to" => $ep->settings->to,
								"zFrom" => $ep->settings->openFrom,
								"zTo" => $ep->settings->openTo,
								"dAtts" => array($ep->settings->date),
								"lgnds" => array(array($lgnds)));
				break;
			case "pinboard" :
				$type = "P";
				$lyrs = array();
                for ($i=0; $i < sizeof($ep->settings->layers); $i++) { 
                    $lyrs[$i]["url"] = $ep->settings->layers[$i]->file;
					$lyrs[$i]["o"] = 1;
                }
                
                $lgnds = array();
                foreach ($ep->settings->legends as $lgnd) {
                    $lgnds[] = $mote_id[$lgnd];
                }

				$xhbt_c = array("iw" => $ep->settings->iw,
								"ih" => $ep->settings->ih,
								"dw" => $ep->settings->dw,
								"dh" => $ep->settings->dh,
								"img" => $ep->settings->imageURL,
								"min" => 5,
								"max" => 5,
								"cAtts" => array($ep->settings->coordMote),
								"pAtts" => array(),
								"sAtts" => array(),
								"lClrs" => array(),
								"lgnds" => array($lgnds),
								"lyrs" => $lyrs);
				break;
			case "flow" :
				$type = "F";
				$xhbt_c = array("w" => $ep->settings->width,
								"gr" => false,
								"fcts" => array_map($get_mote_id, $ep->settings->motes));
				break;
			case "browser" :
				$type = "B";
				$xhbt_c = array("gr" => false,
								"fcts" => array_map($get_mote_id, $ep->settings->motes));
				break;
			case "map" :
				$type = "M";
				
				$mapBase = "";
				$baseLayers = array(".blank", ".mq-aerial", ".mq-base", ".osm-base");
				$lyrs = array();
				$i = 0;
                foreach ($ep->settings->layers as $layer) { 
					if (in_array($layer->id, $baseLayers)) {
						$mapBase = $layer->id;
					}
					else {
						$lyrs[$i]["lid"] = $layer->id;
						$lyrs[$i]["o"] = $layer->opacity;
						$i++;
					}
				}
								
				if (count($lyrs) > 0) {	
					$ids  = array();
		
					foreach($lyrs as $lyr) {
						$ids[] = $lyr['lid'];
					}
		
					$args = array('posts_per_page' => -1,
								  'post_type'      => 'dhp-maps');
					$posts = get_posts($args);
		
					foreach($posts as $post) {
						$mapID = get_post_meta($post->ID, 'dhp_map_id', true);
			
						if(in_array($mapID, $ids)) {
								// Borrowed from dhp_get_map_custom_fields() in dhp-map-library
							$dhp_map_custom_fields = array( 'dhp_map_sname', 'dhp_map_url', 'dhp_map_subdomains',
                           		   	'dhp_map_n_bounds', 'dhp_map_s_bounds', 'dhp_map_e_bounds', 'dhp_map_w_bounds',
                               		'dhp_map_min_zoom', 'dhp_map_max_zoom', 'dhp_map_inverse_y', 'dhp_map_desc', 'dhp_map_credits'
                            );
							
							$mapFields = array();
							foreach ($dhp_map_custom_fields as $field) {
        						$value = get_post_meta($post->ID, $field, true);
        						$mapFields[$field] = $value;
    						}
				
							$maps[] = array("type" => "Map",
											"map-id" => $mapID,
											"map_sname" => $mapFields["dhp_map_sname"],
											"map_url" => $mapFields["dhp_map_url"],
											"map_inverse_y" => $mapFields["dhp_map_inverse_y"],
											"map_subdomains" => $mapFields["dhp_map_subdomains"],
											"map_min_zoom" => $mapFields["dhp_map_min_zoom"],
											"map_max_zoom" => $mapFields["dhp_map_max_zoom"],
											"map_credits" => $mapFields["dhp_map_credits"],
											"map_n_bounds" => $mapFields["dhp_map_n_bounds"],
											"map_s_bounds" => $mapFields["dhp_map_s_bounds"],
											"map_e_bounds" => $mapFields["dhp_map_e_bounds"],
											"map_w_bounds" => $mapFields["dhp_map_w_bounds"],
											);
						}
					}
				}
				
				$xhbt_c = array("clat" => $ep->settings->lat,
								"clon" => $ep->settings->lon,
								"zoom" => $ep->settings->zoom,
								"min" => 5,
								"max" => 5,
								"clstr" => $ep->settings->cluster,
								"cAtts" => array(array($mote_id[$ep->settings->coordMote])),
								"pAtts" => array(),
								"sAtts" => array(),
								"lgnds" => array(array_map($get_mote_id, $ep->settings->legends)),
								"lClrs" => array(),
								"base" => $mapBase,
								"lyrs" => $lyrs);
				break;
			case "cards" :
				$type = "C";
				switch ($ep->settings->width) {
					case "thin" :
						$width = "t";
						break;
					case "med-width" :
						$width = "m";
						break;
					case "wide" :
						$width = "w";
						break;
					default :
						$width = "m";
						break;
				}
				switch ($ep->settings->height) {
					case "short" :
						$height = "s";
						break;
					case "med-height" :
						$height = "m";
						break;
					case "tall" :
						$height = "t";
						break;
					default :
						$height = "m";
						break;
				}
                
                // Use color mote as legend, or null if "disable"
                $lgnds = ($ep->settings->color == "disable") ? "" : $mote_id[$ep->settings->color];

				$xhbt_c = array("lOn" => $ep->settings->titleOn,
								"w" => $width,
								"h" => $height,
								"iAtts" => array(),
								"cnt" => array(array_map($get_mote_id, $ep->settings->content)),
								"lgnds" => array(array($lgnds)));
				break;
			case "tree" :
				$type = "G";
				break;
		}

		$xhbt_views[] = array("l" => $ep->label,
						  	  "n" => "",
						  	  "vf" => $type,
						  	  "c" => $xhbt_c
						  	 );
	}

	$exhibit = array(array("type" => "Exhibit",
					 "xhbt-id" => "xhbt-".$projSlug,
					 "xhbt-gen" => array("l" => $projTitle,
					 					 "hbtn" => $projSettings->general->homeLabel,
					 					 "hurl" => $projSettings->general->homeURL,
					 					 "ts" => array("tmplt-".$projSlug)),
					 "xhbt-views" => $xhbt_views,
					 "xhbt-inspect" => array("sc" => array("atts" => $xhbtInspect["sc"]),
						  					 "yt" => array("atts" => $xhbtInspect["yt"]),
						  					 "t" => array("t1Atts" => $xhbtInspect["transcripts"],
					  									 "t2Atts" => $xhbtInspect["transcripts"],
					  									 "tcAtts" => $xhbtInspect["timestamps"]),
						  					 "modal" => array("aOn" => $xhbtModal["audio"],
						  					 				  "scOn" => $xhbtModal["audio"],
						  					 				  "ytOn" => $xhbtModal["video"],
						  					 				  "tOn" => $xhbtModal["transcript"],
						  					 				  "t2On" => $xhbtModal["transcript2"],
						  					 				  "atts" => array($projSettings->views->select->content)) 
						  					)
					 ));



	$archive["items"] = array_merge($attributes, $template, $exhibit, $maps);
	
	$readme  = "Transferring your DH Press project to Prospect requires that you 1) Import your project's marker data as Prospect records and 2) Import your project's settings using the \"". $filename .".json\" file included in this zip. This file contains all of the Prospect settings necessary to transfer your project with minimal additional work.\n\n\n";
	
	
	$readme .= "To import your DH Press project marker data into Prospect, you may either import the automatically-generated .csv file included in this zip file or manually update your own .csv file.\n\n";
	
	$readme .= "To import the automatically-generated .csv file, follow these steps:\n";
	$readme .= "1) Navigate to Tools > CSV Importer Improved in your WordPress admin panel\n";
	$readme .= "2) Select the \"". $filename .".csv\" file included in this zip\n";
	$readme .= "3) Press \"Import\"\n\n";
	
	$readme .= "If you do not want to use the automatically-generated .csv file, you can also import your DH Press project's marker data manually by modifying your .csv file to follow Prospect's data structure.\n";
	$readme .= "To manually update your .csv file containg your project's data, follow these steps:\n";
	$readme .= "1) In the .csv file, remove the \"project_id\" column\n";
	$readme .= "2) Create a column entitled \"record-id\" and copy the values from the \"csv_post_title\" column\n";
	$readme .= "3) If desired, update the \"csv_post_title\" column values to human-readable titles (see page 46 of the Prospect manual for more information)\n";
	$readme .= "4) Change the \"csv_post_type\" column values to \"prsp-record\"\n";
	$readme .= "5) Create a column entitled \"tmplt-id\" and set its value to \"tmplt-". $projSlug ."\" for every row\n";
	$readme .= "6) Ensure that the rest of your column names match the attribute IDs of their corresponding motes exactly (use the list below as a guide). If any of your mote IDs contained spaces, special characters, or capital letters, the IDs of their corresponding attributes have been changed\n";
	$readme .= "7) After completing these steps, you can import this .csv file into Prospect using the CSV Importer tool by following the same steps for importing the automatically-generated .csv file.\n\n";
	
	$readme .= "For your reference, the following list contains this project's motes and the corresponding attribute IDs that will be used by Prospect. IDs that have been changed are marked with asterisks.\n\n";
	
	$readme .= "===========================================================================\n";
	$readme .= " DH Press Mote Name  :  Original Mote ID  :  New Prospect Attribute ID (**) \n";
	$readme .= "===========================================================================\n";
	foreach ($mote_id as $mote => $id) {
			$nameSpaces = 21 - strlen($mote);
			$nameSpaces = max(0, $nameSpaces); // Set to 0 if negative
			$nameSpaces = str_repeat(" ", $nameSpaces); // Generate whitespace for table spacing
			
			$idSpaces = 19 - strlen($original_mote_ids[$mote]);
			$idSpaces = max(0, $idSpaces); // Set to 0 if negative
			$idSpaces = str_repeat(" ", $idSpaces); // Generate whitespace for table spacing
			
			$readme .= $mote . $nameSpaces . ": " . $original_mote_ids[$mote] . $idSpaces . ": ". $id;
			if ($original_mote_ids[$mote] != $id) {
				$readme .= "  **";
			}
			$readme .= "\n";
	}
	
	
	$readme .= "\n\n\nTo import your DH Press project settings into Prospect:\n";
	$readme .= "1) Navigate to Prospect > Archive in your WordPress admin panel\n";
	$readme .= "2) Select the \"". $filename .".json\" file included in this zip under \"Import JSON Archive File\"\n";
	$readme .= "3) Press \"Upload Archive\"\n\n";
	
	$readme .= "This will generate a Prospect template, exhibit, attributes, and maps which correspond to your DH Press project.\n\n";  
	
	
	$tmpFile = tempnam(sys_get_temp_dir(), "");
	
	$csvFile = dhp_export_as_prospect_csv($postID, "tmplt-" . $projSlug, $mote_id);
	
	$zip = new ZipArchive;
	if ($zip->open($tmpFile, ZipArchive::CREATE) == TRUE) {
		$zip->addFromString($filename . ".json", json_encode($archive));
		$zip->addFromString("README.txt", $readme);
		$zip->addFile($csvFile, $filename . ".csv");
	}
	$zip->close();
	
	fclose($csvFile);
	
	header('Content-disposition: attachment; filename=' . $filename . '.zip');
    header('Content-type: application/zip');
	readfile($tmpFile);
	
	// Delete temporary file
	unlink($tmpFile);
} // dhp_export_to_prospect()


// PURPOSE: Get all of the visual features associated via metadata with the taxonomic terms associated with 1 Mote
// INPUT:	$parent_term = Object for mote/top-level term
//			$taxonomy = root name of taxonomy for Project
// NOTES:	JS code will break if icon_url field not set, so we will check it here and die if failure
//			If icon_url is a color, the "black" field will be set to true if black will contrast with it
// RETURNS: Description of Legends to appear on Map in the following format:
			// {	"type" : "filter",
			// 		"name" : String (top-level-mote-name),
			// 		"terms" :				// 1st level terms & their children
			// 		[
			// 		  {	"name" :  String (inc. top-level-mote-name),
			// 			"id" : integer,
			// 			"icon_url": URL,
			//			"black": boolean,
			// 			"children" :
			// 			[
			// 			  {	"name" : String,
			// 				"term_id" : integer,
			// 				"parent" : integer,
			// 				"count" : String,
			//				"icon_url" : String,
			// 				"term_group" : String
			//			  }, ...
			// 			]
			// 		  }, ...
			// 		],
			// 	}

function dhp_get_category_vals($parent_term, $taxonomy)
{
	$filter_object  = array();
	$filter_parent  = array();

	$filter_object['type']  = "filter";
	$filter_object['name']  = $parent_term->name;
	$filter_object['terms'] = array();

		// Begin with top-level mote name
		// Currently only used by Pinboard View
	$filter_parent['name']     = $parent_term->name;
	$filter_parent['id']       = intval($parent_term->term_id);
	array_push($filter_object['terms'], $filter_parent);

	$myargs = array( 'orderby'       => 'term_group',
					 'hide_empty'    => false, 
					 'parent'        => $parent_term->term_id );
	$children_terms  = get_terms($taxonomy, $myargs);

		// Go through each of the 1st-level values in the category
	foreach ($children_terms as $child) {
			// Does 1st-level term have any 2ndary children?
		$childArgs = array( 'orderby' 		=> 'term_group',
							'hide_empty'    => false,
							'parent'        => $child->term_id );
		$children_terms2 = get_terms( $taxonomy, $childArgs );

			// Save each of the 2ndary children
		$new_children = array();
		foreach ($children_terms2 as $child2) {
			$new_child = array();

			$new_child['name'] = $child2->name;
			$new_child['count'] = intval($child2->count);

				// convert IDs from String to Integer
			$new_child['term_id'] = intval($child2->term_id);
			$new_child['parent'] = intval($child2->parent);

				// 2ndary level colors not currently used, so won't compute B/W contrast
			if ($child2->description) {
				$new_child['icon_url'] = $child2->description;
			} else {
				$new_child['icon_url'] = null;
			}
			array_push($new_children, $new_child);
		} // for each 2ndary-level value

		if ($child->description) {
			$icon_url = $child->description;
		} else {
			$icon_url = null;
		}

			// Now save the top-level category term
		$child_filter				 = array();
		$child_filter['name']        = $child->name;
		$child_filter['id']          = intval($child->term_id);
		$child_filter['icon_url']    = $icon_url;
		$child_filter['children']    = $new_children;

			// If icon_url is a color value, determine if black or white will contrast: algorithms at
			//    http://www.particletree.com/notebook/calculating-color-contrast-for-legible-text/
			//    http://stackoverflow.com/questions/5650924/javascript-color-contraster
		if (substr($icon_url, 0, 1) === '#') {
			$brightness = ((hexdec(substr($icon_url, 1, 2)) * 299.0) +
						(hexdec(substr($icon_url, 3, 2)) * 587.0) +
						(hexdec(substr($icon_url, 5, 2)) * 114.0)) / 255000.0;

			if ($brightness >= 0.5) {
				$child_filter['black'] = true;
			} else {
				$child_filter['black'] = false;
			}
		}

		array_push($filter_object['terms'], $child_filter);
	} // for each 1st-level value

		// Update top-level mote pushed near top of function
	// $filter_parent['children'] = $children_terms;

	return $filter_object;
} // dhp_get_category_vals()


// ========================================= AJAX calls ======================================


// PURPOSE:	Creates Legends and Feature Collections Object (as per OpenLayer) when looking at a project page;
//			That is, return array describing all markers based on filter and visualization
// INPUT:	$project_id = ID of Project to view
//			$index = index of entry-point to display
// RETURNS: JSON object describing all markers associated with Project
//			[0..n-1] contains results from dhp_get_category_vals() defined above;
//			[n] is based on FeatureCollection; exact contents will depend on visualization, but could include:
			// {	"type": "FeatureCollection",	// No longer GeoJSON compliant!
			// 	 	"features" :
			// 		[
			// 			{ "type" : "Feature",	// Only added to FeatureCollections created for Maps
			//							// Only if map or pinboard
			// 			  "geometry" : {
			//					"type" : 1 = Point | 2 = Line | 3 = Polygon
			//					"coordinates" : LongLat (or X-Y)
			//			  },
			//			  "date" : String, 	// Only if Timeline
			// 			  "title" : String, // Used by Select Modal and all EPs
			// 			  "properties" :
			// 				[
			//							// All data corresponding to categories/legends associated with marker
			// 					"categories" : [ integer IDs of category terms ],
			//							// Data used to create modals
			// 					"content" : [
			//						{ moteName : moteValue }, ...
			//					],
			// 					"link" : URL,
			// 					"link2" : URL
			//							// Those below only in the case of transcript markers
			// 					"audio" : String,
			//					"video" : String,
			// 					"transcript" : String,
			// 					"transcript2" : String,
			// 					"timecode" : String,
			// 				],
			// 			}, ...
			// 		]
			// 	}

add_action('wp_ajax_dhpGetMarkers', 'dhp_get_markers' );
add_action('wp_ajax_nopriv_dhpGetMarkers', 'dhp_get_markers');

// PURPOSE:	Handle Ajax call to get all markers for a Project for a non-Tree view
// ASSUMED: The current Entry Point is not a Tree!
// INPUT:	$_POST['project'] is ID of Project
//			$_POST['index'] is the 0-based index of the current Entry Point
// RETURNS:	JSON object of array of marker values

function dhp_get_markers()
{
	$projectID = $_POST['project'];
	$index = $_POST['index'];

		// initialize result array
	$json_Object = array();

	$mQuery = new DHPressMarkerQuery($projectID);
	$projObj = $mQuery->projObj;
	$eps = $mQuery->projSettings->eps[$index];

		// Create NULL values for conditions dependent on EP type
	$mapCF = $pinCF = $dateCF = null;

		// Does each Marker need a "type": "Feature" property?  Yes if GeoJSON
	$addFeature = false;

	switch ($eps->type) {
	case "map":
			// Which field used to encode Lat-Long on map?
		$mapPointsMote = $projObj->getMoteByName($eps->settings->coordMote);
		$mapCF = $mapPointsMote->cf;
			// Might data contain Polygons (rather than just Points)?
		if ($mapPointsMote->delim != '') {
			$mapDelim = $mapPointsMote->delim;
		} else {
			$mapDelim = null;
		}
			// Find all possible legends/filters for this map -- each marker needs these fields
		$filters = $eps->settings->legends;
			// Collect all possible category values/tax names for each mote in all filters
		foreach ($filters as $legend) {
			$term = get_term_by('name', $legend, $mQuery->rootTaxName);
			if ($term) {
				array_push($json_Object, dhp_get_category_vals($term, $mQuery->rootTaxName));
			}
		}
		$addFeature = true;
		break;

	case "pinboard":
			// Which field used to encode Lat-Long on map?
		$pinPointsMote = $projObj->getMoteByName($eps->settings->coordMote);
		$pinCF = $pinPointsMote->cf;
			// Find all possible legends/filters for this pinboard -- each marker needs these fields
		$filters = $eps->settings->legends;
			// Collect all possible category values/tax names for each mote in all filters
		foreach ($filters as $legend) {
			$term = get_term_by('name', $legend, $mQuery->rootTaxName);
			if ($term) {
				array_push($json_Object, dhp_get_category_vals($term, $mQuery->rootTaxName));
			}
		}
		break;

	case "cards":
			// Convert color mote to custom field
		$cardColorMote = $eps->settings->color;
		if ($cardColorMote != null && $cardColorMote !== '' && $cardColorMote != 'disable') {
				// Create a legend for the color values
			$term = get_term_by('name', $cardColorMote, $mQuery->rootTaxName);
			if ($term) {
				array_push($json_Object, dhp_get_category_vals($term, $mQuery->rootTaxName));
			}
		}
			// gather card contents
		foreach ($eps->settings->content as $theContent) {
			array_push($mQuery->selectContent, $theContent);
		}
			// must add all sort and filter motes to content
			// We must also collect all category values/tax names for filters that are Short Text motes
			//	(so Card filter knows possible values) but don't duplicate color legend
		foreach ($eps->settings->filterMotes as $theContent) {
			array_push($mQuery->selectContent, $theContent);
			$filterMote = $projObj->getMoteByName($theContent);
			if ($filterMote->type=='Short Text' && $filterMote->name != $cardColorMote) {
				$term = get_term_by('name', $theContent, $mQuery->rootTaxName);
				if ($term) {
					array_push($json_Object, dhp_get_category_vals($term, $mQuery->rootTaxName));
				}
			}
		}
		foreach ($eps->settings->sortMotes as $theContent) {
			array_push($mQuery->selectContent, $theContent);
		}
		break;

	case "time":
			// Create a legend for the color values
		$term = get_term_by('name', $eps->settings->color, $mQuery->rootTaxName);
		if ($term) {
			array_push($json_Object, dhp_get_category_vals($term, $mQuery->rootTaxName));
		}

		$dateMote = $projObj->getMoteByName($eps->settings->date);
		$dateCF = $dateMote->cf;
		break;

	case "flow":
			// Gather all Short Text Legends used for Facet dimensions
		foreach ($eps->settings->motes as $legend) {
			$term = get_term_by('name', $legend, $mQuery->rootTaxName);
			if ($term) {
				array_push($json_Object, dhp_get_category_vals($term, $mQuery->rootTaxName));
			}
		}
		break;

	case "browser":
			// If mote is Short Text Legends, compute Legend values, else add to marker content
		foreach ($eps->settings->motes as $legend) {
			$defDef = $projObj->getMoteByName($legend);
			if ($defDef->type=='Short Text') {
				$term = get_term_by('name', $legend, $mQuery->rootTaxName);
				if ($term) {
					array_push($json_Object, dhp_get_category_vals($term, $mQuery->rootTaxName));
				}
			} else {
				array_push($mQuery->selectContent, $legend);
			}
		}
		break;
	} // switch

		// Ensure that any new content requested from markers is not redundant
	$mQuery->selectContent = array_unique($mQuery->selectContent);

	$feature_collection = array();
	$feature_collection['type'] = 'FeatureCollection';
	$feature_array = array();


		// Run query to return all marker posts belonging to this Project
	$loop = $projObj->setAllMarkerLoop();
	if($loop->have_posts()){
		foreach($loop->posts as $markerPost){
			$markerID = $markerPost->ID;
				// Feature will hold properties and some other values for each marker
			$thisFeature = array();

				// Only add property if necessary
			if ($addFeature) {
				$thisFeature['type']    = 'Feature';
			}

				// Most data goes into properties field
			$thisFeaturesProperties = $mQuery->getMarkerProperties($markerID);

				// First set up fields required by visualizations, abandon marker if missing

				// Map visualization features?
				// Skip marker if missing necessary LatLong data or not valid numbers
			if ($mapCF != null) {
				$latlon = get_post_meta($markerID, $mapCF, true);
				if (empty($latlon)) {
					continue;
				}
					// Create Polygons? Only if delim given
					// NOTE: Since no longer passing GeoJSON, coord order is: LatLon
				if ($mapDelim) {
					$split = explode($mapDelim, $latlon);
						// Just treat as Point if only one data item
					if (count($split) == 1) {
						$split = explode(',', $latlon);
						$thisFeature['geometry'] = array("type" => 1,
														"coordinates"=> array((float)$split[0], (float)$split[1]));

					} else {
						$poly = array();
						foreach ($split as $thisPt) {
							$pts = explode(',', $thisPt);
							array_push($poly, array((float)$pts[0], (float)$pts[1]));
						}
						if (count($poly) == 2) {
							$thisFeature['geometry'] = array("type" => 2, "coordinates" => $poly);

						} else {
							$thisFeature['geometry'] = array("type" => 3, "coordinates" => $poly);
						}
					}
				} else {
					$split = explode(',', $latlon);
					$thisFeature['geometry'] = array("type" => 1,
													"coordinates"=> array((float)$split[0],(float)$split[1]));
				}
		}

			// Pinboard visualization features
			// Skip marker if missing necessary LatLong data or not valid numbers
		if ($pinCF != null) {
			$xycoord = get_post_meta($markerID, $pinCF, true);
			if (empty($xycoord)) {
				continue;
			}
			$split = explode(',', $xycoord);
			$thisFeature['geometry'] = array("type"=> 1,
											"coordinates"=> array((float)$split[0], (float)$split[1]));
		}

			// Timeline visualization features
			// Skip marker if missing necessary Date
		if ($dateCF != null) {
			$date = get_post_meta($markerID, $dateCF, true);
			if (empty($date)) {
				continue;
			}
			$thisFeature['date'] = $date;
		}

			// Fetch title for marker
		if ($mQuery->titleMote=='the_title') {
			$thisFeature["title"] = get_the_title();
		} else {
			$thisFeature["title"] = get_post_meta($markerID, $mQuery->titleMote, true);
		}

			// Store all of the properties
		$thisFeature['properties'] = $thisFeaturesProperties;
			// Save this marker
		array_push($feature_array, $thisFeature);
		}
	}

	$feature_collection['features'] = $feature_array;
	array_push($json_Object, $feature_collection);

	die(json_encode($json_Object));
} // dhp_get_markers()


// TREE MARKER CODE ==================

// PURPOSE: Retrieve all of the relevant info about this node and all call recursively for all of its children
// INPUT:   $cf_key = custom field to use as primary key
//			$node_id = unique primary key value for the Marker/custom post
//			$mQuery = query used for this project
//			$childrenCF = custom field that contains values that point to each generation
//			$childrenDelim = character delimiter for children Mote value
// RETURNS: Nested Array for $nodeName and all of its children

function dhp_create_tree_node($cf_key, $node_id, $mQuery, $childrenCF, $childrenDelim)
{
		// Get the WP post corresponding to this marker
	$args = array( 
		'post_type' => 'dhp-markers', 
		'posts_per_page' => 1,
		// 'name' => $nodeName,
		// array( 'meta_key' => 'project_id', 'meta_value' => $mQuery->projID )
		'meta_query' => array(
			array('key' => 'project_id', 'value' => $mQuery->projID),
			array('key' => $cf_key, 'value' => $node_id)
		)
	);
	$loop = new WP_Query($args);

		// We can only abort if not found
	if (!$loop->have_posts()) {
		trigger_error("Tree view label assigned to unknown mote");
		return null;
	}

	$loop->the_post();
	$markerID = get_the_ID();

		// Feature will hold properties and some other values for each marker
	$thisFeature = array();

		// Most data goes into properties field
	$thisFeaturesProperties = $mQuery->getMarkerProperties($markerID);

		// Store all of the properties
	$thisFeature['properties'] = $thisFeaturesProperties;

		// Fetch title for marker
	if ($mQuery->titleMote=='the_title') {
		$thisFeature["title"] = get_the_title();
	} else {
		$thisFeature["title"] = get_post_meta($markerID, $mQuery->titleMote, true);
	}

		// Now that we've constructed this feature, call recursively for all of its children
	$childrenVal = get_post_meta($markerID, $childrenCF, true);
	if (!is_null($childrenVal) && ($childrenVal !== '')) {
		$childName = explode($childrenDelim, $childrenVal);

			// Create array for all descendants and call this recursively to fetch them
		$children = array();
		foreach($childName as $theChildName) {
			$trimName = trim($theChildName);
			$theChildData = dhp_create_tree_node($cf_key, $trimName, $mQuery, $childrenCF, $childrenDelim);
				// Don't add if data error (name not found)
			if ($theChildData != null) {
				array_push($children, $theChildData);
			}
		}
			// Store in feature if any descendents generated
		if(count($children) > 0) {
			$thisFeature['children'] = $children;
		}
	}

		// Return this marker
	return $thisFeature;
} // dhp_create_tree_node()


// Enable for both editing and viewing

add_action('wp_ajax_dhpGetMarkerTree', 'dhp_get_marker_tree' );
add_action('wp_ajax_nopriv_dhpGetMarkerTree', 'dhp_get_marker_tree');

// PURPOSE:	Handle Ajax call to get all markers for a Project
// 			Similar to createMarkerArray() but creates tree of marker data not flat array
// INPUT:	$_POST['project'] = ID of Project to view
//			$_POST['index'] = index of entry-point to display
// RETURNS: JSON object describing all markers associated with Project
//			[0] contains results from dhp_get_category_vals() defined above;
//			[1] is a Nested tree
//				{
// 					"name": String,
//					"properties" :
//					[
//							// All data corresponding to categories/legends associated with marker
//						"categories" : [ integer IDs of category terms ],
//							// Data used to create modals
//						"title" : String,
//							// Data needed by select modal or card filter/sort
//						"content" : [
//							{ moteName : moteValue }, ...
//						],
//						"link" : URL,
//						"link2" : URL
//							// Those below only in the case of transcript markers
//						"audio" : String,
//						"transcript" : String,
//						"transcript2" : String,
//						"timecode" : String,
//					],
//					"children" : [
//						Objects of the same sort
//					]
//				}
// ASSUMES:  Color Legend has been created and category/taxonomy bound to Markers

function dhp_get_marker_tree()
{
	$projectID = $_POST['project'];
	$index = $_POST['index'];

		// initialize result array
	$json_Object = array();

	$mQuery = new DHPressMarkerQuery($projectID);
	$projObj = $mQuery->projObj;
	$eps = $mQuery->projSettings->eps[$index];
	$cf_key = $mQuery->projSettings->general->mKey;

	if (is_null($cf_key) || $cf_key == 'disable') {
		trigger_error("Marker primary key not set");
		die("Marker primary key not set");
	}

		// Prepare for fetching markers' children pointer
	$childrenMote = $projObj->getMoteByName($eps->settings->children);
	$childrenDelim = $childrenMote->delim;
	$childrenCF = $childrenMote->cf;
	if (is_null($childrenCF)) {
		trigger_error("Tree view children assigned to unknown mote");
		die("Tree view children assigned to unknown mote");
	}

		// Get the Legend info for this Tree view's Legend data
		// We will assume that Legend has been created and category/taxonomy bound to Markers
	$colorCF = $eps->settings->color;
	if ($colorCF != '' && $colorCF != 'disable') {
		$colorCF = $projObj->getCustomFieldForMote($colorCF);
		if (is_null($colorCF)) {
			trigger_error("Tree view color assigned to unknown mote");
			die("Tree view color assigned to unknown mote");
		}
			// Create a legend for the color values
		$term = get_term_by('name', $eps->settings->color, $mQuery->rootTaxName);
		if ($term) {
			array_push($json_Object, dhp_get_category_vals($term, $mQuery->rootTaxName));
		}
	}

		// Ensure that any new content requested from markers is not redundant
	$mQuery->selectContent = array_unique($mQuery->selectContent);

		// Begin with head node
	$markers = dhp_create_tree_node($cf_key, trim($eps->settings->head), $mQuery, $childrenCF, $childrenDelim);

	array_push($json_Object, $markers);

	die(json_encode($json_Object));
} // dhp_get_marker_tree()


// ====================== AJAX Functions ======================

add_action( 'wp_ajax_dhpSaveProjectSettings', 'dhp_save_project_settings' );

// PURPOSE:	Called by JS code on page to save the settings (constructed by JS) for the Project
// ASSUMES:	Project ID encoded in string

function dhp_save_project_settings()
{
	$settings =  $_POST['settings'];
	$dhp_projectID = $_POST['project'];

	update_post_meta($dhp_projectID, 'project_settings', $settings);

		// Ajax call must terminate with "die"
	die('saving... '. $settings);
} // dhp_save_project_settings()


// PURPOSE:	Initialize the taxonomy terms for a single legend
// INPUT:	$mArray = array of unique values for mote
//			$parent_id = ID of head tax term
//			$projRootTaxName = root taxonomy term for Project
// RETURNS:	array of taxonomic terms belonging to $mote_name

function dhp_initialize_taxonomy($mArray, $parent_id, $projRootTaxName)
{
	$args = array('parent' => $parent_id);

		// Loop through array and create terms with parent(mote_name) for non-empty values
	foreach ($mArray as $value) {
		if (!is_null($value) && $value != '') {
				// WP's term_exists() function doesn't escape slash characters!
				// 	Unlike wp_insert_term() and wp_update_term()!
			$termIs = term_exists(addslashes($value), $projRootTaxName, $parent_id);
				//debug
			if(!$termIs) {
				//if term doesn't exist, create
				wp_insert_term($value, $projRootTaxName, $args);
			} else {
				//update term using id
				wp_update_term($termIs->term_id, $projRootTaxName, $args);
			}
		}
	}
} // dhp_initialize_taxonomy()

// PURPOSE: To associate taxonomic terms with Markers in this Project with corresponding values
// INPUT: 	$projObj = Object of DHPressProject class
//			$custom_field = custom field defined for Project's markers
//			$parent_id = ID of head term of taxonomy
//			$rootTaxName = name of root for all taxonomies belonging to this project

function dhp_bind_tax_to_markers($projObj, $custom_field, $parent_id, $rootTaxName, $mote_delim)
{
		// Now (re)create all subterms
	$loop = $projObj->setAllMarkerLoop();
	if($loop->have_posts()){
		foreach($loop->posts as $markerPost){
			$marker_id = $markerPost->ID;
			$tempMoteValue = get_post_meta($marker_id, $custom_field, true);

				// ignore empty or null values
			if (!is_null($tempMoteValue) && $tempMoteValue != '') {
				$tempMoteArray = array();
				if ($mote_delim) {
					$tempMoteArray = explode($mote_delim, $tempMoteValue );
				} else {
					$tempMoteArray = array($tempMoteValue);
				}
				$theseTerms = array();
				foreach ($tempMoteArray as $value) {
						// Make sure spaces are removed
					$value = trim($value);
						// Since we are specifying $parent_id, term_exists() will return 0/NULL or hash
					$term = term_exists($value, $rootTaxName, $parent_id);
					if ($term !== 0 && $term !== null) {
						array_push($theseTerms, intval($term['term_id']));
					}
				}
					// Ensure that marker is tagged with category terms for this mote
				wp_set_object_terms($marker_id, $theseTerms, $rootTaxName, true);
				// wp_set_post_terms($marker_id, $theseTerms, $rootTaxName, true);
			} 
		}
	}
	
	delete_option("{$rootTaxName}_children");
} // dhp_bind_tax_to_markers()


// creates terms in taxonomy when a legend is created
add_action( 'wp_ajax_dhpGetLegendValues', 'dhp_get_legend_vals' );

// PURPOSE:	Handle Ajax call to retrieve Legend values; create if does not exist already
// RETURNS:	Array of unique values/tax-terms as JSON object
//			This array includes the "head term" (legend/mote name)

function dhp_get_legend_vals()
{
	$mote_name 		= $_POST['moteName'];
	$mote_delim		= $_POST['delim'];
	$custom_field 	= $_POST['customField'];
	$projectID 		= $_POST['project'];

		// Nullify empty string or space
	if ($mote_delim == '' || $mote_delim == ' ') { $mote_delim = null; }

	$projObj      = new DHPressProject($projectID);
	$rootTaxName  = $projObj->getRootTaxName();

		// Does term have to be created? -- Do all the work if so
	if (!term_exists($mote_name, $rootTaxName)) {
		wp_insert_term($mote_name, $rootTaxName);
		$parent_term = get_term_by('name', $mote_name, $rootTaxName);
		$parent_id = $parent_term->term_id;

			// Get unique values used by the related custom field
		$mArray = $projObj->getCustomFieldUniqueDelimValues($custom_field, $mote_delim);

			// Initialize terms with mArray
		dhp_initialize_taxonomy($mArray, $parent_id, $rootTaxName);

			// Bind project's markers to the taxonomic terms
		dhp_bind_tax_to_markers($projObj, $custom_field, $parent_id, $rootTaxName, $mote_delim);
	} else {
		$parent_term = get_term_by('name', $mote_name, $rootTaxName);
		$parent_id = $parent_term->term_id;
	}

		// Find all of the terms derived from mote (parent/head term) in the Project's taxonomy
	$parent_terms_to_exclude = get_terms($rootTaxName, 'parent=0&orderby=term_group&hide_empty=0');

		// Create comma-separated string listing terms derived from other motes
	$exclude_string='';
	$initial = true;
	foreach ( $parent_terms_to_exclude as $term ) {
		if($term->term_id != $parent_id) {
			if(!$initial) {
				$exclude_string.=',';
			}
			$exclude_string.= $term->term_id;
			$initial = false;
		}
	}

		// Get all taxonomic terms for project, excluding all other motes
	$terms_loaded = get_terms($rootTaxName, 'exclude_tree='.$exclude_string.'&orderby=term_group&hide_empty=0');
	$t_count = count($terms_loaded);

		// Parse icon_url data from the description metadata
	if ($t_count > 0) {
		foreach ($terms_loaded as $term) {
			$term->icon_url = $term->description;
		}
	}

	$results = $terms_loaded;

	die(json_encode($results));
} // dhp_get_legend_vals()


add_action( 'wp_ajax_dhpSaveLegendValues', 'dhp_save_legend_vals' );

// PURPOSE:	Handle Ajax function to create or save terms associated with values defined
//			by a mote in a Project (Saving results of Configure Legend function)
// INPUT:	$_POST['project'] = ID of Project
//			$_POST['mote_name'] = name of mote (which is also head term/Legend name)
//			$_POST['terms'] = flat array of mote/legend values
// NOTES:	This function only expects and saves the parent, term_id, term_order and icon_url fields

function dhp_save_legend_vals()
{
	$mote_parent = $_POST['mote_name'];
	$projectID = $_POST['project'];

		// I don't know why terms array can be read directly without JSON decode
	$project_terms = $_POST['terms'];

		// Convert mote_parent to id
	$projRootTaxName = DHPressProject::ProjectIDToRootTaxName($projectID);

	foreach ($project_terms as $term) {
		$term_id        = intval($term['term_id']);
		$parent_term_id = intval($term['parent']);
		$term_order     = intval($term['term_order']);

		$updateArgs = array('parent' => $parent_term_id, 'term_group' =>  $term_order, 'description' => $term['icon_url']);

			// Update term settings
		$result = wp_update_term($term_id, $projRootTaxName, $updateArgs);
		if (is_wp_error($result)) {
			die('Legend for Project '.$projectID.' could not be updated due to error '.$result->get_error_message());
		}
	}
	delete_option("{$projRootTaxName}_children");

	$results = array($projectID, $mote_parent, $projRootTaxName, $project_terms);
	die(json_encode($results));
} // dhp_save_legend_vals()


add_action( 'wp_ajax_dhpRebuildLegendValues', 'dhp_rebuild_legend_vals' );

// PURPOSE:	Handle rebuilding taxonomy (gather custom field values and reassociate with Markers)
// INPUT:	$_POST['project'] = ID of Project
//			$_POST['newTerm'] = name of taxonomy term to add
//			$_POST['legendName'] = name of parent term (mote/Legend) under which it should be added
// RETURNS:	ID of new term

function dhp_rebuild_legend_vals()
{
	$mote_name 		= $_POST['moteName'];
	$custom_field 	= $_POST['customField'];
	$mote_delim		= $_POST['delim'];
	$projectID 		= $_POST['project'];

	$results		= array();

		// Nullify empty string or space
	if ($mote_delim == '' || $mote_delim == ' ') { $mote_delim = null; }

	$projObj      = new DHPressProject($projectID);
	$rootTaxName  = $projObj->getRootTaxName();

		// Has term already been created? -- Do all the work if not
	if (!term_exists($mote_name, $rootTaxName)) {
		$results['existed'] = false;
		wp_insert_term($mote_name, $rootTaxName);
		$parent_term = get_term_by('name', $mote_name, $rootTaxName);
		$parent_id = $parent_term->term_id;
	} else {
		$results['existed'] = true;
		$parent_term = get_term_by('name', $mote_name, $rootTaxName);
		$parent_id = $parent_term->term_id;
			// Empty out any pre-existing subterms in this taxonomy ??
		wp_update_term($parent_id, $rootTaxName);

			// Now delete any Category/Legend values that exist
		$delete_children = get_term_children($parent_id, $rootTaxName);
		if (!is_wp_error($delete_children)) {
			$results['deletedCount'] = count($delete_children);
			foreach ($delete_children as $delete_term) {
				wp_delete_term($delete_term, $rootTaxName);
			}
		} else {
			die('Get term fatal error: '.$delete_children);
		}
	}
	$results['parentID']= $parent_id;

		// Get unique values used by the related custom field
	$mArray = $projObj->getCustomFieldUniqueDelimValues($custom_field, $mote_delim);
	$results['values'] = $mArray;

		// Initialize terms with mArray
	dhp_initialize_taxonomy($mArray, $parent_id, $rootTaxName);

		// Bind project's markers to the taxonomic terms
	dhp_bind_tax_to_markers($projObj, $custom_field, $parent_id, $rootTaxName, $mote_delim);

	die(json_encode($results));
} // dhp_rebuild_legend_vals()


add_action( 'wp_ajax_dhpCreateTermInTax', 'dhp_create_term_in_tax' );

// PURPOSE:	Handle adding new terms to taxonomy (that don't pre-exist in Marker data)
// INPUT:	$_POST['project'] = ID of Project
//			$_POST['newTerm'] = name of taxonomy term to add
//			$_POST['legendName'] = name of parent term (mote/Legend) under which it should be added
// RETURNS:	Array of related data, inc. ID of new term

function dhp_create_term_in_tax()
{
	$projectID 			= $_POST['project'];
	$dhp_term_name		= $_POST['newTerm'];
	$parent_term_name	= $_POST['legendName'];

		// First get Term/Tax info for the Legend (assoc w/this project)
	$projRootTaxName = DHPressProject::ProjectIDToRootTaxName($projectID);
	$parent_term = term_exists($parent_term_name, $projRootTaxName);
	$parent_term_id = $parent_term['term_id'];
	$args = array( 'parent' => $parent_term_id );

	$results = array();
	$results['rootTaxName'] = $projRootTaxName;
	$results['parent'] = $parent_term;
	$results['parentID'] = $parent_term_id;

		// make sure the new term doesn't already exist
	$testTerm = term_exists($dhp_term_name, $projRootTaxName, $parent_term_id);
	if ($testTerm !== 0 && $testTerm !== null) {
		$results['termID'] = 0;
		$results['debug'] = $testTerm['term_id'];
	} else {
			// create new term
		$newTerm = wp_insert_term($dhp_term_name, $projRootTaxName, $args);
		$results['newTerm'] = $newTerm;
		if (is_wp_error($newTerm)) {
			// trigger_error("WP will not create new term ".$dhp_term_name." in taxonomy".$parent_term_name);
			$results['termID'] = 0;
		} else {
			$results['termID'] = $newTerm['term_id'];

				// Clear term taxonomy
			delete_option("{$projRootTaxName}_children");
		}
	}

	die(json_encode($results));
} // dhp_create_term_in_tax()


add_action( 'wp_ajax_dhpDeleteHeadTerm', 'dhp_delete_head_term' );

// PURPOSE:	Delete taxonomic terms in Project and all terms derived from it (as parent)
// INPUT:	$_POST['project'] = ID of Project
//			$_POST['term_name'] = name of taxonomy head term to delete

function dhp_delete_head_term()
{
	$projectID = $_POST['project'];
	$dhp_term_name = $_POST['term_name'];
	$dhp_project = get_post($projectID);
	$dhp_project_slug = $dhp_project->post_name;

		// By default (mote has no corresponding Legend)
	$dhp_delete_children = array();

	$projRootTaxName = DHPressProject::ProjectIDToRootTaxName($projectID);
		// Get ID of term to delete -- don't do anything more if it doesn't exist
	$dhp_delete_parent_term = get_term_by('name', $dhp_term_name, $projRootTaxName);
	if ($dhp_delete_parent_term) {
		$dhp_delete_parent_id = $dhp_delete_parent_term->term_id;
			// Must gather all children and delete them too (first!)
		$dhp_delete_children = get_term_children($dhp_delete_parent_id, $projRootTaxName);
		if (!is_wp_error($dhp_delete_children)) {
			foreach ($dhp_delete_children as $delete_term) {
				wp_delete_term($delete_term, $projRootTaxName);
			}
		}
		wp_delete_term($dhp_delete_parent_id, $projRootTaxName);
	}

	die(json_encode($dhp_delete_children));
} // dhp_delete_head_term()


// Enable for both editing and viewing

add_action('wp_ajax_dhpGetPostContent', 'dhp_get_post_content');
add_action('wp_ajax_nopriv_dhpGetPostContent', 'dhp_get_post_content');

// PURPOSE: Handle Ajax call to fetch the post-view data for a specific marker
// INPUT:	$_POST['marker_id'] = ID of Marker post
//			$_POST['proj_id'] = ID of Project post
// RETURNS:	JSON object of marker data

function dhp_get_post_content()
{
	$marker_id = $_POST['marker_id'];
	$proj_id = $_POST['proj_id'];

	$mQuery = new DHPressMarkerQuery($proj_id);

		// modify select contents so that all post motes are included
	foreach ($mQuery->projSettings->views->post->content as $theMote) {
		array_push($mQuery->selectContent, $theMote);
	}
	$pTitle = $mQuery->projSettings->views->post->title;
	if ($pTitle && $pTitle !== '' && $pTitle !== 'disable') {
		array_push($mQuery->selectContent, $pTitle);
	}
	$mQuery->selectContent = array_unique($mQuery->selectContent);

		// Construct a pseudo marker

		// Feature will hold properties and some other values for each marker
	$thisFeature = array();

		// Most data goes into properties field
	$thisFeaturesProperties = $mQuery->getMarkerProperties($marker_id);

		// Store all of the properties
	$thisFeature['properties'] = $thisFeaturesProperties;

	die(json_encode($thisFeature));
} // dhp_get_post_content()


// Enable for both editing and viewing

add_action('wp_ajax_dhpGetTaxContent', 'dhp_get_tax_content');
add_action('wp_ajax_nopriv_dhpGetTaxContent', 'dhp_get_tax_content');

// PURPOSE: Handle Ajax call to fetch the tax-view data for a specific marker
// INPUT:	$_POST['marker_id'] = ID of Marker post
//			$_POST['proj_id'] = ID of Project post
// RETURNS:	JSON object of marker data

function dhp_get_tax_content()
{
	$marker_id = $_POST['marker_id'];
	$proj_id = $_POST['proj_id'];

	$mQuery = new DHPressMarkerQuery($proj_id);

		// Start out with Marker title Mote
	if ($mQuery->projSettings->general->mTitle != '' && $mQuery->projSettings->general->mTitle != 'disable'
		&& $mQuery->projSettings->general->mTitle != 'the_title')
	{
		array_push($mQuery->selectContent, $mQuery->projSettings->general->mTitle);
	}
		// modify select contents so that all post motes are included
	foreach ($mQuery->projSettings->views->transcript->content as $theMote) {
		array_push($mQuery->selectContent, $theMote);
	}
	$mQuery->selectContent = array_unique($mQuery->selectContent);

		// Construct a pseudo marker

		// Feature will hold properties and some other values for each marker
	$thisFeature = array();

		// Most data goes into properties field
	$thisFeaturesProperties = $mQuery->getMarkerProperties($marker_id);

		// Store all of the properties
	$thisFeature['properties'] = $thisFeaturesProperties;

	die(json_encode($thisFeature));
} // dhp_get_tax_content()


// Enable for both editing and viewing

add_action('wp_ajax_dhpGetTranscriptClip', 'dhp_get_transcript_json');
add_action('wp_ajax_nopriv_dhpGetTranscriptClip', 'dhp_get_transcript_json');

// PURPOSE:	Retrieve section of text file for transcript
// INPUT:	$tran = full text of transcript
//			$clip = String containing from-end time of segment, or -1 if entire transcript needed
// RETURNS:	Excerpt of $tran within the time frame specified by $clip (not encoded as UTF8)
//			This text must begin with the beginning timestamp and end with the final timestamp

function dhp_get_transcript_clip($transcript, $clip)
{
		// Is entire transcript to be used?
	if ($clip == -1 || $clip == '-1') {
		return $transcript;
	}

	$codedTranscript  = utf8_encode($transcript);
	$clipArray        = explode("-", $clip);
	$clipStart        = mb_strpos($codedTranscript, $clipArray[0]);
	$clipEnd          = mb_strpos($codedTranscript, $clipArray[1]);
		// length must include start and end timestamps
	$clipLength       = ($clipEnd + strlen($clipArray[1]) + 1) - ($clipStart - 1) + 1;
		// If no errors in finding timestamps
	if (($clipStart !== false) && ($clipEnd !== false)) {
		$codedClip  = mb_substr($codedTranscript, $clipStart-1, $clipLength, 'UTF-8');
		$returnClip = utf8_decode($codedClip);
		// Otherwise, return array with clipping info
	} else {
		$returnClip = array('clipStart'=> $clipStart,'clipEnd'=> $clipEnd, 'clipArrayend' => $clipArray[1]);
	}
	return $returnClip;
} // dhp_get_transcript_clip()


// PURPOSE:	Load the contents of a transcript file
// INPUT:	$fileUrl = the URL to the file
// RETURNS:	The data in file, if successful

function dhp_load_transcript_from_file($fileUrl)
{
	$content = @file_get_contents($fileUrl);
	if ($content === false) {
		trigger_error("Cannot load transcript file ".$fileUrl);
	}
	return $content;
} // dhp_load_transcript_from_file()


// PURPOSE: AJAX function to retrieve section of transcript when viewing a Marker
// INPUT:	$_POST['project'] = ID of Project post
//			$_POST['transcript'] = URL to file containing contents of transcript
//			$_POST['timecode'] = timestamp specifying excerpt of transcript to return, or -1 = full transcript
// RETURNS:	JSON-encoded section of transcription

function dhp_get_transcript_json()
{
	$dhp_project = $_POST['project'];
	$dhp_transcript_field = $_POST['transcript'];
	$dhp_clip = $_POST['timecode'];

	$dhp_transcript = dhp_load_transcript_from_file($dhp_transcript_field);
	$dhp_transcript_clip = dhp_get_transcript_clip($dhp_transcript, $dhp_clip);

	die(json_encode($dhp_transcript_clip));
} // dhp_get_transcript_json()


// Enable for both editing and viewing

add_action( 'wp_ajax_dhpGetTaxTranscript', 'dhp_get_tax_transcript' );
add_action( 'wp_ajax_nopriv_dhpGetTaxTranscript', 'dhp_get_tax_transcript');

// PURPOSE: AJAX function to retrieve entire transcript when viewing a taxonomy archive page
// INPUT:	$_POST['project'] = ID of Project
//			$_POST['transcript'] = (end of URL) to file containing contents of transcript; slug based on mote value
//			$_POST['tax_term'] = the root taxonomic term that marker must match (based on Project ID)
// RETURNS:	null if not found, or if not associated with transcript; otherwise, JSON-encoded complete transcript with fields:
//				audio, video = data from custom fields
//				settings = entry-point settings for transcript
//				transcript, transcript2 = transcript data itself for each of 2 possible transcripts

function dhp_get_tax_transcript()
{
	$projectID     = $_POST['project'];
	$dhp_tax_term  = $_POST['tax_term'];
	$transFile     = $_POST['transcript'];

		// Search for Marker (which will have transaction data) matching taxonomic data
	$args = array(
		'posts_per_page' => 1,
		'post_type' => 'dhp-markers',
		'tax_query' => array(
			array(
				'taxonomy' => $dhp_tax_term,
				'field' => 'slug',
				'terms' => $transFile
			)
		)
	);
		// Get the result and its metadata (fail if not found)
	$first_marker = get_posts($args);
	if (is_null($first_marker) || (count($first_marker) == 0)) {
		die('');
	}
	$marker_meta = get_post_meta($first_marker[0]->ID);

	$projObj      = new DHPressProject($projectID);
	$rootTaxName  = $projObj->getRootTaxName();
	$proj_settings = $projObj->getAllSettings();

		// Store results to return here
	$dhp_object = array();

		// set defaults
	$dhp_object['audio'] = $dhp_object['video'] = $dhp_object['transcript'] = $dhp_object['transcript2'] = null;

		// What custom fields holds appropriate data? Fetch from Marker
	$dhp_audio_mote = null;
	if ($proj_settings->views->transcript->audio && $proj_settings->views->transcript->audio != '' &&
		$proj_settings->views->transcript->audio != 'disable')
	{
		$dhp_audio_mote = $projObj->getMoteByName($proj_settings->views->transcript->audio);
		$dhp_object['audio'] = $marker_meta[$dhp_audio_mote->cf][0];
	}
	$dhp_video_mote = null;
	if ($proj_settings->views->transcript->video && $proj_settings->views->transcript->video != '' &&
		$proj_settings->views->transcript->video != 'disable')
	{
		$dhp_video_mote = $projObj->getMoteByName($proj_settings->views->transcript->video);
		$dhp_object['video'] = $marker_meta[$dhp_video_mote->cf][0];
	}

	if ($proj_settings->views->transcript->transcript && $proj_settings->views->transcript->transcript != '' &&
		$proj_settings->views->transcript->transcript != 'disable')
	{
		$dhp_transcript_mote = $projObj->getMoteByName($proj_settings->views->transcript->transcript);
		$dhp_transcript_cfield = $dhp_transcript_mote->cf;
		$dhp_transcript = $marker_meta[$dhp_transcript_cfield][0];
		if ($dhp_transcript != 'none') {
			$dhp_transcript = dhp_load_transcript_from_file($dhp_transcript);
			$dhp_object['transcript'] = $dhp_transcript;
		}
	}

		// if project has 2nd transcripts
	if ($proj_settings->views->transcript->transcript2 && $proj_settings->views->transcript->transcript2 != '' &&
		$proj_settings->views->transcript->transcript2 != 'disable')
	{
		$dhp_transcript_mote = $projObj->getMoteByName($proj_settings->views->transcript->transcript2);
		$dhp_transcript_cfield = $dhp_transcript_mote->cf;
		$dhp_transcript = $marker_meta[$dhp_transcript_cfield][0];
		if ($dhp_transcript != 'none') {
			$dhp_transcript = dhp_load_transcript_from_file($dhp_transcript);
			$dhp_object['transcript2'] = $dhp_transcript;
		}
	}

	die(json_encode($dhp_object));
} // dhp_get_tax_transcript()


add_action( 'wp_ajax_dhpAddCustomField', 'dhp_add_custom_field' );

// PURPOSE:	Handle Ajax call to create new custom field with particular value for all Markers in Project
// INPUT:	$_POST['project'] = ID of Project
//			$_POST['field_name'] = name of new custom field to add
//			$_POST['field_value'] = default value to set in all markers belonging to Project

function dhp_add_custom_field()
{
	$dhp_project = $_POST['project'];
	$dhp_custom_field_name = $_POST['field_name'];
	$dhp_custom_field_value = $_POST['field_value'];

	$args = array( 'post_type' => 'dhp-markers', 'meta_key' => 'project_id','meta_value'=>$dhp_project, 'posts_per_page' => -1 );
	$loop = new WP_Query( $args );
	if($loop->have_posts()){
		foreach($loop->posts as $markerPost){
			$marker_id = $markerPost->ID;
			add_post_meta($marker_id, $dhp_custom_field_name, $dhp_custom_field_value, true);
		}
	}

	die();
} // dhp_add_custom_field()


add_action( 'wp_ajax_dhpCreateCustomFieldFilter', 'dhp_create_custom_field_filter' );

// PURPOSE: Handle Ajax call to add the value of custom fields that match "filter condition"
// INPUT:	$_POST['project'] = ID of Project
//			$_POST['field_name'] = name of custom field
//			$_POST['field_value'] = value to set of custom field
//			$_POST['filter_key'] = name of field on which to match
//			$_POST['filter_value'] = value of field to match
// TO DO:	Rename function? dhpSetFieldByCustomFieldFilter?

function dhp_create_custom_field_filter()
{
	$dhp_project 			= $_POST['project'];
	$dhp_custom_field_name 	= $_POST['field_name'];
	$dhp_custom_field_value	= $_POST['field_value'];
	$dhp_custom_filter_key 	= $_POST['filter_key'];
	$dhp_custom_filter_value = $_POST['filter_value'];

	$args = array( 
		'post_type' => 'dhp-markers', 
		'posts_per_page' => -1,
		'meta_query' => array(
			'relation' => 'AND',
			array(
				'key' => 'project_id',
				'value' => $dhp_project
			),
			array(
				'key' => $dhp_custom_filter_key,
				'value' => $dhp_custom_filter_value
			)
		)
	);

	$loop = new WP_Query( $args );
	if($loop->have_posts()){
		foreach($loop->posts as $markerPost){
			$marker_id = $markerPost->ID;
			add_post_meta($marker_id, $dhp_custom_field_name, $dhp_custom_field_value, true);
		}
	}
	
	die();
} // dhp_create_custom_field_filter()


add_action('wp_ajax_dhpUpdateCustomFieldFilter', 'dhp_update_custom_field_filter');

// PURPOSE: To modify the value of a field (based on string replace) in all of a Project's Markers if
//			it satisfies query condition and currently matches a certain value (like Find & Replace)
// INPUT:	$_POST['project'] = ID of Project
//			$_POST['field_name'] = name of custom field we wish to change
//			$_POST['current_value'] = custom field must have this field to be changed
//			$_POST['new_value'] = new value to set
//			$_POST['filter_key'] = custom field upon which search/filter is based
//			$_POST['filter_value'] = value that must be in custom field
// RETURNS:	Number of markers whose values were changed

function dhp_update_custom_field_filter()
{
	$dhp_project 				= $_POST['project'];
	$dhp_custom_field_name		= $_POST['field_name'];
	$dhp_custom_current_value	= $_POST['current_value'];
	$dhp_custom_new_value		= $_POST['new_value'];
	$dhp_custom_filter_key		= $_POST['filter_key'];
	$dhp_custom_filter_value 	= $_POST['filter_value'];

	$args = array( 
		'post_type' => 'dhp-markers', 
		'posts_per_page' => -1,
		'meta_query' => array(
			'relation' => 'AND',
			array(
				'key' => 'project_id',
				'value' => $dhp_project
			),
			array(
				'key' => $dhp_custom_filter_key,
				'value' => $dhp_custom_filter_value
			)
		)
	);
	$dhp_count=0;
	$loop = new WP_Query( $args );
	if($loop->have_posts()){
		foreach($loop->posts as $markerPost){
			$dhp_count++;
			$marker_id = $markerPost->ID;
			if($dhp_custom_field_name=='the_content') {

				$tempPostContent = get_the_content();
				$new_value = str_replace($dhp_custom_current_value, $dhp_custom_new_value, $tempPostContent);
				
				$new_post = array();
				$new_post['ID'] = $marker_id;
				$new_post['post_content'] = $new_value;
				wp_update_post( $new_post );
			}
			else {
				$temp_value = get_post_meta( $marker_id, $dhp_custom_field_name, true );
				//replaces string within the value not the whole value
				$new_value = str_replace($dhp_custom_current_value, $dhp_custom_new_value, $temp_value);
				update_post_meta($marker_id, $dhp_custom_field_name, $new_value);
			}
		}
	}
	die(json_encode($dhp_count));
} // dhp_update_custom_field_filter()


add_action( 'wp_ajax_dhpReplaceCustomFieldFilter', 'dhp_replace_custom_field_filter' );

// PURPOSE: To replace the value of a field in all of a Project's Markers if
//			it satisfies query condition and currently matches a certain value
// INPUT:	$_POST['project'] = ID of Project
//			$_POST['field_name'] = name of custom field we wish to change
//			$_POST['new_value'] = new value which will entirely replace previous value
//			$_POST['filter_key'] = custom field upon which search/filter is based
//			$_POST['filter_value'] = value that must be in custom field
// RETURNS:	Number of markers whose values were changed

function dhp_replace_custom_field_filter()
{
	$dhp_project 				= $_POST['project'];
	$dhp_custom_field_name		= $_POST['field_name'];
	$dhp_custom_new_value		= $_POST['new_value'];
	$dhp_custom_filter_key		= $_POST['filter_key'];
	$dhp_custom_filter_value 	= $_POST['filter_value'];

	$args = array( 
		'post_type' => 'dhp-markers', 
		'posts_per_page' => -1,
		'meta_query' => array(
			'relation' => 'AND',
			array(
				'key' => 'project_id',
				'value' => $dhp_project
			),
			array(
				'key' => $dhp_custom_filter_key,
				'value' => $dhp_custom_filter_value
			)
		)
	);
	$dhp_count=0;
	$loop = new WP_Query( $args );
	while ( $loop->have_posts() ) : $loop->the_post();
		$dhp_count++;
		$marker_id = get_the_ID();
		if($dhp_custom_field_name=='the_content') {
			$tempPostContent = get_the_content();
			$new_value = $dhp_custom_new_value;

			$new_post = array();
			$new_post['ID'] = $marker_id;
			$new_post['post_content'] = $new_value;
			wp_update_post( $new_post );
		}
		else {
			$temp_value = get_post_meta( $marker_id, $dhp_custom_field_name, true );
			//replaces string within the value not the whole value
			$new_value = $dhp_custom_new_value;
			update_post_meta($marker_id, $dhp_custom_field_name, $new_value);
		}
	endwhile;

	die(json_encode($dhp_count));
} // dhp_replace_custom_field_filter()


add_action( 'wp_ajax_dhpGetFieldValues', 'dhp_get_field_values' );

// PURPOSE: Handle Ajax call to get values for custom field
// INPUT:	$_POST['project'] = ID of Project
//			$_POST['field_name'] = name of custom field
// RETURNS:	JSON Object of array of all unique values for the field in the project

function dhp_get_field_values()
{
	$projectID 	= $_POST['project'];
	$fieldName 	= $_POST['field_name'];
	$projObj 	= new DHPressProject($projectID);
	$tempValues	= $projObj->getCustomFieldUniqueValues($fieldName);

	die(json_encode($tempValues));
} // dhp_get_field_values()


add_action( 'wp_ajax_dhpFindReplaceCustomField', 'dhp_find_replace_custom_field' );

// PURPOSE: Handle Ajax function to do string replace on matching values in a custom field in Project
// INPUT:	$_POST['project'] = ID of Project
//			$_POST['field_name'] = name of custom field
//			$_POST['find_value'] = field must match this value to be replaced
//			$_POST['replace_value'] = value to use for string replace in field
// RETURNS:	Number of markers whose values were changed

function dhp_find_replace_custom_field()
{
	$projectID = $_POST['project'];
	$dhp_custom_field_name = $_POST['field_name'];
	$dhp_custom_find_value = $_POST['find_value'];
	$dhp_custom_replace_value = $_POST['replace_value'];
	$projObj = new DHPressProject($projectID);

	$loop = $projObj->setAllMarkerLoop();
	$dhp_count=0;
	if($loop->have_posts()){
		foreach($loop->posts as $markerPost){
			$dhp_count++;
			$marker_id = $markerPost->ID;
			if($dhp_custom_field_name=='the_content') {
				$tempPostContent = get_the_content();
				$new_value = str_replace($dhp_custom_find_value, $dhp_custom_replace_value, $tempPostContent);

				$new_post = array();
				$new_post['ID'] = $marker_id;
				$new_post['post_content'] = $new_value;
				wp_update_post( $new_post );
			}
			else {
				$temp_value = get_post_meta( $marker_id, $dhp_custom_field_name, true );
				//replaces string within the value not the whole value
				$new_value = str_replace($dhp_custom_find_value, $dhp_custom_replace_value, $temp_value);
				update_post_meta($marker_id, $dhp_custom_field_name, $new_value);
			}
		}
	}
	
	die(json_encode($dhp_count));
} // dhp_find_replace_custom_field()


add_action( 'wp_ajax_dhpDeleteCustomField', 'dhp_delete_custom_field' );

// PURPOSE:	Handle Ajax query to remove specific custom field from all markers of a Project
// INPUT:	$_POST['project'] = ID of Project
//			$_POST['field_name'] = name of custom field

function dhp_delete_custom_field()
{
	$projectID = $_POST['project'];
	$dhp_custom_field_name = $_POST['field_name'];
	$projObj = new DHPressProject($projectID);
	
	$loop = $projObj->setAllMarkerLoop();
	if($loop->have_posts()){
		foreach($loop->posts as $markerPost){
			$marker_id = $markerPost->ID;
			delete_post_meta($marker_id, $dhp_custom_field_name);
		}
	}
	
	die();
} // dhp_delete_custom_field()


add_action( 'wp_ajax_dhpGetCustomFields', 'dhp_get_custom_fields' );

// PURPOSE:	Handle Ajax call to retrieve all custom fields defined for a Project
// INPUT:	$_POST['project'] = ID of Project
// RETURNS: JSON Object of array of all custom fields

function dhp_get_custom_fields()
{
	$projectID = $_POST['project'];
	$projObj   = new DHPressProject($projectID);
	
	$dhp_custom_fields = $projObj->getAllCustomFieldNames();
	die(json_encode($dhp_custom_fields));
} // dhp_get_custom_fields()


// PURPOSE: Find all PNG images attached to the given post
// INPUT:   pID is the ID of the post
// RETURNS: Array of [ ]

function dhp_get_attached_PNGs($pID)
{
	$pngs = array();

	$images = get_attached_media('image/png', $pID);
	foreach($images as $image) {
		$onePNG = array();
		$onePNG['id'] = $image->ID;
		$onePNG['title'] = $image->post_title;
		$imageData = wp_get_attachment_image_src($image->ID);
		$onePNG['url'] = $imageData[0];
		$onePNG['w'] = $imageData[1];
		$onePNG['h'] = $imageData[2];
		array_push($pngs, $onePNG);
	}
	return $pngs;
} // dhp_get_attached_PNGs()


// PURPOSE:	Verify that all timestamps can be found in transcription file
// INPUT:	$transcMoteName = name of mote for a transcription setting
// RETURNS:	Error string or ''

function dhp_verify_transcription($projObj, $projSettings, $transcMoteName)
{
		// don't check anything if the setting is disabled
	if ($transcMoteName == '' || $transcMoteName == 'disable') {
		return '';
	}

	$result = '';
	$tcMote = $projSettings->views->transcript->timecode;

		// Problem if timecode not defined
	if ($tcMote == null || $tcMote == '' || $tcMote == 'disable') {
		$result = __('<p>You have not specified the Timestamp setting for the Transcription (despite setting Transcript motes).</p>', 'dhpress');
	} else {
		$numErrors = 0;

		$tcMoteDef = $projObj->getMoteByName($tcMote);
		$transMoteDef = $projObj->getMoteByName($transcMoteName);

		$loop = $projObj->setAllMarkerLoop();
		if($loop->have_posts()){
			foreach($loop->posts as $markerPost){
				$error = false;
				$marker_id = $markerPost->ID;

					// Get this marker's values for 
				$timecode = get_post_meta($marker_id, $tcMoteDef->cf, true);
				$transFile= get_post_meta($marker_id, $transMoteDef->cf, true);

					// Skip check if either one missing
				if ($timecode != null && $timecode != '' && $transFile != null && $transFile != '' && $transFile !== 'none') {
						// Don't check invalid timestamps -- they should have already been reported
					if (preg_match("/\d\d\:\d\d\:\d\d\.\d\d?-\d\d\:\d\d\:\d\d\.\d\d?/", $moteValue) === 1) {
						$content = @file_get_contents($transFile);
						if ($content == false) {
							$result .= sprintf(__('<p>Problem reading file %s </p>', 'dhpress'), $transFile);
							$error = true;
						} else {
							$content  = utf8_encode($content);
							$stamps	  = explode("-", $timecode);
							$clipStart= mb_strpos($content, $stamps[0]);
							if ($clipStart == false) {
								$result .= sprintf(__('<p> Cannot find timestamp %1$s in file %2$s</p>', 'dhpress'), $stamps[0], $transFile);
								$error = true;
							}
							$clipEnd  = mb_strpos($content, $stamps[1]);
							if ($clipEnd == false) {
								$result .= sprintf(__('<p> Cannot find timestamp %1$s in file %2$s</p>', 'dhpress'), $stamps[1], $transFile);
								$error = true;
							}
						} // file contents
					} // if valid timestamp
				} // if timecode and file values
				if ($error && ++$numErrors >= 20) {
					break;
				}
			}
		}
	}

	return $result;
} // dhp_verify_transcription()


// PURPOSE: Ensure metadata attached to category/Legend is consistent and of correct format
// INPUT:   $projObj = project object
//			$theLegend = name of mote to check
//			$checkValues = true if Legend values are to be validated
//			$makiOK = true if can use maki icons
//			$pngOK = true if can use PNG image icons

function dhp_verify_legend($projObj, $theLegend, $checkValues, $makiOK, $pngOK)
{
	if ($theLegend === null || $theLegend === '' || $theLegend === 'disable') {
		return __('<p>Cannot verify unspecified legend.</p>', 'dhpress');
	}

	$moteDef = $projObj->getMoteByName($theLegend);

	$rootTaxName  = $projObj->getRootTaxName();

		// Has Legend not been created yet?
	if (!term_exists($moteDef->name, $rootTaxName)) {
		return sprintf(__('<p>Legend "%s" has not yet been created but must be for project to work.</p>', 'dhpress'), $theLegend);
	}

	$results    = '';

	if ($checkValues) {
			// Find all of the terms derived from mote (parent/head term) in the Project's taxonomy
		$parent_terms_to_exclude = get_terms($rootTaxName, 'parent=0&orderby=term_group&hide_empty=0');

			// Create comma-separated string listing terms derived from other motes
		$exclude_string='';
		$initial = true;
		foreach ($parent_terms_to_exclude as $term) {
			if($term->term_id != $parent_id) {
				if(!$initial) {
					$exclude_string .=',';
				}
				$exclude_string .= $term->term_id;
				$initial = false;
			}
		}

			// Get all taxonomic terms for project, excluding all other motes
		$terms_loaded = get_terms($rootTaxName, 'exclude_tree='.$exclude_string.'&orderby=term_group&hide_empty=0');
		$t_count = count($terms_loaded);

		$usedColor  = false;
		$usedMaki   = false;
		$usedPNG 	= false;
		$mixFlagged = false;

			// Check visualization data (encoded in the description metadata)
			// 	Value must specified for all category/legend terms
			//	Ensure value is a parseable value
			//	Ensure there is not a mixture of icon and color
			//	Ensure only color values are used if required
		if ($t_count > 0) {
			foreach ($terms_loaded as $term) {
				if ($term->description == null || $term->description == '') {
					$results .= sprintf(__('<p>The value %1$s for legend %2$s has no visual setting.</p>', 'dhpress'), $term->name, $theLegend);
				} else {
					$isColor = preg_match("/^#[:xdigit:]{6}$/", $term->description);
					$isMaki = preg_match("/^.maki\-\S/", $term->description);
					$isPNG = preg_match("/^@\S/", $term->description);
					if ($isColor) {
						$usedColor = true;
						if (!$mixFlagged && ($usedMaki || $usedPNG)) {
							$results .= sprintf(__('<p>Illegal mixture of color and icon settings in legend %s.</p>', 'dhpress'), $theLegend);
							$mixFlagged = true;
						}
					} elseif ($isMaki) {
						$usedMaki = true;
						if (!$makiOK) {
							$results .= sprintf(__('<p>The assigned Entry Point cannot use maki-icon setting %1$s for legend %2$s value %3$s.</p>', 'dhpress'), $term->description, $theLegend, $term->name);
						} elseif ($usedColor && !$mixFlagged) {
							$results .= sprintf(__('<p>Illegal mixture of color and icon settings in legend %s.</p>', 'dhpress'), $theLegend);
							$mixFlagged = true;
						}
					} elseif ($isPNG) {
						$usedPNG = true;
						if (!$pngOK) {
							$results .= sprintf(__('<p>The assigned Entry Point cannot use PNG image %1$s for legend %2$s value %3$s.</p>', 'dhpress'), $term->description, $theLegend, $term->name);
						} elseif ($usedColor && !$mixFlagged) {
							$results .= sprintf(__('<p>Illegal mixture of color and icon settings in legend %s.</p>', 'dhpress'), $theLegend);
							$mixFlagged = true;
						}
					} else {
						$results .= sprintf(__('<p>Unknown setting %1$s for legend %2$s value %3$s.</p>', 'dhpress'), $term->description, $theLegend, $term->name);
					}
				}
			}
		}
	} // if checkValues)

	return $results;
} // dhp_verify_legend()


add_action( 'wp_ajax_dhpPerformTests', 'dhp_perform_tests' );

// PURPOSE:	Handle Ajax call to retrieve all custom fields defined for a Project
// INPUT:	$_POST['project'] = ID of Project
// RETURNS: JSON Object of array of all custom fields

function dhp_perform_tests()
{
	$projectID = $_POST['project'];
	$projObj   = new DHPressProject($projectID);
	$results   = '';

	$projSettings = $projObj->getAllSettings();

		// There will be no project settings for New project
	if (empty($projSettings)) {
		$results = __('This is a new project and cannot be verified until it is saved and Markers imported', 'dhpress');

	} else {
			// Ensure any legends used by visualizations have been configured
			// Ensure that configured legends do not mix color and icon types
		foreach ($projSettings->eps as $ep) {
			switch ($ep->type) {
			case 'map':
					// Map Legends can be color, maki-icons or PNG
				foreach ($ep->settings->legends as $theLegend) {
					$results .= dhp_verify_legend($projObj, $theLegend, true, true, true);
				}
				break;
			case 'cards':
					// Card Legends must be color only
				$results .= dhp_verify_legend($projObj, $ep->settings->color, true, false, false);
					// all Short Text Filter Motes must have been created as Legend but values don't matter
				foreach ($ep->settings->filterMotes as $filterMote) {
					if ($filterMote->type === 'Short Text') {
						$results .= dhp_verify_legend($projObj, $filterMote, false, false, false);
					}
				}
				break;
			case 'pinboard':
					// Pinboard Legends currently support color and PNG
				foreach ($ep->settings->legends as $theLegend) {
					$results .= dhp_verify_legend($projObj, $theLegend, true, false, true);
				}
				break;
			case 'tree':
					// Tree legends currently only support color
				$results .= dhp_verify_legend($projObj, $ep->settings->color, true, false, false);
				break;
			case 'time':
					// Time legends currently only support color
				$results .= dhp_verify_legend($projObj, $ep->settings->color, true, false, false);
				break;
			case 'flow':
					// Facet Flows legends currently only require Legend existence
				foreach ($ep->settings->motes as $fMote) {
					$results .= dhp_verify_legend($projObj, $fMote, false, false, false);
				}
				break;
			case 'browser':
					// Browser legends currently only require Legend existence for Short Motes
				foreach ($ep->settings->motes as $fMote) {
					$moteDef = $projObj->getMoteByName($fMote);
					if ($moteDef->type === 'Short Text') {
						$results .= dhp_verify_legend($projObj, $fMote, false, false, false);
					}
				}
				break;
			} // switch()
		}

			// Go through markers and ensure all values are valid:
			//  Go through mote definitions and check values
			//  Stop after >= 20 errors
		$loop = $projObj->setAllMarkerLoop();
		$numErrors = 0;
		$error = false;
		$transcErrors = false;
		if($loop->have_posts()){
			foreach($loop->posts as $markerPost){
				$marker_id = $markerPost->ID;

				foreach ($projSettings->motes as $mote) {
					$moteValue = get_post_meta($marker_id, $mote->cf, true);
						// ignore empty or null values
					if (!is_null($moteValue) && $moteValue != '') {
						$error = false;
						switch ($mote->type) {
						case 'Lat/Lon Coordinates':
						case 'X-Y Coordinates':
							if (preg_match("/(-?\d+(\.?\d?)?),(\s?-?\d+(\.?\d?)?)/", $moteValue) === 0) {
								$results .= sprintf(__('<p>Invalid Coordinate %s', 'dhpress'), $moteValue);
								$error = true;
							}
							break;
						case 'SoundCloud':
								// Just look at the beginning of the URL
							if (preg_match("!https://soundcloud\.com/\w!i", $moteValue) === 0) {
								$results .= __('<p>Invalid SoundCloud URL', 'dhpress');
								$error = true;
							}
							break;
						case 'YouTube':
								// Cannot verify because it is just a raw code
							break;
						case 'Link To':
						case 'Image':
								// Just look at beginning and end of URL
							if (preg_match("!^(https?|ftp)://[^\s]*!i", $moteValue) === 0) {
								$results .= __('<p>Invalid URL', 'dhpress');
								$error = true;
							}
							break;
						case 'Transcript':
								// Just look at beginning and end of URL
							if ($moteValue !== 'none' && (preg_match("!(https?|ftp)://!i", $moteValue) === 0 || preg_match("!\.txt$!i", $moteValue) === 0)) {
								$results .= __('<p>Invalid textfile URL', 'dhpress');
								$error = true;
								$transcErrors = true;
							}
							break;
						case 'Timestamp':
							if (preg_match("/\d\d\:\d\d\:\d\d\.\d\d?-\d\d\:\d\d\:\d\d\.\d\d?/", $moteValue) === 0) {
								$results .= sprintf(__('<p>Invalid Timestamp %s', 'dhpress'), $moteValue);
								$error = true;
								$transcErrors = true;
							}
							break;
						case 'Pointer':
								// Only way to check would be to explode string and check existence of each
								// marker, but this would likely break the WP Query loop -- so ignore for now
							break;
						case 'Date':
								// Single Date or Range, inc. fuzzy
							if (preg_match("/^(open|~?-?\d+(-(\d)+)?(-(\d)+)?)(\/(open|~?-?\d+(-(\d)+)?(-(\d)+)?))?$/", $moteValue) === 0) {
								$results .= __('<p>Invalid Date range', 'dhpress');
								$error = true;
							}
							break;
						} // switch
							// Add rest of error information
						if ($error) {
							$results .=  ' ' . sprintf(__('given for mote %1$s (custom field %2$s) in marker %3$s</p>', 'dhpress'), $mote->name, $mote->cf, get_the_title());
							$numErrors++;
						}
					} // if (!is_null)
				} // foreach
					// don't continue if excessive errors found
				if ($numErrors >= 20) {
					$results .= __('<p>Stopped checking errors in Marker data because more than 20 errors have been found. Correct these and try again.</p>', 'dhpress');
					break;
				}
			}
		}

			// If transcript (fragmentation) source is set, ensure the category has been created
		$source = $projSettings->views->transcript->source;
		if ($source && $source !== '' && $source !== 'disable') {
			$transSrcCheck = dhp_verify_legend($projObj, $source, false, false, false);
			if ($transSrcCheck != '') {
				$results .= sprintf(__('<p>You have specified the Source mote %s for Transcription fragmentation but you have not built it yet as a category.</p>', 'dhpress'), $source);
			}
		}

			// Check transcript data themselves -- this check is inefficient and redundant by nature
			// No previous transcription errors must have been registered!
		if (!$transcErrors) {
			$results .= dhp_verify_transcription($projObj, $projSettings, $projSettings->views->transcript->transcript);
		}

		if (!$transcErrors) {
			$results .= dhp_verify_transcription($projObj, $projSettings, $projSettings->views->transcript->transcript2);
		}

			// Are the results all clear?
		if ($results === '') {
			$results = __('<p>All data on server has been examined and approved.</p>', 'dhpress');
		} else {
			$results .= __('<p>Data tests now complete.</p>', 'dhpress');
		}
	} // if projSettings

	die($results);
} // dhp_perform_tests()


add_action( 'wp_restore_post_revision', 'dhp_project_restore_revision', 10, 2 );

// PURPOSE: Handles returning to an earlier revision of this post
// INPUT:	$post_id = ID of original post
//			$revision_id = ID of new revised post

function dhp_project_restore_revision($post_id, $revision_id)
{
	$dhp_project_settings_fields = array( 'project_settings' );

	$post     = get_post($post_id);
	$revision = get_post($revision_id);

	foreach ($dhp_project_settings_fields as $fieldID) {
		$old = get_metadata( 'post', $revision->ID, $fieldID, true);
		if ( false !== $old) {
			update_post_meta($post_id, $fieldID, $old);
		} else {
			delete_post_meta($post_id, $fieldID );
		}
	} // end foreach
} // dhp_project_restore_revision()


add_action( 'admin_enqueue_scripts', 'add_dhp_project_admin_scripts', 10, 1 );

// Custom scripts to be run on Project new/edit pages only
// PURPOSE: Prepare CSS and JS files for all page types in WP
// INPUT:	$hook = name of template file being loaded
// ASSUMES:	Other WP global variables for current page are set

function add_dhp_project_admin_scripts( $hook )
{
	global $post;

	$blog_id = get_current_blog_id();
	$dev_url = get_admin_url( $blog_id ,'admin-ajax.php');
	$plugin_folder = plugins_url('',dirname(__FILE__));
	$postID  = get_the_ID();

		// Editing a specific project in admin panel
	if ( $hook == 'post-new.php' || $hook == 'post.php' ) {
		if ( $post->post_type == 'dhp-project' ) {
				// Library styles
			wp_enqueue_style('jquery-ui-style', plugins_url('/lib/jquery-ui/jquery-ui.min.css', dirname(__FILE__)) );

			// wp_enqueue_style('wp-jquery-ui-dialog' );
			wp_enqueue_style('maki-sprite-style', plugins_url('/lib/maki/maki-sprite.css',  dirname(__FILE__)) );
				// Lastly, our plug-in specific styles
			wp_enqueue_style('dhp-admin-style', plugins_url('/css/dhp-admin.css',  dirname(__FILE__)),
					array('jquery-ui-style', 'maki-sprite-style') );

				// JavaScript libraries registered by WP
			wp_enqueue_script('jquery');
			wp_enqueue_script('underscore');

				// Will call our own versions of jquery-ui to minimize compatibility problems
			//wp_enqueue_script('dhp-jquery-ui', plugins_url('/lib/jquery-ui/jquery-ui.min.js', dirname(__FILE__)), 'jquery' );
			wp_enqueue_script('jquery-ui-core');
			wp_enqueue_script('jquery-ui-widget');
			wp_enqueue_script('jquery-ui-mouse');
			wp_enqueue_script('jquery-ui-position');
			wp_enqueue_script('jquery-ui-draggable');
			wp_enqueue_script('jquery-ui-resizable');
			wp_enqueue_script('jquery-ui-selectable');
			wp_enqueue_script('jquery-ui-sortable');
			wp_enqueue_script('jquery-ui-accordion');
			wp_enqueue_script('jquery-ui-button');
			wp_enqueue_script('jquery-ui-dialog');
			wp_enqueue_script('jquery-ui-menu');
			wp_enqueue_script('jquery-ui-selectmenu');
			wp_enqueue_script('jquery-ui-slider');
			wp_enqueue_script('jquery-ui-spinner');
			wp_enqueue_script('jquery-ui-tabs');

				// JS libraries specific to DH Press
			wp_enqueue_script('jquery-nestable', plugins_url('/lib/jquery.nestable.js', dirname(__FILE__)), 'jquery' );

				// WP color picker
			wp_enqueue_style('wp-color-picker');
			wp_enqueue_script('wp-color-picker');

				// Random/gradient color libraries
			wp_enqueue_script('randomColor', plugins_url('/lib/randomColor.js', dirname(__FILE__)));
			wp_enqueue_script('rainbowvis', plugins_url('/lib/rainbowvis.js', dirname(__FILE__)));

				// For touch-screen mechanisms
			wp_enqueue_script('dhp-touch-punch', plugins_url('/lib/jquery.ui.touch-punch.js', dirname(__FILE__)),
				array('jquery', 'jquery-ui-core', 'jquery-ui-mouse') );

				// Javascript sprintf implementation
			wp_enqueue_script('sprintf', plugins_url('/lib/sprintf.min.js', dirname(__FILE__)));

			wp_enqueue_script('knockout', plugins_url('/lib/knockout-3.1.0.js', dirname(__FILE__)) );

			wp_enqueue_script('dhp-map-services', plugins_url('/js/dhp-map-services.js', dirname(__FILE__)) );

				// Custom JavaScript for Admin Edit Panel
			$allDepends = array('jquery', 'underscore', 'jquery-ui-core', 'jquery-ui-widget', 'jquery-ui-mouse', 'jquery-ui-position', 'jquery-ui-draggable', 'jquery-ui-resizable', 'jquery-ui-selectable', 'jquery-ui-sortable', 'jquery-ui-accordion', 'jquery-ui-button', 'jquery-ui-dialog', 'jquery-ui-menu', 'jquery-ui-selectmenu', 'jquery-ui-slider', 'jquery-ui-spinner', 'jquery-ui-tabs', 'jquery-nestable', 'wp-color-picker',
								'randomColor', 'rainbowvis', 'sprintf', 'knockout', 'dhp-map-services');
			wp_enqueue_script('dhp-project-script', plugins_url('/js/dhp-project-admin.js', dirname(__FILE__)), $allDepends );

			$pngs = dhp_get_attached_PNGs($postID);
			$localized = array(
				'cancel'				=> __('Cancel', 'dhpress'),
				'delete'				=> __('Delete', 'dhpress'),
				'rebuild'				=> __('Rebuild', 'dhpress'),
				'execute'				=> __('Execute', 'dhpress'),
				'save'					=> __('Save', 'dhpress'),
				'clear_all'				=> __('Clear All', 'dhpress'),
				'random_colors'			=> __('Random Colors', 'dhpress'),
				'gradient'				=> __('Gradient', 'dhpress'),
				'name_me'				=> __('name me', 'dhpress'),
				'choose' 				=> __('- choose -', 'dhpress'),
				'home_button' 			=> __('<p>If you wish to create a "Home" button, you must supply both a URL and label.</p>', 'dhpress'),
				'home_address' 			=> __('<p>The Home address does not appear to be a full, well-formed URL.</p>', 'dhpress'),
				'import_markers' 		=> __('<p>Your project will not work until you import Markers which are associated with this Project (by using this Project ID).</p>', 'dhpress'),
				'define_motes' 			=> __('<p>Your project will not work until you define some motes.</p>', 'dhpress'),
				'pointer_delimiter' 	=> __('<p>Motes of type Pointer require a delimiter character; the Mote named %s has not yet been assigned a delimiter.</p>', 'dhpress'),
				'comma_delimiter' 		=> __('<p>You have specified the commas as the delimiter character for the Lat-Lon Coordinate Mote named %s; you cannot use it as a delimiter, as it is reserved for separating Lat from Lon and cannot be used to form Polygons.</p>', 'dhpress'),
				'no_entry_points' 		=> __('<p>Your project will not work until you create at least one entry point.</p>', 'dhpress'),
				'ep_error'				=> __('<p>%1$s (entry point "%2$s").</p>', 'dhpress'),
				'unlabeled_entry_point' => __('<p>You have an unlabeled entry point. All multiple entry points must be named.</p>', 'dhpress'),
				'map_legend'			=> __('You have not yet added a legend to the Map', 'dhpress'),
				'map_coord_mote'		=> __('You must specify the mote that will provide the coordinate for the Map', 'dhpress'),
				'cards_color_legend'	=> __('We recommend specifying a color legend for the Cards visualization, but none is provided', 'dhpress'),
				'cards_content'			=> __("You haven't yet specified content for the Cards visualization", 'dhpress'),
				'pinboard_width'		=> __('You must specify a valid display width for the Pinboard', 'dhpress'),
				'pinboard_height'		=> __('You must specify a valid display height for the Pinboard', 'dhpress'),
				'pinboard_bg_width'		=> __('You must specify a valid background image width for the Pinboard', 'dhpress'),
				'pinboard_bg_height'	=> __('You must specify a valid background image height for the Pinboard', 'dhpress'),
				'pinboard_legend'		=> __('You have not yet added a legend to the Pinboard', 'dhpress'),
				'pinboard_coord_mote'	=> __('You must specify the mote that will provide the coordinate for the Pinboard', 'dhpress'),
				'tree_head'				=> __('You must specify the head marker for the Tree', 'dhpress'),
				'tree_pointer'			=> __('You must specify the Pointer mote which indicates descending generations for the Tree', 'dhpress'),
				'tree_font_size'		=> __('You must specify a valid font size for the Tree', 'dhpress'),
				'tree_image_width'		=> __('You must specify a valid image width for the Tree', 'dhpress'),
				'tree_image_height'		=> __('You must specify a valid image height for the Tree', 'dhpress'),
				'time_date_mote'		=> __('You must specify the Date mote for the Timeline', 'dhpress'),
				'time_color_legend'		=> __('You must specify a color legend for the Timeline', 'dhpress'),
				'time_band_height'		=> __('You must specify a valid band height for the Timeline', 'dhpress'),
				'time_label_width'		=> __('You must specify a valid x axis label width for the Timeline', 'dhpress'),
				'time_date_start_frame'	=> __('You must specify a valid Date for the start frame of the Timeline', 'dhpress'),
				'time_date_end_frame'	=> __('You must specify a valid Date for the end frame of the Timeline', 'dhpress'),
				'time_date_start_zoom'	=> __('You must specify a valid Date for the start zoom of the Timeline', 'dhpress'),
				'time_date_end_zoom'	=> __('You must specify a valid Date for the end zoom of the Timeline', 'dhpress'),
				'facet_bg_width'		=> __('You must specify a valid background palette width for the Facet Flow view', 'dhpress'),
				'facet_bg_height'		=> __('You must specify a valid background palette height for the Facet Flow view', 'dhpress'),
				'facet_two_motes'		=> __('You need at least two sets of motes for the Facet Flow', 'dhpress'),
				'facet_unique_motes'	=> __('Facet Flow requires unique (not redundant) motes in the list to display', 'dhpress'),
				'facet_browser_mote'	=> __('You need at least one mote for the Facet Browser', 'dhpress'),
				'redundant_motes'		=> __('You have listed redundant motes to display', 'dhpress'),
				'empty_content_mote'	=> __('<p>Your list of motes for the select modal is empty. We suggest you add at least one content mote.</p>', 'dhpress'),
				'transcript_settings'	=> __('<p>Although you have enabled transcripts on archive pages via the "Source" selection, you have not yet specified other necessary transcript settings.</p>', 'dhpress'),
				'tests_being_conducted'	=> __('<p>Tests are now being conducted on the WordPress server. This checks all values for all markers and could take a while.</p><p><b>IMPORTANT</b>: This will only work properly if your project settings have been saved.</p>', 'dhpress'),
				'general_settings'		=> __('General Settings', 'dhpress'),
				'motes'					=> __('Motes', 'dhpress'),
				'entry_points'			=> __('Entry Points', 'dhpress'),
				'misc'					=> __('Misc.', 'dhpress')
			);
			wp_localize_script('dhp-project-script', 'dhpDataLib', array(
				'ajax_url' => $dev_url,
				'projectID' => $postID,
				'pngImages' => $pngs,
				'localized' => json_encode($localized)
			) );

		} else if ( $post->post_type == 'dhp-markers' ) {
			wp_enqueue_style('dhp-admin-style', plugins_url('/css/dhp-admin.css',  dirname(__FILE__) ));
		}

		// Shows list of all Project in admin panel
	} else if ( $hook == 'edit.php'  ) {
		if ( $post->post_type == 'dhp-project' ) {
			wp_enqueue_script('jquery' );
		}
	}
} // add_dhp_project_admin_scripts()


	// PURPOSE: Compare map IDs for sort function
function cmp_map_ids($a, $b)
{
	return strcmp($a["id"], $b["id"]);
} // cmp_map_ids()


	// PURPOSE: Extract DHP custom map data from Map Library so they can be rendered in Map view
	// INPUT:	$mapLayers = array of EP Map layers (each containing ['id' = unique Map ID])
	// RETURNS: Array of data about map layers
	// NOTE:    If id begins with '.' it is a base layer and does not need to be loaded
	// ASSUMES:	Custom Map data has been loaded into WP DB
	// TO DO:	Further error handling if necessary map data doesn't exist?
function dhp_get_map_layer_data($mapLayers)
{
	$mapMetaList = array(	"sname"  	=> "dhp_map_sname",
							"id"     	=> "dhp_map_id",
							"url" 		=> "dhp_map_url",
							"inverseY" 	=> "dhp_map_inverse_y",
							"subd" 		=> "dhp_map_subdomains",
							"minZoom"   => "dhp_map_min_zoom",
							"maxZoom" 	=> "dhp_map_max_zoom",
							"credits"	=> "dhp_map_credits"
						);
	$mapArray = array();

		// Loop thru all map layers, collecting essential data to pass
	foreach($mapLayers as $layer) {
			// Ignore base maps
		if ($layer->id[0] != '.') {
				// Search for Map entry based on map ID
			$args = array( 
				'post_type' => 'dhp-maps', 
				'posts_per_page' => 1,
				'meta_query' => array(
					array('key' => 'dhp_map_id', 'value' => $layer->id)
				)
			);
			$loop = new WP_Query($args);
				// We can only abort if not found
			if (!$loop->have_posts()) {
				trigger_error(__('Map ID cannot be found', 'dhpress'));
				return null;
			}

			$loop->the_post();
			$map_id = get_the_ID();

			$mapData = dhp_get_map_metadata($map_id, $mapMetaList, true);
			wp_reset_query();

				// Do basic error checking to ensure necessary fields exist
			if ($mapData['id'] == '') {
				trigger_error(sprintf(__('No dhp_map_typeid metadata for map named %1$s of id %2$s', 'dhpress'), $layer->name, $layer->id));
			}
			array_push($mapArray, $mapData);
		}
	}
		// Sort array according to map IDs
	usort($mapArray, 'cmp_map_ids');

	return $mapArray;
} // dhp_get_map_layer_data()


// PURPOSE: Includes file with internationalized HTML corresponding to a particular DH Press page
// INPUT: $scriptName = base name of script file (not pathname)

function dhp_include_script($scriptName)
{
	$scriptsPath = plugin_dir_path( __FILE__ ).'scripts/';
	include($scriptsPath.$scriptName);
} // dhp_include_script()


add_filter('the_content', 'dhp_mod_page_content');

// PURPOSE:	Called by WP to modify content to be rendered for a post page
// INPUT:	$content = material to show on page
// RETURNS:	$content with ID of this post and DH Press hooks for marker text and visualization

function dhp_mod_page_content($content)
{
	global $post;

	if ($post && $post->post_type == 'dhp-markers') {

			// NOTE: This is not called in case of Viewing Projects
		return $content.'<div class="dhp-post" id="'.$post->ID.'"><div class="dhp-entrytext"></div></div>';
	}
	else {
		return $content;
	}
} // dhp_mod_page_content()


add_filter( 'query_vars', 'dhp_viz_query_var' );

// PURPOSE: Add the "viz" query variable to WordPress's approved list
function dhp_viz_query_var($vars) {
  $vars[] = "viz";
  return $vars;
}

add_filter( 'single_template', 'dhp_page_template' );

// PURPOSE:	Called by WP to modify output when viewing a page of any type
// INPUT:	$page_template = default path to file to use for template to render page
// RETURNS:	Modified $page_template setting (file path to new php template file)

function dhp_page_template( $page_template )
{
	global $post;
		// For building list of handles upon which page is dependent
	$dependencies = array('jquery', 'underscore');

	$blog_id = get_current_blog_id();
	$ajax_url = get_admin_url( $blog_id ,'admin-ajax.php');
	$post_type = get_query_var('post_type');

		// Viewing a Project?
	if ($post_type == 'dhp-project') {
			// Get rid of theme styles
		wp_dequeue_style('screen');
		wp_deregister_style('screen');
		wp_dequeue_style('events-manager');

		wp_dequeue_script('site');
		wp_deregister_script('site');

		$projObj = new DHPressProject($post->ID);
		$allSettings = $projObj->getAllSettings();

			// Communicate to visualizations by sending parameters in this array
		$vizParams = array();

			// Foundation styles
		wp_enqueue_style('dhp-foundation-style', plugins_url('/lib/foundation-5.1.1/css/foundation.min.css',  dirname(__FILE__)));
		wp_enqueue_style('dhp-foundation-icons', plugins_url('/lib/foundation-icons/foundation-icons.css',  dirname(__FILE__)));

		wp_enqueue_style('dhp-project-css', plugins_url('/css/dhp-project.css',  dirname(__FILE__)), 'dhp-foundation-style', DHP_PLUGIN_VERSION );

		wp_enqueue_script('underscore');
		wp_enqueue_script('jquery');
		wp_enqueue_script('dhp-foundation', plugins_url('/lib/foundation-5.1.1/js/foundation.min.js', dirname(__FILE__)), 'jquery');
		wp_enqueue_script('dhp-modernizr', plugins_url('/lib/foundation-5.1.1/js/vendor/modernizr.js', dirname(__FILE__)), 'jquery');
		wp_enqueue_script('mustache', plugins_url('/lib/mustache.min.js', dirname(__FILE__)));

			// Check query variable "viz" to see which visualization to display -- default = 0
		$vizIndex = (get_query_var('viz')) ? get_query_var('viz') : 0;
		$vizIndex = min($vizIndex, count($allSettings->eps)-1);
		$vizParams['current'] = $vizIndex;

			// Create list of visualization labels for drop-down menu
		$vizMenu = array();
		foreach ($allSettings->eps as $thisEP) {
			array_push($vizMenu, $thisEP->label);
		}
		$vizParams['menu'] = $vizMenu;

			// Visualization specific -- only 1st Entry Point currently supported
			// NOTE: When enqueueing new scripts and styles, ensure that you add them to the appropriate script and style arrays in dhp-view-template.php or they will be dequeued and not load
		$thisEP = $projObj->getEntryPointByIndex($vizIndex);
		switch ($thisEP->type) {
		case 'map':
			wp_enqueue_style('dhp-jquery-ui-style', plugins_url('/lib/jquery-ui/jquery-ui.min.css', dirname(__FILE__)));

			wp_enqueue_style('dhp-map-css', plugins_url('/css/dhp-map.css',  dirname(__FILE__)), '', DHP_PLUGIN_VERSION );
			wp_enqueue_style('leaflet-css', plugins_url('/lib/leaflet-0.7.7/leaflet.css',  dirname(__FILE__)), '', DHP_PLUGIN_VERSION );
			wp_enqueue_style('maki-sprite-style', plugins_url('/lib/maki/maki-sprite.css',  dirname(__FILE__)) );

				// Will call our own versions of jquery-ui to minimize compatibility problems
			//wp_enqueue_script('dhp-jquery-ui', plugins_url('/lib/jquery-ui/jquery-ui.min.js', dirname(__FILE__)), 'jquery');
			wp_enqueue_script('jquery-ui-core');
			wp_enqueue_script('jquery-ui-widget');
			wp_enqueue_script('jquery-ui-mouse');
			wp_enqueue_script('jquery-ui-position');
			wp_enqueue_script('jquery-ui-draggable');
			wp_enqueue_script('jquery-ui-resizable');
			wp_enqueue_script('jquery-ui-selectable');
			wp_enqueue_script('jquery-ui-sortable');
			wp_enqueue_script('jquery-ui-accordion');
			wp_enqueue_script('jquery-ui-button');
			wp_enqueue_script('jquery-ui-dialog');
			wp_enqueue_script('jquery-ui-menu');
			wp_enqueue_script('jquery-ui-selectmenu');
			wp_enqueue_script('jquery-ui-slider');
			wp_enqueue_script('jquery-ui-spinner');
			wp_enqueue_script('jquery-ui-tabs');

			wp_enqueue_script('leaflet', plugins_url('/lib/leaflet-0.7.7/leaflet.js', dirname(__FILE__)));
			wp_enqueue_script('leaflet-maki', plugins_url('/lib/Leaflet.MakiMarkers.js', dirname(__FILE__)), 'leaflet');

				// Has user specified to use Marker Clustering?
			if (isset($thisEP->settings->cluster) && $thisEP->settings->cluster) {
				wp_enqueue_style('dhp-map-cluster-css', plugins_url('/lib/marker-cluster/MarkerCluster.css',  dirname(__FILE__)),
					'leaflet-css', DHP_PLUGIN_VERSION );
				wp_enqueue_style('dhp-map-clusterdef-css', plugins_url('/lib/marker-cluster/MarkerCluster.Default.css',  dirname(__FILE__)),
					'dhp-map-cluster-css', DHP_PLUGIN_VERSION );
				wp_enqueue_script('dhp-maps-cluster', plugins_url('/lib/marker-cluster/leaflet.markercluster.js', dirname(__FILE__)), 'leaflet', DHP_PLUGIN_VERSION);
			}

			wp_enqueue_script('dhp-maps-view', plugins_url('/js/dhp-maps-view.js', dirname(__FILE__)), 'leaflet', DHP_PLUGIN_VERSION);
			wp_enqueue_script('dhp-map-services', plugins_url('/js/dhp-map-services.js', dirname(__FILE__)), 'leaflet', DHP_PLUGIN_VERSION);

				// Get any DHP custom map parameters
			$layerData = dhp_get_map_layer_data($thisEP->settings->layers);
			$vizParams['layerData'] = $layerData;

				// Get any PNG image icons
			$vizParams['pngs'] = dhp_get_attached_PNGs($post->ID);

			array_push($dependencies, 'leaflet', 'dhp-maps-view', 'dhp-map-services', 'jquery-ui-core', 'jquery-ui-widget', 'jquery-ui-mouse', 'jquery-ui-position', 'jquery-ui-draggable', 'jquery-ui-resizable', 'jquery-ui-selectable', 'jquery-ui-sortable', 'jquery-ui-accordion', 'jquery-ui-button', 'jquery-ui-dialog', 'jquery-ui-menu', 'jquery-ui-selectmenu', 'jquery-ui-slider', 'jquery-ui-spinner', 'jquery-ui-tabs');
			break;

		case 'cards':
			wp_enqueue_style('dhp-cards-css', plugins_url('/css/dhp-cards.css',  dirname(__FILE__)) );

			wp_enqueue_script('isotope', plugins_url('/lib/isotope.pkgd.min.js', dirname(__FILE__)));
			wp_enqueue_script('dhp-cards-view', plugins_url('/js/dhp-cards-view.js', dirname(__FILE__)), 
				'isotope' );

			array_push($dependencies, 'isotope', 'dhp-cards-view');
			break;

		case 'pinboard':
			wp_enqueue_style('dhp-jquery-ui-style', plugins_url('/lib/jquery-ui/jquery-ui.min.css', dirname(__FILE__)));

			wp_enqueue_style('foundation-icons-css', plugins_url('/lib/foundation-icons/foundation-icons.css',  dirname(__FILE__)));
			wp_enqueue_style('dhp-pinboard-css', plugins_url('/css/dhp-pinboard.css',  dirname(__FILE__)) );

				// Will call our own versions of jquery-ui to minimize compatibility problems
			//wp_enqueue_script('dhp-jquery-ui', plugins_url('/lib/jquery-ui/jquery-ui.min.js', dirname(__FILE__)), 'jquery');
			wp_enqueue_script('jquery-ui-core');
			wp_enqueue_script('jquery-ui-widget');
			wp_enqueue_script('jquery-ui-mouse');
			wp_enqueue_script('jquery-ui-position');
			wp_enqueue_script('jquery-ui-draggable');
			wp_enqueue_script('jquery-ui-resizable');
			wp_enqueue_script('jquery-ui-selectable');
			wp_enqueue_script('jquery-ui-sortable');
			wp_enqueue_script('jquery-ui-accordion');
			wp_enqueue_script('jquery-ui-button');
			wp_enqueue_script('jquery-ui-dialog');
			wp_enqueue_script('jquery-ui-menu');
			wp_enqueue_script('jquery-ui-selectmenu');
			wp_enqueue_script('jquery-ui-slider');
			wp_enqueue_script('jquery-ui-spinner');
			wp_enqueue_script('jquery-ui-tabs');

			wp_enqueue_script('snap', plugins_url('/lib/snap.svg-min.js', dirname(__FILE__)));
			wp_enqueue_script('dhp-pinboard-view', plugins_url('/js/dhp-pinboard-view.js', dirname(__FILE__)), 
				'snap' );

			if ($thisEP->settings->animscript && $thisEP->settings->animscript !== '') {
				$content = @file_get_contents($thisEP->settings->animscript);
				if ($content === false) {
					trigger_error("Cannot load animation script file ".$thisEP->settings->animscript);
				}
				$vizParams['animscript'] = $content;
			}

				// Get any PNG image icons
			$vizParams['pngs'] = dhp_get_attached_PNGs($post->ID);

			array_push($dependencies, 'snap', 'jquery-ui-core', 'jquery-ui-widget', 'jquery-ui-mouse', 'jquery-ui-position', 'jquery-ui-draggable', 'jquery-ui-resizable', 'jquery-ui-selectable', 'jquery-ui-sortable', 'jquery-ui-accordion', 'jquery-ui-button', 'jquery-ui-dialog', 'jquery-ui-menu', 'jquery-ui-selectmenu', 'jquery-ui-slider', 'jquery-ui-spinner', 'jquery-ui-tabs', 'dhp-pinboard-view');
			break;

		case 'tree':
			wp_enqueue_style('dhp-tree-css', plugins_url('/css/dhp-tree.css',  dirname(__FILE__)) );

			wp_enqueue_script('d3', plugins_url('/lib/d3.min.js', dirname(__FILE__)));
			wp_enqueue_script('dhp-tree-view', plugins_url('/js/dhp-tree-view.js', dirname(__FILE__)), 'd3' );

			array_push($dependencies, 'd3', 'dhp-tree-view');
			break;

		case 'time':
			wp_enqueue_style('dhp-time-css', plugins_url('/css/dhp-time.css',  dirname(__FILE__)) );

			wp_enqueue_script('d3', plugins_url('/lib/d3.min.js', dirname(__FILE__)));
			wp_enqueue_script('dhp-time-view', plugins_url('/js/dhp-time-view.js', dirname(__FILE__)), 'd3' );

			array_push($dependencies, 'd3', 'dhp-time-view');
			break;

		case 'flow':
			wp_enqueue_style('dhp-flow-css', plugins_url('/css/dhp-flow.css',  dirname(__FILE__)) );

			wp_enqueue_script('d3', plugins_url('/lib/d3.min.js', dirname(__FILE__)));
			wp_enqueue_script('d3-parsets', plugins_url('/lib/d3.dhp-parsets.js', dirname(__FILE__)), 'd3');
			wp_enqueue_script('dhp-flow-view', plugins_url('/js/dhp-flow-view.js', dirname(__FILE__)),
				array('d3', 'd3-parsets') );

			array_push($dependencies, 'd3', 'd3-parsets', 'dhp-flow-view');
			break;

		case 'browser':
			wp_enqueue_style('dhp-browser-css', plugins_url('/css/dhp-browser.css',  dirname(__FILE__)) );

			wp_enqueue_script('d3', plugins_url('/lib/d3.min.js', dirname(__FILE__)));
			wp_enqueue_script('dhp-browser-view', plugins_url('/js/dhp-browser-view.js', dirname(__FILE__)),
				'd3' );
			wp_localize_script('dhp-browser-view', 'localize', array('reset' => __('Reset', 'dhpress'),
																	 'reset_all' => __('Reset All', 'dhpress')));

			array_push($dependencies, 'd3', 'dhp-browser-view');
			break;

		default:
			trigger_error("Unknown visualization type: ".$thisEP->type);
			break;
		}

			// Any playback widgets?
		if (($allSettings->views->transcript->audio && $allSettings->views->transcript->audio != '' &&
			$allSettings->views->transcript->audio != 'disable') ||
			($allSettings->views->transcript->video && $allSettings->views->transcript->video != '' &&
			$allSettings->views->transcript->video != 'disable') ||
			($allSettings->views->transcript->transcript && $allSettings->views->transcript->transcript != '' &&
			$allSettings->views->transcript->transcript != 'disable'))
		{
			wp_enqueue_style('dhp-transcript-css', plugins_url('/css/transcriptions.css',  dirname(__FILE__)) );
			wp_enqueue_script('dhp-widget', plugins_url('/js/dhp-widget.js',  dirname(__FILE__)),
				 array('jquery', 'underscore') );
			if ($projObj->selectModalHas('scloud')) {
				wp_enqueue_script('soundcloud-api', 'http://w.soundcloud.com/player/api.js');
				array_push($dependencies, 'soundcloud-api');
			}
			// if ($projObj->selectModalHas('youtube')) {
			// }
			array_push($dependencies, 'dhp-widget');
		}

			// For touch-screen mechanisms
		// wp_enqueue_script('dhp-touch-punch', plugins_url('/lib/jquery.ui.touch-punch.js', dirname(__FILE__)),
		// 	array('jquery', 'dhp-jquery-ui-widget', 'dhp-jquery-ui-mouse') );

		wp_enqueue_script('dhp-services', plugins_url('/js/dhp-services.js', dirname(__FILE__)),
						array('jquery', 'underscore'), DHP_PLUGIN_VERSION );
		array_push($dependencies, 'dhp-services');

			// Enqueue page JS last, after we've determine what dependencies might be
		wp_enqueue_script('dhp-public-project-script', plugins_url('/js/dhp-project-page.js', dirname(__FILE__)), $dependencies, DHP_PLUGIN_VERSION );

		wp_localize_script('dhp-public-project-script', 'dhpData', array(
			'ajax_url'   => $ajax_url,
			'vizParams'  => $vizParams,
			'settings'   => $allSettings
		) );

			// Replace HTML with Project View Template
		$page_template = dirname(__FILE__).'/scripts/dhp-view-template.php';

		// Looking at a Marker/Data entry?
	} else if ( $post_type == 'dhp-markers' ) {
		$project_id = get_post_meta($post->ID, 'project_id',true);
		$projObj = new DHPressProject($project_id);

			// Must insert text needed for dhpServices
		wp_enqueue_script('mustache', plugins_url('/lib/mustache.min.js', dirname(__FILE__)));

		dhp_include_script(DHP_SCRIPT_SERVICES);

		wp_enqueue_style('dhp-project-css', plugins_url('/css/dhp-project.css',  dirname(__FILE__)), '', DHP_PLUGIN_VERSION );

		wp_enqueue_script('jquery');
		wp_enqueue_script('dhp-modernizr', plugins_url('/lib/foundation-5.1.1/js/vendor/modernizr.js', dirname(__FILE__)), 'jquery');
		wp_enqueue_script('underscore');

			// Enqueue last, after dependencies determined
		wp_enqueue_script('dhp-services', plugins_url('/js/dhp-services.js', dirname(__FILE__)),
						array('jquery', 'underscore'), DHP_PLUGIN_VERSION );
		wp_enqueue_script('dhp-marker-script', plugins_url('/js/dhp-marker-page.js', dirname(__FILE__)), $dependencies, DHP_PLUGIN_VERSION);

		wp_localize_script('dhp-marker-script', 'dhpData', array(
			'ajax_url' => $ajax_url,
			'settings' => $projObj->getAllSettings(),
			'proj_id' => $project_id
		) );
	} // else if dhp-markers

	return $page_template;
} // dhp_page_template()


add_filter( 'archive_template', 'dhp_tax_template' );

// PURPOSE: Set template to be used to show results of top-level custom taxonomy display
// INPUT:	$page_template = default path to file to use for template to render page
// RETURNS:	Modified $page_template setting (file path to new php template file)
// NOTES:   The name of the taxonomy is the value of a mote;
//				the name of the tax-term's parent is the name of the mote

function dhp_tax_template( $page_template )
{
	$blog_id = get_current_blog_id();
	$ajax_url = get_admin_url( $blog_id ,'admin-ajax.php');

		// For building list of handles upon which page is dependent
	$dependencies = array('jquery', 'underscore');

		// ensure a Taxonomy archive page is being rendered
	if (is_tax()) {
		global $wp_query;

		$term = $wp_query->get_queried_object();
		$title = $term->taxonomy;
		$term_parent = get_term($term->parent, $title);

			// Set the name of the term's parent, which is also the name of the mote
		$term->parent_name = $term_parent->name;

		$projectID = DHPressProject::RootTaxNameToProjectID($title);
		$projObj = new DHPressProject($projectID);
		$project_settings = $projObj->getAllSettings();

			// Must insert text needed for dhpServices
		wp_enqueue_script('mustache', plugins_url('/lib/mustache.min.js', dirname(__FILE__)));

		dhp_include_script(DHP_SCRIPT_SERVICES);
		dhp_include_script(DHP_SCRIPT_TAX);

			// Are we on a taxonomy/archive page that corresponds to transcript "source"?
		$isTranscript = ($project_settings->views->transcript->source == $term_parent->name);

			// Foundation styles
		wp_enqueue_style('dhp-foundation-css', plugins_url('/lib/foundation-5.1.1/css/foundation.min.css',  dirname(__FILE__)));
		wp_enqueue_style('dhp-foundation-icons', plugins_url('/lib/foundation-icons/foundation-icons.css',  dirname(__FILE__)));

		wp_enqueue_style('dhp-project-css', plugins_url('/css/dhp-project.css',  dirname(__FILE__)), '', DHP_PLUGIN_VERSION );

		wp_enqueue_script('jquery' );
		wp_enqueue_script('dhp-foundation', plugins_url('/lib/foundation-5.1.1/js/foundation.min.js', dirname(__FILE__)), 'jquery');
		wp_enqueue_script('dhp-modernizr', plugins_url('/lib/foundation-5.1.1/js/vendor/modernizr.js', dirname(__FILE__)), 'jquery');
		wp_enqueue_script('underscore');

		if ($isTranscript) {
			wp_enqueue_style('transcript', plugins_url('/css/transcriptions.css',  dirname(__FILE__)), '', DHP_PLUGIN_VERSION );
			if ($projObj->selectModalHas('scloud')) {
				wp_enqueue_script('soundcloud-api', 'http://w.soundcloud.com/player/api.js');
				array_push($dependencies, 'soundcloud-api');
			}
			// if ($projObj->selectModalHas('youtube')) {
			// }
			wp_enqueue_script('dhp-widget', plugins_url('/js/dhp-widget.js',  dirname(__FILE__)),
				 array('jquery', 'underscore'), DHP_PLUGIN_VERSION);
			array_push($dependencies, 'dhp-widget');
		}

		wp_enqueue_script('dhp-services', plugins_url('/js/dhp-services.js', dirname(__FILE__)),
						array('jquery', 'underscore'), DHP_PLUGIN_VERSION );
		array_push($dependencies, 'dhp-services');

			// Enqueue last, after dependencies have been determined
		wp_enqueue_script('dhp-tax-script', plugins_url('/js/dhp-tax-page.js', dirname(__FILE__)), $dependencies, DHP_PLUGIN_VERSION );

		wp_localize_script('dhp-tax-script', 'dhpData', array(
				'project_id' => $projectID,
				'ajax_url' => $ajax_url,
				'tax' => $term,
				'project_settings' => $project_settings,
				'isTranscript' => $isTranscript
			) );
	}
	return $page_template;
} // dhp_tax_template()