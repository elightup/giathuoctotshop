<?php
namespace ELUSHOP;

class Misc {
	public function __construct() {
		add_filter( 'slim_seo_skipped_shortcodes', [ $this, 'skip_shortcodes' ] );
		add_action( 'after_setup_theme', [ $this, 'remove_admin_bar' ] );
	}

	public function skip_shortcodes( $shortcodes ) {
		$shortcodes[] = 'mb_user_profile_register';
		$shortcodes[] = 'mb_user_profile_login';
		$shortcodes[] = 'mb_user_profile_info';
		return $shortcodes;
	}

	public function remove_admin_bar() {
		if ( !current_user_can( 'edit_posts' ) ) {
			show_admin_bar( false );
		}
	}
}