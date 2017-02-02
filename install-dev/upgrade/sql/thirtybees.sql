SET NAMES 'utf8';

ALTER TABLE `PREFIX_customer`
  MODIFY `passwd` VARCHAR(60) NOT NULL;
ALTER TABLE `PREFIX_employee`
  MODIFY `passwd` VARCHAR(60) NOT NULL;
ALTER TABLE `PREFIX_category`
  ADD `display_from_sub` TINYINT(1) UNSIGNED NOT NULL;

CREATE TABLE `PREFIX_module_carrier` (
  `id_module`    INT(10) UNSIGNED NOT NULL,
  `id_shop`      INT(11) UNSIGNED NOT NULL DEFAULT '1',
  `id_reference` INT(11)          NOT NULL
)
  ENGINE = ENGINE_TYPE
  DEFAULT CHARSET = utf8mb4
  COLLATE utf8mb4_unicode_ci;

/* PHP:thirtybees_select_current_payment_modules(); */

CREATE TABLE `PREFIX_redis_servers` (
  `id_redis_server` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip`              VARCHAR(46)      NOT NULL,
  `port`            INT(11) UNSIGNED NOT NULL,
  `auth`            TEXT,
  `db`              INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id_redis_server`)
)
  ENGINE = ENGINE_TYPE
  DEFAULT CHARSET = utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_url_rewrite` (
  `id_url_rewrite` INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `entity`        TINYINT(2) UNSIGNED NOT NULL,
  `id_lang`       INT(11) UNSIGNED    NOT NULL,
  `id_shop`       INT(11) UNSIGNED    NOT NULL,
  `rewrite`       VARCHAR(1000)       NOT NULL,
  `redirect`      TINYINT(2) UNSIGNED NOT NULL,
  PRIMARY KEY (`id_url_rewrite`)
)
  ENGINE = ENGINE_TYPE
  DEFAULT CHARSET = utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_currency_module`
(
  `id_currency` INT(11) UNSIGNED NOT NULL,
  `module`      INT(11) UNSIGNED
)
  ENGINE = ENGINE_TYPE
  DEFAULT CHARSET = utf8mb4
  COLLATE utf8mb4_unicode_ci;
