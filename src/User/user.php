<?php

/**
 * This class handles everything related to users, such as
 * - Change how name is displayed
 * - Ban users
 * - Show total of orders
 * - etc.
 */

namespace ELUSHOP\User;

class user {
	/**
	 * Constructor: add hooks
	 */
	public function init() {
		add_action( 'load-users.php', [ $this, 'users_page' ] );
		add_action( 'wp_ajax_push_user_to_erp', [ $this, 'push_user_to_erp' ] );
		add_action( 'wp_ajax_active_user', [ $this, 'active_user' ] );
		add_action( 'profile_update', [ $this, 'update_user' ], 99, 2 );
		add_action( 'pre_user_query', [ $this, 'user_search_by_multiple_parameters' ] );
	}

	public function update_user( $user_id, $old_user_data ) {
		$user_meta  = get_user_meta( $user_id );
		$birthday   = $user_meta['user_date_birth'][0];
		$user_phone = $user_meta['user_phone2'][0] ?? $user_meta['user_sdt'][0];

		$data_string = json_encode( array(
			'name'           => $user_meta['user_name'][0],
			'birthday'       => date( 'Y-m-d', strtotime( $birthday ) ),
			'drugstore_name' => $user_meta['user_ten_csdk'][0],
			'phone'          => $user_phone,
			'street'         => $user_meta['user_address'][0],
			// 'state_id'       => (int)$user_meta['user_province'][0],
		), JSON_UNESCAPED_UNICODE );

		$response = get_user_meta( $user_id, 'erp_response', true );
		if ( $response == '1' ) {
			// Update user to ERP
			$token = json_decode( $this->get_user_token( $user_id ) );
			$data  = wp_remote_get( 'https://erp.hapu.vn/api/v1/private/user/change_profile', array(
				'headers' => [
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $token->data->access_token,
				],
				'method'  => 'POST',
				'body'    => $data_string,
				'timeout' => 15,
			) );

			// Log User update
			$this->logs_user( $user_id );
		}
	}

	public function user_search_by_multiple_parameters( $query ) {
		global $wpdb;

		if ( empty( $_REQUEST['s'] ) ) {
			return;
		}
		$query->query_from .= ' LEFT JOIN ' . $wpdb->usermeta . ' ON ' . $wpdb->usermeta . '.user_id = ' . $wpdb->users . '.ID';
		$query->query_where = "WHERE 1=1 AND (user_login LIKE '%" . $_REQUEST['s'] . "%' OR user_email LIKE '%" . $_REQUEST['s'] . "%' OR meta_value LIKE '%" . $_REQUEST['s'] . "%')";
		return $query;
	}

	/**
	 * Load hooks for users page
	 * @return void
	 */
	public function users_page() {
		// Columns
		add_filter( 'manage_users_columns', [ $this, 'users_columns' ] );
		add_filter( 'manage_users_sortable_columns', [ $this, 'users_sortable_columns' ] );
		add_filter( 'manage_users_custom_column', [ $this, 'show_users_columns' ], 10, 3 );
		add_action( 'pre_user_query', [ $this, 'order_users' ] );
	}

	public function order_users( $query ) {
		global $pagenow;

		if ( ! is_admin() || 'users.php' !== $pagenow || isset( $_GET['orderby'] ) ) {
			return;
		}
		$query->query_orderby = 'ORDER BY user_registered DESC';
	}

	/**
	 * Change columns for users
	 *
	 * @param array $columns List of columns in users screen.
	 *
	 * @return array
	 */
	public function users_columns( $columns ) {
		$columns['user_name']   = 'Khách hàng';
		$columns['address']     = 'Địa chỉ';
		$columns['orders']      = 'Số đơn hàng';
		$columns['registered']  = 'Thời gian tạo';
		$columns['action']      = 'Tác vụ';
		$columns['message']     = 'Chi tiết lỗi';
		$columns['user_update'] = 'Người cập nhật';
		$columns['time_update'] = 'Thời gian cập nhật';
		unset( $columns['posts'] );
		unset( $columns['name'] );
		return $columns;
	}

	/**
	 * Change sortable columns for users
	 *
	 * @param array $columns List of columns in users screen.
	 *
	 * @return array
	 */
	public function users_sortable_columns( $columns ) {
		return [
			'username'   => 'login',
			'registered' => 'registered',
		];
	}

	/**
	 * Show content of user columns in admin
	 *
	 * @param string $output  Custom column output. Default empty.
	 * @param string $column  Column name.
	 * @param int    $user_id ID of the currently-listed user.
	 *
	 * @return string
	 */
	public function show_users_columns( $output, $column, $user_id ) {
		global $wpdb;
		$user        = get_userdata( $user_id );
		$update_log  = get_user_meta( $user_id, 'update_log', true );
		$count_order = $wpdb->get_col( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->orders WHERE `user` = $user_id;" ) );

		switch ( $column ) {
			case 'user_name':
				$output .= get_user_meta( $user_id, 'user_name', true );
				break;
			case 'action':
				$response = get_user_meta( $user_id, 'erp_response', true );

				if ( ! $response ) {
					$url     = wp_nonce_url( admin_url( 'admin-ajax.php?action=push_user_to_erp&user_id=' . $user_id ), 'account' );
					$output .= '<a href="' . $url . '" class="button" title="ERP">Đẩy user lên ERP</a>';
				} elseif ( $response == 1 ) {
					$output .= '<span class="badge badge--success" style="display: inline-block; color: #fff; background: #28a745; padding: 5px; border-radius: 3px;">Đã đẩy lên ERP</span>';
				} else {
					$output .= '<span class="badge badge--success" style="display: inline-block; color: #fff; background: red; padding: 5px; margin-right: 5px; border-radius: 3px;">Có lỗi khi đẩy lên ERP</span>';
					$url     = wp_nonce_url( admin_url( 'admin-ajax.php?action=push_user_to_erp&user_id=' . $user_id ), 'account' );
					$output .= '<a href="' . $url . '" class="button" title="ERP">Thử lại</a>';
				}

				$user_active = get_user_meta( $user_id, 'active_user', true );
				if ( ! $user_active ) {
					$url     = wp_nonce_url( admin_url( 'admin-ajax.php?action=active_user&user_id=' . $user_id ), 'account' );
					$output .= '<br><a style="margin-top: 5px;" href="' . $url . '" class="button" title="ERP">Kích hoạt TK</a>';
				} else {
					$output .= '<br><span class="badge badge--success" style="display: inline-block; color: #fff; background: #28a745; padding: 5px; border-radius: 3px; margin-top: 5px;">Đã kích hoạt TK</span>';
				}

				break;
			case 'registered':
				if ( $user->user_registered ) {
					$registered = strtotime( $user->user_registered ) + 7 * 3600; // sửa lại ngày đăng kí theo múi giờ Việt Nam
					$output     = date( 'd.m.Y', $registered ) . '<br>';
					$output    .= date( 'H:i', $registered );
				}
				break;
			case 'message':
				$output .= get_user_meta( $user_id, 'erp_message', true );
				break;
			case 'address':
				$output .= get_user_meta( $user_id, 'user_address', true );
				break;
			case 'orders':
				$output .= $count_order[0];
				break;
			case 'user_update':
				if ( ! empty( $update_log['user_update'] ) ) {
					$user_name = get_user_meta( $update_log['user_update'], 'user_name', true );
					$output   .= '<a href="' . get_edit_user_link( $update_log['user_update'] ) . '">' . $user_name . '</a>';
				}
				break;
			case 'time_update':
				if ( ! empty( $update_log['date'] ) ) {
					$date_update = strtotime( $update_log['date'] );
					$output      = date( 'd.m.Y', $date_update ) . '<br>';
					$output     .= date( 'H:i', $date_update );
				}
				break;
		}

		return $output;
	}

	public function active_user() {
		$user_id = $_GET['user_id'];
		update_user_meta( $user_id, 'active_user', 1 );

		// Log User update
		$this->logs_user( $user_id );

		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'users.php' ) );
		die;
	}

	/**
	 * Enable or disable account
	 *
	 * @return void
	 */
	public function push_user_to_erp() {
		$user_id     = $_GET['user_id'];
		$user_meta   = get_user_meta( $user_id );
		$user_data   = get_userdata( $user_id );
		$user_phone  = $user_meta['user_phone2'][0] ?? $user_meta['user_sdt'][0];
		$prefix_user = rwmb_meta( 'prefix_user_erp', [ 'object_type' => 'setting' ], 'setting' );

		$data_string = json_encode( array(
			'login'            => $prefix_user . $user_meta['user_sdt'][0],
			'password'         => '111111',
			'confirm_password' => '111111',
			'name'             => $user_meta['user_name'][0],
			'drugstore_name'   => $user_meta['user_ten_csdk'][0],
			'phone'            => $user_phone,
			'mail'             => $user_data->user_email,
			'street'           => $user_meta['user_address'][0],
			'state_id'         => (int) $user_meta['user_province'][0],
		), JSON_UNESCAPED_UNICODE );

		$token       = json_decode( $this->get_token_api() );
		$data        = wp_remote_get( 'https://erp.hapu.vn/api/v1/public/Authentication/register', array(
			'headers' => [
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $token->data->access_token,
			],
			'method'  => 'POST',
			'body'    => $data_string,
			'timeout' => 15,
		) );
		$response    = json_decode( $data['body'], true );
		$erp_message = $response['code'] == 1 ? '' : $response['message'];
		update_user_meta( $user_id, 'erp_response', $response['code'] );
		update_user_meta( $user_id, 'erp_message', $erp_message );

		// Log User update
		$this->logs_user( $user_id );

		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'users.php' ) );
		die;
	}
	public function get_token_api() {
		$data_string = json_encode( array(
			'login'    => 'xuannt@nodo.vn',
			'password' => '111555',
		), JSON_UNESCAPED_UNICODE );

		$request = wp_remote_get( 'https://erp.hapu.vn/api/v1/public/Authentication/login', array(
			'headers' => [
				'Content-Type' => 'application/json',
			],
			'method'  => 'POST',
			'body'    => $data_string,
			'timeout' => 15,
		) );

		return wp_remote_retrieve_body( $request );
	}

	public function get_user_token( $user_id ) {
		$user_meta   = get_user_meta( $user_id );
		$prefix_user = rwmb_meta( 'prefix_user_erp', [ 'object_type' => 'setting' ], 'setting' );

		$data_string = json_encode( array(
			'login'    => $prefix_user . $user_meta['user_sdt'][0],
			'password' => '111111',
		), JSON_UNESCAPED_UNICODE );

		$request = wp_remote_get( 'https://erp.hapu.vn/api/v1/public/Authentication/login', array(
			'headers' => [
				'Content-Type' => 'application/json',
			],
			'method'  => 'POST',
			'body'    => $data_string,
			'timeout' => 15,
		) );

		return wp_remote_retrieve_body( $request );
	}

	public function logs_user( $user_id ) {
		$current_user = get_current_user_id();

		$data_log = [
			'user_update' => $current_user,
			'date'        => current_time( 'mysql' ),
		];
		update_user_meta( $user_id, 'update_log', $data_log );
	}
}
