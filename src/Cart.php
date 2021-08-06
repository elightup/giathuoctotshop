<?php
namespace ELUSHOP;

class Cart {
	public function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue' ] );

		add_action( 'wp_ajax_get_cart', [ $this, 'ajax_get_cart' ] );
		add_action( 'wp_ajax_set_cart', [ $this, 'ajax_set_cart' ] );

		// Cần viết 3 hàm callback mới cho xử lý add/update/delete product từ cart.
		add_action( 'wp_ajax_cart_add_product', [ $this, 'ajax_cart_add_product' ] );
		add_action( 'wp_ajax_cart_update_product', [ $this, 'ajax_cart_update_product' ] );
		add_action( 'wp_ajax_cart_remove_product', [ $this, 'ajax_cart_remove_product' ] );
		add_action( 'wp_ajax_clear_cart', [ $this, 'ajax_clear_cart' ] );
	}

	public function enqueue() {
		if ( is_cart_page() ) {
			Assets::enqueue_style( 'cart' );
		}

		wp_enqueue_script( 'cart', trailingslashit( ELU_SHOP_URL ) . 'assets/js/cart.js', ['jquery'], uniqid(), true );
		Assets::localize( 'cart', [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'cartUrl' => get_permalink( ps_setting( 'cart_page' ) ),
			'userId'  => get_current_user_id(),
			'nonce'   => wp_create_nonce( 'cart' ),
			'role'    => is_user_logged_in() ? wp_get_current_user()->roles[0] : '',
		], 'CartParams' );
	}

	public static function add_cart() {
		echo '<div class="quantity" data-product="' . get_the_ID() . '">
			<span class="button-minus">-</span>
			<input type="text" class="quantity_products" value="0" size="4" pattern="[0-9]*" inputmode="numeric">
			<span class="button-plus">+</span>
		</div>';
	}

	public static function get_product_info( $id ) {
		$price      = (float) get_post_meta( $id, 'price', true );
		$price_vip2 = get_post_meta( $id, 'price_vip2', true ) ?: $price;
		$price_vip3 = get_post_meta( $id, 'price_vip3', true ) ?: $price;
		$price_vip4 = get_post_meta( $id, 'price_vip4', true ) ?: $price;
		$price_vip5 = get_post_meta( $id, 'price_vip5', true ) ?: $price;
		$price_vip6 = get_post_meta( $id, 'price_vip6', true ) ?: $price;

		$price_sale = (float) get_post_meta( $id, 'flash_sale_price', true );
		$time_start = (int) rwmb_meta( 'flash_sale_time_start', '', $id );
		$time_end   = (int) rwmb_meta( 'flash_sale_time_end', '', $id );
		$time_now   = strtotime( current_time( 'mysql' ) );

		if ( $price_sale && $time_start <= $time_now && $time_now <= $time_end ) {
			$price = $price_vip2 = $price_vip3 = $price_vip4 = $price_vip5 = $price_vip6 = $price_sale;
		}

		return [
			'id'         => $id,
			'title'      => get_the_title( $id ),
			'price'      => intval( $price * 1000 ),
			'price_vip2' => intval( $price_vip2 * 1000 ),
			'price_vip3' => intval( $price_vip3 * 1000 ),
			'price_vip4' => intval( $price_vip4 * 1000 ),
			'price_vip5' => intval( $price_vip5 * 1000 ),
			'price_vip6' => intval( $price_vip6 * 1000 ),
			'url'        => get_post_meta( $id, 'image_url', true ),
			'link'       => get_permalink( $id ),
			'ma_sp'      => get_post_meta( $id, 'ma_sp', true ),
		];
	}

	public function ajax_get_cart() {
		check_ajax_referer( 'cart' );

		$id = (int) filter_input( INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT );
		if ( ! $id || $id !== get_current_user_id() ) {
			wp_send_json_error();
		}

		$data = get_user_meta( $id, 'cart', true );
		if ( empty( $data ) ) {
			$data = [];
		}

		// Always refresh the product info, because users might update their prices.
		$this->refresh_cart_data( $data );

		wp_send_json_success( $data );
	}

	public function ajax_set_cart() {
		check_ajax_referer( 'cart' );

		$id = (int) filter_input( INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT );
		if ( ! $id || $id !== get_current_user_id() ) {
			wp_send_json_error();
		}

		$data = $_POST['data'] ?? [];

		if ( empty( $data ) ) {
			$data = [];
		}

		$this->refresh_cart_data( $data );

		update_user_meta( $id, 'cart', $data );

		wp_send_json_success( $data );
	}

	public function ajax_cart_add_product() {
		check_ajax_referer( 'cart' );

		$id = (int) filter_input( INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT );
		if ( ! $id || $id !== get_current_user_id() ) {
			wp_send_json_error();
		}

		$product_id = isset( $_POST['product_id'] ) ? (int) $_POST['product_id'] : 0;
		$quantity   = isset( $_POST['quantity'] ) ? (int) $_POST['quantity'] : 0;

		if ( ! $product_id || ! $quantity ) {
			wp_send_json_error();
		}

		$data = self::get_product_info( $product_id );
		if ( empty( $data ) ) {
			wp_send_json_error();
		}

		$data['quantity'] = $quantity;

		$cart = get_user_meta( $id, 'cart', true );
		if ( empty( $cart ) || ! is_array( $cart ) ) {
			$cart = [];
		}
		$cart[ $product_id ] = $data;

		update_user_meta( $id, 'cart', $cart );

		wp_send_json_success( $cart );
	}

	public function ajax_cart_update_product() {
		check_ajax_referer( 'cart' );

		$id = (int) filter_input( INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT );
		if ( ! $id || $id !== get_current_user_id() ) {
			wp_send_json_error();
		}

		$product_id = isset( $_POST['product_id'] ) ? (int) $_POST['product_id'] : 0;
		$quantity   = isset( $_POST['quantity'] ) ? (int) $_POST['quantity'] : 0;

		if ( ! $product_id || ! $quantity ) {
			wp_send_json_error();
		}

		$cart = get_user_meta( $id, 'cart', true );
		if ( empty( $cart ) || ! is_array( $cart ) ) {
			$cart = [];
		}

		if ( empty( $cart[ $product_id ] ) || empty( $cart[ $product_id ]['quantity'] ) ) {
			wp_send_json_error();
		}

		$cart[ $product_id ]['quantity'] = $quantity;

		update_user_meta( $id, 'cart', $cart );

		wp_send_json_success( $cart );
	}

	public function ajax_cart_remove_product() {
		check_ajax_referer( 'cart' );

		$id = (int) filter_input( INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT );
		if ( ! $id || $id !== get_current_user_id() ) {
			wp_send_json_error();
		}

		$product_id = isset( $_POST['product_id'] ) ? (int) $_POST['product_id'] : 0;

		if ( ! $product_id ) {
			wp_send_json_error();
		}

		$cart = get_user_meta( $id, 'cart', true );
		if ( empty( $cart ) || ! is_array( $cart ) ) {
			$cart = [];
		}
		unset( $cart[ $product_id ] );

		update_user_meta( $id, 'cart', $cart );

		wp_send_json_success( $cart );
	}

	public function ajax_clear_cart() {
		check_ajax_referer( 'cart' );

		$id = (int) filter_input( INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT );
		if ( ! $id || $id !== get_current_user_id() ) {
			wp_send_json_error();
		}

		$cart = [];

		update_user_meta( $id, 'cart', $cart );

		wp_send_json_success( $cart );
	}

	private function refresh_cart_data( &$data ) {
		foreach ( $data as $product_id => &$product ) {
			if ( empty( $product['quantity'] ) ) {
				unset( $data[ $product_id ] );
				continue;
			}
			$product['quantity'] = (int) $product['quantity'];
			$product = array_merge( $product, self::get_product_info( $product_id ) );
		}
	}
}
