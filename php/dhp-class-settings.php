<?php
// PURPOSE: Include settings for useful addins that are used site wide and not just on dhp pages.
//				Site Tip page: Selecting a post page that provides the text for global help
//				Timeout duration: the number of minutes of inactivity that leads to timeout condition
//				Redirect URL: the destination webpage where browser is redirected upon a timeout
//				Kiosk User Agent: The name of device for which external URL blocking is applied (if any)
//				Block External URLs: A comma-separated list of domains blocked for the browser

if (!class_exists( 'DHPressSettings')) {
	class DHPressSettings
	{
		static function init() 
		{	
			add_action('admin_init', array( __CLASS__, 'admin_init'));
			add_action('admin_menu', array( __CLASS__, 'add_menu'));

				// Register/load scripts if options are set
				if (get_option('tip_url')) {
					add_action('wp_footer', array( __CLASS__, 'dhp_tip_page_content'));
				}

				add_action('init', array( __CLASS__, 'register_scripts'));
				add_action('wp_enqueue_scripts', array( __CLASS__, 'print_scripts'));
		} // init()

		static function admin_init()
		{
			// Set up the settings for this plugin
			self::init_settings();
			// Possibly do additional admin_init tasks
		} // admin_init()

		static function init_settings()
		{
			// register the settings for this plugin
			register_setting('dhp_global_settings-group', 'timeout_duration');
			register_setting('dhp_global_settings-group', 'redirect_url');
			register_setting('dhp_global_settings-group', 'kiosk_useragent');
			register_setting('dhp_global_settings-group', 'kiosk_blockurls');
			register_setting('dhp_global_settings-group', 'tip_url');
		} // init_settings()

			// Add options page for global utilities
		static function add_menu()
		{
			add_options_page('Global Options', 'DH Press Options', 'manage_options', 'dhp-global-options', array(__CLASS__, 'dhp_settings_page'));
		} // add_menu()

			// Add menu callback function
		static function dhp_settings_page() 
		{
			echo self::settings_page_template();
		}

		static function get_launch_url() {
			$launch_url = get_permalink( get_option('kiosk_launch') );

			return $launch_url;
		}

			// PURPOSE: Generate html for page tip select box and select current option
		static function dhp_list_pages_for_tips()
		{ 
			$pages = get_pages();
			$options = '<option value="0" '. selected(get_option('tip_url'),0) . '>-- No Tip Page --</option>';
			foreach ( $pages as $page ) {
				$option  = '<option value="' . $page->ID . '" '. selected(get_option('tip_url'),$page->ID) . '>';
				$option  .= $page->post_title;
				$option  .= '</option>';
				$options .= $option;
			}
			return $options;
		} // dhp_list_pages_for_tips()

			// Template for settings page
		static function settings_page_template()
		{
			ob_start(); ?>
				<div class="wrap">
					<h2>DH Press Global Options</h2>
					<form method="post" action="options.php"> 
						<?php @settings_fields( 'dhp_global_settings-group' ); ?>
						<?php @do_settings_fields( 'dhp_global_settings-group' ); ?>

						<table class="form-table">
							<tr valign="top">
								<th scope="row"><label for="tip_url">Site Tip Page</label></th>
								<td>
									<select name="tip_url" id="tip_url">
										<?php echo self::dhp_list_pages_for_tips(); ?>
									</select>
									Select the post whose text is used for global tips here.
								</td>
							</tr>

							<tr valign="top">
								<th scope="row"><label for="timeout_duration">Timeout Duration(mins)</label></th>
								<td><input type="text" name="timeout_duration" id="timeout_duration" value="<?php echo get_option( 'timeout_duration' ); ?>" />
									Leave blank if no timeout is needed.</td>
							</tr>
							<tr valign="top">
								<th scope="row"><label for="redirect_url">Redirect URL</label></th>
								<td><input type="text" name="redirect_url" id="redirect_url" value="<?php echo get_option( 'redirect_url' ); ?>" />
									Enter URL to redirect site when timeout occurs.</td>
							</tr>
							<tr valign="top">
								<th scope="row"><label for="redirect_url">Kiosk User Agent</label></th>
								<td><input type="text" name="kiosk_useragent" id="kiosk_useragent" value="<?php echo get_option( 'kiosk_useragent' ); ?>" /> 
									Only block external URLs for a specific device. Leave empty if enabled for all devices.</td>
							</tr>
							<tr valign="top">
								<th scope="row"><label for="redirect_url">Block External URLs </label></th>
								<td>Enter comma separated list of domains that you wish to block on kiosk (only applies if User Agent is set above).<br/>
									<textarea name="kiosk_blockurls" id="kiosk_blockurls"><?php echo get_option( 'kiosk_blockurls' ); ?></textarea> 
								</td>
							</tr>
						</table>

						<?php @submit_button(); ?>
					</form>
				</div> 
			<?php
			return ob_get_clean();
		}

			// Add handlebars template to footer for tip modal
		static function dhp_tip_page_content()
		{
			echo self::dhp_tip_page_template();			
		}

			// PURPOSE: Load template in html to be called by javascript and embed in
			//				HTML markup for Foundation modal
		static function dhp_tip_page_template()
		{
			$tip_page = get_option('tip_url');
			if ($tip_page) {
				$tip_obj = get_post($tip_page);
				ob_start(); ?>
				<div id="tipModal" class="reveal-modal medium" data-reveal>
				  <div class="modal-content">
					<div class="modal-header">
					  <h1><?php echo $tip_obj->post_title;?></h1>
					</div>
					<div class="modal-body clearfix">
						<?php remove_filter( 'the_content', 'dhp_mod_page_content' ); ?>
						<?php echo apply_filters( 'the_content', $tip_obj->post_content ); ?>
					</div>
					<div class="reveal-modal-footer clearfix ">
					  <ul class="button-group right"><li><a class="button close-tip" >Close</a></li></ul>
					</div>
				  </div>
					<a class="close-reveal-modal close-tip">&#215;</a>
				</div>
				<?php
				return ob_get_clean();
			}
		} // dhp_tip_page_template()


			// Register scripts/styles used by global settings
		static function register_scripts() 
		{
			wp_register_script( 'dhp-global-settings-script', plugins_url( '/js/dhp-global-settings.js', dirname( __FILE__ ) ), array( 'jquery' ), DHP_PLUGIN_VERSION, true );
		}

		static function print_scripts() 
		{
			$global_tip = false;
			$screen_saver = false;

			global $post;
			if (get_option('tip_url')) {
				$global_tip = true;
			}
			wp_enqueue_script( 'jquery' );
			wp_enqueue_style( 'dhp-foundation-style', plugins_url('/lib/foundation-5.1.1/css/foundation.min.css',  dirname(__FILE__)));
			wp_enqueue_style( 'dhp-foundation-icons', plugins_url('/lib/foundation-icons/foundation-icons.css',  dirname(__FILE__)));
			wp_enqueue_script( 'dhp-foundation', plugins_url('/lib/foundation-5.1.1/js/foundation.min.js', dirname(__FILE__)), 'jquery');
			wp_enqueue_script( 'dhp-modernizr', plugins_url('/lib/foundation-5.1.1/js/vendor/modernizr.js', dirname(__FILE__)), 'jquery');

			wp_enqueue_style( 'dhp-global-settings', plugins_url('/css/dhp-global-settings.css',  dirname(__FILE__)), '', DHP_PLUGIN_VERSION);

				// Load scripts
			wp_enqueue_script( 'dhp-global-settings-script' );
				// Print settings to page
			wp_localize_script( 'dhp-global-settings-script', 'dhpGlobals', array(
				'global_tip' => $global_tip,
				'timeout_duration' => get_option('timeout_duration'),
				'redirect_url' => get_option('redirect_url'),
				'kiosk_useragent' => get_option('kiosk_useragent'),
				'kiosk_blockurls' => get_option('kiosk_blockurls')
			) );
		}
	} // class DHPressSettings
}

if (class_exists( 'DHPressSettings')) {
	DHPressSettings::init();
}