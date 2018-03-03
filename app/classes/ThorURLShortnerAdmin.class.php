<?php 
/**
 * Admin Main Class
 *
 * @param void
 *
 * @return void
 */

if (!class_exists('ThorURLShortnerAdmin')) {
	
	class ThorURLShortnerAdmin {

		public function __construct() {

			// Check if Admin - only load admin scripts when admin is used
			if(is_admin()) {
				// Activation and deactivation hook.
	    		register_activation_hook(WP_PLUGIN_DIR . '/wp-thor-url-shortener/wp-thor-url-shortener.php',  array($this, 'thor_url_shortener_activate'));
				register_deactivation_hook( WP_PLUGIN_DIR . '/wp-thor-url-shortener/wp-thor-url-shortener.php',  array($this, 'thor_url_shortener_deactivate' ));

				// Admin Menu
				add_action('admin_menu', array($this, 'thor_url_shortener_admin_menu'));

				// Software Licensing and Updates
				add_action('admin_init', array($this, 'edd_thor_urlshort_register_option'));

				// Activate, check or deactivate Licenses
				add_action('admin_init', array($this, 'edd_thor_urlshort_activate_license'));
				add_action('admin_init', array($this, 'edd_thor_urlshort_deactivate_license'));
				add_action( 'admin_notices', array($this, 'edd_thor_urlshort_admin_notices'));

				// Plugin Settings
				add_action('admin_init', array($this, 'thor_url_shortener_settings_init'));

				add_action('wpmu_new_blog', array($this, 'thor_url_shortener_on_new_blog'), 10, 6); 		
				add_action('activate_blog', array($this, 'thor_url_shortener_on_new_blog'), 10, 6);
				add_action('admin_enqueue_scripts', array($this, 'thor_url_shortener_admin_head') );

				add_filter('admin_footer_text', array($this, 'urlshortener_admin_footer'));
			}

			add_action('wp_enqueue_scripts', array($this, 'thor_url_shortener_head') );
			
			add_action('widgets_init', array($this, 'thor_url_shortener_register_widget'));
			
			add_action('add_meta_boxes', array($this, 'thor_url_shortener_add_to_post'), 10, 2 );

			add_action('wp_footer', array($this, 'thor_url_shortener_ajax_js'), 10, 2 );

			$options = get_option('thor_url_shortener_settings');

			if($options['thor_url_shortener_shortcode'] && in_array($options['thor_url_shortener_shortcode'],array('shorten','short_url','srt'))){
				add_shortcode($options['thor_url_shortener_shortcode'], array($this,'thor_url_shortener_shortcode'));
			}
			
			if($options['thor_url_shortener_comment']){
				add_action('wp_footer', array($this,'thor_url_shortener_comment_js'));
			}
			
			add_shortcode('show_shortener_form', array($this,'thor_url_shortener_post_form'));
		}

		/* ***************************** PLUGIN (DE-)ACTIVATION *************************** */

		/**
		 * Run single site / network-wide activation of the plugin.
		 *
		 * @param bool $networkwide Whether the plugin is being activated network-wide.
		 */
		function thor_url_shortener_activate() {

		    $networkwide = ($_SERVER['SCRIPT_NAME'] == '/wp-admin/network/plugins.php')?true:false;

			if ( ! is_multisite() || ! $networkwide ) {
				ThorURLShortnerAdmin::_thor_url_shortener_activate();
			}
			else {
				/* Multi-site network activation - activate the plugin for all blogs */
				ThorURLShortnerAdmin::thor_url_shortener_network_activate_deactivate( true );
			}
		}

		/**
		 * Run single site / network-wide de-activation of the plugin.
		 *
		 * @param bool $networkwide Whether the plugin is being de-activated network-wide.
		 */
		function thor_url_shortener_deactivate() {

		    $networkwide = ($_SERVER['SCRIPT_NAME'] == '/wp-admin/network/plugins.php')?true:false;

			if ( ! is_multisite() || ! $networkwide ) {
				ThorURLShortnerAdmin::_thor_url_shortener_deactivate();
			}
			else {
				/* Multi-site network activation - de-activate the plugin for all blogs */
				ThorURLShortnerAdmin::urlshortener_network_activate_deactivate( false );
			}
		}

		/**
		 * Run network-wide (de-)activation of the plugin
		 *
		 * @param bool $activate True for plugin activation, false for de-activation.
		 */
		function thor_url_shortener_network_activate_deactivate( $activate = true ) {
			global $wpdb;

			$network_blogs = $wpdb->get_col( $wpdb->prepare( "SELECT blog_id FROM $wpdb->blogs WHERE site_id = %d", $wpdb->siteid ) );

			if ( is_array( $network_blogs ) && $network_blogs !== array() ) {
				foreach ( $network_blogs as $blog_id ) {
					switch_to_blog( $blog_id );

					if ( $activate === true ) {
						ThorURLShortnerAdmin::_thor_url_shortener_activate();
					}
					else {
						ThorURLShortnerAdmin::_thor_url_shortener_deactivate();
					}

					restore_current_blog();
				}
			}
		}

		/**
		 * On deactivation
		 */
		function _thor_url_shortener_deactivate() {

			global $wpdb;

		    if (function_exists('is_multisite') && is_multisite()) {
		        // check if it is a network activation - if so, run the activation function 
		        // for each blog id
		        if ($networkwide) {
		            $old_blog = $wpdb->blogid;
		            // Get all blog ids
		            $blogids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->blogs}");
		            foreach ($blogids as $blog_id) {
		                switch_to_blog($blog_id);
		            }
		            switch_to_blog($old_blog);
		            return;
		        }   
		    }

		    // Delete Licenses Key
			delete_option('edd_thor_urlshort_license_key' );
			delete_option('edd_thor_urlshort_license_status' );

			// Delete plugin options
			delete_option('thor_url_shortener_settings');
			delete_option('widget_thor_shortener_widget');

			do_action( 'thor_url_shortener_deactivate' );
		}

		/**
		 * Run activation routine on creation / activation of a multisite blog if WP THOR is activated network-wide.
		 *
		 * Will only be called by multisite actions.
		 *
		 * @internal Unfortunately will fail if the plugin is in the must-use directory
		 * @see      https://core.trac.wordpress.org/ticket/24205
		 *
		 * @param int $blog_id Blog ID.
		 */
		function thor_url_shortener_on_new_blog( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {

			global $wpdb;

			if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
				require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
			}
		 
			if (is_plugin_active_for_network('wp-thor-url-shortener/wp-thor-url-shortener.php')) {
				$old_blog = $wpdb->blogid;
				switch_to_blog($blog_id);
				ThorURLShortnerAdmin::urlshortener_activate();
				switch_to_blog($old_blog);
			}
		}

		/**
		 * Runs on activation of the plugin.
		 *
		 * @param void
		 *
		 * @return void
		 */
		function _thor_url_shortener_activate() {
		    // Create new table if necessary

			add_option ( 'thor_url_shortener_settings','post');
			// Activate
			do_action( 'thor_url_shortener_activate' );
		}

		/**
		 * Set The Header
		 *
		 * @param void
		 *
		 * @return void
		 */	
		public function thor_url_shortener_admin_head() {

			// CSS and JS Files used by thor-url-shortener
			wp_enqueue_style( 'thor-url-shortener-admin-style', THORURLSHORTENER_PLUGIN_URL . '/app/views/css/style.css' );

			wp_enqueue_style('thor-url-shortener-admin-stylesheet', THORURLSHORTENER_PLUGIN_URL . '/app/views/css/thor-url-shortener-admin-style.css');

			wp_enqueue_style( 'thor-url-shortener-shortener-font-awesome', THORURLSHORTENER_PLUGIN_URL . '/app/views/css/font-awesome.css' );
			
			wp_enqueue_style( 'thor-url-shortener-bootstrap-style', THORURLSHORTENER_PLUGIN_URL . '/app/views/css/bootstrap.css' );

			wp_enqueue_style( 'thor-url-shortener-bootstrap-theme-style', THORURLSHORTENER_PLUGIN_URL . '/app/views/css/bootstrap-theme.css' );

			wp_enqueue_script( 'thor-url-shortener-bootstrap-js', THORURLSHORTENER_PLUGIN_URL . '/app/views/js/bootstrap.js' );
			wp_enqueue_script('thor-url-shortener-admin-js', THORURLSHORTENER_PLUGIN_URL . '/app/views/js/thor-url-shortener-admin-js.js');
		}


		/**
		 * Set The Header
		 *
		 * @param void
		 *
		 * @return void
		 */	
		public function thor_url_shortener_head(){
			
			wp_enqueue_style('thor-url-shortener-styles-stylesheet', THORURLSHORTENER_PLUGIN_URL . '/app/views/css/thor-url-shortener-styles-stylesheet.css' );	

			wp_enqueue_script('thor-url-shortener-js', THORURLSHORTENER_PLUGIN_URL . '/app/views/js/thor-url-shortener-js.js' );
		}


		/**
		 * Add Admin Menues
		 *
		 * @param void
		 *
		 * @return void
		 */	
		public function thor_url_shortener_admin_menu(){
			add_menu_page ( 'Thor URL Shortener', 'Thor URL Shortener', 'manage_options', 'thor_url_shortener_admin', array($this, 'thor_url_shortener_admin'), plugins_url( 'wp-thor-url-shortener/app/views/images/url_link.png' ), 6 );
		}
		
		/**
		 * Set Admin Menues
		 *
		 * @param void
		 *
		 * @return void
		 */			
		public function thor_url_shortener_admin(){
			//current tab
			if (isset($_GET['tab'])){
				$tab = $_GET['tab'];
			} else {
				$tab = 'general_settings';
			}
			
			//url admin
			$url = get_admin_url() . 'admin.php?page=thor_url_shortener_admin';

			//all tabs available
			$tabs_arr = array('general_settings' => 'General Settings',
								'licenses'	=> 'Licenses',
								'support' => 'Support',
								'hireus' => 'Services',
								'pluginsthemes'	=> 'Plugins/Themes'				
							  );
			
			//include dashboard header
			require_once THORURLSHORTENER_PLUGIN_PATH . '/app/views/dashboard-head.php';
			
			switch ($tab){
				case 'general_settings':
					require_once THORURLSHORTENER_PLUGIN_PATH . '/app/views/settings.php';
				break;
				case 'support':
					require_once THORURLSHORTENER_PLUGIN_PATH . '/app/views/support.php';
				break;
				case 'hireus':
					require_once THORURLSHORTENER_PLUGIN_PATH . '/app/views/hireus.php';
				break;
				case 'licenses':
					require_once THORURLSHORTENER_PLUGIN_PATH . '/app/views/licenses.php';
					break;
				case 'pluginsthemes':
					require_once THORURLSHORTENER_PLUGIN_PATH . '/app/views/pluginsthemes.php';
				break;
			}
		}

		/**
		 * Set The Settings Parameters
		 *
		 * @param void
		 *
		 * @return void
		 */	
		function thor_url_shortener_settings_init(  ) {

			register_setting('thor-url-shortener-settings', 'thor_url_shortener_settings' );

			add_settings_section( 'thor_url_shortener_settings_section', '', array( $this, 'thor_url_shortener_settings_section_callback' ), 'thor-url-shortener-settings', 'section_general' );
			
			add_settings_field( 'thor_url_shortener_url',  __('URL of the moln.co', 'thor_url_shortener'), array( $this, 'thor_url_shortener_render' ), 'thor-url-shortener-settings', 'thor_url_shortener_settings_section' );

			add_settings_field('thor_url_shortener_apikey', __('Enter Your API key', 'thor_url_shortener'), array( $this, 'thor_url_shortener_apikey_render' ), 'thor-url-shortener-settings', 'thor_url_shortener_settings_section');

			add_settings_field('thor_url_shortener_comment', __('Comment URL Shortening', 'thor_url_shortener'),  array( $this, 'thor_url_shortener_comment_render' ), 'thor-url-shortener-settings', 'thor_url_shortener_settings_section');

			add_settings_field('thor_url_shortener_shortcode', __('Shortcode', 'thor_url_shortener'), array( $this, 'thor_url_shortener_shortcode_render' ), 'thor-url-shortener-settings', 'thor_url_shortener_settings_section');

			add_settings_field('thor_url_shortener_type', __('Choose Shortening Type for the Short Code', 'thor_url_shortener'),  array( $this, 'thor_url_shortener_type_render' ), 'thor-url-shortener-settings', 'thor_url_shortener_settings_section');

			add_settings_field('thor_url_shortener_theme', __('Default Widget and Ajax Form Theme', 'thor_url_shortener'), array( $this, 'thor_url_shortener_theme_render' ), 'thor-url-shortener-settings', 'thor_url_shortener_settings_section');

			add_settings_field( 'thor_url_shortener_share',  __('Share Twitter', 'thor_url_shortener'), array( $this, 'thor_url_shortener_share_render' ), 'thor-url-shortener-settings', 'thor_url_shortener_settings_section' );
		}

		/**
		 * Set The Parameters
		 *
		 * @param void
		 *
		 * @return void
		 */	
		function thor_url_shortener_render() { 
			$options = get_option('thor_url_shortener_settings');
			?>
			<input type='text' name='thor_url_shortener_settings[thor_url_shortener_url]' size="45" value='<?php echo $options['thor_url_shortener_url']; ?>' />
			<?php
		}

		/**
		 * Set The Parameters
		 *
		 * @param void
		 *
		 * @return void
		 */	
		function thor_url_shortener_apikey_render() { 
			$options = get_option('thor_url_shortener_settings');
			?>
			<input type='text' name='thor_url_shortener_settings[thor_url_shortener_apikey]' size="45" value='<?php echo $options['thor_url_shortener_apikey']; ?>' />
			<?php
		}

		/**
		 * Set The Parameters
		 *
		 * @param void
		 *
		 * @return void
		 */	
		function thor_url_shortener_comment_render() { 
			$options = get_option('thor_url_shortener_settings');

			$selected = $options['thor_url_shortener_comment'];
			
			echo ' <select id="thor_url_shortener_comment" name="thor_url_shortener_settings[thor_url_shortener_comment]"> ';

			echo ' <option '; 
			if ('0' == $selected) echo 'selected="selected"'; 
			echo ' value="0">'. __( 'Disable', 'thor_url_shortener' ) .'</option>';

			echo '<option '; 
			if ('1' == $selected) echo 'selected="selected"'; 
			echo ' value="1">'. __( 'Enabled', 'thor_url_shortener' ) .'</option>';

			echo '</select>';

			if (isset($this->options['thor_url_shortener_comment'])) {
				esc_attr( $this->options['thor_url_shortener_comment']);
			}
		}

		/**
		 * Set The Parameters
		 *
		 * @param void
		 *
		 * @return void
		 */	
		function thor_url_shortener_shortcode_render() { 
			$options = get_option('thor_url_shortener_settings');

			$selected = $options['thor_url_shortener_shortcode'];
			
			echo ' <select id="thor_url_shortener_shortcode" name="thor_url_shortener_settings[thor_url_shortener_shortcode]"> ';

			echo ' <option '; 
			if ('0' == $selected) echo 'selected="selected"'; 
			echo ' value="0">'. __( 'Disable', 'thor_url_shortener' ) .'</option>';

			echo '<option '; 
			if ('shorten' == $selected) echo 'selected="selected"'; 
			echo ' value="shorten">'. __( '[shorten]', 'thor_url_shortener' ) .'</option>';

			echo '<option '; 
			if ('shorten_url' == $selected) echo 'selected="selected"'; 
			echo ' value="shorten_url">'. __( '[shorten_url]', 'thor_url_shortener' ) .'</option>';

			echo '<option '; 
			if ('srt' == $selected) echo 'selected="selected"'; 
			echo ' value="srt">'. __( '[srt]', 'thor_url_shortener' ) .'</option>';

			echo '</select>';

			if (isset($this->options['thor_url_shortener_shortcode'])) {
				esc_attr( $this->options['thor_url_shortener_shortcode']);
			}
		}

		/**
		 * Set The Parameters
		 *
		 * @param void
		 *
		 * @return void
		 */	
		function thor_url_shortener_type_render() { 
			$options = get_option('thor_url_shortener_settings');

			$selected = $options['thor_url_shortener_type'];
			
			echo ' <select id="thor_url_shortener_type" name="thor_url_shortener_settings[thor_url_shortener_type]"> ';

			echo ' <option '; 
			if ('php' == $selected) echo 'selected="selected"'; 
			echo ' value="php">'. __( 'PHP (Server-Side)', 'thor_url_shortener' ) .'</option>';

			echo '<option '; 
			if ('js' == $selected) echo 'selected="selected"'; 
			echo ' value="js">'. __( 'Javascript (Client-Side)', 'thor_url_shortener' ) .'</option>';

			echo '</select>';

			if (isset($this->options['thor_url_shortener_type'])) {
				esc_attr( $this->options['thor_url_shortener_type']);
			}
		}

				/**
		 * Set The Parameters
		 *
		 * @param void
		 *
		 * @return void
		 */	
		function thor_url_shortener_theme_render() { 
			$options = get_option('thor_url_shortener_settings');

			$selected = $options['thor_url_shortener_theme'];
			
			echo ' <select id="thor_url_shortener_theme" name="thor_url_shortener_settings[thor_url_shortener_theme]"> ';

			echo ' <option '; 
			if ('default' == $selected) echo 'selected="selected"'; 
			echo ' value="default">'. __( 'Default', 'thor_url_shortener' ) .'</option>';

			echo '<option '; 
			if ('tb' == $selected) echo 'selected="selected"'; 
			echo ' value="tb">'. __( 'Transparent &amp; Black', 'thor_url_shortener' ) .'</option>';

			echo '<option '; 
			if ('gb' == $selected) echo 'selected="selected"'; 
			echo ' value="gb">'. __( 'Green &amp; Blue', 'thor_url_shortener' ) .'</option>';

			echo '<option '; 
			if ('db' == $selected) echo 'selected="selected"'; 
			echo ' value="db">'. __( 'Dark Blue', 'thor_url_shortener' ) .'</option>';

			echo '<option '; 
			if ('dr' == $selected) echo 'selected="selected"'; 
			echo ' value="dr">'. __( 'Dark Red', 'thor_url_shortener' ) .'</option>';

			echo '<option '; 
			if ('lg' == $selected) echo 'selected="selected"'; 
			echo ' value="lg">'. __( 'Light Green', 'thor_url_shortener' ) .'</option>';

			echo '<option '; 
			if ('cc' == $selected) echo 'selected="selected"'; 
			echo ' value="cc">'. __( 'Custom', 'thor_url_shortener' ) .'</option>';

			echo '</select>';

			if (isset($this->options['thor_url_shortener_theme'])) {
				esc_attr( $this->options['thor_url_shortener_theme']);
			}
		}

		/**
		 * Set The Parameters
		 *
		 * @param void
		 *
		 * @return void
		 */	
		function thor_url_shortener_share_render() { 
			$options = get_option('thor_url_shortener_settings');
			?>
			<textarea name='thor_url_shortener_settings[thor_url_shortener_share]' id='thor_url_shortener_settings[thor_url_shortener_share]'><?php if($options['thor_url_shortener_share']) echo $options['thor_url_shortener_share'] ?></textarea>
			<label>
				The custom text to pre-fill tweets with (e.g. Shortened via <?php echo $options['thor_url_shortener_url'] ?>)
			</label>

			<?php
		}

		/**
		 * Settings Section Callbck
		 *
		 * @param void
		 *
		 * @return void
		 */	
		function thor_url_shortener_settings_section_callback() { 
			echo __('Required settings for the plugin and the App.', 'thor_url_shortener');
		}

		/**
		 * load the translations
		 *
		 * @param void
		 *
		 * @return void
		 */
		function thor_url_shortener_load_textdomain() {
			load_plugin_textdomain('thor_url_shortener', false, basename(dirname( __FILE__ )).'/lang'); 
		}

		/**
		 * Admin Footer.
		 *
		 * @param void
		 *
		 * @return void
		 */
		function urlshortener_admin_footer() {
			global $pagenow;
			
			if ($pagenow == 'admin.php') {
				$page = $_GET['page'];
				switch($page) {
					case 'thor_url_shortener_admin':
						echo "<div class=\"social-links alignleft\"><i>Created by <a href='http://thunderbeardesign.com'>ThunderBear Design</a></i>				
						<a href=\"http://twitter.com/tbearmarketing\" class=\"twitter\" target=\"_blank\"><span
						class=\"dashicons dashicons-twitter\"></span></a>
						<a href=\"https://www.facebook.com/thunderbeardesign\" class=\"facebook\"
				   target=\"_blank\"><span class=\"dashicons dashicons-facebook\"></span></a>
						<a href=\"https://thunderbeardesign.com/feed/\" class=\"rss\" target=\"_blank\"><span
						class=\"dashicons dashicons-rss\"></span></a>
						</div>";
						break;
					default:
						return;
				}
			}
		}

		/**
		 * Write debug info as a text file and download it.
		 *
		 * @param void
		 *
		 * @return void
		 */
		public function download_debuginfo_as_text() {

			global $wpdb, $wp_version;
			$debug_info = array();
			$debug_info['Home URL'] = esc_url( home_url() );
			$debug_info['Site URL'] = esc_url( site_url() );
			$debug_info['PHP'] = esc_html( PHP_VERSION );
			$debug_info['MYSQL'] = esc_html( $wpdb->db_version() );
			$debug_info['WordPress'] = esc_html( $wp_version );
			$debug_info['OS'] = esc_html( PHP_OS );
			if ( extension_loaded( 'imagick' ) ) {
				$imagickobj = new Imagick();
				$imagick    = $message = preg_replace( " #((http|https|ftp)://(\S*?\.\S*?))(\s|\;|\)|\]|\[|\{|\}|,|\"|'|:|\<|$|\.\s)#i", "'<a href=\"$1\" target=\"_blank\">$3</a>$4'", $imagickobj->getversion() );
			} else {
				$imagick['versionString'] = 'Not Installed';
			}
			$debug_info['Imagick'] = $imagick['versionString'];
			if ( extension_loaded( 'gd' ) ) {
				$gd = gd_info();
			} else {
				$gd['GD Version'] = 'Not Installed';
			}
			$debug_info['GD'] = esc_html( $gd['GD Version'] );
			$debug_info['[php.ini] post_max_size'] = esc_html( ini_get( 'post_max_size' ) );
			$debug_info['[php.ini] upload_max_filesize'] = esc_html( ini_get( 'upload_max_filesize' ) );
			$debug_info['[php.ini] memory_limit'] = esc_html( ini_get( 'memory_limit' ) );
			$active_theme = wp_get_theme();
			$debug_info['Theme Name'] = esc_html( $active_theme->Name );
			$debug_info['Theme Version'] = esc_html( $active_theme->Version );
			$debug_info['Author URL'] = esc_url( $active_theme->{'Author URI'} );

			$urlshortener_options = get_option( 'thor_url_shortener_settings' );
			$urlshortener_options = array_merge( $debug_info, $urlshortener_options );
			if( ! empty( $urlshortener_options ) ) {

				$url = wp_nonce_url('admin.php?page=thor_url_shortener_admin&tab=support&subtab=debuginfo','thor-debuginfo');
				if (false === ($creds = request_filesystem_credentials($url, '', false, false, null)) ) {
					return true;
				}
				
				if (!WP_Filesystem($creds)) {
					request_filesystem_credentials($url, '', true, false, null);
					return true;
				}
				
				global $wp_filesystem;
				$contentdir = trailingslashit($wp_filesystem->wp_content_dir());
				
				$in = '==============================================================================' . PHP_EOL;
				$in .= '================================== Debug Info ================================' . PHP_EOL;
				$in .=  '==============================================================================' . PHP_EOL . PHP_EOL . PHP_EOL;

				foreach ( $urlshortener_options as $option => $value ) {
					$in .= ucwords( str_replace( '_', ' ', $option ) ) . str_repeat( ' ', 50 - strlen($option) ) . wp_strip_all_tags( $value ) . PHP_EOL;
				}

				mb_convert_encoding($in, "ISO-8859-1", "UTF-8");
				
				if(!$wp_filesystem->put_contents($contentdir.'urlshortener_debuginfo.txt', $in, FS_CHMOD_FILE)) {
					echo 'Failed saving file';
				}
				return content_url()."/urlshortener_debuginfo.txt"; 
			}
		}

		/**
		 * Show debug_info.
		 *
		 * @access public
		 *
		 * @param  void
		 *
		 * @return void
		 */
		public function debug_info() {
			global $wpdb, $wp_version;
			$debug_info               = array();
			$debug_info['Home URL']   = esc_url( home_url() );
			$debug_info['Site URL']   = esc_url( site_url() );
			$debug_info['PHP']        = esc_html( PHP_VERSION );
			$debug_info['MYSQL']      = esc_html( $wpdb->db_version() );
			$debug_info['WordPress']  = esc_html( $wp_version );
			$debug_info['OS']         = esc_html( PHP_OS );
			if ( extension_loaded( 'imagick' ) ) {
				$imagickobj = new Imagick();
				$imagick    = $message = preg_replace( " #((http|https|ftp)://(\S*?\.\S*?))(\s|\;|\)|\]|\[|\{|\}|,|\"|'|:|\<|$|\.\s)#i", "'<a href=\"$1\" target=\"_blank\">$3</a>$4'", $imagickobj->getversion() );
			} else {
				$imagick['versionString'] = 'Not Installed';
			}
			$debug_info['Imagick'] = $imagick['versionString'];
			if ( extension_loaded( 'gd' ) ) {
				$gd = gd_info();
			} else {
				$gd['GD Version'] = 'Not Installed';
			}
			$debug_info['GD']                            = esc_html( $gd['GD Version'] );
			$debug_info['[php.ini] post_max_size']       = esc_html( ini_get( 'post_max_size' ) );
			$debug_info['[php.ini] upload_max_filesize'] = esc_html( ini_get( 'upload_max_filesize' ) );
			$debug_info['[php.ini] memory_limit']        = esc_html( ini_get( 'memory_limit' ) );
			$debug_info['Installed Plugins']             = $this->get_plugin_info();
			$active_theme                                = wp_get_theme();
			$debug_info['Theme Name']                    = esc_html( $active_theme->Name );
			$debug_info['Theme Version']                 = esc_html( $active_theme->Version );
			$debug_info['Author URL']                    = esc_url( $active_theme->{'Author URI'} );

			/* get all Settings */
			$urlshortener_options = get_option( 'thor_url_shortener_settings' );
			if ( is_array( $urlshortener_options ) ) {
				foreach ( $urlshortener_options as $option => $value ) {
					$debug_info[ ucwords( str_replace( '_', ' ', $option ) ) ] = $value;
				}
			}

			$this->debug_info = $debug_info;
		}

		/**
		 * Get plugin_info.
		 *
		 * @access public
		 *
		 * @param  void
		 *
		 * @return array 
		 */
		public function get_plugin_info() {
			$active_plugins = (array) get_option( 'active_plugins', array() );

			$urlshortener_plugins = array();
			foreach ( $active_plugins as $plugin ) {
				$plugin_data    = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
				$version_string = '';
				if ( ! empty( $plugin_data['Name'] ) ) {
					$urlshortener_plugins[] = esc_html( $plugin_data['Name'] ) . ' ' . esc_html__( 'by', 'urlshortener' ) . ' ' . $plugin_data['Author'] . ' ' . esc_html__( 'version', 'urlshortener' ) . ' ' . $plugin_data['Version'] . $version_string;
				}
			}
			if ( 0 === count( $urlshortener_plugins ) ) {
				return false;
			} else {
				return implode( ', <br/>', $urlshortener_plugins );
			}
		}


		//
		// Licensing and update functions
		//
		function edd_thor_urlshort_register_option() {
			// creates our settings in the options table
			register_setting('edd_thor_urlshort_license', 'edd_thor_urlshort_license_key', array($this, 'edd_thor_urlshort_sanitize_license'));
		}

		function edd_thor_urlshort_sanitize_license( $new ) {
			$old = get_option( 'edd_thor_urlshort_license_key' );
			if( $old && $old != $new ) {
				delete_option( 'edd_thor_urlshort_license_status' ); 
				// new license has been entered, so must reactivate
			}
			return $new;
		}

		/************************************
		* this illustrates how to activate a license key
		*************************************/

		function edd_thor_urlshort_activate_license() {

			// listen for our activate button to be clicked
			if( isset( $_POST['edd_thor_urlshort_license_activate'] ) ) {

				// run a quick security check
			 	if( ! check_admin_referer( 'edd_thor_urlshort_nonce', 'edd_thor_urlshort_nonce' ) )
					return; // get out if we didn't click the Activate button

				// retrieve the license from the database
				$license = trim( get_option( 'edd_thor_urlshort_license_key' ) );


				// data to send in our API request
				$api_params = array(
					'edd_action' => 'activate_license',
					'license'    => $license,
					'item_name'  => urlencode( THORURLSHORTENER_SL_ITEM_NAME ), // the name of our product in EDD
					'url'        => home_url()
				);

				// Call the custom API.
				$response = wp_remote_post( THORURLSHORTENER_SL_STORE_URL, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

				// make sure the response came back okay
				if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {

					if ( is_wp_error( $response ) ) {
						$message = $response->get_error_message();
					} else {
						$message = __( 'An error occurred, please try again.' );
					}
				} else {
					$license_data = json_decode( wp_remote_retrieve_body( $response ) );
					if ( false === $license_data->success ) {
						switch( $license_data->error ) {
							case 'expired' :
								$message = sprintf(
									__( 'Your license key expired on %s.' ),
									date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, current_time( 'timestamp' ) ) )
								);
								break;
							case 'revoked' :
								$message = __( 'Your license key has been disabled.' );
								break;
							case 'missing' :
								$message = __( 'Invalid license.' );
								break;
							case 'invalid' :
							case 'site_inactive' :
								$message = __( 'Your license is not active for this URL.' );
								break;
							case 'item_name_mismatch' :
								$message = sprintf( __( 'This appears to be an invalid license key for %s.' ), THORURLSHORTENER_SL_ITEM_NAME );
								break;
							case 'no_activations_left':
								$message = __( 'Your license key has reached its activation limit.' );
								break;
							default :

								$message = __( 'An error occurred, please try again.' );
								break;
						}
					}
				}

				// Check if anything passed on a message constituting a failure
				if ( ! empty( $message ) ) {
					$base_url = admin_url( 'plugins.php?page=' . THORURLSHORTENER_PLUGIN_LICENSE_PAGE );
					$redirect = add_query_arg( array( 'sl_activation' => 'false', 'message' => urlencode( $message ) ), $base_url );

					wp_redirect( $redirect );
					exit();
				}

				// $license_data->license will be either "valid" or "invalid"

				update_option( 'edd_thor_urlshort_license_status', $license_data->license );
				wp_redirect( admin_url( 'plugins.php?page=' . THORURLSHORTENER_PLUGIN_LICENSE_PAGE ) );
				exit();
			}
		}

		/***********************************************
		* Illustrates how to deactivate a license key.
		* This will decrease the site count
		***********************************************/

		function edd_thor_urlshort_deactivate_license() {

			// listen for our activate button to be clicked
			if( isset( $_POST['edd_thor_urlshort_license_deactivate'] ) ) {

				// run a quick security check
			 	if( ! check_admin_referer( 'edd_thor_urlshort_nonce', 'edd_thor_urlshort_nonce' ) )
					return; // get out if we didn't click the Activate button

				// retrieve the license from the database
				$license = trim( get_option( 'edd_thor_urlshort_license_key' ) );


				// data to send in our API request
				$api_params = array(
					'edd_action' => 'deactivate_license',
					'license'    => $license,
					'item_name'  => urlencode( THORURLSHORTENER_SL_ITEM_NAME ), // the name of our product in EDD
					'url'        => home_url()
				);

				// Call the custom API.
				$response = wp_remote_post( THORURLSHORTENER_SL_STORE_URL, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

				// make sure the response came back okay
				if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {

					if ( is_wp_error( $response ) ) {
						$message = $response->get_error_message();
					} else {
						$message = __( 'An error occurred, please try again.' );
					}

					$base_url = admin_url( 'plugins.php?page=' . THORURLSHORTENER_PLUGIN_LICENSE_PAGE );
					$redirect = add_query_arg( array( 'sl_activation' => 'false', 'message' => urlencode( $message ) ), $base_url );

					wp_redirect( $redirect );
					exit();
				}

				// decode the license data
				$license_data = json_decode( wp_remote_retrieve_body( $response ) );

				// $license_data->license will be either "deactivated" or "failed"
				if( $license_data->license == 'deactivated' ) {
					delete_option( 'edd_thor_urlshort_license_status' );
				}

				wp_redirect( admin_url( 'plugins.php?page=' . THORURLSHORTENER_PLUGIN_LICENSE_PAGE ) );
				exit();
			}
		}

		/************************************
		* check if a license key is still valid the updater does this for you,
		* so this is only needed if you
		* want to do something custom
		*************************************/

		public function edd_thor_urlshort_check_license() {

			global $wp_version;

			$license = trim( get_option( 'edd_thor_urlshort_license_key' ) );

			$api_params = array(
				'edd_action' => 'check_license',
				'license' => $license,
				'item_name' => urlencode( THORURLSHORTENER_SL_ITEM_NAME ),
				'url'       => home_url()
			);

			// Call the custom API.
			$response = wp_remote_post( THORURLSHORTENER_SL_STORE_URL, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

			if ( is_wp_error( $response ) )
				return false;

			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			if( $license_data->license == 'valid' ) {
				echo 'valid'; exit;
				// this license is still valid
			} else {
				echo 'invalid'; exit;
				// this license is no longer valid
			}
		}

		/**
		 * This is a means of catching errors from the activation method above and displaying it to the customer
		 */
		public function edd_thor_urlshort_admin_notices() {
			if ( isset( $_GET['sl_activation'] ) && ! empty( $_GET['message'] ) ) {

				switch( $_GET['sl_activation'] ) {

					case 'false':
						$message = urldecode( $_GET['message'] );
						?>
						<div class="error">
							<p><?php echo $message; ?></p>
						</div>
						<?php
						break;

					case 'true':
					default:
						// Developers can put a custom success message here for when activation is successful if they way.
						break;
				}
			}
		}

		//
		// Plugin Specifics
		//

		/**
		 * Register Widget
		 * @since v1.0
		 **/
		public static function thor_url_shortener_register_widget(){
			register_widget('thor_shortener_widget');  
		}

		/**
		 * JS for Comments 
		 * @since 1.0
		 **/
		public static function thor_url_shortener_comment_js() {
			$options = get_option('thor_url_shortener_settings');

			$url= $options['thor_url_shortener_url'];	
			$key = $options['thor_url_shortener_apikey'];

			echo "<script type='text/javascript' id='urlshortener-comment'>
						jQuery('#comments a').shorten({ 
						 	url:'".$url."', 
							key:'".$key."'
						});
					</script>";
		}	

		/**
		 * Shortcode Function
		 * @since v1.0
		 **/
		public static function thor_url_shortener_shortcode($atts,$content){
			$link=(isset($atts["link"]) && $atts["link"])?true:false;
		  return ThorURLShortnerAdmin::thor_url_shortener_shorten($content,$link);
		}	

		/**
		 * Shorten URL
		 * @since v1.0
		 **/
		public function thor_url_shortener_shorten($url,$link=false){	
			$options = get_option('thor_url_shortener_settings'); 

			$url= $options['thor_url_shortener_url'];	
			$key = $options['thor_url_shortener_apikey'];

			if($options["thor_url_shortener_type"]=="php"){
		      $short=ThorURLShortnerAdmin::thor_http_request($url."api?api=".$key."&url=".strip_tags(trim($url)));
		      $short=json_decode($short,TRUE);
		      if(!$short["error"]){
		          if($link){
					return "<a href='{$short["short"]}' target='_blank'>{$short["short"]}</a>";
		          }else{
		          	return $short["short"];
		          }
		      }
				}
				if($options["thor_url_shortener_type"]=="js") {
//					add_action('wp_footer', array($this, 'thor_url_shortener_ajax_js'), 10, 2 );
					return "<a href='$url' target='_blank' rel='nofollow' class='Thor_Short_Link_JS'>$url</a>";		
				}
		}
		/**
		 * Send HTTP Request
		 * @since v1.0
		 **/	
		public function thor_http_request($url){
			if(in_array('curl', get_loaded_extensions())){			
				$curl = curl_init();
				curl_setopt_array($curl, array(
				    CURLOPT_RETURNTRANSFER => 1,
				    CURLOPT_URL => $url,
				    CURLOPT_USERAGENT => 'WP Thor URL Shortener'
				));
				$resp = curl_exec($curl);
				curl_close($curl);		
				return $resp;
			}

			if(ini_get('allow_url_fopen')){
				return @file_get_contents($url);
			}
		}
		
		/**
		 * JS for Post URLs
		 * @since 1.0
		 **/
		public static function thor_url_shortener_ajax_js(){

			$options = get_option('thor_url_shortener_settings');

			echo "<script type='text/javascript' id='Thor_ajax-js'>
					jQuery('.Thor_Short_Link_JS').shorten({ 
					 	url:'".$options['thor_url_shortener_url']."', 
						key:'".$options['thor_url_shortener_apikey']."'
					});
				</script>";
		}	


		/**
		 * Add meta box to post/page
		 * @since 1.0
		 **/
		public static function thor_url_shortener_add_to_post( $post_type, $post ) {
		    add_meta_box( 
		        'quick_url_shorten_admin',
		        __( 'URL Shortener', 'shortener-plugin'),
		        array($this,'thor_url_shortener_render_box'),
		        'post',
		        'normal',
		        'default'
		    );
		    add_meta_box( 
		        'quick_url_shorten_admin',
		        __( 'URL Shortener', 'shortener-plugin'),
		        array($this,'thor_url_shortener_render_box'),
		        'page',
		        'normal',
		        'default'
		    );	    
		    add_meta_box( 
		        'quick_url_shorten_admin',
		        __( 'URL Shortener', 'shortener-plugin'),
		        array($this,'thor_url_shortener_render_box'),
		        'custom-type',
		        'normal',
		        'default'
		    );	    
		}

		/**
		 * Rendered Box
		 * @since 1.0
		 **/
		public static function thor_url_shortener_render_box() {
			$options = get_option('thor_url_shortener_settings');

			echo '<div data-url="'.$options["thor_url_shortener_url"].'" id="quick_url_shorten_admin_form">
				<div id="quick_url_shorten_admin_message"></div>
				<label for="quick_url_shorten_admin_input">Long URL</label>
				<p><input type="text" style="width:300px; max-width:50%" id="quick_url_shorten_admin_input" name="quick_url_shorten_admin_url"autocomplete="off" value="">
				<button type="button" id="quick_url_shorten_admin_button" class="button">Shorten</button>
				</p>
				<p>
				<label for="quick_url_shorten_admin_custom">Custom Alias:</label>
				<input type="text" style="width:150px" id="quick_url_shorten_admin_custom" name="quick_url_shorten_admin_custom" autocomplete="off" value="">
				</p>
				<input type="hidden" id="quick_url_shorten_admin_api" value="'.$options["thor_url_shortener_apikey"].'"/>
				</div>';	
		}
	}

	/**
	 * Widget Class
	 **/
	class thor_shortener_widget extends WP_Widget {
	 	
		/**
		 * @var Configuraion
		 **/
		public static $config=array();

		/**
		 * Register widget with WordPress.
		 */
		function __construct() {
			parent::__construct(
				'thor_shortener_widget', // Base ID
				__( 'Thor URL Shortner', 'moln.co' ), // Name
				array( 'description' => __( 'A URL Shortner Widget', 'moln.co' ), ) // Args
			);
		}

		/**
		 * Register Widget
		 * @since 1.0
		 **/
	  function thor_shortener_widget() {  
	    $widget_ops = array( 'classname' => 'thor_shortener_widget', 'description' => __('Displays the frontend form for shortning URLs ', 'shortener-plugin') );   
	    $this->WP_Widget('thor_shortener_widget', __('URL Shortener Widget', 'shortener-plugin'), $widget_ops);  
	  }  

		/**
		 * Generate Widget Form 
		 * @since 1.0
		 **/		
		function form($instance) {
	    $instance = wp_parse_args( (array) $instance, array( 'title' => '','theme'=> '') );
	    $title = $instance['title'];    
		  echo '<p><label for="'.$this->get_field_id('title').'">Title: <input class="widefat" id="'.$this->get_field_id('title').'" name="'.$this->get_field_name('title').'" type="text" value="'.esc_attr($title).'" /></label></p>';
		  echo '<p><label for="'.$this->get_field_id('theme').'">Theme:</label><select class="widefat" name="'.$this->get_field_name('theme').'" data-id="'.$instance['theme'].'">';	
			echo '<option value="default" '.(empty($instance['theme'])?'selected':'').'>Default</option>';
			echo '<option value="tb" '.(($instance['theme']=='tb')?'selected':'').'>Transparent &amp; Black</option>';
			echo '<option value="gb" '.(($instance['theme']=='gb')?'selected':'').'>Green &amp; Blue</option>';
			echo '<option value="db" '.(($instance['theme']=='db')?'selected':'').'>Dark Blue</option>';
			echo '<option value="dr" '.(($instance['theme']=='dr')?'selected':'').'>Dark Red</option>';
			echo '<option value="lg" '.(($instance['theme']=='lg')?'selected':'').'>Light Green</option>';
			echo '<option value="cc" '.(($instance['theme']=='cc')?'selected':'').'>Custom</option>';
		  echo '</select></p>';
		}

		/**
		 * Update Settings
		 * @since 1.0
		 **/
	  function update($new_instance, $old_instance){
	    $instance = $old_instance;
	    $instance['title'] = $new_instance['title'];
	    $instance['theme'] = $new_instance['theme'];
	    return $instance;
	  }

	  /**
	   * In-Post/In-Page Forms
	   * @since 1.0
	   **/
		public static function thor_url_shortener_post_form($atts,$content) {
			$options = get_option('thor_url_shortener_settings');

			$URI = $options['thor_url_shortener_url'];	
			$theme = $options['thor_url_shortener_theme'];	
			require_once THORURLSHORTENER_PLUGIN_PATH . '/app/views/form.php';
		}

		/**
		 * In Template Widget
		 * @since 1.0
		 **/
	  function widget($args, $instance) {
	  	
	  	$options = get_option('thor_url_shortener_settings');

	    extract($args, EXTR_SKIP);
	 
	    echo $before_widget;
	    $title = empty($instance['title']) ? ' ' : apply_filters('widget_title', $instance['title']);
	    $theme = empty($instance['theme']) ? $options['thor_url_shortener_theme'] : $instance['theme'];
	 
	    if (!empty($title))	echo $before_title . $title . $after_title;
				$URI = $options['thor_url_shortener_url'];
				require_once THORURLSHORTENER_PLUGIN_PATH . '/app/views/form.php';
		    echo $after_widget;
		  }
		}
} //end of if class exists