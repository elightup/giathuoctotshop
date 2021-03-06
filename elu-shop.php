<?php
/**
 * Plugin Name: Giá thuốc Shop
 * Plugin URI:  https://titanweb.vn
 * Description: Giải pháp thương mại điện tử tối ưu.
 * Version:     1.0.0
 * Author:      TitanWeb
 * Author URI:  https://titanweb.vn
 */

namespace ELUSHOP;

// Prevent loading this file directly.
defined( 'ABSPATH' ) || die;

require 'vendor/autoload.php';

define( 'ELU_SHOP_URL', plugin_dir_url( __FILE__ ) );
define( 'ELU_SHOP_DIR', plugin_dir_path( __FILE__ ) );
define( 'ELU_SHOP_VER', '1.0.0' );

load_plugin_textdomain( 'gtt-shop', false, plugin_basename( ELU_SHOP_DIR ) . '/languages' );

new Schema;

( new Product\PostType() )->init();
new Cart;
new Checkout;
new Order\OrderAgain;
new Order\UpdateOrder;
( new Order\Notification() )->init();
( new User\invoice() )->init();
( new Account() )->init();

( new Settings() )->init();

new Misc;

if ( is_admin() ) {
	( new Order\AdminList() )->init();
	new Order\Ajax;
	( new User\user() )->init();
	new User\Views;
	new User\export;
	new Assets;
}

function is_cart_page() {
	return is_page() && get_the_ID() == ps_setting( 'cart_page' );
}
function is_checkout_page() {
	return is_page() && get_the_ID() == ps_setting( 'checkout_page' );
}
