
/* SQL script for migration from PrestaShop to thirty bees (1.0.0). */

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
  ENGINE = InnoDB
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
  ENGINE = InnoDB
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
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_currency_module`
(
  `id_currency` INT(11) UNSIGNED NOT NULL,
  `id_module`   INT(11) UNSIGNED
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_page_cache` (
  `id_page_cache` INT(11) UNSIGNED AUTO_INCREMENT,
  `cache_hash`    VARCHAR(32) NOT NULL,
  `id_currency`   INT(11) UNSIGNED,
  `id_language`   INT(11) UNSIGNED,
  `id_country`    INT(11) UNSIGNED,
  `id_shop`       INT(11) UNSIGNED,
  `cache`         TEXT NOT NULL,
  `cache_size`    INT(10) UNSIGNED,
  `entity_type`   VARCHAR(30) NOT NULL,
  `id_entity`     INT(11) UNSIGNED,
  UNIQUE KEY `cache_combo` (`cache_hash`, `id_currency`, `id_language`, `id_country`, `id_shop`),
  PRIMARY KEY (`id_page_cache`),
  INDEX (`cache_hash`),
  INDEX (`id_currency`),
  INDEX (`id_language`),
  INDEX (`id_country`),
  INDEX (`id_shop`),
  INDEX (`id_entity`),
  INDEX (`entity_type`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE utf8mb4_unicode_ci;
