<?php 
/*
 * Admin main class
 */
if (!class_exists('ThorAdmin')) {
	
	class ThorAdmin {

		public function __construct() {

			// Activation and deactivation hook.
    		register_activation_hook(WP_PLUGIN_DIR . '/wp-thor-fcm/wp-thor-fcm.php',  array($this, 'thor_fcm_activate'));
			register_deactivation_hook( WP_PLUGIN_DIR . '/wp-thor-fcm/wp-thor-fcm.php',  array($this, 'thor_fcm_deactivate' ));

			// Admin Menu
			add_action('admin_menu', array($this, 'thor_admin_menu') );
			add_action('admin_init', array($this, 'thor_fcm_settings_init') );

			add_action('rest_api_init', array( $this, 'addThorFCMRouteV2') );

			add_action( 'wpmu_new_blog',  array($this, 'thor_on_new_blog', 10, 6)); 		
			add_action( 'activate_blog',  array($this, 'thor_on_new_blog', 10, 6));
			
			add_action('admin_enqueue_scripts', array($this, 'thor_head') );			
//			add_action('init', array($this, 'thor_fcm_register'));
			
			add_action('plugins_loaded', array($this, 'thor_fcm_load_textdomain'));
			add_action('transition_post_status', array($this, 'thor_fcm_notification',2,3));
			add_action('add_meta_boxes', array($this, 'thor_fcm_add_metabox'));
			add_action('send_fcm', array($this, 'thor_sendFCM'));


			add_filter('admin_footer_text', array($this, 'thor_fcm_admin_footer'));
		}


		/* ***************************** PLUGIN (DE-)ACTIVATION *************************** */

		/**
		 * Run single site / network-wide activation of the plugin.
		 *
		 * @param bool $networkwide Whether the plugin is being activated network-wide.
		 */


		function thor_fcm_activate() {

		    $networkwide = ($_SERVER['SCRIPT_NAME'] == '/wp-admin/network/plugins.php')?true:false;

			if ( ! is_multisite() || ! $networkwide ) {
				ThorAdmin::_thor_fcm_activate();
			}
			else {
				/* Multi-site network activation - activate the plugin for all blogs */
				ThorAdmin::thor_fcm_network_activate_deactivate( true );
			}
		}

		/**
		 * Run single site / network-wide de-activation of the plugin.
		 *
		 * @param bool $networkwide Whether the plugin is being de-activated network-wide.
		 */
		function thor_fcm_deactivate() {

		    $networkwide = ($_SERVER['SCRIPT_NAME'] == '/wp-admin/network/plugins.php')?true:false;

			if ( ! is_multisite() || ! $networkwide ) {
				ThorAdmin::_thor_fcm_deactivate();
			}
			else {
				/* Multi-site network activation - de-activate the plugin for all blogs */
				ThorAdmin::fcm_network_activate_deactivate( false );
			}
		}

		/**
		 * Run network-wide (de-)activation of the plugin
		 *
		 * @param bool $activate True for plugin activation, false for de-activation.
		 */
		function thor_fcm_network_activate_deactivate( $activate = true ) {
			global $wpdb;

			$network_blogs = $wpdb->get_col( $wpdb->prepare( "SELECT blog_id FROM $wpdb->blogs WHERE site_id = %d", $wpdb->siteid ) );

			if ( is_array( $network_blogs ) && $network_blogs !== array() ) {
				foreach ( $network_blogs as $blog_id ) {
					switch_to_blog( $blog_id );

					if ( $activate === true ) {
						ThorAdmin::_thor_fcm_activate();
					}
					else {
						ThorAdmin::_thor_fcm_deactivate();
					}

					restore_current_blog();
				}
			}
		}

		/**
		 * On deactivation, flush the rewrite rules so XML sitemaps stop working.
		 */
		function _thor_fcm_deactivate() {

			global $wpdb;
						
			$wpdb->query(sprintf('DROP TABLE `' . $wpdb->prefix . 'thor_fcm_users`'));

			do_action( 'thor_fcm_deactivate' );
		}

		/**
		 * Run activation routine on creation / activation of a multisite blog if WP THOR FCM is activated network-wide.
		 *
		 * Will only be called by multisite actions.
		 *
		 * @internal Unfortunately will fail if the plugin is in the must-use directory
		 * @see      https://core.trac.wordpress.org/ticket/24205
		 *
		 * @param int $blog_id Blog ID.
		 */
		function thor_on_new_blog( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {

			global $wpdb;

			if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
				require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
			}
		 
			if (is_plugin_active_for_network('wp-thor-fcm/wp-thor-fcm.php')) {
				$old_blog = $wpdb->blogid;
				switch_to_blog($blog_id);
				ThorAdmin::fcm_activate();
				switch_to_blog($old_blog);
			}
		}

		/**
		 * Runs on activation of the plugin.
		 */
		function _thor_fcm_activate() {
		    // Create new table if necessary
			global $wpdb;

			if(!ThorAdmin::thor_fcm_check_table()) {

				$charset_collate = '';
				
				if (!empty($wpdb->charset)) {
					$charset_collate .= sprintf(' DEFAULT CHARACTER SET %s', $wpdb->charset);
				}
				if (!empty($wpdb->collate)) {
					$charset_collate .= ' COLLATE ' . $wpdb->collate;
				}		

				$sql_device_users = 'CREATE TABLE `' . $wpdb->prefix . 'thor_fcm_users` (
							`id` int(11) NOT NULL AUTO_INCREMENT,
		        			`fcm_regid` text,
							`os` text,
							`model` text,
							`send_msg` int,
		        			`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		        			PRIMARY KEY (`id`)
							)';

				$wpdb->query(sprintf($sql_device_users, $charset_collate));
								
			}		

		// Set optional variables

			add_option('thor_fcm_total_msg', 0);
			add_option('thor_fcm_fail_msg', 0);
			add_option('thor_fcm_success_msg', 0);

			do_action( 'thor_fcm_activate' );
		}


		/*
		 * Install the needed database table
		 */
		 
		function thor_fcm_check_table() {
			global $wpdb;
			
			$tables = $wpdb->get_col('SHOW TABLES');

			if (in_array($wpdb->prefix . 'thor_fcm_users', $tables) && in_array($wpdb->prefix . 'thor_fcm_users', $tables)) {
				return true;
			} else {
				return false;
			}
		}

		/* ***************************** SET THE HEADER *************************** */

		public function thor_head(){

			if (isset($_GET['page']) && $_GET['page']=='thor_admin'){

				wp_enqueue_style( 'thor-admin-style', THORFCM_PLUGIN_URL . '/app/views/css/style.css' );
				wp_enqueue_style( 'thor-font-awesome', THORFCM_PLUGIN_URL . '/app/views/css/font-awesome.css' );
				wp_enqueue_style( 'thor-bootstrap-style', THORFCM_PLUGIN_URL . '/app/views/css/bootstrap.css' );
				wp_enqueue_style( 'thor-bootstrap-theme-style', THORFCM_PLUGIN_URL . '/app/views/css/bootstrap-theme.css' );

				wp_enqueue_script('thor_chart', THORFCM_PLUGIN_URL . '/app/views/js/chart.js');
				wp_enqueue_script('thor_countUp', THORFCM_PLUGIN_URL . '/app/views/js/countUp.min.js');
				wp_enqueue_script( 'thor-bootstrap-js', THORFCM_PLUGIN_URL . '/app/views/js/bootstrap.js' );
				wp_enqueue_script( 'thor-jquery-flot-js', THORFCM_PLUGIN_URL . '/app/views/js/jquery.flot.js' );
				wp_enqueue_script( 'thor-jquery-flot-pie-js', THORFCM_PLUGIN_URL . '/app/views/js/jquery.flot.pie.js' );
				wp_localize_script( 'thor-admin-js', 'thor_base_url', get_site_url() );
				wp_localize_script( 'thor-admin-js', 'thor_admin_url', get_admin_url() . 'admin.php?page=thor_admin' );				
			}
		}

		public function thor_admin_menu(){
			add_menu_page ( 'FCM', 'FCM', 'manage_options', 'thor_admin', array($this, 'thor_admin') );
		}
		
		public function thor_admin(){
			//current tab
			if (isset($_GET['tab'])){
				$tab = $_GET['tab'];
			} else {
				$tab = 'dashboard';
			}
			
			//url admin
			$url = get_admin_url() . 'admin.php?page=thor_admin';

			//all tabs available
			$tabs_arr = array(
								'dashboard' => 'Dashboard',
								'message' => 'New Message',
								'device' => 'All Devices',
								'general_settings' => 'General Settings',							
							  );
			
			//some functions for admin dashboard
			//require_once PATH . 'admin/functions.php';
			//include dashboard header
			require_once THORFCM_PLUGIN_PATH . '/app/views/dashboard-head.php';
			
			switch ($tab){
				case 'message':
					require_once THORFCM_PLUGIN_PATH . '/app/controllers/send_message.php';
				break;
				case 'general_settings':
					require_once THORFCM_PLUGIN_PATH . '/app/views/settings.php';
				break;
				case 'device':
					require_once THORFCM_PLUGIN_PATH . '/app/controllers/devicelist.php';
				break;
				case 'dashboard':
					require_once THORFCM_PLUGIN_PATH . '/app/controllers/dashboard.php';
				break;
				case 'delete-device':
					require_once THORFCM_PLUGIN_PATH . '/app/controllers/delete-device.php';
				break;
				case 'list-device':
					require_once THORFCM_PLUGIN_PATH . '/app/controllers/listdevice.php';
				break;
			}
		}

//
// Set the settings parameters
//

		function thor_fcm_settings_init(  ) { 

			register_setting('thor-fcm-settings', 'thor_fcm_settings' );
			add_settings_section( 'thor_fcm_settings_section', '', array( $this, 'thor_fcm_settings_section_callback' ), 'thor-fcm-settings', 'section_general' );
			
			add_settings_field( 'thor_fcm_field_api_key',  __('Google API Key', 'thor_fcm'), array( $this, 'thor_fcm_api_key_render' ), 'thor-fcm-settings', 'thor_fcm_settings_section' );

			add_settings_field('thor_fcm_checkbox_spi', __('Send post notification', 'thor_fcm'), array( $this, 'thor_fcm_checkbox_spi_render' ), 'thor-fcm-settings', 'thor_fcm_settings_section');

			add_settings_field('thor_fcm_checkbox_abl', __('Display admin-bar link', 'thor_fcm'),  array( $this, 'thor_fcm_checkbox_abl_render' ), 'thor-fcm-settings', 'thor_fcm_settings_section');

			add_settings_field('thor_fcm_checkbox_debug', __('Show debug response', 'thor_fcm'), array( $this, 'thor_fcm_checkbox_debug_render' ), 'thor-fcm-settings', 'thor_fcm_settings_section');
		}

		function thor_fcm_api_key_render() { 
			$options = get_option('thor_fcm_settings');
			?>
			<input type='text' name='thor_fcm_settings[thor_fcm_field_api_key]' size="45" value='<?php echo $options['thor_fcm_field_api_key']; ?>' />
			<?php
		}

		function thor_fcm_checkbox_spi_render() { 
			$options = get_option('thor_fcm_settings');
			if(!isset($options['thor_fcm_checkbox_spi'])) { 
				$setting = 0; 
			}else {
				$setting = $options['thor_fcm_checkbox_spi'];
			}
			?>
			<input type='checkbox' name='thor_fcm_settings[thor_fcm_checkbox_spi]' value="1" <?php checked($setting, 1); ?> />
			<?php
		}

		function thor_fcm_checkbox_abl_render() { 
			$options = get_option('thor_fcm_settings');
			if(!isset($options['thor_fcm_checkbox_abl'])) { 
				$setting = 0; 
			}else {
				$setting = $options['thor_fcm_checkbox_abl'];
			}
			?>
			<input type='checkbox' name='thor_fcm_settings[thor_fcm_checkbox_abl]' value="1" <?php checked($setting, 1); ?> />
			<?php
		}

		function thor_fcm_checkbox_debug_render() { 
			$options = get_option('thor_fcm_settings');
			if(!isset($options['thor_fcm_checkbox_debug'])) { 
				$setting = 0; 
			}else {
				$setting = $options['thor_fcm_checkbox_debug'];
			}
			?>
			<input type='checkbox' name='thor_fcm_settings[thor_fcm_checkbox_debug]' value="0" <?php checked($setting, 1); ?> />
			<?php
		}

		function thor_fcm_settings_section_callback() { 
			echo __('Required settings for the plugin and the App.', 'thor_fcm');
		}

		// Register ToolBar
		function thor_fcm_toolbar() {
			$options = get_option('thor_fcm_settings');
			if(isset($options['thor_fcm_checkbox_abl']) && $options['thor_fcm_checkbox_abl'] != false && current_user_can('edit_posts')) {
				global $wp_admin_bar;
				$page = get_site_url().'/wp-admin/admin.php?page=thor_admin';
				$args = array(
					'id'     => 'thor_fcm',
					'title'  => '<img class="dashicons dashicons-cloud">FCM</img>', 'thor_fcm',
					'href'   =>  "$page" );
					
				$wp_admin_bar->add_menu($args);
			}
		}

		//
		// Operating System (OS)
		//
		function thor_fcm_os() {
			if(isset($_GET["os"])) {
				return $os = $_GET["os"];
			}else {
				return $os = 'not set';
			}
		}

		//
		// Mobile Phone Model
		//
		function thor_fcm_model() {
			if(isset($_GET["model"])) {
				return $model = $_GET["model"];
			}else {
				return $model = 'not set';
			}
		}

		// load the translations
		function thor_fcm_load_textdomain() {
			load_plugin_textdomain('thor_fcm', false, basename(dirname( __FILE__ )).'/lang'); 
		}

		// send notification for post update and new post
		function thor_fcm_notification($new_status, $old_status, $post) {
			if(!isset($_POST['thor_fcm_metabox_nonce']) || !wp_verify_nonce($_POST['thor_fcm_metabox_nonce'], 'thor_fcm_notification')) {
				return;
			}

			if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
				return;
			}
			
			if(!current_user_can('edit_post', get_the_ID($post) )) {
				return;
			}

			$options = get_option('thor_fcm_settings');
			$_POST['thor_fcm_notification'] = $_POST['thor_fcm_notification'] ? 'on' : 'off';	
			if($options['thor_fcm_checkbox_spi'] != false || $_POST['thor_fcm_notification'] == 'on') {
				
				$post_title = get_the_title($post);
				$post_url = get_permalink($post);	   
				$post_id = get_the_ID($post);
				$post_author = get_the_author_meta('display_name', $post->post_author);
				$message = $post_title . ";" . $post_url . ";". $post_id . ";" . $post_author . ";";
				$d;
				
				if($old_status == 'publish' && $new_status == 'publish' && 'post' == get_post_type($post)) {
					$d = ThorAdmin::thor_sendFCM($message, "update", 010, null, null, false);
					$_SESSION['thor_fcm_msg'] = $d;
				}else if($old_status != 'publish' && $new_status == 'publish' && 'post' == get_post_type($post)) {
					$d = ThorAdmin::thor_sendFCM($message, "new_post", 010, null, null, false);
					$_SESSION['thor_fcm_msg'] = $d;
				}
				
				session_commit();
				$_POST['thor_fcm_data'] = 1;
				add_filter('redirect_post_location', array($this, 'thor_fcm_metabox_notice_var'), 99);		
			}
		}

		// footer change
		function thor_fcm_admin_footer() {
			echo "<p><i>Created by <a href='http://thunderbeardesign.com'>ThunderBear Design</a></i></p>";
			return;
		}

		// Add the meta boxes for the editor
		function thor_fcm_add_metabox() {
			add_meta_box(
				'thor_fcm_notification',
				__('WP THOR FCM', 'thor_fcm' ),
				array($this, 'thor_fcm_metabox'),
				'post',
				'side',
				'high'
			);
		}

		function thor_fcm_metabox($post) {
			wp_nonce_field('thor_fcm_notification', 'thor_fcm_metabox_nonce');
			
			$options = get_option('thor_fcm_settings');
			if(!isset($options['thor_fcm_checkbox_spi'])) { 
				$check = 0; 
			}else {
				$check = $options['thor_fcm_checkbox_spi'];
			}
			
			?>
			<label class="selectit">
				<input name="thor_fcm_notification" id="thor_fcm_notification" type="checkbox" <?php checked($check, '1'); ?> />
					<?php _e('Send FCM Notification', 'thor_fcm'); ?>
			</label>
			<input name="thor_fcm_data" id="thor_fcm_data" type="hidden" />
			<?php
		}

		function thor_fcm_metabox_notice_var($location) {
			remove_filter('redirect_post_location', array($this, 'thor_fcm_metabox_notice_var'), 99);
			return add_query_arg(array('thor_fcm-data' => $_POST['thor_fcm_data']), $location);
		}

		// export db to csv
		function thor_fcm_export() {

			global $wpdb;
			$table_name = $wpdb->prefix.'thor_fcm_users';
			$query = "SELECT * FROM $table_name";
			$datas = $wpdb->get_results($query);
						
			$url = wp_nonce_url('admin.php?page=fcm-export','thor-fcm-export');
			if (false === ($creds = request_filesystem_credentials($url, '', false, false, null)) ) {
				return true;
			}
			
			if (!WP_Filesystem($creds)) {
				request_filesystem_credentials($url, '', true, false, null);
				return true;
			}
			
			
			global $wp_filesystem;
			$contentdir = trailingslashit($wp_filesystem->wp_content_dir());
			
			$in = "Databse ID;FCM Registration ID;Device OS;Device Model;Created At;Messages sent to this Device;\n";
			foreach($datas as $data) {
				$in .=  $data->id.";".$data->fcm_regid.";".$data->os.";".$data->model.";".$data->created_at.";".$data->send_msg."\n";
			}
			mb_convert_encoding($in, "ISO-8859-1", "UTF-8");
			
			if(!$wp_filesystem->put_contents($contentdir.'THOR-FCM-Export.csv', $in, FS_CHMOD_FILE)) {
				echo 'Failed saving file';
			}
			return content_url()."/FCM-Export.csv"; 
		}		


		// Check Variable NUM
		public function num($num) {
			if(empty($num)) {
				return $num = 0;
			}else {
				return $num;
			}
		}

		// render the device view with the data
		public function fcm_display_view($id, $regId, $os, $model, $date, $num, $total) {
			if(!current_user_can('edit_posts')) {
				wp_die(__('You have not sufficient capabilities to view this page', 'fcm'), __('Will Not WorK', 'fcm'));
			}
		
			$set_date = get_option('date_format');
			$set_time = get_option('time_format');
			$set = $set_date.' '.$set_time;
			
			$old_date_timestamp = strtotime($date);
			$new_date = date($set, $old_date_timestamp);
			
			// get view
			include THORFCM_PLUGIN_PATH . '/app/views/display_device_details.php';
			
			if(isset($_POST['msg'])) {
				$message = $_POST["msg"];
				$priority = $_POST["priority"];
				$time = $_POST["time"];
				print_r(thor_sendFCM($message, "message", $regId, $priority, $time));
			}
		}

	//
	// Support Functions
	// Get Extra Table Nav
	function extra_tablenav( $which ) {
		if($which == "top") {
			if(isset($_GET['action']) && $_GET['action'] == 'delete' ) { ?>
				<div id="message" class="updated">
					<p><strong><?php _e('Device with ID','thor_gcm'); ?><i><?php echo "&nbsp;";echo $_GET['device'];echo "&nbsp;"; ?></i><?php _e('deleted','thor_gcm'); ?></strong></p>
				</div> <?php
			} 
		}
		
		if($which == "bottom") {
			echo "Created by Pixelart Web and App Development";
		}
	}
   

	// Get Columns
	public function get_columns() {
        $columns = array(
			'fcm_regid'	=> __('FCM ID','thor_gcm'),
			'id'		=> __('Database ID','thor_gcm'),
            'os'		=> __('Device OS','thor_gcm'),
            'model'		=> __('Device Model','thor_gcm'),
            'created_at'=> __('Registered At','thor_gcm')			
        );

        return $columns;
    }
	
	// Get Hidden Columns
	public function get_hidden_columns(){
        return array();
    }
	
	// Get Seached
	private function get_seached($item) {
	
	}
	
	// Get sorted columns
	public function get_sortable_columns(){
        return array('os' => array('os', true),
		             'model' => array('model', true),
					 'created_at' => array('created_at', true),
					 'id' => array('id', true),
					 'fcm_regid' => array('gcm_regid', true));
    }
	
	// Get Column Default
	public function column_default($item, $column_name) {
        switch($column_name) {
			case 'fcm_regid':
            case 'id':
            case 'os':
            case 'model':
            case 'created_at':
			    if($item->$column_name != null){
                 return $item->$column_name;
			    }else {
				   return "";
			    }
        }
    }
	
	// Get Column FCM Regid
	public function column_fcm_regid($item) {
		$actions = array( 
			'view'    => sprintf('<a href="?page=%s&action=%s&device=%s">%s</a>',$_REQUEST['page'],'view',$item->id, __('View','thor_fcm')),
			'delete'  => sprintf('<a href="?page=%s&action=%s&device=%s">%s</a>',$_REQUEST['page'],'delete',$item->id, __('Delete','thor_fcm')) );
			
		$set = sprintf('<a class="row-title" href="?page=%s&action=%s&device=%s">%s</a>',$_REQUEST['page'],'view',$item->id,$item->fcm_regid);

		return sprintf('%1$s %2$s', $set, $this->row_actions($actions) );
    }

	// Column Created
	public function column_created_at($item) {
		$date = $item->created_at;
		$set_date = get_option('date_format');
		$set_time = get_option('time_format');
		$set = $set_date.' '.$set_time;
		
		$old_date_timestamp = strtotime($date);
		$new_date = date($set, $old_date_timestamp);   
		
		$txt = sprintf('%s', $new_date);

		return sprintf('%1$s', $txt);
    }
	
	//
	public function no_items() {
		_e('No registered Devices','thor_fcm');
	}

	// Display Devices
	function thor_fcm_display_devices() {
		if(!current_user_can('edit_posts')) {
			wp_die(__('You have not sufficient capabilities to view this page', 'thor_fcm'), __('No access', 'thor_fcm'));
		}
		
		$wp_list_table = new Device_List_Table();

		$wp_list_table->prepare_items();
		if(isset($_GET['action']) && $_GET['action'] == 'view') {
		}else {
			?>
			<div class="wrap">
				<h2><?php _e('FCM › All Devices','thor_fcm'); ?></h2>
					<form method="get">
						<input type="hidden" name="page" value="<?php echo $_REQUEST['page']; ?>" />
						<?php $wp_list_table->search_box('search', 'search_id'); ?>
					</form>
				<?php $wp_list_table->display(); ?>
			</div>
		<?php
		}
	}

	/*
	*
	* FCM Send Notification
	*
	*/
	function thor_sendFCM($message, $type, $regid, $priority = 0, $time = 2419200, $response = true) {
		global $wpdb;
		$table_name = $wpdb->prefix.'thor_fcm_users';
		$options = get_option('thor_fcm_settings');
	    $apiKey = $options['thor_fcm_field_api_key'];
	    $url = 'https://fcm.googleapis.com/fcm/send';
		$result;
		$id;
		$inf;
		
		if($regid == 010) {
			$id = ThorAdmin::thor_getIds();
		}else {
			$id = $regid;
		}

		if($regid == 010 && $id >= 1000){

			$newId = array_chunk($id, 1000);
			foreach ($newId as $inner_id) {
				$fields = array(
					'delay_while_idle' => true,
					'time_to_live' => intval($time),
	        		'registration_ids' => $inner_id,
					'priority' => intval($priority),
	        		'data' => array($type => $message) 
				);
				
				$headers = array(
	    			'Authorization' => 'key=' . $apiKey,
	    			'Content-Type' => 'application/json'
				);

				$result = wp_remote_post($url, array(
					'method' => 'POST',
					'headers' => $headers,
					'httpversion' => '1.0',
					'sslverify' => false,
					'body' => json_encode($fields) )
				);
			}
		} else {

			$fields = array(
				'delay_while_idle' => true,
				'time_to_live' => intval($time),
	        	'registration_ids' => array($id),
				'priority' => intval($priority),
	        	'data' => array($type => $message)
			);
			
			$headers = array(
	    		'Authorization' => 'key=' . $apiKey,
	    		'Content-Type' => 'application/json'
			);

			$result = wp_remote_post($url, array(
				'method' => 'POST',
				'headers' => $headers,
				'httpversion' => '1.0',
				'sslverify' => false,
				'body' => json_encode($fields))
			);
		}

		if ( is_wp_error( $response ) ) {
   			$error_message = $response->get_error_message();
   			echo "Something went wrong: $error_message";
   		}
	    $msg = $result['body'];

	    $answer = json_decode($msg);
	    
		if(!empty($answer)) {
			ThorAdmin::thor_fcm_check($answer);
			$success = $answer->{'success'};
			$fail = $answer->{'failure'};
		
			// Successful Messages
			$success_num = get_option('thor_fcm_success_msg', 0);
			update_option('thor_fcm_success_msg', $success_num+$success);

			// Failed Messages
			$fail_num = get_option('thor_fcm_fail_msg', 0);
			update_option('thor_fcm_fail_msg', $fail_num+$fail);
			
			// Total Messages
			$total_msg = get_option('thor_fcm_total_msg', 0);
			update_option('thor_fcm_total_msg', $total_msg+1);
			
			$ids = ThorAdmin::thor_getIds();
			for($i=0; $i < count($ids); $i++) {
				$temp = $ids[$i];

				// Call the Model to update total messages
				include THORFCM_PLUGIN_PATH . '/app/models/update_sent_messages.php';
			}
		}

		$options = get_option('thor_fcm_settings');
		if(isset($options['thor_fcm_checkbox_debug']) && $options['thor_fcm_checkbox_debug'] != false) {
			$inf = array('num' => 1, 'msg' => $message, 'debug' => $msg);
		} else if($answer != null) {
			$inf = array('num' => 2, 'msg' => $message, 'success' => $success, 'fail' => $fail);
		} else {
			$inf = array('num' => 3, 'msg' => __('Couldn\'t send. Try again or check FCM Server status','thor_fcm'));
		}
		
		if($response) {
			ThorAdmin::thor_fcm_notices($inf);
		} else {
			return $inf;
		}
	}


	/*
	*
	* Get all IDs
	*
	*/
	function thor_getIds() {
	    
		// Call the Model to get the data
		include THORFCM_PLUGIN_PATH . '/app/models/thor_getids.php';

	    if ($res != false) {
	        foreach($res as $row){
	            array_push($devices, $row->fcm_regid);
	        }
	    }
		
	    return $devices;
	}


	/*
	*
	* Work on false IDs
	*
	*/
	function thor_fcm_check($answer) {
	   
	   $allIds = ThorAdmin::thor_getIds();
	   $resId = array();
	   $errId = array();
	   $err = array();
	   $can = array();
	   
	   global $wpdb;
	   $table_name = $wpdb->prefix.'thor_fcm_users';


	   foreach($answer->results as $index=>$element) {
	    if(isset($element->registration_id)) {
	     $resId[] = $index;
	    }
	   }
	   
	   foreach($answer->results as $index=>$element){
	    if(isset($element->error) && $element->error != "Unavailable"){
	      $errId[] = $index;
	    }
	   }

		if(!empty($resId)) {
			for($i=0; $i<count($resId); $i++) {
				array_push($can, $allIds[$resId[$i]]);
			}
		}

		if(!empty($errId)) {
			for($i=0; $i<count($errId); $i++) {
				array_push($err, $allIds[$errId[$i]]);
			}
		}

	   if($err != null) {
		for($i=0; $i < count($err); $i++) {

			$s = $wpdb->query($wpdb->prepare("DELETE FROM $table_name WHERE fcm_regid=%s", $err[$i]));
		}
	   } 
	   if($can != null) {
		for($i=0; $i < count($can); $i++) {
			
			$r = $wpdb->query($wpdb->prepare("DELETE FROM $table_name WHERE fcm_regid=%s", $can[$i]));
		}
	   }
	}

	function thor_fcm_notices($notice) {
		if($notice != null) {
			$num = $notice['num'];
			$msg = $notice['msg'];
			
			if($num == 1) { ?>
				<div id='message' class='updated'>
					<p><b><?php _e('Message sent.','thor_fcm'); ?></b><i>&nbsp;&nbsp;(<?php echo $msg; ?>)</i></p><p><?php echo $notice['debug']; ?></p>
				</div>
				<?php
			} else if($num == 2) { ?>
				<div id='message' class='updated'>
					<p><b><?php _e('Message sent.','thor_fcm'); ?></b><i>&nbsp;&nbsp;(<?php echo $msg; ?>)</i></p>
					<p><?php echo __('success:','thor_fcm') . $notice['success']; ?>  &nbsp;&nbsp;<?php echo __('fail:','thor_fcm') . $notice['fail']; ?></p>
				</div>
				<?php
			} else if($num == 3) { ?>
				<div id='message' class='error'>
					<p><b><?php _e('Error','thor_fcm'); ?></b></p>
					<p><?php echo $msg; ?></p>
				</div>
				<?php			
			}
		} else if(isset($_GET['thor-fcm-data'])) {
			$d = $_SESSION['thor_fcm_msg'];
			session_unset();
			session_commit();
			ThorAdmin::thor_fcm_notices($d) ;
		}
	}

	/* Register query for registration API*/
	function fcm_main_query_vars($vars){
		$vars[] = 'wp-thor-fcm-api';
		return $vars;
	}
	

	/******************** HTML STUFF ********************/
			
	function thor_fcm_register() {
	 
		if (isset($_GET["regId"])) {
			global $wpdb;   
			$fcm_regid = $_GET["regId"];
			$os = thor_fcm_os();
			$model = thor_fcm_model();
			$time = date("Y-m-d H:i:s");

			// Get User Details
			include THORFCM_PLUGIN_PATH . '/app/models/get_regid_from_user.php';
			
			if(!$result) {		
				// Update Users Mobile Details	
				include THORFCM_PLUGIN_PATH . '/app/models/update_users_mobile_details.php';
				exit(__('You are now registered','thor_fcm'));
			} else {
			  exit(__('You are already registered','thor_fcm'));
			}
		}
	}

	/**	Handle API Requests for registration user
	 *  url     : http://www.domain-wp.com/wp-thor-fcm-api=register
	 *  type    : POST 
	 *  payload : JSON
	 */
	function fcm_main_parse_requests() {
		global $wp;

		if(isset($wp->query_vars['wp-thor-fcm-api'])){
			$api_fcm = $wp->query_vars['wp-thor-fcm-api'];

			if($api_fcm == 'register'){
				
				$fcm_rest = new Fcm_Rest();

				if($fcm_rest->get_request_method() != "POST") $fcm_rest->response('',406);
				$api_data 	 = json_decode(file_get_contents("php://input"), true);
				$regid 		 = $api_data['regid'];
				$serial 	 = $api_data['serial'];
				$device_name = $api_data['device_name'];
				$os_version  = $api_data['os_version'];
				
				if ($regid) {
					// insert POST request into database
					$res = fcm_data_insert_user($regid, $device_name, $serial, $os_version);
					if($res == 1){
						$data = json_encode(array('status'=> 'success', 'message'=>'Infoooooos'));
						$fcm_rest->response($data, 200);
					}else{
						$data = json_encode(array('status'=> 'failed', 'message'=>'failed when insert to database'));
						$fcm_rest->response($data, 200);	
					}
				}else{
					$data = json_encode(array('status'=> 'failed', 'message'=>'regid cannot null'));
					$fcm_rest->response($data, 200);
				}
			} else if($api_fcm == 'info'){
				$fcm_rest = new Fcm_Rest();
				$data = json_encode(array('status'=> 'ok', 'wp_fcm_version'=>'1.0'));
				$fcm_rest->response($data, 200);	
			}else{
				$data = array('status'=> 'failed', 'message'=>'Invalid Parameter');
				fcm_tools_respon_simple($data);
			}
		}
	}


	/**
	 * Registers the routes for all and single options
	 *
	 * @author Chris Hutchinson <chris_hutchinson@me.com>
	 *
	 * @return void
	 *
	 * @since 1.3.1 	Switched to array() notation (over [] notation) to support PHP < 5.4
	 * @since 1.3.0
	 */
	function addThorFCMRouteV2() {
		register_rest_route( 'thorfcmapi/v1', '/author
			', array(
//			'methods' => array('GET'),
			'methods' => WP_REST_Server::READABLE,
			'callback' => array( $this, 'my_awesome_func' )
		) );

		register_rest_route( 'wp/v2/thorfcmapi', '/options', array(
			'methods' => array('GET'),
			'callback' => array( $this, 'addFCMOptionRouteV2cb' )
		) );
	}

	/**
	 * The callback for the `wp/v2/acf/options` endpoint
	 * 
	 * @author Chris Hutchinson <chris_hutchinson@me.com>
	 *
	 * @param WP_REST_Request 	$request 	The WP_REST_Request object
	 *
	 * @return array|string 	The single requested option, or all options 
	 *
	 * @see ACFtoWPAPI::addFCMOptionRouteV2()
	 *
	 * @since 1.3.0
	 */
	function addFCMOptionRouteV2cb( WP_REST_Request $request ) {
		if($request['option']) {
			return get_field($request['option'], 'option');
		}

		return get_fields('option');
	}


	/**
	 * Grab latest post title by an author!
	 *
	 * @param array $data Options for the function.
	 * @return string|null Post title for the latest,  * or null if none.
	 */
	function my_awesome_func( $data ) {
		$posts = get_posts( array(
			'author' => $data['id'],
		) );

		if ( empty( $posts ) ) {
			return null;
		}

		return $posts[0]->post_title;
	}

	/**
	 * Grab latest post title by an author!
	 *
	 * @param array $data Options for the function.
	 * @return string|null Post title for the latest,  * or null if none.
	 */
	function get_latest_func( $data ) {
		$posts = get_posts( array(
			'author' => $data['id'],
		) );

		if ( empty( $posts ) ) {
			return null;
		}

		return $posts[0]->post_title;
	}

  } //end of class
} //end of if class exists