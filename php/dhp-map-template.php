<?php
/*
Template Name: Map Template
*/

// NOTE:    The dhp-maps-view.js code is only set up to display a single map in the dhp-visual DIV;
//          Displaying multiple maps (one per post in a list) would require modifications

?>
<?php get_header(); ?>

<div id="content" class="widecolumn">
 <?php if (have_posts()) : while (have_posts()) : the_post();?>

 <div class="post">
 <h1>MAP LIBRARY ENTRY</h1>
 <!-- <h2 id="post-<?php the_ID(); ?>"><?php the_title();?></h2> -->

 <div class="dhp-entrytext">
  <?php the_content('<p class="serif">Read the rest of this page &raquo;</p>'); ?>
  <?php
    $postid = get_the_ID();
    echo "<br/>";

    //echo $postid;
    $args = array(
            'p'             => $postid,
            'numberposts'     => $num,
            'offset'          => 0,
            //'category'        => ,
            'orderby'         => 'menu_order, post_title', // post_date, rand
            'order'           => 'DESC',
            //'include'         => ,
            //'exclude'         => ,
            //'meta_key'        => ,
            //'meta_value'      => $postid,
            'post_type'       => 'dhp-maps',
            //'post_mime_type'  => ,
            //'post_parent'     => ,
            'post_status'     => 'publish',
            'suppress_filters' => true
        );
        $posts = get_posts( $args );
 
        global $dhp_map_custom_fields;

        $html = '';
        foreach ( $posts as $post ) {
            the_title();

                // Get all custom fields about this map -- call function in dhp-map-library.php
            $mapMetaData = dhp_get_map_custom_fields($post->ID, $dhp_map_custom_fields);

                // Pass map data required to show it as parameters in hidden form
            echo "<form name='map-params-form'>";
            echo "<input type='hidden' id='map-id' name='map-typeid' value='".$mapMetaData['dhp_map_id']."'>";
            echo "<input type='hidden' id='map-sname' name='map-sname' value='".$mapMetaData['dhp_map_sname']."'>";
            echo "<input type='hidden' id='map-url' name='map-url' value='".$mapMetaData['dhp_map_url']."'>";
            echo "<input type='hidden' id='map-subdomains' name='map-subdomains' value='".$mapMetaData['dhp_map_subdomains']."'>";
            echo "<input type='hidden' id='map-zoom-min' name='map-zoom-min' value='".$mapMetaData['dhp_map_min_zoom']."'>";
            echo "<input type='hidden' id='map-zoom-max' name='map-zoom-max' value='".$mapMetaData['dhp_map_max_zoom']."'>";
            echo "<input type='hidden' id='map-inverse-y' name='map-inverse-y' value='".$mapMetaData['dhp_map_inverse_y']."'>";
            echo "<input type='hidden' id='map-desc' name='map-desc' value='".$mapMetaData['dhp_map_desc']."'>";
            echo "<input type='hidden' id='map-credits' name='map-credits' value='".$mapMetaData['dhp_map_credits']."'>";

            echo "<input type='hidden' id='map-n_bounds' name='map-n_bounds' value='".$mapMetaData['dhp_map_n_bounds']."'>";
            echo "<input type='hidden' id='map-s_bounds' name='map-s_bounds' value='".$mapMetaData['dhp_map_s_bounds']."'>";
            echo "<input type='hidden' id='map-e_bounds' name='map-e_bounds' value='".$mapMetaData['dhp_map_e_bounds']."'>";
            echo "<input type='hidden' id='map-w_bounds' name='map-s_bounds' value='".$mapMetaData['dhp_map_w_bounds']."'>";

            echo "</form>";

                // Show all map data
            echo "<br/>";

            echo "<table border=1>";
            echo "<tr><td colspan=2 align=center><b>Map Information<b></td><tr>";

            echo "<tr><td><b>WordPress Post ID: </b></td><td>".$post->ID."</td></tr>";
            echo "<tr><td><b>Map ID: </b></td><td>".$mapMetaData['dhp_map_id']."</td></tr>";
            echo "<tr><td><b>Short title: </b></td><td>".$mapMetaData['dhp_map_sname']."</td></tr>";
            echo "<tr><td><b>Description: </b></td><td>".$mapMetaData['dhp_map_desc']."</td></tr>";
            echo "<tr><td><b>URL: </b></td><td>".$mapMetaData['dhp_map_url']."</td></tr>";
            echo "<tr><td><b>Subdomains: </b></td><td>".$mapMetaData['dhp_map_subdomains']."</td></tr>";
            echo "<tr><td><b>Credits: </b></td><td>".$mapMetaData['dhp_map_credits']."</td></tr>";
            echo "<tr><td><b>N,S,E,W Bounds: </b></td><td>".$mapMetaData['dhp_map_n_bounds'].", ".$mapMetaData['dhp_map_s_bounds'].", ".$mapMetaData['dhp_map_e_bounds'].", ".$mapMetaData['dhp_map_w_bounds']."</td></tr>";
            echo "<tr><td><b>Min/Max Zoom: </b></td><td>".$mapMetaData['dhp_map_min_zoom']."/".$mapMetaData['dhp_map_max_zoom']."</td></tr>";
            echo "<tr><td><b>Inverse Y-axis: </b></td><td>".$mapMetaData['dhp_map_inverse_y']."</td></tr>";
            echo "<tr><td><b></b></td><td></td></tr>";
            echo "</table>";
        }
  ?>
 </div>
 <div id="dhp-visual"></div>
 <button id="hide">Hide</button>
 </div>
 <?php endwhile; endif; ?>

 <?php edit_post_link('Edit this entry.', '<p>', '</p>'); ?>
</div>

<?php get_footer(); ?>