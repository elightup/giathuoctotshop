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
	 * @param array $columns List of columns in users screen
	 *
	 * @return array
	 */
	public function users_columns( $columns ) {
		$columns['registered'] = 'Thời gian tạo';
		$columns['action'] = 'Tác vụ';
		unset( $columns['posts'] );
		return $columns;
	}

	/**
	 * Change sortable columns for users
	 *
	 * @param array $columns List of columns in users screen
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
		$user = get_userdata( $user_id );
		switch ( $column ) {
			case 'action':
				$response = get_user_meta( $user_id, 'erp_response', true );

				if ( ! $response ) {
					$url = wp_nonce_url( admin_url( 'admin-ajax.php?action=push_user_to_erp&user_id=' . $user_id ), 'account' );
					$output .= '<a href="' . $url . '" class="button" title="ERP">Đẩy user lên ERP</a>';
				} elseif ( $response == 1 ) {
					$output .= '<span class="badge badge--success" style="display: inline-block; color: #fff; background: #28a745; padding: 5px; border-radius: 3px;">Đã đẩy lên ERP</span>';
				} else {
					$output .= '<span class="badge badge--success" style="display: inline-block; color: #fff; background: red; padding: 5px; margin-right: 5px; border-radius: 3px;">Có lỗi khi đẩy lên ERP</span>';
					$url = wp_nonce_url( admin_url( 'admin-ajax.php?action=push_user_to_erp&user_id=' . $user_id ), 'account' );
					$output .= '<a href="' . $url . '" class="button" title="ERP">Thử lại</a>';
				}
				break;
			case 'registered':
				if ( $user->user_registered ) {
					$registered = strtotime( $user->user_registered ) + 7 * 3600; // sửa lại ngày đăng kí theo múi giờ Việt Nam
					$output = date( 'd.m.Y', $registered ) . '<br>';
					$output .= date ( 'H:i', $registered );
				}
				break;
		}

		return $output;
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
		$prefix_user = rwmb_meta( 'prefix_user_erp', ['object_type' => 'setting'], 'setting' );

		$data_string = json_encode( array(
			'login'            => $prefix_user . $user_meta['user_sdt'][0],
			'password'         => "111111",
			'confirm_password' => "111111",
			'name'             => $user_meta['user_name'][0],
			'drugstore_name'   => $user_meta['user_ten_csdk'][0],
			'phone'            => $user_meta['user_sdt'][0],
			'mail'             => $user_data->user_email,
			'street'           => $user_meta['user_address'][0],
			'state_id'         => (int)$user_meta['user_province'][0],
		), JSON_UNESCAPED_UNICODE );

		$token = json_decode( $this->get_token_api() );
		$data = wp_remote_get( 'http://clone.hapu.vn/api/v1/public/Authentication/register', array(
			'headers' => [
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $token->data->access_token,
			],
			'method'  => 'POST',
			'body'    => $data_string,
			'timeout' => 15,
		) );
		$response = json_decode( $data['body'], true );
		update_user_meta( $user_id, 'erp_response', $response['code'] );
		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'users.php' ) );
		die;
	}
	public function get_token_api() {
		$data_string = json_encode( array(
			'login'    => 'xuannt@nodo.vn',
			'password' => '111555',
		), JSON_UNESCAPED_UNICODE );

		$request = wp_remote_get( 'http://clone.hapu.vn/api/v1/public/Authentication/login', array(
			'headers' => [
				'Content-Type'  => 'application/json',
			],
			'method'  => 'POST',
			'body'    => $data_string,
			'timeout' => 15,
		) );

		return wp_remote_retrieve_body( $request );
	}
}
