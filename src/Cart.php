<?php
namespace ELUSHOP;

class Cart {
	public function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue' ] );

		add_action( 'wp_ajax_get_cart', [ $this, 'ajax_get_cart' ] );
		add_action( 'wp_ajax_set_cart', [ $this, 'ajax_set_cart' ] );
	}

	public function enqueue() {
		if ( is_cart_page() ) {
			Assets::enqueue_style( 'cart' );
		}

		Assets::enqueue_script( 'cart' );
		Assets::localize( 'cart', [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'cartUrl' => get_permalink( ps_setting( 'cart_page' ) ),
			'userId'  => get_current_user_id(),
			'nonce'   => wp_create_nonce( 'cart' ),
		], 'CartParams' );
	}

	public static function add_cart() {
		echo '<div class="quantity" data-product="' . get_the_ID() . '">
			<span class="button-minus">-</span>
			<input type="text" class="quantity_products" value="0" size="4" pattern="[0-9]*" inputmode="numeric">
			<span class="button-plus">+</span>
		</div>';
	}

	private static function get_product_info( $id ) {
		$price      = (float) get_post_meta( $id, 'price', true );
		$price_vip2 = (float) get_post_meta( $id, 'price_vip2', true );
		$price_vip3 = (float) get_post_meta( $id, 'price_vip3', true );
		$price_vip4 = (float) get_post_meta( $id, 'price_vip4', true );
		$price_vip5 = (float) get_post_meta( $id, 'price_vip5', true );
		$price_vip6 = (float) get_post_meta( $id, 'price_vip6', true );

		$price_sale = (float) get_post_meta( $id, 'flash_sale_price', true );
		$time_start = (int) rwmb_meta( 'flash_sale_time_start', $id );
		$time_end   = (int) rwmb_meta( 'flash_sale_time_end', $id );
		$time_now   = strtotime( current_time( 'mysql' ) );

		$role = is_user_logged_in() ? wp_get_current_user()->roles[0] : '';
		switch ( $role ) {
			case 'vip2':
				$price = $price_vip2 ?: $price;
				break;
			case 'vip3':
				$price = $price_vip3 ?: $price;
				break;
			case 'vip4':
				$price = $price_vip4 ?: $price;
				break;
			case 'vip5':
				$price = $price_vip5 ?: $price;
				break;
			case 'vip6':
				$price = $price_vip6 ?: $price;
				break;
		}

		if ( $price_sale && $time_start <= $time_now && $time_now <= $time_end ) {
			$price = $price_sale;
		}

		return [
			'id'    => $id,
			'title' => get_the_title( $id ),
			'price' => intval( $price * 1000 ),
			'url'   => get_post_meta( $id, 'image_url', true ),
			'link'  => get_permalink( $id ),
			'ma_sp' => get_post_meta( $id, 'ma_sp', true ),
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
		foreach ( $data as $product_id => &$product ) {
			if ( empty( $product['quantity'] ) ) {
				unset( $data[ $product_id ] );
				continue;
			}
			$product['quantity'] = (int) $product['quantity'];
			$product = array_merge( $product, self::get_product_info( $product_id ) );
		}

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

		update_user_meta( $id, 'cart', $data );

		wp_send_json_success();
	}
}
