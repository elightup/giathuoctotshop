<?php

namespace ELUSHOP\Product;

class PostType {
	public function init() {
		add_action( 'init', [ $this, 'register_post_type' ] );
		add_action( 'init', [ $this, 'register_taxonomies' ] );
		add_filter( 'rwmb_meta_boxes', [ $this, 'register_meta_boxes' ] );
		add_filter( 'rwmb_product_info_before_save_post', [ $this, 'save_old_price' ] );
	}

	public function register_post_type() {
		$labels = [
			'name'               => __( 'Sản phẩm', 'gtt-shop' ),
			'singular_name'      => __( 'Sản phẩm', 'gtt-shop' ),
			'add_new'            => _x( 'Thêm Sản phẩm mới', 'Sản phẩm', 'gtt-shop' ),
			'add_new_item'       => __( 'Thêm Sản phẩm mới', 'gtt-shop' ),
			'edit_item'          => __( 'Sửa Sản phẩm', 'gtt-shop' ),
			'new_item'           => __( 'Sản phẩm mới', 'gtt-shop' ),
			'view_item'          => __( 'Xem Sản phẩm', 'gtt-shop' ),
			'view_items'         => __( 'Xem Sản phẩm', 'gtt-shop' ),
			'search_items'       => __( 'Tìm kiếm Sản phẩm', 'gtt-shop' ),
			'not_found'          => __( 'Không có sản phẩm.', 'gtt-shop' ),
			'not_found_in_trash' => __( 'Không có sản phẩm trong thùng rác.', 'gtt-shop' ),
			'parent_item_colon'  => __( 'Parent Products:', 'gtt-shop' ),
			'all_items'          => __( 'Tất cả Sản phẩm', 'gtt-shop' ),
		];
		$options = get_option( 'gtt_shop' );
		$slug    = isset( $options[ 'product_slug' ] ) ? $options[ 'product_slug' ] : 'product';

		$args   = [
			'label'       => __( 'Sản phẩm', 'gtt-shop' ),
			'labels'      => $labels,
			'supports'    => [ 'title', 'editor', 'excerpt', 'thumbnail', 'comments' ],
			'public'      => true,
			'has_archive' => true,
			'menu_icon'   => 'dashicons-cart',
			'rewrite'     => [ 'slug' => $slug ],
		];

		register_post_type( 'product', $args );
	}

	public function register_taxonomies() {
		$tag_labels = [
			'name'                       => __( 'Tags', 'gtt-shop' ),
			'singular_name'              => __( 'Tag', 'gtt-shop' ),
			'all_items'                  => __( 'All Tags', 'gtt-shop' ),
			'edit_item'                  => __( 'Edit Tag', 'gtt-shop' ),
			'view_item'                  => __( 'View Tag', 'gtt-shop' ),
			'update_item'                => __( 'Update Tag', 'gtt-shop' ),
			'add_new_item'               => __( 'Add New Tag', 'gtt-shop' ),
			'new_item_name'              => __( 'New Tag Name', 'gtt-shop' ),
			'parent_item'                => __( 'Parent Tag', 'gtt-shop' ),
			'parent_item_colon'          => __( 'Parent Tag:', 'gtt-shop' ),
			'search_items'               => __( 'Search Tags', 'gtt-shop' ),
			'popular_items'              => __( 'Popular Tags', 'gtt-shop' ),
			'separate_items_with_commas' => __( 'Separate tags with commas', 'gtt-shop' ),
			'add_or_remove_items'        => __( 'Add or remove tags', 'gtt-shop' ),
			'choose_from_most_used'      => __( 'Choose from the most used tags', 'gtt-shop' ),
			'not_found'                  => __( 'No tags found', 'gtt-shop' ),
			'back_to_items'              => __( '&larr; Back to tags', 'gtt-shop' ),
		];
		$tag_args   = [
			'label'             => __( 'Tags', 'gtt-shop' ),
			'labels'            => $tag_labels,
			'show_ui'           => true,
			'show_admin_column' => true,
		];
		$options  = get_option( 'gtt_shop' );
		$tag_slug = isset( $options[ 'product_tag_slug' ] ) ? $options[ 'product_tag_slug' ] : 'product-tag';
		register_taxonomy( $tag_slug, 'product', $tag_args );

		$type_labels = [
			'name'                       => __( 'Nhóm thuốc', 'gtt-shop' ),
			'singular_name'              => __( 'Nhóm thuốc', 'gtt-shop' ),
			'all_items'                  => __( 'All Nhóm thuốc', 'gtt-shop' ),
			'edit_item'                  => __( 'Edit Nhóm thuốc', 'gtt-shop' ),
			'view_item'                  => __( 'View Nhóm thuốc', 'gtt-shop' ),
			'update_item'                => __( 'Update Nhóm thuốc', 'gtt-shop' ),
			'add_new_item'               => __( 'Add New Nhóm thuốc', 'gtt-shop' ),
			'new_item_name'              => __( 'New Nhóm thuốc Name', 'gtt-shop' ),
			'parent_item'                => __( 'Parent Nhóm thuốc', 'gtt-shop' ),
			'parent_item_colon'          => __( 'Parent Nhóm thuốc:', 'gtt-shop' ),
			'search_items'               => __( 'Search Nhóm thuốc', 'gtt-shop' ),
			'popular_items'              => __( 'Popular Nhóm thuốc', 'gtt-shop' ),
			'separate_items_with_commas' => __( 'Separate Nhóm thuốc with commas', 'gtt-shop' ),
			'add_or_remove_items'        => __( 'Add or remove Nhóm thuốc', 'gtt-shop' ),
			'choose_from_most_used'      => __( 'Choose from the most used Nhóm thuốc', 'gtt-shop' ),
			'not_found'                  => __( 'No Nhóm thuốc found', 'gtt-shop' ),
			'back_to_items'              => __( '&larr; Back to Nhóm thuốc', 'gtt-shop' ),
		];
		$type_args   = [
			'label'             => __( 'Nhóm thuốc', 'gtt-shop' ),
			'labels'            => $type_labels,
			'hierarchical'      => true,
			'show_admin_column' => true,
		];
		$options  = get_option( 'gtt_shop' );
		$type_slug = isset( $options[ 'product_type_slug' ] ) ? $options[ 'product_type_slug' ] : 'product-type';
		register_taxonomy( $type_slug, 'product', $type_args );

		$manufacturers_labels = [
			'name'                       => __( 'Nhà sản xuất', 'gtt-shop' ),
			'singular_name'              => __( 'Nhà sản xuất', 'gtt-shop' ),
			'all_items'                  => __( 'All Nhà sản xuất', 'gtt-shop' ),
			'edit_item'                  => __( 'Edit Nhà sản xuất', 'gtt-shop' ),
			'view_item'                  => __( 'View Nhà sản xuất', 'gtt-shop' ),
			'update_item'                => __( 'Update Nhà sản xuất', 'gtt-shop' ),
			'add_new_item'               => __( 'Add New Nhà sản xuất', 'gtt-shop' ),
			'new_item_name'              => __( 'New Nhà sản xuất Name', 'gtt-shop' ),
			'parent_item'                => __( 'Parent Nhà sản xuất', 'gtt-shop' ),
			'parent_item_colon'          => __( 'Parent Nhà sản xuất:', 'gtt-shop' ),
			'search_items'               => __( 'Search Nhà sản xuất', 'gtt-shop' ),
			'popular_items'              => __( 'Popular Nhà sản xuất', 'gtt-shop' ),
			'separate_items_with_commas' => __( 'Separate Nhà sản xuất with commas', 'gtt-shop' ),
			'add_or_remove_items'        => __( 'Add or remove Nhà sản xuất', 'gtt-shop' ),
			'choose_from_most_used'      => __( 'Choose from the most used Nhà sản xuất', 'gtt-shop' ),
			'not_found'                  => __( 'No Nhà sản xuất found', 'gtt-shop' ),
			'back_to_items'              => __( '&larr; Back to Nhà sản xuất', 'gtt-shop' ),
		];
		$manufacturers_args   = [
			'label'             => __( 'Nhà sản xuất', 'gtt-shop' ),
			'labels'            => $manufacturers_labels,
			'hierarchical'      => true,
			'show_admin_column' => true,
		];
		$options  = get_option( 'gtt_shop' );
		$manufacturers_slug = isset( $options[ 'manufacturers_slug' ] ) ? $options[ 'manufacturers_slug' ] : 'manufacturers';
		register_taxonomy( $manufacturers_slug, 'product', $manufacturers_args );

		$ingredients_labels = [
			'name'                       => __( 'Hoạt chất', 'gtt-shop' ),
			'singular_name'              => __( 'Hoạt chất', 'gtt-shop' ),
			'all_items'                  => __( 'All Hoạt chất', 'gtt-shop' ),
			'edit_item'                  => __( 'Edit Hoạt chất', 'gtt-shop' ),
			'view_item'                  => __( 'View Hoạt chất', 'gtt-shop' ),
			'update_item'                => __( 'Update Hoạt chất', 'gtt-shop' ),
			'add_new_item'               => __( 'Add New Hoạt chất', 'gtt-shop' ),
			'new_item_name'              => __( 'New Hoạt chất Name', 'gtt-shop' ),
			'parent_item'                => __( 'Parent Hoạt chất', 'gtt-shop' ),
			'parent_item_colon'          => __( 'Parent Hoạt chất:', 'gtt-shop' ),
			'search_items'               => __( 'Search Hoạt chất', 'gtt-shop' ),
			'popular_items'              => __( 'Popular Hoạt chất', 'gtt-shop' ),
			'separate_items_with_commas' => __( 'Separate Hoạt chất with commas', 'gtt-shop' ),
			'add_or_remove_items'        => __( 'Add or remove Hoạt chất', 'gtt-shop' ),
			'choose_from_most_used'      => __( 'Choose from the most used Hoạt chất', 'gtt-shop' ),
			'not_found'                  => __( 'No Hoạt chất found', 'gtt-shop' ),
			'back_to_items'              => __( '&larr; Back to Hoạt chất', 'gtt-shop' ),
		];
		$ingredients_args   = [
			'label'             => __( 'Hoạt chất', 'gtt-shop' ),
			'labels'            => $ingredients_labels,
			'hierarchical'      => true,
			'show_admin_column' => true,
		];
		$options  = get_option( 'gtt_shop' );
		$ingredients_slug = isset( $options[ 'ingredients_slug' ] ) ? $options[ 'ingredients_slug' ] : 'ingredients';
		register_taxonomy( $ingredients_slug, 'product', $ingredients_args );
	}

	public function register_meta_boxes( $meta_boxes ) {
		$product_id = isset( $_REQUEST['post'] ) ? $_REQUEST['post'] : '';
		$meta_boxes[] = [
			'id'         => 'product_info',
			'title'      => 'Thông tin sản phẩm',
			'post_types' => [ 'product' ],
			'fields'     => [
				[
					'id'      => 'price',
					'name'    => 'Giá VIP 1',
					'append'  => 'nghìn đồng',
					'columns' => 6,
				],
				[
					'name'    => 'Giá VIP 1 cũ',
					'type'    => 'custom_html',
					'std'     => get_post_meta( $product_id , 'price_old', true ) ? get_post_meta( $product_id , 'price_old', true ) . ' (nghìn đồng)' : 'Chưa có',
					'columns' => 6,
				],
				[
					'id'      => 'price_vip2',
					'name'    => 'Giá VIP 2',
					'append'  => 'nghìn đồng',
					'columns' => 6,
				],
				[
					'name'    => 'Giá VIP 2 cũ',
					'type'    => 'custom_html',
					'std'     => get_post_meta( $product_id , 'price_vip2_old', true ) ? get_post_meta( $product_id , 'price_vip2_old', true ) . ' (nghìn đồng)' : 'Chưa có',
					'columns' => 6,
				],
				[
					'id'      => 'price_vip3',
					'name'    => 'Giá VIP 3',
					'append'  => 'nghìn đồng',
					'columns' => 6,
				],
				[
					'name'    => 'Giá VIP 3 cũ',
					'type'    => 'custom_html',
					'std'     => get_post_meta( $product_id , 'price_vip3_old', true ) ? get_post_meta( $product_id , 'price_vip3_old', true ) . ' (nghìn đồng)' : 'Chưa có',
					'columns' => 6,
				],
				[
					'id'      => 'price_vip4',
					'name'    => 'Giá VIP 4',
					'append'  => 'nghìn đồng',
					'columns' => 6,
				],
				[
					'name'    => 'Giá VIP 4 cũ',
					'type'    => 'custom_html',
					'std'     => get_post_meta( $product_id , 'price_vip4_old', true ) ? get_post_meta( $product_id , 'price_vip4_old', true ) . ' (nghìn đồng)' : 'Chưa có',
					'columns' => 6,
				],
				[
					'id'      => 'price_vip5',
					'name'    => 'Giá VIP 5',
					'append'  => 'nghìn đồng',
					'columns' => 6,
				],
				[
					'name'    => 'Giá VIP 5 cũ',
					'type'    => 'custom_html',
					'std'     => get_post_meta( $product_id , 'price_vip5_old', true ) ? get_post_meta( $product_id , 'price_vip5_old', true ) . ' (nghìn đồng)' : 'Chưa có',
					'columns' => 6,
				],
				[
					'id'      => 'price_vip6',
					'name'    => 'Giá VIP 6',
					'append'  => 'nghìn đồng',
					'columns' => 6,
				],
				[
					'name'    => 'Giá VIP 6 cũ',
					'type'    => 'custom_html',
					'std'     => get_post_meta( $product_id , 'price_vip6_old', true ) ? get_post_meta( $product_id , 'price_vip6_old', true ) . ' (nghìn đồng)' : 'Chưa có',
					'columns' => 6,
				],
				[
					'id'      => 'image_url',
					'name'    => 'Link ảnh thumbnail',
					'columns' => 6,
				],
			],
		];
		return $meta_boxes;
	}

	/**
	 * Lưu giá cũ của giá thường, vip1, vip2
	 *
	 */
	public function save_old_price( $post_id ) {
		$submit_price = rwmb_request()->post( 'price' );
		$old_price = get_post_meta( $post_id, 'price', true );
		if ( $submit_price != $old_price ) {
			update_post_meta( $post_id, 'price_old', $old_price );
		}

		$submit_price_vip2 = rwmb_request()->post( 'price_vip2' );
		$old_price_vip2 = get_post_meta( $post_id, 'price_vip2', true );
		if ( $submit_price_vip2 != $old_price_vip2 ) {
			update_post_meta( $post_id, 'price_vip2_old', $old_price_vip2 );
		}

		$submit_price_vip3 = rwmb_request()->post( 'price_vip3' );
		$old_price_vip3 = get_post_meta( $post_id, 'price_vip3', true );
		if ( $submit_price_vip3 != $old_price_vip3 ) {
			update_post_meta( $post_id, 'price_vip3_old', $old_price_vip3 );
		}

		$submit_price_vip4 = rwmb_request()->post( 'price_vip4' );
		$old_price_vip4 = get_post_meta( $post_id, 'price_vip4', true );
		if ( $submit_price_vip4 != $old_price_vip4 ) {
			update_post_meta( $post_id, 'price_vip4_old', $old_price_vip4 );
		}

		$submit_price_vip5 = rwmb_request()->post( 'price_vip5' );
		$old_price_vip5 = get_post_meta( $post_id, 'price_vip5', true );
		if ( $submit_price_vip5 != $old_price_vip5 ) {
			update_post_meta( $post_id, 'price_vip5_old', $old_price_vip5 );
		}

		$submit_price_vip6 = rwmb_request()->post( 'price_vip6' );
		$old_price_vip6 = get_post_meta( $post_id, 'price_vip6', true );
		if ( $submit_price_vip6 != $old_price_vip6 ) {
			update_post_meta( $post_id, 'price_vip6_old', $old_price_vip6 );
		}
	}
}
