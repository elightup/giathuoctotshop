<?php

namespace ELUSHOP\Order;

class AdminList {
	/**
	 * @var Table
	 */
	protected $table;

	public function init() {
		add_action( 'admin_menu', [ $this, 'add_menu' ] );
		add_filter( 'set-screen-option', [ $this, 'set_screen_option' ], 10, 3 );
		add_action( 'wp_ajax_update_order_admin', [ $this, 'update_order_admin' ] );
		add_action( 'wp_ajax_nopriv_update_order_admin', [ $this, 'update_order_admin' ] );
	}

	public function add_menu() {
		$page = add_submenu_page(
			'edit.php?post_type=product',
			__( 'Đơn hàng', 'gtt-shop' ),
			__( 'Đơn hàng', 'gtt-shop' ),
			'edit_posts',
			'orders',
			[ $this, 'render' ]
		);
		add_action( "load-$page", [ $this, 'create_table' ] );
		add_action( "load-$page", [ $this, 'add_screen_options' ] );
		add_action( "admin_print_styles-$page", [ $this, 'enqueue' ] );
	}

	public function create_table() {
		$this->table = new Table();
	}

	public function add_screen_options() {
		$args = [
			'label'   => __( 'Số đơn hàng trên một trang', 'gtt-shop' ),
			'default' => 20,
			'option'  => 'orders_per_page',
		];
		add_screen_option( 'per_page', $args );
	}

	public function set_screen_option( $status, $option, $value ) {
		return 'orders_per_page' === $option ? $value : $status;
	}

	public function enqueue() {
		wp_enqueue_style( 'order-list', ELU_SHOP_URL . 'assets/css/order-list.css' );
		wp_enqueue_script( 'order-admin-update', ELU_SHOP_URL . 'assets/js/order-admin-update.js', [], '', true );
		wp_localize_script(
			'order-admin-update',
			'OrderUpdate',
			[
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			]
		);
	}

	public function update_order_admin() {
		$info     = isset( $_POST['info'] ) ? $_POST['info'] : [];
		$order_id = isset( $_POST['order_id'] ) ? $_POST['order_id'] : [];
		global $wpdb;
		// $url = add_query_arg(
		// 	[
		// 		'view' => 'order',
		// 		'id'   => $wpdb->insert_id,
		// 		'type' => 'checkout',
		// 	],
		// 	get_permalink( ps_setting( 'confirmation_page' ) )
		// );

		$wpdb->update(
			$wpdb->orders,
			[ 'info' => json_encode( $info ) ],
			[ 'id' => $order_id ],
			[ '%s' ]
		);

		wp_send_json_success( $order_id );
	}

	public function render() {
		if ( isset( $_GET['action'] ) && 'view' === $_GET['action'] && ! empty( $_GET['id'] ) ) {
			$this->renderItem();
		} else {
			$this->renderList();
		}
	}

	protected function renderItem() {
		include ELU_SHOP_DIR . 'templates/admin/order.php';
	}

	protected function renderList() {
		include ELU_SHOP_DIR . 'templates/admin/orders.php';
	}
}
