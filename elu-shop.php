<?php
/**
 * Plugin Name: Giathuoctot Shop
 * Plugin URI:  https://elightup.com
 * Description: An easy e-commerce solution for WordPress.
 * Version:     0.1.0
 * Author:      eLightUp
 * Author URI:  https://elightup.com
 * License:     GPL 2+
 * Text Domain: gtt-shop
 * Domain Path: /languages/
 */

namespace ELUSHOP;

// Prevent loading this file directly.
defined( 'ABSPATH' ) || die;

require 'vendor/autoload.php';

define( 'ELU_SHOP_URL', plugin_dir_url( __FILE__ ) );
define( 'ELU_SHOP_DIR', plugin_dir_path( __FILE__ ) );
define( 'ELU_SHOP_VER', '0.1.0' );

load_plugin_textdomain( 'gtt-shop', false, plugin_basename( ELU_SHOP_DIR ) . '/languages' );

$schema = new Schema();
$schema->register_tables();
register_activation_hook( __FILE__, function () use ( $schema ) {
	$schema->create_tables();
} );

( new Product\PostType() )->init();
( new Cart() )->init();
( new Checkout() )->init();
( new Order\Notification() )->init();
( new User\invoice() )->init();
( new Account() )->init();

( new Settings() )->init();
if ( is_admin() ) {
    ( new Order\AdminList() )->init();
	( new User\user() )->init();
	new User\Views;
} else {
	( new Assets() )->init();
}

function is_cart_page() {
	return is_page() && get_the_ID() == ps_setting( 'cart_page' );
}
function is_checkout_page() {
	return is_page() && get_the_ID() == ps_setting( 'checkout_page' );
}