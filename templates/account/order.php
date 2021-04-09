<?php
global $wpdb;
$id   = intval( $_GET['id'] );
$item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->orders WHERE `id`=%d", $id ) );
$info = json_decode( $item->info, true );
?>

<?php if ( isset( $_GET['type'] ) && 'checkout' === $_GET['type'] ) : ?>
	<div class="alert alert-info"><?php esc_html_e( 'Thank you for your order. Your order is currently being processed. Below is the order information. We will contact you as soon as possible!', 'gtt-shop' ); ?></div>
<?php endif; ?>
<div class="info-order text-center">
	<?php esc_html_e( 'Order Details', 'gtt-shop' ); ?>
</div>
<div class="detail-order">
	<div class="line-items float-left col-lg-6">
		<h4><?php esc_html_e( 'Order', 'gtt-shop' ); ?> #<?= $id; ?></h4>
		<table class="order table">
			<tr>
				<th><?php esc_html_e( 'Time', 'gtt-shop' ); ?>:</th>
				<td><?= $item->date; ?></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Status', 'gtt-shop' ); ?>:</th>
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
			</tr>
			<!-- <tr>
				<th><?php esc_html_e( 'Shipping Method', 'gtt-shop' ); ?></th>
				<td><?= $info['delivery']; ?></td>
			</tr> -->
			<tr>
				<th><?php esc_html_e( 'Total', 'gtt-shop' ); ?>:</th>
				<td><?= number_format( $item->amount, 0, '', '.' ); ?> <?= ps_setting( 'currency' ); ?></td>
			</tr>
		</table>
	</div>
	<div class="customer-details float-left col-lg-6 ">
		<h4><?php esc_html_e( 'Customer Details', 'gtt-shop' ); ?></h4>
		<table class="customer table">
			<tr>
				<th><?php esc_html_e( 'Name', 'gtt-shop' ); ?></th>
				<td><?= $info['name']; ?></td>
			</tr>
			<!-- <tr>
				<th><?php esc_html_e( 'Email', 'gtt-shop' ); ?>:</th>
				<td><?= $info['email']; ?></td>
			</tr> -->
			<tr>
				<th><?php esc_html_e( 'Phone', 'gtt-shop' ); ?>:</th>
				<td><?= $info['phone']; ?></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Address', 'gtt-shop' ); ?>:</th>
				<td><?= $info['address']; ?></td>
			</tr>
		</table>
	</div>
</div>
<div class="order-list col-lg-12 clear">
<h4><?php esc_html_e( 'Products', 'gtt-shop' ); ?></h4>
	<table class="order-products table">
		<thead>
		<tr>
			<th><?php esc_html_e( 'Product', 'gtt-shop' ); ?></th>
			<th><?php esc_html_e( 'Quantity', 'gtt-shop' ); ?></th>
			<th><?php esc_html_e( 'Price', 'gtt-shop' ); ?></th>
			<th><?php esc_html_e( 'Total', 'gtt-shop' ); ?></th>
		</tr>
		</thead>
		<tbody>
		<?php
		$products = json_decode( $item->data, true );
		foreach ( $products as $product ) :
			?>
			<tr>
				<td><?= $product['title']; ?></td>
				<td><?= $product['quantity']; ?></td>
				<td><?= number_format( $product['price'], 0, '', '.' ); ?> <?= ps_setting( 'currency' ); ?></td>
				<td><?= number_format( $product['quantity'] * $product['price'], 0, '', '.' ); ?> <?= ps_setting( 'currency' ); ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
</div>