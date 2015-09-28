<?php
// PURPOSE: Include settings for useful addins that are used site wide and not just on dhp pages.
//				Site Tip page: Selecting a post page that provides the text for global help
//				Timeout duration: the number of minutes of inactivity that leads to timeout condition
//				Redirect URL: the destination webpage where browser is redirected upon a timeout
//				Kiosk User Agent: The name of device for which external URL blocking is applied (if any)
//				Block URLs: A comma-separated list of domains to check against the URLs on a webpage;
//					If any are found on the page, they are disabled.

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
			add_options_page(__('Global Options', 'dhpress'), __('DH Press Options', 'dhpress'), 'manage_options', 'dhp-global-options', array(__CLASS__, 'dhp_settings_page'));
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
			$options = '<option value="0" '. selected(get_option('tip_url'),0) . '>' . __('-- No Tip Page --', 'dhpress') . '</option>';
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
					<h2><?php _e('DH Press Global Options', 'dhpress'); ?></h2>
					<form method="post" action="options.php"> 
						<?php @settings_fields( 'dhp_global_settings-group' ); ?>
						<?php @do_settings_fields( 'dhp_global_settings-group' ); ?>

						<table class="form-table">
							<tr valign="top">
								<th scope="row"><label for="tip_url"><?php _e('Site Tip Page', 'dhpress'); ?></label></th>
								<td>
									<select name="tip_url" id="tip_url">
										<?php echo self::dhp_list_pages_for_tips(); ?>
									</select>
									<?php _e('Select the Page whose text is used for global tips here.', 'dhpress'); ?>
								</td>
							</tr>

							<tr valign="top">
								<th scope="row"><label for="timeout_duration"><?php _e('Timeout Duration (minutes)', 'dhpress'); ?></label></th>
								<td><input type="text" name="timeout_duration" id="timeout_duration" value="<?php echo get_option( 'timeout_duration' ); ?>" />
									<?php _e('Leave blank if no timeout is needed.', 'dhpress'); ?></td>
							</tr>
							<tr valign="top">
								<th scope="row"><label for="redirect_url"><?php _e('Redirect URL', 'dhpress'); ?></label></th>
								<td><input type="text" name="redirect_url" id="redirect_url" value="<?php echo get_option( 'redirect_url' ); ?>" />
									<?php _e('Enter URL to redirect site when timeout occurs.', 'dhpress'); ?></td>
							</tr>
							<tr valign="top">
								<th scope="row"><label for="redirect_url"><?php _e('Kiosk User Agent', 'dhpress'); ?></label></th>
								<td><input type="text" name="kiosk_useragent" id="kiosk_useragent" value="<?php echo get_option( 'kiosk_useragent' ); ?>" /> 
									<?php _e('Only block external URLs for a specific device or browser. Leave empty if enabled for all devices and browsers.', 'dhpress'); ?></td>
							</tr>
							<tr valign="top">
								<th scope="row"><label for="redirect_url"><?php _e('Block External URLs', 'dhpress'); ?></label></th>
								<td><?php _e('Enter comma separated list of domains that you wish to block if they appear in URLs on a webpage.', 'dhpress'); ?><br/>
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

			// Add template to footer for tip modal
		static function dhp_tip_page_content()
		{
			echo self::dhp_tip_page_template();			
		}

			// PURPOSE: Inject Help Tip text into HTML for DH Press pages (if set)
		static function dhp_tip_page_template()
		{
			global $post;

			if ($post->post_type == 'dhp-project') {
				$tip_page = get_option('tip_url');
				if ($tip_page) {
						// Load style for Help Tip
					wp_enqueue_style('dhp-global-settings', plugins_url('/css/dhp-global-settings.css',  dirname(__FILE__)), '', DHP_PLUGIN_VERSION);

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
						  <ul class="button-group right"><li><a class="button close-tip" ><?php _e('Close', 'dhpress'); ?></a></li></ul>
						</div>
					  </div>
						<a class="close-reveal-modal close-tip">&#215;</a>
					</div>
					<?php
					return ob_get_clean();
				} // if tip_page
			} // if dhp-project
		} // dhp_tip_page_template()


			// Register scripts/styles used by global settings
		static function register_scripts() 
		{
			wp_register_script( 'dhp-global-settings-script', plugins_url( '/js/dhp-global-settings.js', dirname( __FILE__ ) ), array( 'jquery' ), DHP_PLUGIN_VERSION, true );
		}

			// PURPOSE: Inject global settings data and code into DH Press Project pages
		static function print_scripts() 
		{
			global $post;
			if ($post->post_type == 'dhp-project') {
				$timeout = intval(get_option('timeout_duration'));
				$redirect = get_option('redirect_url');
				$blocks = get_option('kiosk_blockurls');
					// Only bother if there are settings to pass
				if (($timeout > 0 && ($redirect != FALSE && $redirect != '')) || ($blocks != FALSE && $blocks != '')) {
					wp_enqueue_script('jquery');

						// Load scripts
					wp_enqueue_script('dhp-global-settings-script');
						// Print settings to page
					wp_localize_script('dhp-global-settings-script', 'dhpGlobals', array(
						'timeout_duration' => $timeout,
						'redirect_url' => $redirect,
						'kiosk_useragent' => get_option('kiosk_useragent'),
						'kiosk_blockurls' => $blocks
					) );
				} // if there are settings
			} // if dhp-project
		}
	} // class DHPressSettings
}

if (class_exists('DHPressSettings')) {
	DHPressSettings::init();
}