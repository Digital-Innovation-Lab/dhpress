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

	define('DHP_SCRIPT_PROJ_VIEW',  'dhp-script-proj-view.txt');
	define('DHP_SCRIPT_MAP_VIEW',   'dhp-script-map-view.txt');
	define('DHP_SCRIPT_CARDS_VIEW',   'dhp-script-cards-view.txt');
	define('DHP_SCRIPT_PINBOARD_VIEW',   'dhp-script-pin-view.txt');
	define('DHP_SCRIPT_FLOW_VIEW',   'dhp-script-flow-view.txt');
	define('DHP_SCRIPT_TIME_VIEW',   'dhp-script-time-view.txt');

	// define( 'DHP_SCRIPT_TREE_VIEW',   'dhp-script-tree-view.txt' );   // currently unneeded
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
		echo dhptmplt_get_script_text(DHP_SCRIPT_TIME_VIEW);
		break;
	case 'flow':
		echo dhptmplt_get_script_text(DHP_SCRIPT_FLOW_VIEW);
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
		<div class="spinner"></div>
	</div>
</div>


<script id="dhp-script-epviz-menu" type="x-tmpl-mustache">
  <li class="divider"></li>
  <li class="has-dropdown">
      <a href="#">Change View</a>
      <ul class="dropdown epviz-dropdown">
          <!-- links -->
      </ul>
  </li>
</script>


<script id="dhp-script-legend-head" type="x-tmpl-mustache">
  <div id="legends" class="" style=""><div class="legend-row"></div></div>
</script>


<script id="dhp-script-legend-hideshow" type="x-tmpl-mustache">
  <div class="row check-all"> 
    <div class="small-2 large-1 columns"><input type="checkbox" checked="checked"></div>
    <div class="small-10 large-10 columns"><a class="value" data-id="all"><b>Hide/Show All</b></a></div>
  </div>
</script>


<script id="dhp-script-tip-div" type="x-tmpl-mustache">
	<li>
		<a href="#" class="tips" data-reveal-id="tipModal" data-reveal><i class="fi-info"></i>Tips</a>
	</li>
</script>

<script id="dhp-script-transc-scroll" type="x-tmpl-mustache">
	<div style="padding-top:5px">
		<input type="checkbox" id="transcSyncOn" name="transcSyncOn" checked> Scroll transcript to follow playback
	</div>
	<br/>
</script>

<!-- text related to creating Date strings -->
<script id="dhp-date-nolater" type="x-tmpl-mustache">
no later than {{date}}
</script>

<script id="dhp-date-atleast" type="x-tmpl-mustache">
at least {{date}}
</script>

<script id="dhp-date-about" type="x-tmpl-mustache">
about {{date}}
</script>

<script id="dhp-date-from-to" type="x-tmpl-mustache">
From {{d1}} to {{d2}}
</script>

<!-- text for button labels -->
<script id="dhp-btnlbl-linkto" type="x-tmpl-mustache">
See {{name}} webpage
</script>

<script id="dhp-btnlbl-youtube" type="x-tmpl-mustache">
Go to YouTube page
</script>

<script id="dhp-btnlbl-sndcld" type="x-tmpl-mustache">
Go to SoundCloud page
</script>

<script id="dhp-btnlbl-transcript" type="x-tmpl-mustache">
Look at Transcript file
</script>