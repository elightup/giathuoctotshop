<?php
namespace ELUSHOP\User;

class Views {
	public function __construct() {
		add_filter( 'views_users', [ $this, 'add_views' ] );
		add_action( 'admin_bar_menu', [ $this, 'admin_bar_notification' ], 99 );

		add_action( 'restrict_manage_users', [ $this, 'filter_user_restrict'] );
		add_action( 'pre_get_users', [ $this, 'filter_users' ] );
	}

	public function add_views( $views ) {
		$type = filter_input( INPUT_GET, 'gtt-type' );
		$views['inactive'] = '<a href="' . esc_url( add_query_arg( 'gtt-type', 'inactive', admin_url( 'users.php' ) ) ) . '" class="' . ( $type === 'inactive' ? 'current' : '' ) . '">KH chưa kích hoạt <span class="count">(' . $this->get_inactive_count() . ')</span></a>';
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

	public function filter_user_restrict ( $which ) {
		$date_start = '<input type="date" name="start_date_%s" id="user_date" value="" style="margin-left: 10px">';
		$date_end = '<input type="date" name="end_date_%s" id="user_date" value="" style="margin-left: 10px">';
		$ct = '<select name="city_%s" id="city" style="float:none;margin-left:10px;">
					<option value="">%s</option>%s</select>';
		$cities = get_cities_array();
		$options = [];
		foreach ( $cities as $city ) {
			$id = $city['key'];
			$name = $city['value'];
			$options .= '<option value="'. $id .'">'.$name.'</option>';
		}
		$select = sprintf( $ct, $which, __( 'Thành phố' ), $options );
		$date_st = sprintf( $date_start, $which );
		$date_ed = sprintf( $date_end, $which );
		echo $date_st;
		echo $date_ed;
		echo $select;
		submit_button(__( 'Filter' ), null, $which, false);
	}

	public function filter_users( \WP_User_Query $query ) {
		$city_top         = isset( $_GET['city_top'] ) ? $_GET['city_top'] : null;
		$city_bottom      = isset( $_GET['city_bottom'] ) ? $_GET['city_bottom'] : null;
		$dateStart_top    = isset( $_GET['start_date_top']  )? $_GET['start_date_top'] : null;
		$dateStart_bottom = isset( $_GET['start_date_bottom'] ) ? $_GET['start_date_bottom'] : null;
		$dateEnd_top      = isset( $_GET['end_date_top'] ) ? $_GET['end_date_top'] : null;
		$dateEnd_bottom   = isset( $_GET['end_date_bottom'] ) ? $_GET['end_date_bottom'] : null;
		if ( !empty($city_top) OR !empty($city_bottom) ) {
			$city = !empty($city_top) ? $city_top : $city_bottom;
			$meta_query = array (array (
				'key' => 'user_province',
				'value' => $city,
				'compare' => 'LIKE'
			 ));
			 $query->set('meta_query', $meta_query);
		}
		if ( !empty($dateStart_top) && !empty($dateEnd_top) OR !empty($dateStart_bottom) && !empty($dateEnd_bottom) ) {
			$dateStart 	= !empty($dateStart_top) ? $dateStart_top : $dateStart_bottom;
			$dateEnd 	= !empty($dateEnd_top) ? $dateEnd_top : $dateEnd_bottom;
			$date_query = array(
				'relation' => 'AND',
				array(
					'before'        => $dateEnd,
					'after'         => $dateStart,
					'inclusive'     => true,
				),
			);
			$query->set( 'date_query', $date_query );
		}

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
			$query->set( 'date_query', [
				'year'  => date( 'Y' ),
				'month' => date( 'n' ),
				'day'   => date( 'j' ),
			] );
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
			$query->set( 'date_query', [
				'year'  => date( 'Y' ),
				'month' => date( 'n' ),
				'day'   => date( 'j' ),
			] );
		}
		if ( $type === 'inactive' ) {
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

	private function get_inactive_count() {
		$users = get_users( [
			'gtt_custom' => true,
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