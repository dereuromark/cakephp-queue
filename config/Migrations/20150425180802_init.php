<?php

use Phinx\Migration\AbstractMigration;

class Init extends AbstractMigration {

	/**
	 * Change Method.
	 *
	 * More information on this method is available here:
	 * http://docs.phinx.org/en/latest/migrations.html#the-change-method
	 *
	 * Uncomment this method if you would like to use it.
	 */
	//public function change() {
	//}

	/**
	 * Migrate Up.
	 *
	 * @return void
	 */
	public function up() {
		$sql = <<<SQL
CREATE TABLE IF NOT EXISTS `queued_tasks` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `jobtype` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `data` text COLLATE utf8_unicode_ci,
  `group` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `reference` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `notbefore` datetime DEFAULT NULL,
  `fetched` datetime DEFAULT NULL,
  `completed` datetime DEFAULT NULL,
  `progress` float(3,2) unsigned DEFAULT NULL,
  `failed` int(3) NOT NULL DEFAULT '0',
  `failure_message` text COLLATE utf8_unicode_ci,
  `workerkey` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;
SQL;
		$this->query($sql);
	}

	/**
	 * Migrate Down.
	 *
	 * @return void
	 */
	public function down() {
	}
}
