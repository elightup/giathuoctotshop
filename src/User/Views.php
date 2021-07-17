<?php
namespace ELUSHOP\User;

class Views {
	public function __construct() {
		add_filter( 'views_users', [ $this, 'add_views' ] );
		add_action( 'admin_bar_menu', [ $this, 'admin_bar_notification' ], 99 );

		add_action( 'pre_get_users', [ $this, 'filter_users' ] );
	}

	public function add_views( $views ) {
		$type = filter_input( INPUT_GET, 'gtt-type' );

		$views['today-inactive'] = '<a href="' . esc_url( add_query_arg( 'gtt-type', 'today-inactive', admin_url( 'users.php' ) ) ) . '" class="' . ( $type === 'today-inactive' ? 'current' : '' ) . '">KH mới chưa kích hoạt <span class="count">(' . $this->get_today_inactive_count() . ')</span></a>';
		$views['today-active'] = '<a href="' . esc_url( add_query_arg( 'gtt-type', 'today-active', admin_url( 'users.php' ) ) ) . '" class="' . ( $type === 'today-active' ? 'current' : '' ) . '">KH mới đã kích hoạt <span class="count">(' . $this->get_today_active_count() . ')</span></a>';

		return $views;
	}

	public function admin_bar_notification( \WP_Admin_Bar $wp_admin_bar ) {
		$wp_admin_bar->add_node( [
			'id'    => 'today-inactive',
			'title' => '<span class="bubble">' . $this->get_today_inactive_count() . '</span> KH mới',
			'href'  => esc_url( add_query_arg( 'gtt-type', 'today-inactive', admin_url( 'users.php' ) ) ),
		] );
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

		if ( $query->get( 'gtt_custom' ) ) {
			return;
		}

		$query->set( 'date_query', [
			'year'  => date( 'Y' ),
			'month' => date( 'n' ),
			'day'   => date( 'j' ),
		] );

		if ( $type === 'today-active' ) {
			$meta_query = $query->get( 'meta_query' );
			if ( empty( $meta_query ) ) {
				$meta_query = [];
			}
			$meta_query[] = [
				'key'   => 'active_user',
				'value' => 1,
			];
			$query->set( 'meta_query', $meta_query );
		}
		if ( $type === 'today-inactive' ) {
			$meta_query = $query->get( 'meta_query' );
			if ( empty( $meta_query ) ) {
				$meta_query = [];
			}
			$meta_query[] = [
				'key'     => 'active_user',
				'value'   => 1,
				'compare' => 'NOT EXISTS',
			];
			$query->set( 'meta_query', $meta_query );
		}
	}

	private function get_today_inactive_count() {
		$users = get_users( [
			'gtt_custom' => true,
			'date_query' => [
				'year'  => date( 'Y' ),
				'month' => date( 'n' ),
				'day'   => date( 'j' ),
			],
			'meta_query' => [
				[
					'key'     => 'active_user',
					'value'   => 1,
					'compare' => 'NOT EXISTS',
				],
			],
			'fields' => 'ID',
		] );

		return count( $users );
	}

	private function get_today_active_count() {
		$users = get_users( [
			'gtt_custom' => true,
			'date_query' => [
				'year'  => date( 'Y' ),
				'month' => date( 'n' ),
				'day'   => date( 'j' ),
			],
			'meta_query' => [
				[
					'key'   => 'active_user',
					'value' => 1,
				],
			],
			'fields'     => 'ID',
		] );

		return count( $users );
	}
}