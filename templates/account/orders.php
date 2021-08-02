<?php
if ( ! is_user_logged_in() ) {
	echo 'Bạn chưa đăng nhập';
	return;
}

global $wpdb;
$items = $wpdb->get_results( $wpdb->prepare(
	"SELECT `id`, `date`, `status`, `amount`, `voucher` FROM $wpdb->orders
	 WHERE `user`=%d
	 ORDER BY `date` DESC
	 ",
	get_current_user_id()
) );

if ( empty( $items ) ) :
	?>
	<div class="alert alert--warning">Bạn không có đơn hàng nào.
		<a href="<?= get_post_type_archive_link( 'product' ); ?>"><?php esc_html_e( 'Click vào đây ', 'gtt-shop' )?></a><?php esc_html_e( 'để bắt đầu mua sản phẩm', 'gtt-shop' )?>
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
			<td>
				<?php
					$voucher = json_decode( $item->voucher, true );
					if ( ! $voucher ) {
						echo number_format( $item->amount, 0, '', '.' ) . ps_setting( 'currency' );
					} else {
						$giam_gia = 0;
						if ( $voucher['voucher_type'] == 'by_price' ) {
							$giam_gia = $voucher['voucher_price'];
						} else {
							$giam_gia = $voucher['voucher_price'] * $item->amount / 100;
						}
						$amount = $item->amount - $giam_gia;
						echo number_format( $amount, 0, '', '.' ) . ps_setting( 'currency' );
					}
				?>
			</td>
			<td><a href="<?= get_permalink( ps_setting( 'confirmation_page' ) ) ?>?view=order&id=<?= $item->id; ?>"><?php esc_html_e( 'Xem chi tiết', 'gtt-shop' )?></a></td>
		</tr>
	<?php endforeach; ?>
</table>
