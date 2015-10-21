<title><?php the_title(); ?></title>
<?php wp_head(); ?>

<nav class="top-bar dhp-nav" data-topbar data-options="is_hover: false">
	<ul class="title-area">
		<li class="name">
			<h1 style="font-style:italic"><a href="#"><?php _e('My Site', 'dhpress'); ?></a></h1>
		</li>
		<li class="toggle-topbar menu-icon"><a href="#"><?php _e('Menu', 'dhpress'); ?></a></li>
	</ul>

	<section class="top-bar-section">
		<ul class="right">
		</ul>

		<ul class="left">
		</ul>
	</section>
</nav>

<div id="dhp-visual"></div>

<?php wp_footer();

	define('DHP_SCRIPT_SERVICES',  'dhp-script-services.php');
	define('DHP_SCRIPT_PROJ_VIEW',  'dhp-script-proj-view.txt');  // Does not exist...
	define('DHP_SCRIPT_MAP_VIEW',   'dhp-script-map-view.php');
	define('DHP_SCRIPT_CARDS_VIEW',   'dhp-script-cards-view.php');
	define('DHP_SCRIPT_PINBOARD_VIEW',   'dhp-script-pin-view.php');
	define('DHP_SCRIPT_FLOW_VIEW',   'dhp-script-flow-view.php');
	define('DHP_SCRIPT_TIME_VIEW',   'dhp-script-time-view.php');

	// define( 'DHP_SCRIPT_TREE_VIEW',   'dhp-script-tree-view.txt' );   // currently unneeded
	// define( 'DHP_SCRIPT_BROWSER_VIEW',   'dhp-script-browser-view.txt' );   // currently unneeded

	// define( 'DHP_SCRIPT_TAX_TRANS',  'dhp-script-tax-trans.txt' );	// currently unneeded
	// define( 'DHP_SCRIPT_TRANS_VIEW', 'dhp-script-trans-view.txt' );   // currently unneeded

	$scriptsPath = plugin_dir_path( __FILE__ );
	function dhptmplt_include_script($scriptName) {
		include($scriptsPath.$scriptName);
	} // dhptmplt_include_script()

	global $post;
	$postID = $post->ID;
	$projObj = new DHPressProject($postID);

		// get global text elements
	//echo dhptmplt_get_script_text(DHP_SCRIPT_SERVICES);
	include(plugin_dir_path( __FILE__ ) . DHP_SCRIPT_SERVICES);

		// insert title of post in hidden field
	echo '<input type="hidden" id="dhp-view-title" value="'.get_the_title($postID).'"/>';

		// Which visualization is being shown
	$vizIndex = (get_query_var('viz')) ? get_query_var('viz') : 0;
	$ep = $projObj->getEntryPointByIndex($vizIndex);

	switch ($ep->type) {
	case 'map':
		dhptmplt_include_script(DHP_SCRIPT_MAP_VIEW);
		break;
	case 'cards':
		dhptmplt_include_script(DHP_SCRIPT_CARDS_VIEW);
		break;
	case 'pinboard':
		dhptmplt_include_script(DHP_SCRIPT_PINBOARD_VIEW);
		break;
	case 'tree':
			// currently nothing is used
		// $projscript .= dhptmplt_get_script_text(DHP_SCRIPT_TREE_VIEW);
		break;
	case 'time':
		dhptmplt_include_script(DHP_SCRIPT_TIME_VIEW);
		break;
	case 'flow':
		dhptmplt_include_script(DHP_SCRIPT_FLOW_VIEW);
		break;
	case 'browser':
			// currently nothing is used
		// $projscript .= dhptmplt_get_script_text(DHP_SCRIPT_BROWSER_VIEW);
		break;
	}

?>

<div id="markerModal" class="reveal-modal" data-reveal>
	<div class="modal-content">
		<div class="modal-header">
			<h3 id="markerModalLabel"></h3>
		</div>
		<div class="modal-body clearfix">
		</div>
		<div class="reveal-modal-footer clearfix ">
			<ul class="button-group right"><li><a class="button close-select-modal" ><?php _e('Close', 'dhpress'); ?></a></li></ul>
		</div>
	</div>
	<a class="close-reveal-modal close-select-modal">&#215;</a>
</div>


<div id="loading" class="reveal-modal tiny" data-reveal>
	<div class="loading-content">
		<h3 class="loading-title"><?php _e('Loading Project', 'dhpress'); ?></h3>
		<div class="spinner"></div>
	</div>
</div>


<script id="dhp-script-epviz-menu">
  <li class="divider"></li>
  <li class="has-dropdown">
      <a href="#"><?php _e('Change View', 'dhpress'); ?></a>
      <ul class="dropdown epviz-dropdown">
          <!-- links -->
      </ul>
  </li>
</script>


<script id="dhp-script-legend-head">
  <div id="legends" class="" style=""><div class="legend-row"></div></div>
</script>


<script id="dhp-script-legend-hideshow">
  <div class="row check-all"> 
    <div class="small-2 large-1 columns"><input type="checkbox" checked="checked"></div>
    <div class="small-10 large-10 columns"><a class="value" data-id="all"><b><?php _e('Hide/Show All', 'dhpress'); ?></b></a></div>
  </div>
</script>


<script id="dhp-script-tip-div" type="">
	<li>
		<a href="#" class="tips" data-reveal-id="tipModal" data-reveal><i class="fi-info"></i><?php _e('Tips', 'dhpress'); ?></a>
	</li>
</script>

<script id="dhp-script-transc-scroll" type="">
	<div style="padding-top:5px">
		<input type="checkbox" id="transcSyncOn" name="transcSyncOn" checked> <?php _e('Scroll transcript to follow playback', 'dhpress'); ?>
	</div>
	<br/>
</script>
