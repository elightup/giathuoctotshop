<?php

namespace ELUSHOP\Order;

class Table extends \WP_List_Table {
	protected $base_url;

	public function __construct() {
		parent::__construct(
			[
				'singular' => 'order',
				'plural'   => 'orders',
			]
		);
		$this->base_url = admin_url( 'edit.php?page=orders&post_type=product' );
	}

	protected function get_views() {
		$links = [];

		$status = isset( $_REQUEST['status'] ) ? $_REQUEST['status'] : '';

		// All items.
		$class        = $status ? '' : ' class="current"';
		$links['all'] = "<a href='{$this->base_url}'$class>" . sprintf( 'Tất cả <span class="count">(%s)</span>', $this->get_total_items() ) . '</a>';

		$statuses = [
			'completed'  => __( 'Đã hoàn thành', 'elu-shop' ),
			'pending' => __( 'Đang xử lý', 'elu-shop' ),
			'trash'   => __( 'Thùng rác', 'elu-shop' ),
		];
		foreach ( $statuses as $key => $label ) {
			$class         = $key === $status ? ' class="current"' : '';
			$url           = add_query_arg( 'status', $key, $this->base_url );
			$links[ $key ] = "<a href='$url'$class>" . sprintf( "$label <span class='count'>(%s)</span>", $this->get_total_items( $key ) ) . '</a>';
		}
		return $links;
	}

	protected function extra_tablenav( $which ) {
		?>
		<div class="alignleft actions">
			<?php
			if ( 'top' === $which ) {
				$this->months_dropdown( 'order' );
				submit_button( __( 'Lọc', 'elu-shop' ), '', 'filter_action', false, [ 'id' => 'post-query-submit' ] );
			}

			if ( isset( $_GET['status'] ) && 'trash' === $_GET['status'] && $this->get_total_items( 'trash' ) ) {
				submit_button( __( 'Xoá hết thùng rác', 'elu-shop' ), 'apply', 'delete_all', false );
			}
			?>
		</div>
		<?php
	}

	protected function months_dropdown( $post_type ) {
		global $wpdb, $wp_locale;

		$where  = $this->get_sql_where_clause();
		$months = $wpdb->get_results(
			"
			SELECT DISTINCT YEAR( `date` ) AS year, MONTH( `date` ) AS month
			FROM $wpdb->orders
			$where
			ORDER BY `date` DESC
		"
		);

		$month_count = count( $months );
		if ( ! $month_count || ( 1 == $month_count && 0 == $months[0]->month ) ) {
			return;
		}

		$m = isset( $_GET['m'] ) ? (int) $_GET['m'] : 0;
		?>
		<select name="m" id="filter-by-date">
			<option<?php selected( $m, 0 ); ?> value="0"><?php __( 'All dates', 'elu-shop' ); ?></option>
			<?php
			foreach ( $months as $arc_row ) {
				if ( 0 == $arc_row->year ) {
					continue;
				}

				$month = zeroise( $arc_row->month, 2 );
				$year  = $arc_row->year;

				printf(
					"<option %s value='%s'>%s</option>\n",
					selected( $m, $year . $month, false ),
					esc_attr( $arc_row->year . $month ),
					/* translators: 1: month name, 2: 4-digit year */
					sprintf( __( '%1$s %2$d' ), $wp_locale->get_month( $month ), $year )
				);
			}
			?>
		</select>
		<?php
	}

	public function prepare_items() {
		global $wpdb;

		$this->handle_actions();

		$this->_column_headers = $this->get_column_info();

		$per_page = $this->get_items_per_page( 'orders_per_page', 20 );
		$page     = $this->get_pagenum();

		$this->set_pagination_args(
			[
				'total_items' => $this->get_total_items(),
				'per_page'    => $per_page,
			]
		);

		$where  = $this->get_sql_where_clause();
		$order  = $this->get_sql_order_clause();
		$limit  = "LIMIT $per_page";
		$offset = ' OFFSET ' . ( $page - 1 ) * $per_page;
		$sql    = "SELECT * FROM $wpdb->orders $where $order $limit $offset";

		$this->items = $wpdb->get_results( $sql, 'ARRAY_A' );
	}

	protected function get_total_items( $status = '' ) {
		global $wpdb;

		$where = '';
		if ( $status ) {
			$where = $wpdb->prepare( 'WHERE `status`=%s', $status );
		}
		return $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->orders $where" );
	}

	protected function get_sql_where_clause() {
		global $wpdb;

		$where = [];
		if ( isset( $_REQUEST['status'] ) ) {
			$where[] = $wpdb->prepare( '`status`=%s', $_REQUEST['status'] );
		}

		return $where ? 'WHERE ' . implode( ' ', $where ) : '';
	}

	protected function get_sql_order_clause() {
		$order  = 'ORDER BY date';
		$order .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' DESC';
		return $order;
	}

	public function get_columns() {
		$columns = [
			'cb'       => '<input type="checkbox">',
			'id'       => 'ID',
			'status'   => __( 'Trạng thái', 'elu-shop' ),
			'customer' => __( 'Khách hàng', 'elu-shop' ),
			'products' => __( 'Sản phẩm', 'elu-shop' ),
			'amount'   => __( 'Tổng tiền', 'elu-shop' ),
			'date'     => __( 'Ngày', 'elu-shop' ),
			'action'   => __( 'Thao tác', 'elu-shop' ),
		];

		return $columns;
	}

	public function get_sortable_columns() {
		$sortable_columns = [
			'date'   => [ 'date', true ],
		];

		return $sortable_columns;
	}

	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="orders[]" value="%s">',
			intval( $item['id'] )
		);
	}

	public function column_id( $item ) {
		$title = sprintf(
			'<a href="%s"><strong>' . __( 'Đơn hàng', 'elu-shop' ) . ': #%d</strong></a>',
			add_query_arg(
				[
					'action' => 'view',
					'id'     => $item['id'],
				],
				$this->base_url
			),
			$item['id']
		);
		return $title . $this->row_actions( $this->get_row_actions( $item ) );
	}

	public function column_default( $item, $column_name ) {
		return isset( $item[ $column_name ] ) ? $item[ $column_name ] : '';
	}

	public function column_status( $item ) {
		$statuses = [
			'pending' => [ 'badge', __( 'Đang xử lý', 'elu-shop' ) ],
			'completed'  => [ 'badge badge--success', __( 'Đã hoàn thành', 'elu-shop' ) ],
			'trash'   => [ 'badge badge--danger', __( 'Đã xoá', 'elu-shop' ) ],
		];
		$status   = $statuses[ $item['status'] ];
		$user     = get_userdata( $item['user'] );
		$payments = $item['info'];
		$payments = json_decode( $payments, true );

		printf( '<span class="%s">%s</span>', $status[0], $status[1] );
		if ( 'pending' === $item['status'] ) {
			printf(
				'<a href="%s" class="button">' . __( 'Đã hoàn thành', 'elu-shop' ) . ' </a>',
				add_query_arg(
					[
						'action'   => 'close',
						'id'       => $item['id'],
						// 'user'     => $user->ID,
						'amount'   => number_format( $item['amount'], 0, '', '.' ),
						// 'payments' => $payments[0]['pay'],
						'_wpnonce' => wp_create_nonce( 'ps_close_order' ),
					],
					$this->base_url
				)
			);
		}
		if ( 'completed' === $item['status'] ) {
			printf(
				'<a href="%s" class="button">' . __( 'Đang xử lý', 'elu-shop' ) . ' </a>',
				add_query_arg(
					[
						'action'   => 'open',
						'id'       => $item['id'],
						// 'user'     => $user->ID,
						'amount'   => number_format( $item['amount'], 0, '', '.' ),
						// 'payments' => $payments[0]['pay'],
						'_wpnonce' => wp_create_nonce( 'ps_open_order' ),
					],
					$this->base_url
				)
			);
		}
	}

	public function column_products( $item ) {
		$data   = $item['data'];
		$data   = json_decode( $data, true );
		$output = [];
		foreach ( $data as $product ) {
			$output[] = sprintf( '%s: %s', $product['title'], $product['quantity'] );
		}
		echo implode( '<br>', $output );
	}

	public function column_customer( $item ) {
		$info        = json_decode( $item['info'] );
		$user_id     = $item['user'];
		$user_data   = get_userdata( $user_id );
		$status_user = get_user_meta( $user_id, 'erp_response', true );
		$status_user = ! isset( $status_user ) || $status_user != 1 ? 'Chưa kích hoạt' : 'Đã kích hoạt';

		echo esc_html( $info->name ) . '<br>';
		if ( $user_data ) {
			echo 'Login: ' . $user_data->user_login . '(' . $status_user . ')';
		} else {
			echo 'KH chưa đăng nhập';
		}
	}

	public function column_amount( $item ) {
		$voucher = json_decode( $item['voucher'], true );
		if ( ! $voucher ) {
			echo number_format_i18n( $item['amount'], 0 ) . ' ' . ps_setting( 'currency' );
			return;
		}
		$giam_gia = 0;
		if ( $voucher['voucher_type'] == 'by_price' ) {
			$giam_gia = $voucher['voucher_price'];
		} else {
			$giam_gia = $voucher['voucher_price'] * $item['amount'] / 100;
		}
		$amount = $item['amount'] - $giam_gia;
		echo number_format( $amount, 0, '', '.' ); ?> <?= ps_setting( 'currency' );
	}

	public function column_payments( $item ) {
		$payments = $item['info'];
		$payments = json_decode( $payments, true );
		echo $payments[0]['pay'];
	}

	public function column_action( $item ) {
		$statuses = [
			'pending' => [ 'badge', __( 'Có lỗi khi đẩy lên ERP', 'elu-shop' ) ],
			'completed'  => [ 'badge badge--success', __( 'Đã đẩy lên ERP', 'elu-shop' ) ],
		];
		$status   = $statuses[ $item['push_erp'] ];
		printf( '<span class="%s">%s</span>', $status[0], $status[1] );

		if ( 'pending' === $item['push_erp'] ) {
			printf(
				'<a href="%s" class="button tips">' . __( 'Thử lại', 'elu-shop' ) . ' </a>',
				add_query_arg(
					[
						'action'   => 'push_to_erp',
						'id'       => $item['id'],
						'user_id'  => $item['user'],
						'_wpnonce' => wp_create_nonce( 'gtt_push_order_to_erp' ),
					],
					$this->base_url
				)
			);
		} else {
			printf(
				'<a href="%s" class="button tips">' . __( 'Đẩy lên lần nữa', 'elu-shop' ) . ' </a>',
				add_query_arg(
					[
						'action'   => 'push_to_erp',
						'id'       => $item['id'],
						'user_id'  => $item['user'],
						'_wpnonce' => wp_create_nonce( 'gtt_push_order_to_erp' ),
					],
					$this->base_url
				)
			);
		}
	}

	protected function get_row_actions( $item ) {
		$actions = [
			'view' => sprintf(
				'<a href="%s">' . esc_html__( 'Chi tiết', 'elu-shop' ) . '</a>',
				add_query_arg(
					[
						'action' => 'view',
						'id'     => $item['id'],
					],
					$this->base_url
				)
			),
		];
		if ( 'trash' === $item['status'] ) {
			$actions['untrash'] = sprintf(
				'<a href="%s">' . esc_html__( 'Phục hồi', 'elu-shop' ) . '</a>',
				add_query_arg(
					[
						'action'   => 'untrash',
						'id'       => $item['id'],
						'_wpnonce' => wp_create_nonce( 'ps_untrash_order' ),
					],
					$this->base_url
				)
			);
		} else {
			$actions['trash'] = sprintf(
				'<a href="%s">' . esc_html__( 'Xoá', 'elu-shop' ) . '</a>',
				add_query_arg(
					[
						'action'   => 'trash',
						'id'       => $item['id'],
						'_wpnonce' => wp_create_nonce( 'ps_trash_order' ),
					],
					$this->base_url
				)
			);
		}
		$actions['delete'] = sprintf(
			'<a href="%s">' . esc_html__( 'Xoá vĩnh viễn', 'elu-shop' ) . '</a>',
			add_query_arg(
				[
					'action'   => 'delete',
					'id'       => $item['id'],
					'_wpnonce' => wp_create_nonce( 'ps_delete_order' ),
				],
				$this->base_url
			)
		);
		// $actions['push_to_erp'] = sprintf(
		// 	'<a href="%s">' . esc_html__( 'Đẩy lên ERP', 'elu-shop' ) . '</a>',
		// 	add_query_arg(
		// 		[
		// 			'action'   => 'push_to_erp',
		// 			'id'       => $item['id'],
		// 			'_wpnonce' => wp_create_nonce( 'gtt_push_order_to_erp' ),
		// 		],
		// 		$this->base_url
		// 	)
		// );
		return $actions;
	}

	public function get_bulk_actions() {
		$actions = [
			'bulk-trash'  => __( 'Xoá', 'elu-shop' ),
			'bulk-delete' => __( 'Xoá sạch thùng rác', 'elu-shop' ),
		];

		if ( isset( $_GET['status'] ) && 'trash' === $_GET['status'] && $this->get_total_items( 'trash' ) ) {
			$actions['bulk-untrash'] = __( 'Phục hồi', 'elu-shop' );
		}

		return $actions;
	}

	public function current_action() {
		if ( isset( $_REQUEST['delete_all'] ) || isset( $_REQUEST['delete_all2'] ) ) {
			return 'delete_all';
		}

		return parent::current_action();
	}

	public function handle_actions() {
		global $wpdb;

		if ( 'trash' === $this->current_action() ) {
			check_admin_referer( 'ps_trash_order' );
			$this->update_item_status( $_GET['id'], 'trash' );
			return;
		}
		if ( 'untrash' === $this->current_action() ) {
			check_admin_referer( 'ps_untrash_order' );
			$this->update_item_status( $_GET['id'], 'pending' );
			return;
		}
		if ( 'open' === $this->current_action() ) {
			check_admin_referer( 'ps_open_order' );
			$check_budget_user = $this->check_budget_user( $_GET['user'], $_GET['amount'] );

			$this->update_item_status( $_GET['id'], 'pending' );
			return;
		}
		if ( 'close' === $this->current_action() ) {
			check_admin_referer( 'ps_close_order' );

			$this->update_item_status( $_GET['id'], 'completed' );
			return;
		}
		if ( 'push_to_erp' === $this->current_action() ) {
			check_admin_referer( 'gtt_push_order_to_erp' );

			$this->push_to_erp( $_GET['id'], $_GET['user_id'] );
			// $this->update_push_erp_status( $_GET['id'], 'completed' );
			return;
		}
		if ( 'delete' === $this->current_action() ) {
			check_admin_referer( 'ps_delete_order' );
			$this->delete_item( absint( $_GET['id'] ) );
			return;
		}
		if ( 'bulk-trash' === $this->current_action() ) {
			check_admin_referer( 'bulk-orders' );
			$ids = esc_sql( $_GET['orders'] );
			foreach ( $ids as $id ) {
				$this->update_item_status( $id, 'trash' );
			}
			return;
		}
		if ( 'bulk-untrash' === $this->current_action() ) {
			check_admin_referer( 'bulk-orders' );
			$ids = esc_sql( $_GET['orders'] );
			foreach ( $ids as $id ) {
				$this->update_item_status( $id, 'pending' );
			}
			return;
		}
		if ( 'bulk-delete' === $this->current_action() ) {
			check_admin_referer( 'bulk-orders' );
			$ids = esc_sql( $_GET['orders'] );
			foreach ( $ids as $id ) {
				$this->delete_item( $id );
			}
			return;
		}
		if ( 'delete_all' === $this->current_action() ) {
			$wpdb->delete(
				$wpdb->orders,
				[ 'status' => 'trash' ]
			);
		}
	}

	protected function update_item_status( $id, $status ) {
		global $wpdb;

		$wpdb->update(
			$wpdb->orders,
			[ 'status' => $status ],
			[ 'id' => $id ],
			[ '%s' ]
		);
	}

	protected function delete_item( $id ) {
		global $wpdb;

		$wpdb->delete(
			$wpdb->orders,
			[ 'id' => $id ],
			[ '%d' ]
		);
	}

	protected function update_push_erp_status( $id, $status ) {
		global $wpdb;

		$wpdb->update(
			$wpdb->orders,
			[ 'push_erp' => $status ],
			[ 'id' => $id ],
			[ '%s' ]
		);
	}

	protected function push_to_erp( $id, $user_id ) {
		$products = $this->get_product_from_order_id( $id );
		$products = reset( $products );

		$amount  = $products['amount'];
		$voucher = $products['voucher'];
		$voucher = json_decode( $voucher, true );
		if ( ! $voucher ) {
			$giam_gia = 0;
		}
		if ( $voucher['voucher_type'] == 'by_price' ) {
			$giam_gia = $voucher['voucher_price'] / 1000;
		} else {
			$giam_gia = ( $voucher['voucher_price'] * $amount / 100 ) / 1000;
		}

		$data_product = $products['data'];
		$data_product = json_decode( $data_product, true );

		$data_customer = $products['info'];
		$data_customer = json_decode( $data_customer, true );

		$products_api = [];
		foreach ( $data_product as $product ) {
			$products_api[] = [
				'product_code' => $product['ma_sp'],
				'qty'          => (int)$product['quantity'],
				'unit_price'   => (int)$product['price'] / 1000,
			];
		}

		$data_string = json_encode( array(
			'note'         => $products['note'],
			'payment_term' => $data_customer['payment_method'],
			'products'     => $products_api,
			'discount'     => $giam_gia,
			'giathuoctot'  => 'True',
		), JSON_UNESCAPED_UNICODE );

		$token      = json_decode( $this->get_token_api( $user_id  ) );
		$token_data = $token->error ? '' : $token->data->access_token;
		$data = wp_remote_get( 'https://erp.hapu.vn/api/v1/private/pre_order/create', array(
			'headers' => [
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $token_data,
			],
			'method'  => 'POST',
			'body'    => $data_string,
			'timeout' => 15,
		) );
		$response   = json_decode( $data['body'], true );
		$erp_status = $response['code'] == 1 ? 'completed' : 'pending';
		global $wpdb;
		$this->update_push_erp_status( $_GET['id'], $erp_status );
	}

	public function get_product_from_order_id( $id ) {
		global $wpdb;

		$where = $wpdb->prepare( '`id`=%s', $id );
		$sql    = "SELECT * FROM $wpdb->orders WHERE $where";

		return $wpdb->get_results( $sql, 'ARRAY_A' );
	}

	public function get_token_api( $user_id  ) {
		$user_meta   = get_user_meta( $user_id );
		$prefix_user = rwmb_meta( 'prefix_user_erp', ['object_type' => 'setting'], 'setting' );

		$data_string = json_encode( array(
			'login'    => $prefix_user . $user_meta['user_sdt'][0],
			'password' => '111111',
		), JSON_UNESCAPED_UNICODE );

		$request = wp_remote_get( 'https://erp.hapu.vn/api/v1/public/Authentication/login', array(
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
