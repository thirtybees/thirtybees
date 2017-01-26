SET NAMES 'utf8';

ALTER TABLE `PREFIX_customer` MODIFY `passwd` VARCHAR(60) NOT NULL;
ALTER TABLE `PREFIX_employee` MODIFY `passwd` VARCHAR(60) NOT NULL;
ALTER TABLE `PREFIX_category` ADD `display_from_sub` TINYINT(1) UNSIGNED NOT NULL;

CREATE TABLE `PREFIX_module_carrier` (
  `id_module`INT(10) unsigned NOT NULL,
  `id_shop`INT(11) unsigned NOT NULL DEFAULT '1',
  `id_reference` INT(11) NOT NULL
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8 COLLATION;

/* PHP:thirtybees_select_current_payment_modules(); */

CREATE TABLE `PREFIX_redis_servers` (
  `id_redis_server` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip`              VARCHAR(46)      NOT NULL,
  `port`            INT(11) UNSIGNED NOT NULL,
  `auth`            TEXT,
  `db`              INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id_modules_perfs`)
)
  ENGINE = ENGINE_TYPE DEFAULT CHARSET = utf8 COLLATION;
