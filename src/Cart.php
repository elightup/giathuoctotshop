<?php

namespace ELUSHOP;

use WP_Query;
class Cart {
	public function init() {
		// Register scripts to make sure 'cart' is available everywhere and can be used in other scripts.
		add_action( 'wp_enqueue_scripts', [ $this, 'register_scripts' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_struger_data' ] );
	}

	public function register_scripts() {
		if ( is_cart_page() ) {
			wp_register_style( 'cart', ELU_SHOP_URL . 'assets/css/cart.css' );
		}

		wp_register_script( 'notification', ELU_SHOP_URL . 'assets/js/notification.min.js', [ 'jquery' ], '', true );
		wp_register_script( 'alertify', ELU_SHOP_URL . 'assets/js/alertify.min.js', [ 'jquery' ], '1.11.1', true );
		wp_register_script( 'cart', ELU_SHOP_URL . 'assets/js/cart.js', [ 'jquery', 'notification', 'alertify' ], ELU_SHOP_VER, true );
		wp_localize_script(
			'cart',
			'CartParams',
			[
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'cartUrl' => get_permalink( ps_setting( 'cart_page' ) ),
				'user_id' => is_user_logged_in() ? get_current_user_id() : '',
			]
		);
	}

	public function enqueue() {
		wp_enqueue_script( 'alertify' );
		wp_enqueue_style( 'cart' );
		wp_enqueue_script( 'cart' );
	}

	public function enqueue_struger_data() {
		$currency = ! empty( ps_setting( 'currency' ) ) ? ps_setting( 'currency' ) : 'USD';
		$price =  ! empty( rwmb_meta( 'price', get_the_ID() ) ) ? rwmb_meta( 'price', get_the_ID() ) : 0;
	?>
	<script type="application/ld+json">
	{
		"@context": "http://schema.org/",
		"@type": "Product",
		"name": "<?php the_title() ?>",
		"image": [
			"<?php echo wp_get_attachment_url( get_post_thumbnail_id( get_the_ID(), 'full' ) )?>"
		],
		"description": "<?php echo esc_html( get_the_excerpt() ) ?>",
		"sku": "<?php the_ID() ?>",
		"brand": {
			"@type": "Thing",
			"name": "<?php echo get_bloginfo('name') ?>"
		},
		"offers": {
			"@type": "Offer",
			"priceCurrency": "<?php echo $currency ?>",
			"price": "<?php echo $price ?>",
			"url": "<?php the_permalink() ?>",
			"itemCondition": "http://schema.org/UsedCondition",
			"availability": "http://schema.org/InStock"
		}
	}
	</script>

	<?php
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
		<input type="number" id="quantity_products" class="quantity_products input-text qty text" step="1" min="1" max="" name="quantity" value="1" title="Qty" size="4" pattern="[0-9]*" inputmode="numeric">
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
		<input type="button" value="-" class="button-minus" data-info="' . esc_attr( wp_json_encode( self::get_product_info( $args['id'] ) ) ) . '" data-type="' . $args['type'] . '" data-field="quantity">
		<input type="number" id="quantity_products" class="quantity_products input-text qty text" step="1" min="1" max="" name="quantity" value="0" title="Qty" size="4" pattern="[0-9]*" inputmode="numeric">
		<input type="button" value="+" class="button-plus" data-info="' . esc_attr( wp_json_encode( self::get_product_info( $args['id'] ) ) ) . '" data-type="' . $args['type'] . '" data-field="quantity">
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
}
