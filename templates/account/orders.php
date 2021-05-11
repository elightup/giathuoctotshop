<h3><?php esc_html_e( 'Order List', 'gtt-shop' )?></h3>
<?php
global $wpdb;
$items = $wpdb->get_results( $wpdb->prepare(
	"SELECT `id`, `date`, `status`, `amount` FROM $wpdb->orders
	 WHERE `user`=%d
	 ORDER BY `date` DESC
	 ",
	get_current_user_id()
) );

if ( empty( $items ) ) :
	?>
	<div class="alert alert--warning">Bạn không có đơn hàng nào
		<a href="<?= get_post_type_archive_link( 'product' ); ?>"><?php esc_html_e( 'Click vào đây', 'gtt-shop' )?></a><?php esc_html_e( 'để bắt đầu mua sản phẩm', 'gtt-shop' )?>
	</div>
	<?php
	return;
endif;
?>

<table class="orders">
	<tr>
		<th><?php esc_html_e( 'Mã đơn hàng', 'gtt-shop' )?></th>
		<th><?php esc_html_e( 'Thời gian', 'gtt-shop' )?></th>
		<th><?php esc_html_e( 'Trạng thái', 'gtt-shop' )?></th>
		<th><?php esc_html_e( 'Tổng tiền', 'gtt-shop' )?></th>
		<th><?php esc_html_e( 'Thao tác', 'gtt-shop' )?></th>
	</tr>
	<?php foreach ( $items as $item ) : ?>
		<tr>
			<td>#<?= $item->id; ?></td>
			<td><?= $item->date; ?></td>
			<td>
				<?php
				$statuses = [
					'pending' => [ 'badge', __( 'Đang xử lý', 'gtt-shop' ) ],
					'completed'  => [ 'badge badge--success', __( 'Đã hoàn thành', 'gtt-shop' ) ],
					'trash'   => [ 'badge badge--danger', __( 'Đã xoá', 'gtt-shop' ) ],
				];
				$status   = $statuses[ $item->status ];
				printf( '<span class="%s">%s</span>', $status[0], $status[1] );
				?>
			</td>
			<td><?= $item->amount; ?> <?= ps_setting( 'currency' ); ?></td>
			<td><a href="?view=order&id=<?= $item->id; ?>"><?php esc_html_e( 'Xem chi tiết', 'gtt-shop' )?></a></td>
		</tr>
	<?php endforeach; ?>
</table>
