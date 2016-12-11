<?php

if(!class_exists('WP_List_Table')){
   require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class FCM_List_Table extends WP_List_Table {
	
	function __construct() {
		parent::__construct( array(
			'singular'=> 'fcm_device',
			'plural' => 'fcm_devices',
			'ajax'   => false) 
		);
	}
  
	public function prepare_items() {
		global $wpdb;

		// get users
		include THORFCM_PLUGIN_PATH . '/app/models/get_all_users.php';
				
        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();
		
		// Ordering parameters 
		$orderby;
		$order;
		if(!empty($_GET['orderby'])) { 
			$orderby = $_GET['orderby']; 
		}
		
        if(!empty($_GET['order'])) { 
			$order = $_GET['order']; 
		}
		
        if(!empty($orderby) & !empty($order)){ 
			$query.=' ORDER BY '.$orderby.' '.$order; 
		}
		
		// Pagination parameters
        $totalitems = $wpdb->query($query);
        $perpage = 10;
        $paged = !empty($_GET["paged"]) ? mysql_real_escape_string($_GET["paged"]) : '';
		
        if(empty($paged) || !is_numeric($paged) || $paged<=0 ){ 
			$paged=1; 
		}
		
        $totalpages = ceil($totalitems/$perpage);
		if(!empty($paged) && !empty($perpage)){
			$offset=($paged-1)*$perpage;
			$query.=' LIMIT '.(int)$offset.','.(int)$perpage;
		}

		// Register the pagination
		//$wp_list_table = new FCM_List_Table();
		//$wp_list_table->set_pagination_args( array(

		$this->set_pagination_args( array(
			"total_items" => $totalitems,
			"total_pages" => $totalpages,
			"per_page" => $perpage) 
		);
		
        $this->_column_headers = array($columns, $hidden, $sortable);
		
		//Filter for search
		if(empty($_GET['s'])) {
			$this->items = $wpdb->get_results($query); 
		} else {
			$se = $_GET['s'];
			
			// get device details
			include THORFCM_PLUGIN_PATH . '/app/models/get_device_details.php';

			$this->items = $wpdb->get_results($query);
		}
    }
	
	function extra_tablenav( $which ) {
		if($which == "top") {
			if(isset($_GET['action']) && $_GET['action'] == 'delete' ) { ?>
				<div id="message" class="updated">
					<p><strong><?php _e('Device with ID','fcm'); ?><i><?php echo "&nbsp;";echo $_GET['device'];echo "&nbsp;"; ?></i><?php _e('deleted','fcm'); ?></strong></p>
				</div> <?php
			} 
		}
	}
   
	public function get_columns(){
        $columns = array(
			'fcm_regid'	=> __('FCM ID','fcm'),
			'id'		=> __('Database ID','fcm'),
            'os'		=> __('Device OS','fcm'),
            'model'		=> __('Device Model','fcm'),
            'created_at'=> __('Registered At','fcm')			
        );

        return $columns;
    }
	
	public function get_hidden_columns(){
        return array();
    }
	
	private function get_seached($item) {
	
	}
	
	public function get_sortable_columns(){
        return array('os' => array('os', true),
		             'model' => array('model', true),
					 'created_at' => array('created_at', true),
					 'id' => array('id', true),
					 'fcm_regid' => array('fcm_regid', true));
    }
	
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
	
	public function column_fcm_regid($item) {
		$actions = array( 
			'view'    => sprintf('<a href="?page=%s&tab=%s&device=%s">%s</a>',$_REQUEST['page'],'list-device',$item->id, __('View','fcm')),
			'delete'  => sprintf('<a href="?page=%s&tab=%s&device=%s">%s</a>',$_REQUEST['page'],'delete-device',$item->id, __('Delete','fcm')) );
			
		$set = sprintf('<a class="row-title" href="?page=%s&tab=%s&device=%s">%s</a>',$_REQUEST['page'],'list-device',$item->id,$item->fcm_regid);

		return sprintf('%1$s %2$s', $set, $this->row_actions($actions) );
    }
	
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
	
	public function no_items() {
		_e('No registered Devices','fcm');
	}
}
?>