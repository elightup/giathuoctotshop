<?php $this->table->prepare_items(); ?>
<div class="wrap">
	<h1><?php esc_html_e( 'Đơn hàng', 'gtt-shop' ); ?></h1>
	<form id="posts-filter" method="get">
		<input type="hidden" name="page" value="logs">
		<?php
		$this->table->views();
		$this->table->search_box( __( 'Tìm kiếm logs', 'gtt-shop' ), 'order' );
		$this->table->display();
		?>
	</form>
</div>
