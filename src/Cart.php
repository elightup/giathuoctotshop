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

	public static function cart( $args = [] ) {
		$args             = wp_parse_args(
			$args,
			[
				'id'   => get_the_ID(),
				'text' => __( 'Buy now', 'gtt-shop' ),
				'type' => __( 'Added to shopping cart', 'gtt-shop' ),
				'echo' => true,
			]
		);
		$quantity         = '<div class="quantity">
		<input type="text" class="quantity_products" step="1" min="0" name="quantity" value="1" size="4" pattern="[0-9]*" inputmode="numeric">
		</div>';
		$button_view_cart = sprintf(
			'<a class="add-to-cart buy-now btn btn-primary" data-info="%s" data-type="%s">%s</a>',
			esc_attr( wp_json_encode( self::get_product_info( $args['id'] ) ) ),
			esc_attr( $args['type'] ),
			esc_attr( $args['text'] )
		);
		if ( $args['echo'] ) {
			echo '<div class="cart-button">' . $quantity . $button_view_cart . '</div>';
		}
	}

	public static function add_cart( $args = [] ) {
		$args     = wp_parse_args(
			$args,
			[
				'id'   => get_the_ID(),
				'text' => __( 'Add cart', 'gtt-shop' ),
				'type' => __( 'Added to shopping cart', 'gtt-shop' ),
				'echo' => true,
			]
		);
		$quantity = '<div class="quantity">
		<span class="button-minus" data-info="' . esc_attr( wp_json_encode( self::get_product_info( $args['id'] ) ) ) . '" data-type="' . $args['type'] . '" data-field="quantity">-</span>
		<input type="text" class="quantity_products" step="1" min="0" name="quantity" value="0" size="4" pattern="[0-9]*" inputmode="numeric">
		<span class="button-plus" data-info="' . esc_attr( wp_json_encode( self::get_product_info( $args['id'] ) ) ) . '" data-type="' . $args['type'] . '" data-field="quantity">+</span>
		</div>';

		$button_add_cart = sprintf(
			'<a class="add-to-cart btn-primary wp-block-button__link" data-info="%s" data-type="%s">%s</a>',
			esc_attr( wp_json_encode( self::get_product_info( $args['id'] ) ) ),
			esc_attr( $args['type'] ),
			esc_attr( $args['text'] )
		);
		$cart_page = get_permalink( ps_setting( 'cart_page' ) );
		if ( $args['echo'] ) {
			// echo '<div class="cart-button">' . $quantity . $button_add_cart . '
			// 	<a class="view-cart btn-primary wp-block-button__link" href="' . $cart_page . '" title="' . __( 'View cart', 'gtt-shop' ) . '">'. __( 'View cart', 'gtt-shop' ) .'</a>
			// </div>';
			echo '<div class="cart-button">' . $quantity . '</div>';
		}
	}

	protected static function get_product_info( $id ) {
		$price = 0;
		$price_original = ! empty( get_post_meta( $id, 'price', true ) ) ? get_post_meta( $id, 'price', true ) : 0;
		$price_vip2 = get_post_meta( $id, 'price_vip2', true );
		$price_vip3 = get_post_meta( $id, 'price_vip3', true );
		$price_vip4 = get_post_meta( $id, 'price_vip4', true );
		$price_vip5 = get_post_meta( $id, 'price_vip5', true );
		$price_vip6 = get_post_meta( $id, 'price_vip6', true );
		$price_sale = get_post_meta( $id, 'flash_sale_price', true );
		$ma_sp      = get_post_meta( $id, 'ma_sp', true );
		$image_url  = get_post_meta( $id, 'image_url', true );

		$time_start = (int)rwmb_meta( 'flash_sale_time_start', get_queried_object_id() );
		$time_end   = (int)rwmb_meta( 'flash_sale_time_end', get_queried_object_id() );
		$time_now   = strtotime( current_time( 'mysql' ) );

		$role = is_user_logged_in() ? wp_get_current_user()->roles[0] : '';
		switch ( $role ) {
			case 'vip2':
				$price_original = $price_vip2 ? $price_vip2 : $price_original;
				break;
			case 'vip3':
				$price_original = $price_vip3 ? $price_vip3 : $price_original;
				break;
			case 'vip4':
				$price_original = $price_vip4 ? $price_vip4 : $price_original;
				break;
			case 'vip5':
				$price_original = $price_vip5 ? $price_vip5 : $price_original;
				break;
			case 'vip6':
				$price_original = $price_vip6 ? $price_vip6 : $price_original;
				break;
			default:
				$price_original = $price_original;
				break;
		}

		if ( $price_sale == NULL || $time_start > $time_now || $time_now > $time_end ) {
			$price = $price_original;
		} else {
			$price = $price_sale;
		}
		return [
			'id'    => $id,
			'title' => get_the_title( $id ),
			'price' => $price * 1000,
			'url'   => $image_url,
			'link'  => get_permalink( $id ),
			'ma_sp' => $ma_sp,
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
