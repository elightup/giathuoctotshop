<?php
namespace ELUSHOP\User;

class Views {
	public function __construct() {
		add_filter( 'views_users', [ $this, 'add_views' ] );

		add_action( 'pre_get_users', [ $this, 'filter_users' ] );
	}

	public function add_views( $views ) {
		$type = filter_input( INPUT_GET, 'gtt-type' );

		$views['today-inactive'] = '<a href="' . esc_url( add_query_arg( 'gtt-type', 'today-inactive', admin_url( 'users.php' ) ) ) . '" class="' . ( $type === 'today-inactive' ? 'current' : '' ) . '>KH mới</a>';
		$views['today-active'] = '<a href="' . esc_url( add_query_arg( 'gtt-type', 'today-active', admin_url( 'users.php' ) ) ) . '" class="' . ( $type === 'today-active' ? 'current' : '' ) . '>KH mới kích hoạt</a>';

		return $views;
	}

	public function filter_users( \WP_User_Query $query ) {
		if ( ! is_admin() ) {
			return;
		}
		$screen = get_current_screen();
		if ( $screen->id !== 'users' ) {
			return;
		}

		$type = filter_input( INPUT_GET, 'gtt-type' );
		if ( empty( $type ) ) {
			return;
		}

		$query->set( 'date_query', [
			'year'  => date( 'Y' ),
			'month' => date( 'n' ),
			'day'   => date( 'j' ),
		] );

		if ( $type === 'today-active' ) {
			$query->set( 'meta_key', 'active_user' );
			$query->set( 'meta_value', 1 );
		}
		if ( $type === 'today-inactive' ) {
			$query->set( 'meta_query', [
				[
					'key'     => 'active_user',
					'value'   => 1,
					'compare' => 'NOT EXISTS',
				],
			] );
		}
	}
}