<?php

namespace ELUSHOP\Product;

class PostType {
	public function init() {
		add_action( 'init', [ $this, 'register_post_type' ] );
		add_action( 'init', [ $this, 'register_taxonomies' ] );
		add_filter( 'rwmb_meta_boxes', [ $this, 'register_meta_boxes' ] );
	}

	public function register_post_type() {
		$labels = [
			'name'               => __( 'Products', 'gtt-shop' ),
			'singular_name'      => __( 'Product', 'gtt-shop' ),
			'add_new'            => _x( 'Add New', 'Product', 'gtt-shop' ),
			'add_new_item'       => __( 'Add New Product', 'gtt-shop' ),
			'edit_item'          => __( 'Edit Product', 'gtt-shop' ),
			'new_item'           => __( 'New Product', 'gtt-shop' ),
			'view_item'          => __( 'View Product', 'gtt-shop' ),
			'view_items'         => __( 'View Products', 'gtt-shop' ),
			'search_items'       => __( 'Search Products', 'gtt-shop' ),
			'not_found'          => __( 'No products found.', 'gtt-shop' ),
			'not_found_in_trash' => __( 'No products found in Trash.', 'gtt-shop' ),
			'parent_item_colon'  => __( 'Parent Products:', 'gtt-shop' ),
			'all_items'          => __( 'All Products', 'gtt-shop' ),
		];
		$options = get_option( 'gtt_shop' );
		$slug    = isset( $options[ 'product_slug' ] ) ? $options[ 'product_slug' ] : 'product';

		$args   = [
			'label'       => __( 'Products', 'gtt-shop' ),
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
		$category_labels = [
			'name'                       => __( 'Categories', 'gtt-shop' ),
			'singular_name'              => __( 'Category', 'gtt-shop' ),
			'all_items'                  => __( 'All Categories', 'gtt-shop' ),
			'edit_item'                  => __( 'Edit Category', 'gtt-shop' ),
			'view_item'                  => __( 'View Category', 'gtt-shop' ),
			'update_item'                => __( 'Update Category', 'gtt-shop' ),
			'add_new_item'               => __( 'Add New Category', 'gtt-shop' ),
			'new_item_name'              => __( 'New Category Name', 'gtt-shop' ),
			'parent_item'                => __( 'Parent Category', 'gtt-shop' ),
			'parent_item_colon'          => __( 'Parent Category:', 'gtt-shop' ),
			'search_items'               => __( 'Search Categories', 'gtt-shop' ),
			'popular_items'              => __( 'Popular Categories', 'gtt-shop' ),
			'separate_items_with_commas' => __( 'Separate categories with commas', 'gtt-shop' ),
			'add_or_remove_items'        => __( 'Add or remove categories', 'gtt-shop' ),
			'choose_from_most_used'      => __( 'Choose from the most used categories', 'gtt-shop' ),
			'not_found'                  => __( 'No categories found', 'gtt-shop' ),
			'back_to_items'              => __( '&larr; Back to categories', 'gtt-shop' ),
		];
		$category_args   = [
			'label'             => __( 'Categories', 'gtt-shop' ),
			'labels'            => $category_labels,
			'hierarchical'      => true,
			'show_admin_column' => true,
		];
		$options       = get_option( 'gtt_shop' );
		$category_slug = isset( $options[ 'product_category_slug' ] ) ? $options[ 'product_category_slug' ] : 'product-category';
		register_taxonomy( $category_slug, 'product', $category_args );

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
		$options  = get_option( 'gtt_shop' );
		$currency = $options[ 'currency' ];
		$meta_boxes[] = [
			'title'      => __( 'Product Information', 'gtt-shop' ),
			'post_types' => [ 'product' ],
			'fields'     => [
				[
					'id'   => 'price',
					'name' => __( 'Price', 'gtt-shop' ),
					'type' => 'number',
					'min'  => 0,
					'desc' => sprintf( __( 'In %s.', 'gtt-shop' ), $currency ),
					'size' => 10,
				],
				[
					'id'   => 'price_before_sale',
					'name' => __( 'Price before sale', 'gtt-shop' ),
					'type' => 'number',
					'min'  => 0,
					'desc' => sprintf( __( 'In %s. Leave blank if the product has no discount.', 'gtt-shop' ), $currency ),
					'size' => 10,
				],
			],
		];
		return $meta_boxes;
	}
}
