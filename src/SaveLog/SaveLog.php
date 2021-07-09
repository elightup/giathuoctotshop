<?php
namespace ELUSHOP\SaveLog;

class SaveLog {

	protected $table;

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_menu' ] );
		add_action( 'save_post_product', [ $this, 'save_log_product' ] );
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

	public function save_log_product( $post_id ) {
		$data = [
			'object_type' => 'Sản phẩm',
			'object_id'   => $post_id,
			'user_update' => get_current_user_id(),
			'action'      => 'Update Sản phẩm',
		];
		self::insert_logs_table( $data );
	}

	public static function insert_logs_table( $data ) {
		$object_type = $data['object_type'];
		$object_id   = $data['object_id'];
		$action      = $data['action'];
		$user_update = $data['user_update'];

		global $wpdb;
		$log_id = $wpdb->insert(
			$wpdb->prefix . 'logs',
			[
				'object_type' => $object_type,
				'object_id'   => $object_id,
				'action'      => $action,
				'date'        => current_time( 'mysql' ),
				'user_update' => $user_update,
			]
		);
	}
}

?>