<?php
namespace ELUSHOP;

class Misc {
	public function __construct() {
		add_filter( 'slim_seo_skipped_shortcodes', [ $this, 'skip_shortcodes' ] );
	}

	public function skip_shortcodes( $shortcodes ) {
		$shortcodes[] = 'mb_user_profile_register';
		$shortcodes[] = 'mb_user_profile_login';
		$shortcodes[] = 'mb_user_profile_info';
		return $shortcodes;
	}
}