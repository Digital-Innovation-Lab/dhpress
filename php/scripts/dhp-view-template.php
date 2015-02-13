<title><?php the_title(); ?></title>
<?php wp_head(); ?>

<nav class="top-bar dhp-nav" data-topbar data-options="is_hover: false">
	<ul class="title-area">
		<li class="name">
			<h1 style="font-style:italic"><a href="#">My Site</a></h1>
		</li>
		<li class="toggle-topbar menu-icon"><a href="#">Menu</a></li>
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

	define( 'DHP_SCRIPT_PROJ_VIEW',  'dhp-script-proj-view.txt' );
	define( 'DHP_SCRIPT_MAP_VIEW',   'dhp-script-map-view.txt' );
	define( 'DHP_SCRIPT_CARDS_VIEW',   'dhp-script-cards-view.txt' );
	define( 'DHP_SCRIPT_PINBOARD_VIEW',   'dhp-script-pin-view.txt' );

	// define( 'DHP_SCRIPT_TREE_VIEW',   'dhp-script-tree-view.txt' );   // currently unneeded
	// define( 'DHP_SCRIPT_TIME_VIEW',   'dhp-script-time-view.txt' );   // currently unneeded
	// define( 'DHP_SCRIPT_FLOW_VIEW',   'dhp-script-flow-view.txt' );   // currently unneeded
	// define( 'DHP_SCRIPT_BROWSER_VIEW',   'dhp-script-browser-view.txt' );   // currently unneeded

	// define( 'DHP_SCRIPT_TAX_TRANS',  'dhp-script-tax-trans.txt' );	// currently unneeded
	// define( 'DHP_SCRIPT_TRANS_VIEW', 'dhp-script-trans-view.txt' );   // currently unneeded

	function dhptmplt_get_script_text($scriptname)
	{
		$scriptpath = plugin_dir_path( __FILE__ ).$scriptname;
		if (!file_exists($scriptpath)) {
			trigger_error("Script file ".$scriptpath." not found");
		}
		$scripthandle = fopen($scriptpath, "r");
		$scripttext = file_get_contents($scriptpath);
		fclose($scripthandle);
		return $scripttext;
	} // dhptmplt_get_script_text()

	global $post;
	$postID = $post->ID;
	$projObj = new DHPressProject($postID);

		// insert title of post in hidden field
	echo '<input type="hidden" id="dhp-view-title" value="'.get_the_title($postID).'"/>';

		// Which visualization is being shown
	$vizIndex = (get_query_var('viz')) ? get_query_var('viz') : 0;
	$ep = $projObj->getEntryPointByIndex($vizIndex);

	switch ($ep->type) {
	case 'map':
		echo dhptmplt_get_script_text(DHP_SCRIPT_MAP_VIEW);
		break;
	case 'cards':
		echo dhptmplt_get_script_text(DHP_SCRIPT_CARDS_VIEW);
		break;
	case 'pinboard':
		echo dhptmplt_get_script_text(DHP_SCRIPT_PINBOARD_VIEW);
		break;
	case 'tree':
			// currently nothing is used
		// $projscript .= dhptmplt_get_script_text(DHP_SCRIPT_TREE_VIEW);
		break;
	case 'time':
			// currently nothing is used
		// $projscript .= dhptmplt_get_script_text(DHP_SCRIPT_TIME_VIEW);
		break;
	case 'flow':
			// currently nothing is used
		// $projscript .= dhptmplt_get_script_text(DHP_SCRIPT_FLOW_VIEW);
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
			<ul class="button-group right"><li><a class="button close-select-modal" >Close</a></li></ul>
		</div>
	</div>
	<a class="close-reveal-modal close-select-modal">&#215;</a>
</div>


<div id="loading" class="reveal-modal tiny" data-reveal>
	<div class="loading-content">
		<h3 class="loading-title">Loading Project</h3>
	</div>
</div>


<script id="dhp-script-epviz-menu" type="text/x-handlebars-template">
  <li class="divider"></li>
  <li class="has-dropdown">
      <a href="#">Change View</a>
      <ul class="dropdown epviz-dropdown">
          <!-- links -->
      </ul>
  </li>
</script>


<script id="dhp-script-legend-head" type="text/x-handlebars-template">
  <div id="legends" class="" style=""><div class="legend-row"></div></div>
</script>


<script id="dhp-script-legend-hideshow" type="text/x-handlebars-template">
  <div class="row check-all"> 
    <div class="small-2 large-1 columns"><input type="checkbox" checked="checked"></div>
    <div class="small-10 large-10 columns"><a class="value" data-id="all"><b>Hide/Show All</b></a></div>
  </div>
</script>
