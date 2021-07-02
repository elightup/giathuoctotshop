<?php

namespace ELUSHOP\SaveLog;

class Table extends \WP_List_Table {
	protected $base_url;

	public function __construct() {
		parent::__construct(
			[
				// 'singular' => 'log',
				'plural'   => 'logs',
			]
		);
		$this->base_url = admin_url( 'edit.php?page=logs' );
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
			FROM $wpdb->prefix" . "logs" . "
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
		$sql    = "SELECT * FROM $wpdb->prefix" . "logs" . "$where $order $limit $offset";

		$this->items = $wpdb->get_results( $sql, 'ARRAY_A' );
	}

	protected function get_total_items( $status = '' ) {
		global $wpdb;

		$where = '';
		if ( $status ) {
			$where = $wpdb->prepare( 'WHERE `status`=%s', $status );
		}
		return $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->prefix" . "logs" . " $where" );
	}


	protected function get_sql_where_clause() {
		global $wpdb;

		$where = [];

		if ( isset( $_REQUEST['s'] ) ) {
			$where_user            = "WHERE 1=1 AND (user_login LIKE '%" . $_REQUEST['s'] . "%' OR user_email LIKE '%" . $_REQUEST['s'] . "%' OR meta_value LIKE '%" . $_REQUEST['s'] . "%')";
			$sql                   = "SELECT DISTINCT ID FROM $wpdb->users LEFT JOIN $wpdb->usermeta ON " . $wpdb->usermeta . ".user_id = " . $wpdb->users . ".ID $where_user";
			$get_user_from_request = $wpdb->get_results( $sql, 'ARRAY_A' );

			$user_id_arr = [];
			foreach ( $get_user_from_request as $key => $user_id ) {
				$user_id_arr[] = (int)$user_id['ID'];
			}
			$user_id_arr = implode( ',', $user_id_arr );
			$where[]     = '`user` IN ( ' . $user_id_arr . ' )';
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
			'date'        => __( 'Thời gian', 'elu-shop' ),
			// 'id'          => 'STT',
			'object_type' => __( 'Object Type', 'elu-shop' ),
			'object_id'   => __( 'Object ID', 'elu-shop' ),
			'action'      => __( 'Thao tác', 'elu-shop' ),
			'user_update' => __( 'Người chỉnh sửa', 'elu-shop' ),
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

	public function column_default( $item, $column_name ) {
		return isset( $item[ $column_name ] ) ? $item[ $column_name ] : '';
	}


	public function column_action( $item ) {
		// printf(
		// 	'<a href="%s" class="button tips">' . __( 'Đẩy lên lần nữa', 'elu-shop' ) . ' </a>',
		// 	add_query_arg(
		// 		[
		// 			'action'   => 'push_to_erp',
		// 			'id'       => $item['id'],
		// 			'user_id'  => $item['user'],
		// 			'_wpnonce' => wp_create_nonce( 'gtt_push_order_to_erp' ),
		// 		],
		// 		$this->base_url
		// 	)
		// );
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
}
