<?php
namespace ELUSHOP;

class Schema {
	public function __construct() {
		global $wpdb;
		$wpdb->tables[]      = 'orders';
		$wpdb->orders        = $wpdb->prefix . 'orders';
		$wpdb->notifications = $wpdb->prefix . 'notifications';

		$this->create_tables();
	}

	public function create_tables() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		global $wpdb;

		// Orders table.
		$sql      = "
			CREATE TABLE $wpdb->orders (
				`id` mediumint unsigned NOT NULL auto_increment,
				`date` datetime NOT NULL,
				`status` varchar(12) NOT NULL,
				`push_erp` varchar(12) NOT NULL,
				`push_message` varchar(100) NOT NULL,
				`user` mediumint unsigned NOT NULL,
				`amount` int unsigned NOT NULL,
				`voucher` text,
				`note` text,
				`update_log` text,
				`info` longtext,
				`info_shipping` longtext,
				`data` longtext,
				`data_update` longtext,
				PRIMARY KEY  (`id`),
				KEY `date` (`date`),
				KEY `status` (`status`),
				KEY `user` (`user`)
			);
		";
		$sql_noti = "
			CREATE TABLE $wpdb->notifications (
				`id` mediumint unsigned NOT NULL auto_increment,
				`user` mediumint NOT NULL,
				`order_id` mediumint NOT NULL,
				`date` datetime NOT NULL,
				`status` varchar(12),
				`note` text,
				PRIMARY KEY  (`id`)
			);
		";

		dbDelta( $sql );
		dbDelta( $sql_noti );
	}
}
