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
		add_action( 'wp_ajax_set_activiti_user', [ $this, 'set_activiti_user' ] );
		add_action( 'wp_ajax_soft_delete_user', [ $this, 'soft_delete_user' ] );
		add_action( 'user_row_actions', [ $this, 'set_row_action' ],10,2 );

	}

	public function update_user( $user_id, $old_user_data ) {
		$user_meta   = get_user_meta( $user_id );
		$user_data   = get_userdata( $user_id );
		$user_phone  = $user_meta['user_phone2'][0] ?? $user_meta['user_sdt'][0];
		$prefix_user = rwmb_meta( 'prefix_user_erp', [ 'object_type' => 'setting' ], 'setting' );

		$data_string = json_encode( array(
			'login'          => $prefix_user . $user_meta['user_sdt'][0],
			'name'           => $user_meta['user_name2'][0],
			'drugstore_name' => $user_meta['user_ten_csdk'][0],
			'phone'          => $user_phone,
			'dob'            => $user_meta['user_date_birth'][0],
			'email'          => $user_data->user_email,
			'address'        => $user_meta['user_address'][0],
			'business_form'  => $user_meta['user_hinhthuc_kd'][0],
			'channel_sale'   => 'giathuoc',
			'user_market'    => $user_meta['user_nvpt'][0],
		), JSON_UNESCAPED_UNICODE );

		$response = get_user_meta( $user_id, 'erp_response', true );
		if ( $response == '1' ) {
			// Update user to ERP
			$data = wp_remote_get( 'https://erp.hapu.vn/rest_api/public/user/register', array(
				'headers' => [
					'Content-Type' => 'application/json',
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
		$columns['user_name']   = 'Kh??ch h??ng';
		$columns['address']     = '?????a ch???';
		$columns['orders']      = 'S??? ????n h??ng';
		$columns['registered']  = 'Th???i gian t???o';
		$columns['gioithieu']   = 'Ng?????i gi???i thi???u';
		$columns['action']      = 'T??c v???';
		$columns['message']     = 'Chi ti???t l???i';

		$columns['user_update'] = 'Ng?????i c???p nh???t';
		$columns['time_update'] = 'Th???i gian c???p nh???t';
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
				$output .= get_user_meta( $user_id, 'user_name2', true ) ?? '';
				break;
			case 'action':
				$response = get_user_meta( $user_id, 'erp_response', true );

				if ( ! $response ) {
					$url     = wp_nonce_url( admin_url( 'admin-ajax.php?action=push_user_to_erp&user_id=' . $user_id ), 'account' );
					$output .= '<a href="' . $url . '" class="button" title="ERP">?????y user l??n ERP</a>';
				} elseif ( $response == 1 ) {
					$output .= '<span class="badge badge--success" style="display: inline-block; color: #fff; background: #28a745; padding: 5px; border-radius: 3px;">???? ?????y l??n ERP</span>';
				} else {
					$output .= '<span class="badge badge--success" style="display: inline-block; color: #fff; background: red; padding: 5px; margin-right: 5px; border-radius: 3px;">C?? l???i khi ?????y l??n ERP</span>';
					$url     = wp_nonce_url( admin_url( 'admin-ajax.php?action=push_user_to_erp&user_id=' . $user_id ), 'account' );
					$output .= '<a href="' . $url . '" class="button" title="ERP">Th??? l???i</a>';
				}

				$user_active = get_user_meta( $user_id, 'active_user', true );
				if ( ! $user_active ) {
					$url     = wp_nonce_url( admin_url( 'admin-ajax.php?action=active_user&user_id=' . $user_id ), 'account' );
					$output .= '<br><a style="margin-top: 5px;" href="' . $url . '" class="button" title="ERP">K??ch ho???t TK</a>';
				} else {
					$output .= '<br><span class="badge badge--success" style="display: inline-block; color: #fff; background: #28a745; padding: 5px; border-radius: 3px; margin-top: 5px;">???? k??ch ho???t TK</span>';
				}

				break;
			case 'registered':
				if ( $user->user_registered ) {
					$registered = strtotime( $user->user_registered ) + 7 * 3600; // s???a l???i ng??y ????ng k?? theo m??i gi??? Vi???t Nam
					$output     = date( 'd.m.Y', $registered ) . '<br>';
					$output    .= date( 'H:i', $registered );
				}
				break;
			case 'gioithieu':
				$output .= get_user_meta( $user_id, 'user_nguoi_gioithieu', true );
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
	public function set_activiti_user(){
		$user_id = $_GET['user_id'];
		update_user_meta( $user_id, 'trash_user', 0 );

		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'users.php' ) );
		die;

	}
	public function soft_delete_user(){
		$user_id = $_GET['user_id'];
		update_user_meta( $user_id, 'trash_user', 1 );
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
			'login'          => $prefix_user . $user_meta['user_sdt'][0],
			'name'           => $user_meta['user_name2'][0],
			'drugstore_name' => $user_meta['user_ten_csdk'][0],
			'phone'          => $user_phone,
			'dob'            => $user_meta['user_date_birth'][0],
			'email'          => $user_data->user_email,
			'address'        => $user_meta['user_address'][0],
			'business_form'  => $user_meta['user_hinhthuc_kd'][0],
			'channel_sale'   => 'giathuoc',
			'partner_code'   => $user_meta['user_code'][0],
			'user_market'    => $user_meta['user_nvpt'][0],
		), JSON_UNESCAPED_UNICODE );

		$data        = wp_remote_get( 'https://erp.hapu.vn/rest_api/public/user/register', array(
			'headers' => [
				'Content-Type' => 'application/json',
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

	public function logs_user( $user_id ) {
		$current_user = get_current_user_id();

		$data_log = [
			'user_update' => $current_user,
			'date'        => current_time( 'mysql' ),
		];
		update_user_meta( $user_id, 'update_log', $data_log );
	}
	public function set_row_action($actions,$user_object){

		$status = get_user_meta( $user_object->ID, 'trash_user', true );
		unset($actions['delete']);

		if($status == 1){
			$url     = wp_nonce_url( admin_url( 'admin-ajax.php?action=set_activiti_user&user_id=' . $user_object->ID ), 'account' );
			$actions['set_activiti_user'] = "<a style='color:#28a745' href='" .$url . "'>Kh??i phu??c KH</a>";
			return $actions;
		}else{
			$url     = wp_nonce_url( admin_url( 'admin-ajax.php?action=soft_delete_user&user_id=' . $user_object->ID ), 'account' );
		$actions['soft_delete_user'] = "<a style='color:red' href='" .$url . "'>Xo??a</a>";
		return $actions;

		}

	}
}
