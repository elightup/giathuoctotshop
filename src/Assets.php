<?php
namespace ELUSHOP;

class Assets {
	public function __construct() {
		add_action( 'admin_head', [ $this, 'output_css' ] );
	}

	public static function enqueue_style( $path, $deps = [] ) {
		wp_enqueue_style( $path, trailingslashit( ELU_SHOP_URL ) . "assets/css/$path.css", $deps, filemtime( trailingslashit( ELU_SHOP_DIR ) . "assets/css/$path.css" ) );
	}

	public static function enqueue_script( $path, $deps = [ 'jquery' ] ) {
		wp_enqueue_script( $path, trailingslashit( ELU_SHOP_URL ) . "assets/js/$path.js", $deps, filemtime( trailingslashit( ELU_SHOP_DIR ) . "assets/js/$path.js" ), true );
	}

	public static function localize( $path, $data, $name = null ) {
		if ( ! $name ) {
			$name = str_replace( ' ', '', ucwords( str_replace( [ '/', '-', '_' ], ' ', $path ) ) );
		}
		wp_localize_script( $path, $name, $data );
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
