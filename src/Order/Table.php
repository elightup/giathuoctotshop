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
		$links['all'] = sprintf(
			'<a href="%s"%s>%s <span class="count">(%s)</span></a>',
			$this->base_url,
			$class,
			'Tất cả',
			$this->get_total_items()
		);

		$push_erp     = isset( $_REQUEST['push_erp'] ) ? $_REQUEST['push_erp'] : '';
		$statuses_erp = [
			'completed' => __( 'Đã đẩy lên ERP', 'elu-shop' ),
			'pending'   => __( 'Lỗi khi đẩy lên ERP', 'elu-shop' ),
		];
		foreach ( $statuses_erp as $key => $label ) {
			$class               = $key === $push_erp ? ' class="current"' : '';
			$url                 = add_query_arg( 'push_erp', $key, $this->base_url );
			$links[ "erp_$key" ] = sprintf(
				'<a href="%s"%s>%s <span class="count">(%s)</span></a>',
				$url,
				$class,
				$label,
				$this->get_total_items_by_erp( $key )
			);
		}

		$statuses = [
			'completed' => __( 'Đã hoàn thành', 'elu-shop' ),
			'pending'   => __( 'Đang xử lý', 'elu-shop' ),
			'trash'     => __( 'Thùng rác', 'elu-shop' ),
		];
		foreach ( $statuses as $key => $label ) {
			$class         = $key === $status ? ' class="current"' : '';
			$url           = add_query_arg( 'status', $key, $this->base_url );
			$links[ $key ] = sprintf(
				'<a href="%s"%s>%s <span class="count">(%s)</span></a>',
				$url,
				$class,
				$label,
				$this->get_total_items( $key )
			);
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

	protected function get_total_items_by_erp( $status = '' ) {
		global $wpdb;

		$where = '';
		if ( $status ) {
			$where = $wpdb->prepare( 'WHERE `push_erp`=%s', $status );
		}
		return $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->orders $where" );
	}

	protected function get_sql_where_clause() {
		global $wpdb;

		$where = [];
		if ( isset( $_REQUEST['status'] ) ) {
			$where[] = $wpdb->prepare( '`status`=%s', $_REQUEST['status'] );
		}

		if ( isset( $_REQUEST['push_erp'] ) ) {
			$where[] = $wpdb->prepare( '`push_erp`=%s', $_REQUEST['push_erp'] );
		}

		if ( isset( $_REQUEST['s'] ) ) {
			$s          = esc_sql( $_REQUEST['s'] );
			$where_user = "WHERE u.user_login LIKE '%" . $s . "%' OR u.user_email LIKE '%" . $s . "%' OR um.meta_value LIKE '%" . $s . "%'";
			$sql        = "SELECT DISTINCT ID FROM $wpdb->users AS u LEFT JOIN $wpdb->usermeta AS um ON um.user_id=u.ID $where_user";
			$user_ids   = $wpdb->get_col( $sql );

			$user_ids = implode( ',', $user_ids );
			if ( substr( $s, 0, 1 ) === '#' ) {
				$where[] = '`id` =' . substr( $s, 1 );
			} else {
				$where[] = '`user` IN ( ' . $user_ids . ' )';
			}
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
			'cb'          => '<input type="checkbox">',
			'id'          => 'ID',
			'customer'    => __( 'Khách hàng', 'elu-shop' ),
			'products'    => __( 'Sản phẩm', 'elu-shop' ),
			'amount'      => __( 'Tổng tiền', 'elu-shop' ),
			'date'        => __( 'Ngày tạo', 'elu-shop' ),
			'status'      => __( 'Trạng thái', 'elu-shop' ),
			'erp'         => 'ERP',
			'action'      => __( 'Thao tác', 'elu-shop' ),
			'user_update' => __( 'Người chỉnh sửa', 'elu-shop' ),
			'time_update' => __( 'Thời gian chỉnh sửa', 'elu-shop' ),
		];

		return $columns;
	}

	public function get_sortable_columns() {
		$sortable_columns = [
			'date' => [ 'date', true ],
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
			add_query_arg( [
				'action' => 'view',
				'id'     => $item['id'],
			], $this->base_url ),
			$item['id']
		);
		return $title . $this->row_actions( $this->get_row_actions( $item ) );
	}

	public function column_default( $item, $column_name ) {
		return isset( $item[ $column_name ] ) ? $item[ $column_name ] : '';
	}

	public function column_status( $item ) {
		$statuses = [
			'pending'   => [ 'badge', __( 'Đang xử lý', 'elu-shop' ) ],
			'completed' => [ 'badge badge--success', __( 'Đã hoàn thành', 'elu-shop' ) ],
			'trash'     => [ 'badge badge--danger', __( 'Đã xoá', 'elu-shop' ) ],
		];
		$status   = $statuses[ $item['status'] ];
		$payments = $item['info'];
		$payments = json_decode( $payments, true );

		printf( '<span class="%s">%s</span>', $status[0], $status[1] );
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
		$info          = json_decode( $item['info'] );
		$user_id       = $item['user'];
		$user_name_erp = get_user_meta( $user_id, 'user_name2' )[0] ?? '';
		$user_data     = get_userdata( $user_id );
		$status_user   = get_user_meta( $user_id, 'erp_response', true );
		$status_user   = ! isset( $status_user ) || $status_user != 1 ? 'Chưa đẩy lên ERP' : 'Đã đẩy lên ERP';

		echo esc_html( $user_name_erp ) . '<br>';
		if ( $user_data ) {
			echo 'Login: ' . esc_html( $user_data->user_login ) . '(' . esc_html( $status_user ) . ')';
		} else {
			echo 'KH chưa đăng nhập';
		}
	}

	public function column_amount( $item ) {
		$voucher = json_decode( $item['voucher'], true );
		if ( ! $voucher ) {
			echo esc_html( number_format_i18n( $item['amount'], 0 ) ) . ' ' . esc_html( ps_setting( 'currency' ) );
			return;
		}
		$giam_gia = 0;
		if ( $voucher['voucher_type'] == 'by_price' ) {
			$giam_gia = $voucher['voucher_price'];
		} else {
			$giam_gia = $voucher['voucher_price'] * $item['amount'] / 100;
		}
		$amount = $item['amount'] - $giam_gia;
		echo number_format( $amount, 0, '', '.' );
		?>
		<?= esc_html( ps_setting( 'currency' ) );
	}

	public function column_payments( $item ) {
		$payments = $item['info'];
		$payments = json_decode( $payments, true );
		echo esc_html( $payments[0]['pay'] );
	}

	public function column_action( $item ) {
		if ( 'pending' === $item['status'] ) {
			printf(
				'<a href="#" class="gtt-button gtt-close" data-id="%d" title="Đánh dấu hoàn thành"><span class="dashicons dashicons-yes"></span></a>',
				esc_attr( $item['id'] )
			);
		} elseif ( 'completed' === $item['status'] ) {
			printf(
				'<a href="#" class="gtt-button gtt-open" data-id="%d" title="Đánh dấu đang xử lý"><span class="dashicons dashicons-hourglass"></span></a>',
				esc_attr( $item['id'] )
			);
		}

		printf(
			'<a href="#" class="gtt-button gtt-repush" data-id="%d" title="Đẩy lại lên ERP"><span class="dashicons dashicons-cloud-upload"></span></a>',
			esc_attr( $item['id'] )
		);
	}

	public function column_erp( $item ) {
		$statuses = [
			'pending'   => [ 'badge', __( 'Có lỗi khi đẩy lên ERP', 'elu-shop' ) ],
			'completed' => [ 'badge badge--success', __( 'Đã đẩy lên ERP', 'elu-shop' ) ],
		];
		if ( empty( $item['push_erp'] ) || ! isset( $statuses[ $item['push_erp'] ] ) ) {
			return;
		}
		$status = $statuses[ $item['push_erp'] ];
		printf( '<span class="%s">%s</span><br>%s', esc_attr( $status[0] ), esc_html( $status[1] ), esc_html( $item['push_message'] ) );
	}

	public function column_user_update( $item ) {
		if ( ! empty( $item['update_log'] ) ) {
			$log       = json_decode( $item['update_log'] );
			$user_name = get_user_meta( $log->user_update, 'user_name', true );
			echo '<a href="' . esc_url( get_edit_user_link( $log->user_update ) ) . '">' . esc_html( $user_name ) . '</a>';
		}
	}

	public function column_time_update( $item ) {
		if ( ! empty( $item['update_log'] ) ) {
			$log         = json_decode( $item['update_log'] );
			$date_update = strtotime( $log->date );
			echo esc_html( date( 'd.m.Y H:i', $date_update ) );
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

	protected function update_push_erp_status( $id, $status, $message ) {
		global $wpdb;

		$wpdb->update(
			$wpdb->orders,
			[
				'push_erp'     => $status,
				'push_message' => $message,
			],
			[ 'id' => $id ],
			[ '%s' ]
		);
	}
}
