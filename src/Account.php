<?php

namespace ELUSHOP;

use ELUSHOP\TemplateLoader;

class Account {
	public function init() {
		add_filter( 'the_content', [ $this, 'filter_content_account' ] );
	}

	public function filter_content_account( $content ) {
		if ( ! $this->is_page_account() ) {
			return $content;
		}
		ob_start();
		TemplateLoader::instance()->get_template_part( 'account/orders' );
		return ob_get_clean();
	}

	protected function is_page_account() {
		return is_page() && get_the_ID() == ps_setting( 'account_page' );
	}
}
