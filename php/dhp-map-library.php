<?php
// PURPOSE: Handles DH Press custom maps (entering and editing of map data)
// NOTES:   In order for maps to be used by DH Press projects, there must be entries in the Maps Library, either
//              entered by hand or imported via CSV files
//          The following custom fields are required for all overlay maps in the Map Library:
//              dhp_map_id          = unique ID for this map (String)
//              dhp_map_sname       = a short title for map, does not need to be unique
//              dhp_map_url         = URL for map on map server
//              dhp_map_subdomains  = extra urls for tile server subdomains separated by |
//              dhp_map_n_bounds    = latitude of northern bounds of map/overlay
//              dhp_map_s_bounds    = latitude of southern bounds of map/overlay
//              dhp_map_e_bounds    = longitude of eastern bounds of map/overlay
//              dhp_map_w_bounds    = longitude of western bounds of map/overlay
//              dhp_map_min_zoom    = minimum zoom for map (Integer)
//              dhp_map_max_zoom    = maximum zoom for map (Integer)
//          The following custom fields are for purposes of documenting and identifying maps:
//              dhp_map_desc
//              dhp_map_credits

// Since maps are implemented with Leaflet, support for Google base maps has been removed

    // A list of all of the custom fields associated with Map post types
$dhp_map_custom_fields = array( 'dhp_map_id', 'dhp_map_sname', 'dhp_map_url', 'dhp_map_subdomains',
                                'dhp_map_n_bounds', 'dhp_map_s_bounds', 'dhp_map_e_bounds', 'dhp_map_w_bounds',
                                'dhp_map_min_zoom', 'dhp_map_max_zoom', 'dhp_map_desc', 'dhp_map_credits'
                            );


// ============================== Init Functions ============================

add_action( 'init', 'dhp_mapset_init' );

    // PURPOSE: Add new taxonomy for mapsets
function dhp_mapset_init()
{
  $labels = array(
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

  $args = array(
    'labels' => $labels,
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
  register_post_type('dhp-maps',$args);
}


add_action( 'admin_enqueue_scripts', 'add_dhp_map_library_scripts', 10, 1 );

    // PURPOSE: Prepare CSS and JS files for all page types in WP
    // INPUT:   $hook = name of template file being loaded
    // ASSUMES: Other WP global variables for current page are set
    // NOTES:   Custom scripts to be run on new/edit Map pages only (not view)
function add_dhp_map_library_scripts( $hook )
{
    global $post;

    if ( $hook == 'edit.php'|| $hook == 'post-new.php' || $hook == 'post.php' ) {
        if ( $post->post_type === 'dhp-maps' ) {
            wp_enqueue_script('jquery' );
        }
    }
} // add_dhp_map_library_scripts()


    // PURPOSE: Return all of the data in custom fields associated with a particular Map post
    // INPUT:   $postID = ID of the Map post
    //          $cfArray = array of names of custom fields
    // RETURNS: An associative array (hash) of values
function dhp_get_map_custom_fields($postID, $cfArray)
{
    $returnVals = array();

    foreach ($cfArray as $key) {
        $dataItem = get_post_meta($postID, $key, true);
        $returnVals[$key] = $dataItem;
    }
    return $returnVals;
} // dhp_get_map_custom_fields()


    // PURPOSE: Update the data in custom fields associated with a particular Map post from _POST[] vars
    // INPUT:   $postID = ID of the Map post
    //          $cfArray = array of names of custom fields to update
function dhp_update_map_from_post($postID, $cfArray)
{
    foreach ($cfArray as $key) {
        $dataItem = $_POST[$key];
        update_post_meta($postID, $key, $dataItem);
    }
} // dhp_update_map_from_post()


add_action('add_meta_boxes', 'add_dhp_map_settings_box');

// Add the Meta Box for map attributes
function add_dhp_map_settings_box()
{
    add_meta_box(
        'dhp_map_settings_meta_box',       // $id
        'Map Attributes',                  // title
        'show_dhp_map_settings_box',       // callback function name
        'dhp-maps',                        // post-type
        'normal',                          // $context
        'high');                           // $priority
}

    // PURPOSE: Handle creating HTML to show/edit custom fields specific to Map marker
    // ASSUMES: $post global is set to the Map post we are currently looking at
    // TO DO:   Must be more efficient means of selecting option
function show_dhp_map_settings_box()
{
    global $post, $dhp_map_custom_fields;

        // Setup nonce
    echo '<input type="hidden" name="dhp_nonce" id="dhp_nonce" value="'.wp_create_nonce('dhp_nonce'.$post->ID).'" />';

        // Fetch all custom fields for this Map
    $mapAttributes = dhp_get_map_custom_fields($post->ID, $dhp_map_custom_fields);

    echo '<table>';
    echo '<tr><td colspan=2><label>Please enter the map information below:</label></td></tr>';
    echo '<tr><td align=right>*Map ID:</td><td><input name="dhp_map_id" id="dhp_map_id" type="text" size="60" value="'.$mapAttributes['dhp_map_id'].'"/></td></tr>';
    echo '<tr><td align=right>*Short title:</td><td><input name="dhp_map_sname" id="dhp_map_sname" type="text" size="60" value="'.$mapAttributes['dhp_map_sname'].'"/></td></tr>';
    echo '<tr><td align=right>*URL:</td><td><input name="dhp_map_url" id="dhp_map_url" type="text" size="30" value="'.$mapAttributes['dhp_map_url'].'"/></td></tr>';
    echo '<tr><td align=right>Subdomains:</td><td><input name="dhp_map_subdomains" id="dhp_map_subdomains" type="text" size="30" value="'.$mapAttributes['dhp_map_subdomains'].'"/></td></tr>';

    echo '<tr><td align=right>*North bounds:</td><td><input name="dhp_map_n_bounds" id="dhp_map_n_bounds" type="text" size="10" value="'.$mapAttributes['dhp_map_n_bounds'].'"/></td></tr>';
    echo '<tr><td align=right>*South bounds:</td><td><input name="dhp_map_s_bounds" id="dhp_map_s_bounds" type="text" size="10" value="'.$mapAttributes['dhp_map_s_bounds'].'"/></td></tr>';
    echo '<tr><td align=right>*East bounds:</td><td><input name="dhp_map_e_bounds" id="dhp_map_e_bounds" type="text" size="10" value="'.$mapAttributes['dhp_map_e_bounds'].'"/></td></tr>';
    echo '<tr><td align=right>*West bounds:</td><td><input name="dhp_map_w_bounds" id="dhp_map_w_bounds" type="text" size="10" value="'.$mapAttributes['dhp_map_w_bounds'].'"/></td></tr>';
    echo '<tr><td align=right>*Minimum Zoom:</td><td><input name="dhp_map_min_zoom" id="dhp_map_min_zoom" type="text" size="2" value="'.$mapAttributes['dhp_map_min_zoom'].'"/></td></tr>';
    echo '<tr><td align=right>*Maximum Zoom:</td><td><input name="dhp_map_max_zoom" id="dhp_map_max_zoom" type="text" size="2" value="'.$mapAttributes['dhp_map_max_zoom'].'"/></td></tr>';

    echo '<tr><td align=right>Description:</td><td><input name="dhp_map_desc" id="dhp_map_desc" type="text" size="60" value="'.$mapAttributes['dhp_map_desc'].'"/></td></tr>';
    echo '<tr><td align=right>Credits:</td><td><input name="dhp_map_credits" id="dhp_map_credits" type="text" size="30" value="'.$mapAttributes['dhp_map_credits'].'"/></td></tr>';
    echo '</table>';
} // show_dhp_map_settings_box()


add_action('save_post', 'save_dhp_map_settings');  

    // PURPOSE: Save values from UI edit boxes into Map post
    // INPUT:   $post_id = ID of Map marker
function save_dhp_map_settings($post_id)
{
    global $dhp_map_custom_fields;

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

    dhp_update_map_from_post($post_id, $dhp_map_custom_fields);
} // save_dhp_map_settings()


add_filter( 'single_template', 'dhp_map_page_template' );

    // PURPOSE: Handle setting template to be used for Map type
    // INPUT:   $page_template = default name of template file
    // RETURNS: Modified name of template file
    // ASSUMES: map template will pass Map post custom fields to JavaScript

function dhp_map_page_template( $page_template )
{
    global $post;

    $post_type = get_query_var('post_type');

    if ( $post_type == 'dhp-maps' ) {
        // $dhp_map_id = get_post_meta($post->ID, 'dhp_map_id',true);

        $page_template = dirname( __FILE__ ) . '/dhp-map-template.php';

        wp_enqueue_style('dhp-project-css', plugins_url('/css/dhp-project.css',  dirname(__FILE__)), '', DHP_PLUGIN_VERSION );
        wp_enqueue_style('dhp-map-css', plugins_url('/css/dhp-map.css',  dirname(__FILE__)), '', DHP_PLUGIN_VERSION );
        wp_enqueue_style('leaflet-css', plugins_url('/lib/leaflet-0.7.3/leaflet.css',  dirname(__FILE__)), '', DHP_PLUGIN_VERSION );

        wp_enqueue_script('jquery');
        wp_enqueue_script('underscore');

        // wp_enqueue_script('dhp-google-map-script', 'http'. ( is_ssl() ? 's' : '' ) .'://maps.google.com/maps/api/js?v=3&amp;sensor=false');

        wp_enqueue_script('leaflet', plugins_url('/lib/leaflet-0.7.3/leaflet.js', dirname(__FILE__)));
        wp_enqueue_script('dhp-map-services', plugins_url('/js/dhp-map-services.js', dirname(__FILE__)), 'leaflet', DHP_PLUGIN_VERSION);
        wp_enqueue_script('dhp-map-page', plugins_url('/js/dhp-map-page.js', dirname(__FILE__)), array('leaflet', 'dhp-map-services' ), DHP_PLUGIN_VERSION);
    }
    return $page_template;
} // dhp_map_page_template()


?>
