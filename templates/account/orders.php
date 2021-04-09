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
	<div class="alert alert--warning"><?php esc_html_e( 'You have no orders.', 'gtt-shop' )?>
		<a href="<?= get_post_type_archive_link( 'product' ); ?>"><?php esc_html_e( 'Click here', 'gtt-shop' )?></a><?php esc_html_e( 'to start the purchase', 'gtt-shop' )?>
	</div>
	<?php
	return;
endif;
?>

<table class="orders">
	<tr>
		<th><?php esc_html_e( 'Order ID', 'gtt-shop' )?></th>
		<th><?php esc_html_e( 'Time', 'gtt-shop' )?></th>
		<th><?php esc_html_e( 'Status', 'gtt-shop' )?></th>
		<th><?php esc_html_e( 'Total', 'gtt-shop' )?></th>
		<th><?php esc_html_e( 'Action', 'gtt-shop' )?></th>
	</tr>
	<?php foreach ( $items as $item ) : ?>
		<tr>
			<td>#<?= $item->id; ?></td>
			<td><?= $item->date; ?></td>
			<td>
				<?php
				$statuses = [
					'pending' => [ 'badge', __( 'Pending', 'gtt-shop' ) ],
					'completed'  => [ 'badge badge--success', __( 'Completed', 'gtt-shop' ) ],
					'trash'   => [ 'badge badge--danger', __( 'Deleted', 'gtt-shop' ) ],
				];
				$status   = $statuses[ $item->status ];
				printf( '<span class="%s">%s</span>', $status[0], $status[1] );
				?>
			</td>
			<td><?= $item->amount; ?> <?= ps_setting( 'currency' ); ?></td>
			<td><a href="?view=order&id=<?= $item->id; ?>"><?php esc_html_e( 'See details', 'gtt-shop' )?></a></td>
		</tr>
	<?php endforeach; ?>
</table>
