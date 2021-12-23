<?php

namespace ELUSHOP;

class Settings {
	public function init() {
		add_filter( 'mb_settings_pages', [ $this, 'register_settings_page' ] );
		add_filter( 'rwmb_meta_boxes', [ $this, 'register_meta_boxes' ] );
	}

	public function register_settings_page( $settings_pages ) {
		$settings_pages[] = [
			'id'          => 'gtt-shop',
			'option_name' => 'gtt_shop',
			'menu_title'  => __( 'Cài đặt', 'gtt-shop' ),
			'parent'      => 'edit.php?post_type=product',
			'capability'  => 'install_plugins',
			'style'       => 'no-boxes',
			'columns'     => true,
			'tabs'        => [
				'general' => __( 'Chung', 'gtt-shop' ),
				'payment' => __( 'Thanh toán', 'gtt-shop' ),
			],
		];
		$settings_pages[] = [
			'id'          => 'gtt-shop-voucher',
			'option_name' => 'gtt_shop',
			'menu_title'  => __( 'Vouchers', 'gtt-shop' ),
			'capability'  => 'edit_pages',
			'parent'      => 'edit.php?post_type=product',
			'style'       => 'no-boxes',
			'columns'     => true,
		];
		return $settings_pages;
	}

	public function register_meta_boxes( $meta_boxes ) {
		if ( ! function_exists( 'mb_settings_page_load' ) ) {
			return $meta_boxes;
		}
		$meta_boxes[] = [
			'id'             => 'general',
			'title'          => ' ',
			'settings_pages' => 'gtt-shop',
			'tab'            => 'general',
			'fields'         => [
				[
					'id'   => 'product_slug',
					'name' => __( 'Product Slug', 'gtt-shop' ),
					'type' => 'text',
					'std'  => 'product',
				],
				[
					'id'   => 'product_tag_slug',
					'name' => __( 'Product Tag Slug', 'gtt-shop' ),
					'type' => 'text',
					'std'  => 'product-tag',
				],
				[
					'id'        => 'cart_page',
					'name'      => __( 'Trang giỏ hàng', 'gtt-shop' ),
					'type'      => 'post',
					'post_type' => 'page',
				],
				[
					'id'        => 'checkout_page',
					'name'      => __( 'Trang thanh toán', 'gtt-shop' ),
					'type'      => 'post',
					'post_type' => 'page',
				],
				[
					'id'        => 'confirmation_page',
					'name'      => __( 'Trang xác nhận', 'gtt-shop' ),
					'type'      => 'post',
					'post_type' => 'page',
				],
				[
					'id'        => 'account_page',
					'name'      => __( 'Trang tài khoản', 'gtt-shop' ),
					'type'      => 'post',
					'post_type' => 'page',
				],
			],
		];
		$meta_boxes[] = [
			'id'             => 'payment',
			'title'          => ' ',
			'settings_pages' => 'gtt-shop',
			'tab'            => 'payment',
			'fields'         => [
				[
					'id'   => 'currency',
					'type' => 'text',
					'name' => __( 'Đơn vị tiền tệ', 'gtt-shop' ),
				],
				[
					'id'     => 'payment_methods',
					'type'   => 'group',
					'name'   => __( 'Phương thức thanh toán', 'gtt-shop' ),
					'clone'  => true,
					'fields' => [
						[
							'id'   => 'payment_method_title',
							'type' => 'text',
						],
						[
							'id'      => 'payment_method_description',
							'type'    => 'wysiwyg',
							'options' =>
							[
								'textarea_rows' => 6,
								'media_buttons' => false,
								'quicktags'     => false,
							],
						],
					],
				],
			],
		];

		$meta_boxes[] = [
			'id'             => 'voucher',
			'title'          => ' ',
			'settings_pages' => 'gtt-shop-voucher',
			// 'tab'            => 'vouchers',
			'fields'         => [
				[
					'id'          => 'vouchers_group',
					'type'        => 'group',
					'collapsible' => true,
					'clone'       => true,
					'group_title' => 'Voucher {#}',
					'fields'      => [
						[
							'name' => __( 'Mã voucher', 'gtt-shop' ),
							'id'   => 'voucher_id',
							'type' => 'text',
						],
						[
							'name'    => __( 'Loại voucher', 'gtt-shop' ),
							'id'      => 'voucher_type',
							'type'    => 'select_advanced',
							'options' => [
								'by_price'   => __( 'Theo giá', 'gtt-shop' ),
								'by_percent' => __( 'Theo phần trăm', 'gtt-shop' ),
							],
						],
						[
							'name' => __( 'Mức giá', 'gtt-shop' ),
							'id'   => 'voucher_price',
							'type' => 'number',
						],
						[
							'name'       => __( 'Ngày hết hạn', 'gtt-shop' ),
							'id'         => 'voucher_expiration_date',
							'type'       => 'date',
							'timestamp'  => true,
							'js_options' => [
								'dateFormat' => 'dd-mm-yy',
							],
						],
						[
							'name' => __( 'Số lượng giới hạn', 'gtt-shop' ),
							'id'   => 'voucher_soluong',
							'type' => 'number',
						],
						[
							'name' => __( 'Điều kiện (Giá nhỏ nhất để áp dụng)', 'gtt-shop' ),
							'id'   => 'voucher_dieukien',
							'type' => 'number',
						],
					],
				],
			],
		];

		return $meta_boxes;
	}
}
