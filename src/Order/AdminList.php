<?php

namespace ELUSHOP\Order;

use ELUSHOP\Assets;

class AdminList {
	/**
	 * @var Table
	 */
	protected $table;

	public function init() {
		add_action( 'admin_menu', [ $this, 'add_menu' ] );
		add_filter( 'set-screen-option', [ $this, 'set_screen_option' ], 10, 3 );
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
		Assets::enqueue_style( 'order-list' );
		Assets::enqueue_script( 'order-list' );
		Assets::localize( 'order-list', [
			'nonce' => [
				'close'  => wp_create_nonce( 'close' ),
				'open'   => wp_create_nonce( 'open' ),
				'repush' => wp_create_nonce( 'repush' ),
			],
		] );
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
