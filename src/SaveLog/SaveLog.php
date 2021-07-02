<?php
namespace ELUSHOP\SaveLog;

class SaveLog {

	protected $table;

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
				'object_id'   => $user_id,
				'action'      => '',
				'date'        => current_time( 'mysql' ),
				'user_update' => $object_id,
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
		add_action( "load-$page", [ $this, 'create_table' ] );
	}
	public function create_table() {
		$this->table = new Table();
	}

	public function render() {
		include ELU_SHOP_DIR . 'templates/admin/logs.php';
	}
}

?>