<?php
namespace ELUSHOP;

class Assets {
	public function __construct() {
		add_action( 'admin_head', [ $this, 'output_css' ] );
	}

	public function output_css() {
		?>
		<style>
		#wpadminbar .bubble {
			display: inline-block;
			box-sizing: border-box;
			margin-right: 2px;
			padding: 0 5px;
			min-width: 18px;
			line-height: 18px;
			height: 18px;
			border-radius: 9px;
			background-color: #d63638;
			color: #fff;
			font-size: 11px;
		}
		</style>
		<?php
	}
}
