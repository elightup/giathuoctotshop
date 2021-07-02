<?php
namespace ELUSHOP\SaveLog;

class SaveLog {

	function __construct() {
		add_action( 'profile_update', [ $this, 'user_object_log' ], 99, 2 );
		add_action( 'admin_menu', [ $this, 'add_menu' ] );
	}
	public function user_object_log( $user_id, $old_user_data ) {
		$object_id = get_current_user_id();
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'logs',
			[
				'object_type' => '',
				'object_id'   => $object_id,
				'action'      => '',
				'date'        => current_time( 'mysql' ),
				'user_update' => $user_id,
			]
		);
	}

	public function add_menu() {
		$page = add_menu_page(
			__( 'Logs Record', 'gtt-shop' ),
			__( 'Logs Record', 'gtt-shop' ),
			'edit_posts',
			'logs',
			[ $this, 'render' ],
			'',
			80
		);
		// add_action( "load-$page", [ $this, 'create_table' ] );
		// add_action( "load-$page", [ $this, 'add_screen_options' ] );
		// add_action( "admin_print_styles-$page", [ $this, 'enqueue' ] );
	}
	public function render() {
		$this->renderList();
	}
	protected function renderList() {
		include ELU_SHOP_DIR . 'templates/admin/logs.php';
	}
}

?>