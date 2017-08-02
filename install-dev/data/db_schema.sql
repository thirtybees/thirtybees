SET SESSION sql_mode = '';
SET NAMES 'utf8mb4';

CREATE TABLE `PREFIX_access` (
  `id_profile` INT(11) UNSIGNED NOT NULL,
  `id_tab`     INT(11) UNSIGNED NOT NULL,
  `view`       INT(11)          NOT NULL,
  `add`        INT(11)          NOT NULL,
  `edit`       INT(11)          NOT NULL,
  `delete`     INT(11)          NOT NULL,
  PRIMARY KEY (`id_profile`, `id_tab`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_accessory` (
  `id_product_1` INT(11) UNSIGNED NOT NULL,
  `id_product_2` INT(11) UNSIGNED NOT NULL,
  KEY `accessory_product` (`id_product_1`, `id_product_2`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_address` (
  `id_address`      INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `id_country`      INT(11) UNSIGNED    NOT NULL,
  `id_state`        INT(11) UNSIGNED             DEFAULT NULL,
  `id_customer`     INT(11) UNSIGNED    NOT NULL DEFAULT '0',
  `id_manufacturer` INT(11) UNSIGNED    NOT NULL DEFAULT '0',
  `id_supplier`     INT(11) UNSIGNED    NOT NULL DEFAULT '0',
  `id_warehouse`    INT(11) UNSIGNED    NOT NULL DEFAULT '0',
  `alias`           VARCHAR(32)         NOT NULL,
  `company`         VARCHAR(64)                  DEFAULT NULL,
  `lastname`        VARCHAR(32)         NOT NULL,
  `firstname`       VARCHAR(32)         NOT NULL,
  `address1`        VARCHAR(128)        NOT NULL,
  `address2`        VARCHAR(128)                 DEFAULT NULL,
  `postcode`        VARCHAR(12)                  DEFAULT NULL,
  `city`            VARCHAR(64)         NOT NULL,
  `other`           TEXT,
  `phone`           VARCHAR(32)                  DEFAULT NULL,
  `phone_mobile`    VARCHAR(32)                  DEFAULT NULL,
  `vat_number`      VARCHAR(32)                  DEFAULT NULL,
  `dni`             VARCHAR(16)                  DEFAULT NULL,
  `date_add`        DATETIME            NOT NULL,
  `date_upd`        DATETIME            NOT NULL,
  `active`          TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
  `deleted`         TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_address`),
  KEY `address_customer` (`id_customer`),
  KEY `id_country` (`id_country`),
  KEY `id_state` (`id_state`),
  KEY `id_manufacturer` (`id_manufacturer`),
  KEY `id_supplier` (`id_supplier`),
  KEY `id_warehouse` (`id_warehouse`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_alias` (
  `id_alias` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `alias`    VARCHAR(64)      NOT NULL,
  `search`   VARCHAR(255)     NOT NULL,
  `active`   TINYINT(1)       NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_alias`),
  UNIQUE KEY `alias` (`alias`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_attachment` (
  `id_attachment` INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `file`          VARCHAR(40)         NOT NULL,
  `file_name`     VARCHAR(128)        NOT NULL,
  `file_size`     BIGINT(11) UNSIGNED NOT NULL DEFAULT '0',
  `mime`          VARCHAR(128)        NOT NULL,
  PRIMARY KEY (`id_attachment`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_attachment_lang` (
  `id_attachment` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_lang`       INT(11) UNSIGNED NOT NULL,
  `name`          VARCHAR(32)               DEFAULT NULL,
  `description`   TEXT,
  PRIMARY KEY (`id_attachment`, `id_lang`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_product_attachment` (
  `id_product`    INT(11) UNSIGNED NOT NULL,
  `id_attachment` INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id_product`, `id_attachment`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_attribute` (
  `id_attribute`       INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_attribute_group` INT(11) UNSIGNED NOT NULL,
  `color`              VARCHAR(32)               DEFAULT NULL,
  `position`           INT(11) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_attribute`),
  KEY `attribute_group` (`id_attribute_group`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_attribute_group` (
  `id_attribute_group` INT(11) UNSIGNED                  NOT NULL AUTO_INCREMENT,
  `is_color_group`     TINYINT(1)                        NOT NULL DEFAULT '0',
  `group_type`         ENUM ('select', 'radio', 'color') NOT NULL DEFAULT 'select',
  `position`           INT(11) UNSIGNED                  NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_attribute_group`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_attribute_group_lang` (
  `id_attribute_group` INT(11) UNSIGNED NOT NULL,
  `id_lang`            INT(11) UNSIGNED NOT NULL,
  `name`               VARCHAR(128)     NOT NULL,
  `public_name`        VARCHAR(64)      NOT NULL,
  PRIMARY KEY (`id_attribute_group`, `id_lang`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_attribute_impact` (
  `id_attribute_impact` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_product`          INT(11) UNSIGNED NOT NULL,
  `id_attribute`        INT(11) UNSIGNED NOT NULL,
  `weight`              DECIMAL(20, 6)   NOT NULL,
  `price`               DECIMAL(17, 2)   NOT NULL,
  PRIMARY KEY (`id_attribute_impact`),
  UNIQUE KEY `id_product` (`id_product`, `id_attribute`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_attribute_lang` (
  `id_attribute` INT(11) UNSIGNED NOT NULL,
  `id_lang`      INT(11) UNSIGNED NOT NULL,
  `name`         VARCHAR(128)     NOT NULL,
  PRIMARY KEY (`id_attribute`, `id_lang`),
  KEY `id_lang` (`id_lang`, `name`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_carrier` (
  `id_carrier`           INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `id_reference`         INT(11) UNSIGNED    NOT NULL,
  `id_tax_rules_group`   INT(11) UNSIGNED             DEFAULT '0',
  `name`                 VARCHAR(64)         NOT NULL,
  `url`                  VARCHAR(255)                 DEFAULT NULL,
  `active`               TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `deleted`              TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `shipping_handling`    TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
  `range_behavior`       TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `is_module`            TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `is_free`              TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `shipping_external`    TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `need_range`           TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `external_module_name` VARCHAR(64)                  DEFAULT NULL,
  `shipping_method`      INT(2)              NOT NULL DEFAULT '0',
  `position`             INT(11) UNSIGNED    NOT NULL DEFAULT '0',
  `max_width`            INT(10)                      DEFAULT '0',
  `max_height`           INT(10)                      DEFAULT '0',
  `max_depth`            INT(10)                      DEFAULT '0',
  `max_weight`           DECIMAL(20, 6)               DEFAULT '0',
  `grade`                INT(10)                      DEFAULT '0',
  PRIMARY KEY (`id_carrier`),
  KEY `deleted` (`deleted`, `active`),
  KEY `id_tax_rules_group` (`id_tax_rules_group`),
  KEY `reference` (`id_reference`, `deleted`, `active`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_carrier_lang` (
  `id_carrier` INT(11) UNSIGNED NOT NULL,
  `id_shop`    INT(11) UNSIGNED NOT NULL DEFAULT '1',
  `id_lang`    INT(11) UNSIGNED NOT NULL,
  `delay`      VARCHAR(128)              DEFAULT NULL,
  PRIMARY KEY (`id_lang`, `id_shop`, `id_carrier`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_carrier_zone` (
  `id_carrier` INT(11) UNSIGNED NOT NULL,
  `id_zone`    INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id_carrier`, `id_zone`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_cart` (
  `id_cart`                 INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `id_shop_group`           INT(11) UNSIGNED    NOT NULL DEFAULT '1',
  `id_shop`                 INT(11) UNSIGNED    NOT NULL DEFAULT '1',
  `id_carrier`              INT(11) UNSIGNED    NOT NULL,
  `delivery_option`         TEXT                NOT NULL,
  `id_lang`                 INT(11) UNSIGNED    NOT NULL,
  `id_address_delivery`     INT(11) UNSIGNED    NOT NULL,
  `id_address_invoice`      INT(11) UNSIGNED    NOT NULL,
  `id_currency`             INT(11) UNSIGNED    NOT NULL,
  `id_customer`             INT(11) UNSIGNED    NOT NULL,
  `id_guest`                INT(11) UNSIGNED    NOT NULL,
  `secure_key`              VARCHAR(32)         NOT NULL DEFAULT '-1',
  `recyclable`              TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
  `gift`                    TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `gift_message`            TEXT,
  `mobile_theme`            TINYINT(1)          NOT NULL DEFAULT '0',
  `allow_seperated_package` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `date_add`                DATETIME            NOT NULL,
  `date_upd`                DATETIME            NOT NULL,
  PRIMARY KEY (`id_cart`),
  KEY `cart_customer` (`id_customer`),
  KEY `id_address_delivery` (`id_address_delivery`),
  KEY `id_address_invoice` (`id_address_invoice`),
  KEY `id_carrier` (`id_carrier`),
  KEY `id_lang` (`id_lang`),
  KEY `id_currency` (`id_currency`),
  KEY `id_guest` (`id_guest`),
  KEY `id_shop_group` (`id_shop_group`),
  KEY `id_shop_2` (`id_shop`, `date_upd`),
  KEY `id_shop` (`id_shop`, `date_add`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_cart_rule` (
  `id_cart_rule`            INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `id_customer`             INT UNSIGNED        NOT NULL DEFAULT '0',
  `date_from`               DATETIME            NOT NULL,
  `date_to`                 DATETIME            NOT NULL,
  `description`             TEXT,
  `quantity`                INT(11) UNSIGNED    NOT NULL DEFAULT '0',
  `quantity_per_user`       INT(11) UNSIGNED    NOT NULL DEFAULT '0',
  `priority`                INT(11) UNSIGNED    NOT NULL DEFAULT 1,
  `partial_use`             TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `code`                    VARCHAR(254)        NOT NULL,
  `minimum_amount`          DECIMAL(17, 2)      NOT NULL DEFAULT '0',
  `minimum_amount_tax`      TINYINT(1)          NOT NULL DEFAULT '0',
  `minimum_amount_currency` INT UNSIGNED        NOT NULL DEFAULT '0',
  `minimum_amount_shipping` TINYINT(1)          NOT NULL DEFAULT '0',
  `country_restriction`     TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `carrier_restriction`     TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `group_restriction`       TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `cart_rule_restriction`   TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `product_restriction`     TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `shop_restriction`        TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `free_shipping`           TINYINT(1)          NOT NULL DEFAULT '0',
  `reduction_percent`       DECIMAL(5, 2)       NOT NULL DEFAULT '0',
  `reduction_amount`        DECIMAL(17, 2)      NOT NULL DEFAULT '0',
  `reduction_tax`           TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `reduction_currency`      INT(11) UNSIGNED    NOT NULL DEFAULT '0',
  `reduction_product`       INT(10)             NOT NULL DEFAULT '0',
  `gift_product`            INT(11) UNSIGNED    NOT NULL DEFAULT '0',
  `gift_product_attribute`  INT(11) UNSIGNED    NOT NULL DEFAULT '0',
  `highlight`               TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `active`                  TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `date_add`                DATETIME            NOT NULL,
  `date_upd`                DATETIME            NOT NULL,
  PRIMARY KEY (`id_cart_rule`),
  KEY `id_customer` (`id_customer`, `active`, `date_to`),
  KEY `group_restriction` (`group_restriction`, `active`, `date_to`),
  KEY `id_customer_2` (`id_customer`, `active`, `highlight`, `date_to`),
  KEY `group_restriction_2` (`group_restriction`, `active`, `highlight`, `date_to`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_cart_rule_lang` (
  `id_cart_rule` INT(11) UNSIGNED NOT NULL,
  `id_lang`      INT(11) UNSIGNED NOT NULL,
  `name`         VARCHAR(254)     NOT NULL,
  PRIMARY KEY (`id_cart_rule`, `id_lang`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_cart_rule_country` (
  `id_cart_rule` INT(11) UNSIGNED NOT NULL,
  `id_country`   INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id_cart_rule`, `id_country`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_cart_rule_group` (
  `id_cart_rule` INT(11) UNSIGNED NOT NULL,
  `id_group`     INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id_cart_rule`, `id_group`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_cart_rule_carrier` (
  `id_cart_rule` INT(11) UNSIGNED NOT NULL,
  `id_carrier`   INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id_cart_rule`, `id_carrier`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_cart_rule_combination` (
  `id_cart_rule_1` INT(11) UNSIGNED NOT NULL,
  `id_cart_rule_2` INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id_cart_rule_1`, `id_cart_rule_2`),
  KEY `id_cart_rule_1` (`id_cart_rule_1`),
  KEY `id_cart_rule_2` (`id_cart_rule_2`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_cart_rule_product_rule_group` (
  `id_product_rule_group` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_cart_rule`          INT(11) UNSIGNED NOT NULL,
  `quantity`              INT(11) UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_product_rule_group`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_cart_rule_product_rule` (
  `id_product_rule`       INT(11) UNSIGNED                                                            NOT NULL AUTO_INCREMENT,
  `id_product_rule_group` INT(11) UNSIGNED                                                            NOT NULL,
  `type`                  ENUM ('products', 'categories', 'attributes', 'manufacturers', 'suppliers') NOT NULL,
  PRIMARY KEY (`id_product_rule`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_cart_rule_product_rule_value` (
  `id_product_rule` INT(11) UNSIGNED NOT NULL,
  `id_item`         INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id_product_rule`, `id_item`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_cart_cart_rule` (
  `id_cart`      INT(11) UNSIGNED NOT NULL,
  `id_cart_rule` INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id_cart`, `id_cart_rule`),
  KEY (`id_cart_rule`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_cart_rule_shop` (
  `id_cart_rule` INT(11) UNSIGNED NOT NULL,
  `id_shop`      INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id_cart_rule`, `id_shop`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_cart_product` (
  `id_cart`              INT(11) UNSIGNED NOT NULL,
  `id_product`           INT(11) UNSIGNED NOT NULL,
  `id_address_delivery`  INT(11) UNSIGNED          DEFAULT '0',
  `id_shop`              INT(11) UNSIGNED NOT NULL DEFAULT '1',
  `id_product_attribute` INT(11) UNSIGNED NOT NULL DEFAULT '0',
  `quantity`             INT(11) UNSIGNED NOT NULL DEFAULT '0',
  `date_add`             DATETIME         NOT NULL,
  PRIMARY KEY (`id_cart`, `id_product`, `id_product_attribute`, `id_address_delivery`),
  KEY `id_product_attribute` (`id_product_attribute`),
  KEY `id_cart_order` (`id_cart`, `date_add`, `id_product`, `id_product_attribute`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_category` (
  `id_category`      INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `id_parent`        INT(11) UNSIGNED    NOT NULL,
  `id_shop_default`  INT(11) UNSIGNED    NOT NULL DEFAULT 1,
  `level_depth`      TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
  `nleft`            INT(11) UNSIGNED    NOT NULL DEFAULT '0',
  `nright`           INT(11) UNSIGNED    NOT NULL DEFAULT '0',
  `active`           TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `display_from_sub` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `date_add`         DATETIME            NOT NULL,
  `date_upd`         DATETIME            NOT NULL,
  `position`         INT(11) UNSIGNED    NOT NULL DEFAULT '0',
  `is_root_category` TINYINT(1)          NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_category`),
  KEY `category_parent` (`id_parent`),
  KEY `nleftrightactive` (`nleft`, `nright`, `active`),
  KEY `level_depth` (`level_depth`),
  KEY `nright` (`nright`),
  KEY `activenleft` (`active`, `nleft`),
  KEY `activenright` (`active`, `nright`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_category_group` (
  `id_category` INT(11) UNSIGNED NOT NULL,
  `id_group`    INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id_category`, `id_group`),
  KEY `id_category` (`id_category`),
  KEY `id_group` (`id_group`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_category_lang` (
  `id_category`      INT(11) UNSIGNED NOT NULL,
  `id_shop`          INT(11) UNSIGNED NOT NULL DEFAULT '1',
  `id_lang`          INT(11) UNSIGNED NOT NULL,
  `name`             VARCHAR(128)     NOT NULL,
  `description`      TEXT,
  `link_rewrite`     VARCHAR(128)     NOT NULL,
  `meta_title`       VARCHAR(128)              DEFAULT NULL,
  `meta_keywords`    VARCHAR(255)              DEFAULT NULL,
  `meta_description` VARCHAR(255)              DEFAULT NULL,
  PRIMARY KEY (`id_category`, `id_shop`, `id_lang`),
  KEY `category_name` (`name`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_category_product` (
  `id_category` INT(11) UNSIGNED NOT NULL,
  `id_product`  INT(11) UNSIGNED NOT NULL,
  `position`    INT(11) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_category`, `id_product`),
  INDEX (`id_product`),
  INDEX (`id_category`, `position`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_cms` (
  `id_cms`          INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `id_cms_category` INT(11) UNSIGNED    NOT NULL,
  `position`        INT(11) UNSIGNED    NOT NULL DEFAULT '0',
  `active`          TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `indexation`      TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_cms`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_cms_lang` (
  `id_cms`           INT(11) UNSIGNED NOT NULL,
  `id_lang`          INT(11) UNSIGNED NOT NULL,
  `id_shop`          INT(11) UNSIGNED NOT NULL DEFAULT '1',
  `meta_title`       VARCHAR(128)     NOT NULL,
  `meta_description` VARCHAR(255)              DEFAULT NULL,
  `meta_keywords`    VARCHAR(255)              DEFAULT NULL,
  `content`          LONGTEXT,
  `link_rewrite`     VARCHAR(128)     NOT NULL,
  PRIMARY KEY (`id_cms`, `id_shop`, `id_lang`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_cms_category` (
  `id_cms_category` INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `id_parent`       INT(11) UNSIGNED    NOT NULL,
  `level_depth`     TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
  `active`          TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `date_add`        DATETIME            NOT NULL,
  `date_upd`        DATETIME            NOT NULL,
  `position`        INT(11) UNSIGNED    NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_cms_category`),
  KEY `category_parent` (`id_parent`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_cms_category_lang` (
  `id_cms_category`  INT(11) UNSIGNED NOT NULL,
  `id_lang`          INT(11) UNSIGNED NOT NULL,
  `id_shop`          INT(11) UNSIGNED NOT NULL DEFAULT '1',
  `name`             VARCHAR(128)     NOT NULL,
  `description`      TEXT,
  `link_rewrite`     VARCHAR(128)     NOT NULL,
  `meta_title`       VARCHAR(128)              DEFAULT NULL,
  `meta_keywords`    VARCHAR(255)              DEFAULT NULL,
  `meta_description` VARCHAR(255)              DEFAULT NULL,
  PRIMARY KEY (`id_cms_category`, `id_shop`, `id_lang`),
  KEY `category_name` (`name`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_cms_category_shop` (
  `id_cms_category` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_shop`         INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id_cms_category`, `id_shop`),
  KEY `id_shop` (`id_shop`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_compare` (
  `id_compare`  INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_customer` INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id_compare`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_compare_product` (
  `id_compare` INT(11) UNSIGNED NOT NULL,
  `id_product` INT(11) UNSIGNED NOT NULL,
  `date_add`   DATETIME         NOT NULL,
  `date_upd`   DATETIME         NOT NULL,
  PRIMARY KEY (`id_compare`, `id_product`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_configuration` (
  `id_configuration` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_shop_group`    INT(11) UNSIGNED          DEFAULT NULL,
  `id_shop`          INT(11) UNSIGNED          DEFAULT NULL,
  `name`             VARCHAR(254)     NOT NULL,
  `value`            TEXT,
  `date_add`         DATETIME         NOT NULL,
  `date_upd`         DATETIME         NOT NULL,
  PRIMARY KEY (`id_configuration`),
  KEY `id_shop` (`id_shop`),
  KEY `id_shop_group` (`id_shop_group`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_configuration_lang` (
  `id_configuration` INT(11) UNSIGNED NOT NULL,
  `id_lang`          INT(11) UNSIGNED NOT NULL,
  `value`            TEXT,
  `date_upd`         DATETIME DEFAULT NULL,
  PRIMARY KEY (`id_configuration`, `id_lang`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_configuration_kpi` (
  `id_configuration_kpi` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_shop_group`        INT(11) UNSIGNED          DEFAULT NULL,
  `id_shop`              INT(11) UNSIGNED          DEFAULT NULL,
  `name`                 VARCHAR(64)      NOT NULL,
  `value`                TEXT,
  `date_add`             DATETIME         NOT NULL,
  `date_upd`             DATETIME         NOT NULL,
  PRIMARY KEY (`id_configuration_kpi`),
  KEY `name` (`name`),
  KEY `id_shop` (`id_shop`),
  KEY `id_shop_group` (`id_shop_group`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_configuration_kpi_lang` (
  `id_configuration_kpi` INT(11) UNSIGNED NOT NULL,
  `id_lang`              INT(11) UNSIGNED NOT NULL,
  `value`                TEXT,
  `date_upd`             DATETIME DEFAULT NULL,
  PRIMARY KEY (`id_configuration_kpi`, `id_lang`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_connections` (
  `id_connections` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_shop_group`  INT(11) UNSIGNED NOT NULL DEFAULT '1',
  `id_shop`        INT(11) UNSIGNED NOT NULL DEFAULT '1',
  `id_guest`       INT(11) UNSIGNED NOT NULL,
  `id_page`        INT(11) UNSIGNED NOT NULL,
  `ip_address`     BIGINT           NULL     DEFAULT NULL,
  `date_add`       DATETIME         NOT NULL,
  `http_referer`   VARCHAR(255)              DEFAULT NULL,
  PRIMARY KEY (`id_connections`),
  KEY `id_guest` (`id_guest`),
  KEY `date_add` (`date_add`),
  KEY `id_page` (`id_page`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_connections_page` (
  `id_connections` INT(11) UNSIGNED NOT NULL,
  `id_page`        INT(11) UNSIGNED NOT NULL,
  `time_start`     DATETIME         NOT NULL,
  `time_end`       DATETIME DEFAULT NULL,
  PRIMARY KEY (`id_connections`, `id_page`, `time_start`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_connections_source` (
  `id_connections_source` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_connections`        INT(11) UNSIGNED NOT NULL,
  `http_referer`          VARCHAR(255)              DEFAULT NULL,
  `request_uri`           VARCHAR(255)              DEFAULT NULL,
  `keywords`              VARCHAR(255)              DEFAULT NULL,
  `date_add`              DATETIME         NOT NULL,
  PRIMARY KEY (`id_connections_source`),
  KEY `connections` (`id_connections`),
  KEY `orderby` (`date_add`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_contact` (
  `id_contact`       INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `email`            VARCHAR(128)        NOT NULL,
  `customer_service` TINYINT(1)          NOT NULL DEFAULT '0',
  `position`         TINYINT(2) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_contact`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_contact_lang` (
  `id_contact`  INT(11) UNSIGNED NOT NULL,
  `id_lang`     INT(11) UNSIGNED NOT NULL,
  `name`        VARCHAR(32)      NOT NULL,
  `description` TEXT,
  PRIMARY KEY (`id_contact`, `id_lang`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_country` (
  `id_country`                 INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `id_zone`                    INT(11) UNSIGNED    NOT NULL,
  `id_currency`                INT(11) UNSIGNED    NOT NULL DEFAULT '0',
  `iso_code`                   VARCHAR(3)          NOT NULL,
  `call_prefix`                INT(10)             NOT NULL DEFAULT '0',
  `active`                     TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `contains_states`            TINYINT(1)          NOT NULL DEFAULT '0',
  `need_identification_number` TINYINT(1)          NOT NULL DEFAULT '0',
  `need_zip_code`              TINYINT(1)          NOT NULL DEFAULT '1',
  `zip_code_format`            VARCHAR(12)         NOT NULL DEFAULT '',
  `display_tax_label`          BOOLEAN             NOT NULL,
  PRIMARY KEY (`id_country`),
  KEY `country_iso_code` (`iso_code`),
  KEY `country_` (`id_zone`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_country_lang` (
  `id_country` INT(11) UNSIGNED NOT NULL,
  `id_lang`    INT(11) UNSIGNED NOT NULL,
  `name`       VARCHAR(64)      NOT NULL,
  PRIMARY KEY (`id_country`, `id_lang`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_currency` (
  `id_currency`     INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `name`            VARCHAR(32)         NOT NULL,
  `iso_code`        VARCHAR(3)          NOT NULL DEFAULT '0',
  `iso_code_num`    VARCHAR(3)          NOT NULL DEFAULT '0',
  `sign`            VARCHAR(8)          NOT NULL,
  `blank`           TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `format`          TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `decimals`        TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
  `conversion_rate` DECIMAL(13, 6)      NOT NULL,
  `deleted`         TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `active`          TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_currency`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_customer` (
  `id_customer`                INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `id_shop_group`              INT(11) UNSIGNED    NOT NULL DEFAULT '1',
  `id_shop`                    INT(11) UNSIGNED    NOT NULL DEFAULT '1',
  `id_gender`                  INT(11) UNSIGNED    NOT NULL,
  `id_default_group`           INT(11) UNSIGNED    NOT NULL DEFAULT '1',
  `id_lang`                    INT(11) UNSIGNED    NULL,
  `id_risk`                    INT(11) UNSIGNED    NOT NULL DEFAULT '1',
  `company`                    VARCHAR(64),
  `siret`                      VARCHAR(14),
  `ape`                        VARCHAR(5),
  `firstname`                  VARCHAR(32)         NOT NULL,
  `lastname`                   VARCHAR(32)         NOT NULL,
  `email`                      VARCHAR(128)        NOT NULL,
  `passwd`                     VARCHAR(60)         NOT NULL,
  `last_passwd_gen`            TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `birthday`                   DATE                         DEFAULT NULL,
  `newsletter`                 TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `ip_registration_newsletter` VARCHAR(15)                  DEFAULT NULL,
  `newsletter_date_add`        DATETIME                     DEFAULT NULL,
  `optin`                      TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `website`                    VARCHAR(128),
  `outstanding_allow_amount`   DECIMAL(20, 6)      NOT NULL DEFAULT '0.00',
  `show_public_prices`         TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `max_payment_days`           INT(11) UNSIGNED    NOT NULL DEFAULT '60',
  `secure_key`                 VARCHAR(32)         NOT NULL DEFAULT '-1',
  `note`                       TEXT,
  `active`                     TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `is_guest`                   TINYINT(1)          NOT NULL DEFAULT '0',
  `deleted`                    TINYINT(1)          NOT NULL DEFAULT '0',
  `date_add`                   DATETIME            NOT NULL,
  `date_upd`                   DATETIME            NOT NULL,
  PRIMARY KEY (`id_customer`),
  KEY `customer_email` (`email`),
  KEY `customer_login` (`email`, `passwd`),
  KEY `id_customer_passwd` (`id_customer`, `passwd`),
  KEY `id_gender` (`id_gender`),
  KEY `id_shop_group` (`id_shop_group`),
  KEY `id_shop` (`id_shop`, `date_add`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_customer_group` (
  `id_customer` INT(11) UNSIGNED NOT NULL,
  `id_group`    INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id_customer`, `id_group`),
  INDEX customer_login(id_group),
  KEY `id_customer` (`id_customer`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_customer_message` (
  `id_customer_message` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_customer_thread`  INT(11)                   DEFAULT NULL,
  `id_employee`         INT(11) UNSIGNED          DEFAULT NULL,
  `message`             MEDIUMTEXT       NOT NULL,
  `file_name`           VARCHAR(18)               DEFAULT NULL,
  `ip_address`          VARCHAR(16)               DEFAULT NULL,
  `user_agent`          VARCHAR(128)              DEFAULT NULL,
  `date_add`            DATETIME         NOT NULL,
  `date_upd`            DATETIME         NOT NULL,
  `private`             TINYINT          NOT NULL DEFAULT '0',
  `read`                TINYINT(1)       NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_customer_message`),
  KEY `id_customer_thread` (`id_customer_thread`),
  KEY `id_employee` (`id_employee`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;


CREATE TABLE `PREFIX_customer_message_sync_imap` (
  `md5_header` VARBINARY(32) NOT NULL,
  KEY `md5_header_index` (`md5_header`(4))
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_customer_thread` (
  `id_customer_thread` INT(11) UNSIGNED                                NOT NULL AUTO_INCREMENT,
  `id_shop`            INT(11) UNSIGNED                                NOT NULL DEFAULT '1',
  `id_lang`            INT(11) UNSIGNED                                NOT NULL,
  `id_contact`         INT(11) UNSIGNED                                NOT NULL,
  `id_customer`        INT(11) UNSIGNED                                         DEFAULT NULL,
  `id_order`           INT(11) UNSIGNED                                         DEFAULT NULL,
  `id_product`         INT(11) UNSIGNED                                         DEFAULT NULL,
  `status`             ENUM ('open', 'closed', 'pending1', 'pending2') NOT NULL DEFAULT 'open',
  `email`              VARCHAR(128)                                    NOT NULL,
  `token`              VARCHAR(12)                                              DEFAULT NULL,
  `date_add`           DATETIME                                        NOT NULL,
  `date_upd`           DATETIME                                        NOT NULL,
  PRIMARY KEY (`id_customer_thread`),
  KEY `id_shop` (`id_shop`),
  KEY `id_lang` (`id_lang`),
  KEY `id_contact` (`id_contact`),
  KEY `id_customer` (`id_customer`),
  KEY `id_order` (`id_order`),
  KEY `id_product` (`id_product`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;


CREATE TABLE `PREFIX_customization` (
  `id_customization`     INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `id_product_attribute` INT(11) UNSIGNED    NOT NULL DEFAULT '0',
  `id_address_delivery`  INT(11) UNSIGNED    NOT NULL DEFAULT '0',
  `id_cart`              INT(11) UNSIGNED    NOT NULL,
  `id_product`           INT(10)             NOT NULL,
  `quantity`             INT(10)             NOT NULL,
  `quantity_refunded`    INT                 NOT NULL DEFAULT '0',
  `quantity_returned`    INT                 NOT NULL DEFAULT '0',
  `in_cart`              TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_customization`, `id_cart`, `id_product`, `id_address_delivery`),
  KEY `id_product_attribute` (`id_product_attribute`),
  KEY `id_cart_product` (`id_cart`, `id_product`, `id_product_attribute`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_customization_field` (
  `id_customization_field` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_product`             INT(11) UNSIGNED NOT NULL,
  `type`                   TINYINT(1)       NOT NULL,
  `required`               TINYINT(1)       NOT NULL,
  PRIMARY KEY (`id_customization_field`),
  KEY `id_product` (`id_product`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_customization_field_lang` (
  `id_customization_field` INT(11) UNSIGNED NOT NULL,
  `id_lang`                INT(11) UNSIGNED NOT NULL,
  `id_shop`                INT(11) UNSIGNED NOT NULL DEFAULT '1',
  `name`                   VARCHAR(255)     NOT NULL,
  PRIMARY KEY (`id_customization_field`, `id_lang`, `id_shop`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_customized_data` (
  `id_customization` INT(11) UNSIGNED NOT NULL,
  `type`             TINYINT(1)       NOT NULL,
  `index`            INT(3)           NOT NULL,
  `value`            VARCHAR(255)     NOT NULL,
  PRIMARY KEY (`id_customization`, `type`, `index`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_date_range` (
  `id_date_range` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `time_start`    DATETIME         NOT NULL,
  `time_end`      DATETIME         NOT NULL,
  PRIMARY KEY (`id_date_range`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_delivery` (
  `id_delivery`     INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_shop`         INT UNSIGNED     NULL     DEFAULT NULL,
  `id_shop_group`   INT UNSIGNED     NULL     DEFAULT NULL,
  `id_carrier`      INT(11) UNSIGNED NOT NULL,
  `id_range_price`  INT(11) UNSIGNED          DEFAULT NULL,
  `id_range_weight` INT(11) UNSIGNED          DEFAULT NULL,
  `id_zone`         INT(11) UNSIGNED NOT NULL,
  `price`           DECIMAL(20, 6)   NOT NULL,
  PRIMARY KEY (`id_delivery`),
  KEY `id_zone` (`id_zone`),
  KEY `id_carrier` (`id_carrier`, `id_zone`),
  KEY `id_range_price` (`id_range_price`),
  KEY `id_range_weight` (`id_range_weight`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_employee` (
  `id_employee`              INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `id_profile`               INT(11) UNSIGNED    NOT NULL,
  `id_lang`                  INT(11) UNSIGNED    NOT NULL DEFAULT '0',
  `lastname`                 VARCHAR(32)         NOT NULL,
  `firstname`                VARCHAR(32)         NOT NULL,
  `email`                    VARCHAR(128)        NOT NULL,
  `passwd`                   VARCHAR(60)         NOT NULL,
  `last_passwd_gen`          TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `stats_date_from`          DATE                         DEFAULT NULL,
  `stats_date_to`            DATE                         DEFAULT NULL,
  `stats_compare_from`       DATE                         DEFAULT NULL,
  `stats_compare_to`         DATE                         DEFAULT NULL,
  `stats_compare_option`     INT(1) UNSIGNED     NOT NULL DEFAULT 1,
  `preselect_date_range`     VARCHAR(32)                  DEFAULT NULL,
  `bo_color`                 VARCHAR(32)                  DEFAULT NULL,
  `bo_theme`                 VARCHAR(32)                  DEFAULT NULL,
  `bo_css`                   VARCHAR(64)                  DEFAULT NULL,
  `default_tab`              INT(11) UNSIGNED    NOT NULL DEFAULT '0',
  `bo_width`                 INT(11) UNSIGNED    NOT NULL DEFAULT '0',
  `bo_menu`                  TINYINT(1)          NOT NULL DEFAULT '1',
  `active`                   TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `optin`                    TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
  `id_last_order`            INT(11) UNSIGNED    NOT NULL DEFAULT '0',
  `id_last_customer_message` INT(11) UNSIGNED    NOT NULL DEFAULT '0',
  `id_last_customer`         INT(11) UNSIGNED    NOT NULL DEFAULT '0',
  `last_connection_date`     DATE                         DEFAULT '1970-01-01',
  PRIMARY KEY (`id_employee`),
  KEY `employee_login` (`email`, `passwd`),
  KEY `id_employee_passwd` (`id_employee`, `passwd`),
  KEY `id_profile` (`id_profile`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_employee_shop` (
  `id_employee` INT(11) UNSIGNED NOT NULL,
  `id_shop`     INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id_employee`, `id_shop`),
  KEY `id_shop` (`id_shop`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_feature` (
  `id_feature` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `position`   INT(11) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_feature`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_feature_lang` (
  `id_feature` INT(11) UNSIGNED NOT NULL,
  `id_lang`    INT(11) UNSIGNED NOT NULL,
  `name`       VARCHAR(128) DEFAULT NULL,
  PRIMARY KEY (`id_feature`, `id_lang`),
  KEY (`id_lang`, `name`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_feature_product` (
  `id_feature`       INT(11) UNSIGNED NOT NULL,
  `id_product`       INT(11) UNSIGNED NOT NULL,
  `id_feature_value` INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id_feature`, `id_product`),
  KEY `id_feature_value` (`id_feature_value`),
  KEY `id_product` (`id_product`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_feature_value` (
  `id_feature_value` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_feature`       INT(11) UNSIGNED NOT NULL,
  `custom`           TINYINT(3) UNSIGNED       DEFAULT NULL,
  PRIMARY KEY (`id_feature_value`),
  KEY `feature` (`id_feature`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_feature_value_lang` (
  `id_feature_value` INT(11) UNSIGNED NOT NULL,
  `id_lang`          INT(11) UNSIGNED NOT NULL,
  `value`            VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id_feature_value`, `id_lang`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `PREFIX_gender` (
  `id_gender` INT(11)    NOT NULL AUTO_INCREMENT,
  `type`      TINYINT(1) NOT NULL,
  PRIMARY KEY (`id_gender`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `PREFIX_gender_lang` (
  `id_gender` INT(11) UNSIGNED NOT NULL,
  `id_lang`   INT(11) UNSIGNED NOT NULL,
  `name`      VARCHAR(20)      NOT NULL,
  PRIMARY KEY (`id_gender`, `id_lang`),
  KEY `id_gender` (`id_gender`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_group` (
  `id_group`             INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `reduction`            DECIMAL(17, 2)      NOT NULL DEFAULT '0.00',
  `price_display_method` TINYINT             NOT NULL DEFAULT '0',
  `show_prices`          TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
  `date_add`             DATETIME            NOT NULL,
  `date_upd`             DATETIME            NOT NULL,
  PRIMARY KEY (`id_group`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_group_lang` (
  `id_group` INT(11) UNSIGNED NOT NULL,
  `id_lang`  INT(11) UNSIGNED NOT NULL,
  `name`     VARCHAR(32)      NOT NULL,
  PRIMARY KEY (`id_group`, `id_lang`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_group_reduction` (
  `id_group_reduction` MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_group`           INT(11) UNSIGNED   NOT NULL,
  `id_category`        INT(11) UNSIGNED   NOT NULL,
  `reduction`          DECIMAL(4, 3)      NOT NULL,
  PRIMARY KEY (`id_group_reduction`),
  UNIQUE KEY (`id_group`, `id_category`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_product_group_reduction_cache` (
  `id_product` INT UNSIGNED  NOT NULL,
  `id_group`   INT UNSIGNED  NOT NULL,
  `reduction`  DECIMAL(4, 3) NOT NULL,
  PRIMARY KEY (`id_product`, `id_group`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_product_carrier` (
  `id_product`           INT(11) UNSIGNED NOT NULL,
  `id_carrier_reference` INT(11) UNSIGNED NOT NULL,
  `id_shop`              INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id_product`, `id_carrier_reference`, `id_shop`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_guest` (
  `id_guest`            INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_operating_system` INT(11) UNSIGNED          DEFAULT NULL,
  `id_web_browser`      INT(11) UNSIGNED          DEFAULT NULL,
  `id_customer`         INT(11) UNSIGNED          DEFAULT NULL,
  `javascript`          TINYINT(1)                DEFAULT '0',
  `screen_resolution_x` SMALLINT(5) UNSIGNED      DEFAULT NULL,
  `screen_resolution_y` SMALLINT(5) UNSIGNED      DEFAULT NULL,
  `screen_color`        TINYINT(3) UNSIGNED       DEFAULT NULL,
  `sun_java`            TINYINT(1)                DEFAULT NULL,
  `adobe_flash`         TINYINT(1)                DEFAULT NULL,
  `adobe_director`      TINYINT(1)                DEFAULT NULL,
  `apple_quicktime`     TINYINT(1)                DEFAULT NULL,
  `real_player`         TINYINT(1)                DEFAULT NULL,
  `windows_media`       TINYINT(1)                DEFAULT NULL,
  `accept_language`     VARCHAR(8)                DEFAULT NULL,
  `mobile_theme`        TINYINT(1)       NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_guest`),
  KEY `id_customer` (`id_customer`),
  KEY `id_operating_system` (`id_operating_system`),
  KEY `id_web_browser` (`id_web_browser`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_hook` (
  `id_hook`     INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(64)      NOT NULL,
  `title`       VARCHAR(64)      NOT NULL,
  `description` TEXT,
  `position`    TINYINT(1)       NOT NULL DEFAULT '1',
  `live_edit`   TINYINT(1)       NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_hook`),
  UNIQUE KEY `hook_name` (`name`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_hook_alias` (
  `id_hook_alias` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `alias`         VARCHAR(64)      NOT NULL,
  `name`          VARCHAR(64)      NOT NULL,
  PRIMARY KEY (`id_hook_alias`),
  UNIQUE KEY `alias` (`alias`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_hook_module` (
  `id_module` INT(11) UNSIGNED    NOT NULL,
  `id_shop`   INT(11) UNSIGNED    NOT NULL DEFAULT '1',
  `id_hook`   INT(11) UNSIGNED    NOT NULL,
  `position`  TINYINT(2) UNSIGNED NOT NULL,
  PRIMARY KEY (`id_module`, `id_hook`, `id_shop`),
  KEY `id_hook` (`id_hook`),
  KEY `id_module` (`id_module`),
  KEY `position` (`id_shop`, `position`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_hook_module_exceptions` (
  `id_hook_module_exceptions` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_shop`                   INT(11) UNSIGNED NOT NULL DEFAULT '1',
  `id_module`                 INT(11) UNSIGNED NOT NULL,
  `id_hook`                   INT(11) UNSIGNED NOT NULL,
  `file_name`                 VARCHAR(255)              DEFAULT NULL,
  PRIMARY KEY (`id_hook_module_exceptions`),
  KEY `id_module` (`id_module`),
  KEY `id_hook` (`id_hook`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_image` (
  `id_image`   INT(11) UNSIGNED     NOT NULL AUTO_INCREMENT,
  `id_product` INT(11) UNSIGNED     NOT NULL,
  `position`   SMALLINT(2) UNSIGNED NOT NULL DEFAULT '0',
  `cover`      TINYINT(1) UNSIGNED  NULL     DEFAULT NULL,
  PRIMARY KEY (`id_image`),
  KEY `image_product` (`id_product`),
  UNIQUE KEY `id_product_cover` (`id_product`, `cover`),
  UNIQUE KEY `idx_product_image` (`id_image`, `id_product`, `cover`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_image_lang` (
  `id_image` INT(11) UNSIGNED NOT NULL,
  `id_lang`  INT(11) UNSIGNED NOT NULL,
  `legend`   VARCHAR(128) DEFAULT NULL,
  PRIMARY KEY (`id_image`, `id_lang`),
  KEY `id_image` (`id_image`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_image_type` (
  `id_image_type` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`          VARCHAR(64)      NOT NULL,
  `width`         INT(11) UNSIGNED NOT NULL,
  `height`        INT(11) UNSIGNED NOT NULL,
  `products`      TINYINT(1)       NOT NULL DEFAULT '1',
  `categories`    TINYINT(1)       NOT NULL DEFAULT '1',
  `manufacturers` TINYINT(1)       NOT NULL DEFAULT '1',
  `suppliers`     TINYINT(1)       NOT NULL DEFAULT '1',
  `scenes`        TINYINT(1)       NOT NULL DEFAULT '1',
  `stores`        TINYINT(1)       NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_image_type`),
  KEY `image_type_name` (`name`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_lang` (
  `id_lang`          INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `name`             VARCHAR(32)         NOT NULL,
  `active`           TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
  `iso_code`         CHAR(2)             NOT NULL,
  `language_code`    CHAR(5)             NOT NULL,
  `date_format_lite` CHAR(32)            NOT NULL DEFAULT 'Y-m-d',
  `date_format_full` CHAR(32)            NOT NULL DEFAULT 'Y-m-d H:i:s',
  `is_rtl`           TINYINT(1)          NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_lang`),
  KEY `lang_iso_code` (`iso_code`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_manufacturer` (
  `id_manufacturer` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`            VARCHAR(64)      NOT NULL,
  `date_add`        DATETIME         NOT NULL,
  `date_upd`        DATETIME         NOT NULL,
  `active`          TINYINT(1)       NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_manufacturer`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_manufacturer_lang` (
  `id_manufacturer`   INT(11) UNSIGNED NOT NULL,
  `id_lang`           INT(11) UNSIGNED NOT NULL,
  `description`       TEXT,
  `short_description` TEXT,
  `meta_title`        VARCHAR(128) DEFAULT NULL,
  `meta_keywords`     VARCHAR(255) DEFAULT NULL,
  `meta_description`  VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id_manufacturer`, `id_lang`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_message` (
  `id_message`  INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `id_cart`     INT(11) UNSIGNED             DEFAULT NULL,
  `id_customer` INT(11) UNSIGNED    NOT NULL,
  `id_employee` INT(11) UNSIGNED             DEFAULT NULL,
  `id_order`    INT(11) UNSIGNED    NOT NULL,
  `message`     TEXT                NOT NULL,
  `private`     TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
  `date_add`    DATETIME            NOT NULL,
  PRIMARY KEY (`id_message`),
  KEY `message_order` (`id_order`),
  KEY `id_cart` (`id_cart`),
  KEY `id_customer` (`id_customer`),
  KEY `id_employee` (`id_employee`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_message_readed` (
  `id_message`  INT(11) UNSIGNED NOT NULL,
  `id_employee` INT(11) UNSIGNED NOT NULL,
  `date_add`    DATETIME         NOT NULL,
  PRIMARY KEY (`id_message`, `id_employee`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_meta` (
  `id_meta`      INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `page`         VARCHAR(64)         NOT NULL,
  `configurable` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_meta`),
  UNIQUE KEY `page` (`page`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_meta_lang` (
  `id_meta`     INT(11) UNSIGNED NOT NULL,
  `id_shop`     INT(11) UNSIGNED NOT NULL DEFAULT '1',
  `id_lang`     INT(11) UNSIGNED NOT NULL,
  `title`       VARCHAR(128)              DEFAULT NULL,
  `description` VARCHAR(255)              DEFAULT NULL,
  `keywords`    VARCHAR(255)              DEFAULT NULL,
  `url_rewrite` VARCHAR(254)     NOT NULL,
  PRIMARY KEY (`id_meta`, `id_shop`, `id_lang`),
  KEY `id_shop` (`id_shop`),
  KEY `id_lang` (`id_lang`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_module` (
  `id_module` INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `name`      VARCHAR(64)         NOT NULL,
  `active`    TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `version`   VARCHAR(8)          NOT NULL,
  PRIMARY KEY (`id_module`),
  KEY `name` (`name`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_module_access` (
  `id_profile` INT(11) UNSIGNED NOT NULL,
  `id_module`  INT(11) UNSIGNED NOT NULL,
  `view`       TINYINT(1)       NOT NULL DEFAULT '0',
  `configure`  TINYINT(1)       NOT NULL DEFAULT '0',
  `uninstall`  TINYINT(1)       NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_profile`, `id_module`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_module_country` (
  `id_module`  INT(11) UNSIGNED NOT NULL,
  `id_shop`    INT(11) UNSIGNED NOT NULL DEFAULT '1',
  `id_country` INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id_module`, `id_shop`, `id_country`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_module_currency` (
  `id_module`   INT(11) UNSIGNED NOT NULL,
  `id_shop`     INT(11) UNSIGNED NOT NULL DEFAULT '1',
  `id_currency` INT(11)          NOT NULL,
  PRIMARY KEY (`id_module`, `id_shop`, `id_currency`),
  KEY `id_module` (`id_module`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_module_group` (
  `id_module` INT(11) UNSIGNED NOT NULL,
  `id_shop`   INT(11) UNSIGNED NOT NULL DEFAULT '1',
  `id_group`  INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id_module`, `id_shop`, `id_group`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_module_carrier` (
  `id_module`    INT(11) UNSIGNED NOT NULL,
  `id_shop`      INT(11) UNSIGNED NOT NULL DEFAULT '1',
  `id_reference` INT(11)          NOT NULL
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_operating_system` (
  `id_operating_system` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`                VARCHAR(64)               DEFAULT NULL,
  PRIMARY KEY (`id_operating_system`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_orders` (
  `id_order`                 INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `reference`                VARCHAR(9),
  `id_shop_group`            INT(11) UNSIGNED    NOT NULL DEFAULT '1',
  `id_shop`                  INT(11) UNSIGNED    NOT NULL DEFAULT '1',
  `id_carrier`               INT(11) UNSIGNED    NOT NULL,
  `id_lang`                  INT(11) UNSIGNED    NOT NULL,
  `id_customer`              INT(11) UNSIGNED    NOT NULL,
  `id_cart`                  INT(11) UNSIGNED    NOT NULL,
  `id_currency`              INT(11) UNSIGNED    NOT NULL,
  `id_address_delivery`      INT(11) UNSIGNED    NOT NULL,
  `id_address_invoice`       INT(11) UNSIGNED    NOT NULL,
  `current_state`            INT(11) UNSIGNED    NOT NULL,
  `secure_key`               VARCHAR(32)         NOT NULL DEFAULT '-1',
  `payment`                  VARCHAR(255)        NOT NULL,
  `conversion_rate`          DECIMAL(13, 6)      NOT NULL DEFAULT 1,
  `module`                   VARCHAR(64)                  DEFAULT NULL,
  `recyclable`               TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `gift`                     TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `gift_message`             TEXT,
  `mobile_theme`             TINYINT(1)          NOT NULL DEFAULT '0',
  `shipping_number`          VARCHAR(64)                  DEFAULT NULL,
  `total_discounts`          DECIMAL(20, 6)      NOT NULL DEFAULT '0.00',
  `total_discounts_tax_incl` DECIMAL(20, 6)      NOT NULL DEFAULT '0.00',
  `total_discounts_tax_excl` DECIMAL(20, 6)      NOT NULL DEFAULT '0.00',
  `total_paid`               DECIMAL(20, 6)      NOT NULL DEFAULT '0.00',
  `total_paid_tax_incl`      DECIMAL(20, 6)      NOT NULL DEFAULT '0.00',
  `total_paid_tax_excl`      DECIMAL(20, 6)      NOT NULL DEFAULT '0.00',
  `total_paid_real`          DECIMAL(20, 6)      NOT NULL DEFAULT '0.00',
  `total_products`           DECIMAL(20, 6)      NOT NULL DEFAULT '0.00',
  `total_products_wt`        DECIMAL(20, 6)      NOT NULL DEFAULT '0.00',
  `total_shipping`           DECIMAL(20, 6)      NOT NULL DEFAULT '0.00',
  `total_shipping_tax_incl`  DECIMAL(20, 6)      NOT NULL DEFAULT '0.00',
  `total_shipping_tax_excl`  DECIMAL(20, 6)      NOT NULL DEFAULT '0.00',
  `carrier_tax_rate`         DECIMAL(10, 3)      NOT NULL DEFAULT '0.00',
  `total_wrapping`           DECIMAL(20, 6)      NOT NULL DEFAULT '0.00',
  `total_wrapping_tax_incl`  DECIMAL(20, 6)      NOT NULL DEFAULT '0.00',
  `total_wrapping_tax_excl`  DECIMAL(20, 6)      NOT NULL DEFAULT '0.00',
  `round_mode`               TINYINT(1)          NOT NULL DEFAULT '2',
  `round_type`               TINYINT(1)          NOT NULL DEFAULT '1',
  `invoice_number`           INT(11) UNSIGNED    NOT NULL DEFAULT '0',
  `delivery_number`          INT(11) UNSIGNED    NOT NULL DEFAULT '0',
  `invoice_date`             DATETIME            NOT NULL,
  `delivery_date`            DATETIME            NOT NULL,
  `valid`                    INT(1) UNSIGNED     NOT NULL DEFAULT '0',
  `date_add`                 DATETIME            NOT NULL,
  `date_upd`                 DATETIME            NOT NULL,
  PRIMARY KEY (`id_order`),
  KEY `reference` (`reference`),
  KEY `id_customer` (`id_customer`),
  KEY `id_cart` (`id_cart`),
  KEY `invoice_number` (`invoice_number`),
  KEY `id_carrier` (`id_carrier`),
  KEY `id_lang` (`id_lang`),
  KEY `id_currency` (`id_currency`),
  KEY `id_address_delivery` (`id_address_delivery`),
  KEY `id_address_invoice` (`id_address_invoice`),
  KEY `id_shop_group` (`id_shop_group`),
  KEY (`current_state`),
  KEY `id_shop` (`id_shop`),
  INDEX `date_add`(`date_add`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_order_detail_tax` (
  `id_order_detail` INT(11)        NOT NULL,
  `id_tax`          INT(11)        NOT NULL,
  `unit_amount`     DECIMAL(16, 6) NOT NULL DEFAULT '0.00',
  `total_amount`    DECIMAL(16, 6) NOT NULL DEFAULT '0.00',
  KEY (`id_order_detail`),
  KEY `id_tax` (`id_tax`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_order_invoice` (
  `id_order_invoice`                INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_order`                        INT(11)          NOT NULL,
  `number`                          INT(11)          NOT NULL,
  `delivery_number`                 INT(11)          NOT NULL,
  `delivery_date`                   DATETIME,
  `total_discount_tax_excl`         DECIMAL(20, 6)   NOT NULL DEFAULT '0.00',
  `total_discount_tax_incl`         DECIMAL(20, 6)   NOT NULL DEFAULT '0.00',
  `total_paid_tax_excl`             DECIMAL(20, 6)   NOT NULL DEFAULT '0.00',
  `total_paid_tax_incl`             DECIMAL(20, 6)   NOT NULL DEFAULT '0.00',
  `total_products`                  DECIMAL(20, 6)   NOT NULL DEFAULT '0.00',
  `total_products_wt`               DECIMAL(20, 6)   NOT NULL DEFAULT '0.00',
  `total_shipping_tax_excl`         DECIMAL(20, 6)   NOT NULL DEFAULT '0.00',
  `total_shipping_tax_incl`         DECIMAL(20, 6)   NOT NULL DEFAULT '0.00',
  `shipping_tax_computation_method` INT(11) UNSIGNED NOT NULL,
  `total_wrapping_tax_excl`         DECIMAL(20, 6)   NOT NULL DEFAULT '0.00',
  `total_wrapping_tax_incl`         DECIMAL(20, 6)   NOT NULL DEFAULT '0.00',
  `shop_address`                    TEXT                      DEFAULT NULL,
  `invoice_address`                 TEXT                      DEFAULT NULL,
  `delivery_address`                TEXT                      DEFAULT NULL,
  `note`                            TEXT,
  `date_add`                        DATETIME         NOT NULL,
  PRIMARY KEY (`id_order_invoice`),
  KEY `id_order` (`id_order`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `PREFIX_order_invoice_tax` (
  `id_order_invoice` INT(11)        NOT NULL,
  `type`             VARCHAR(15)    NOT NULL,
  `id_tax`           INT(11)        NOT NULL,
  `amount`           DECIMAL(10, 6) NOT NULL DEFAULT '0.000000',
  KEY `id_tax` (`id_tax`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_order_detail` (
  `id_order_detail`               INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `id_order`                      INT(11) UNSIGNED    NOT NULL,
  `id_order_invoice`              INT(11)                      DEFAULT NULL,
  `id_warehouse`                  INT(11) UNSIGNED             DEFAULT '0',
  `id_shop`                       INT(11) UNSIGNED    NOT NULL,
  `product_id`                    INT(11) UNSIGNED    NOT NULL,
  `product_attribute_id`          INT(11) UNSIGNED             DEFAULT NULL,
  `product_name`                  VARCHAR(255)        NOT NULL,
  `product_quantity`              INT(11) UNSIGNED    NOT NULL DEFAULT '0',
  `product_quantity_in_stock`     INT(10)             NOT NULL DEFAULT '0',
  `product_quantity_refunded`     INT(11) UNSIGNED    NOT NULL DEFAULT '0',
  `product_quantity_return`       INT(11) UNSIGNED    NOT NULL DEFAULT '0',
  `product_quantity_reinjected`   INT(11) UNSIGNED    NOT NULL DEFAULT '0',
  `product_price`                 DECIMAL(20, 6)      NOT NULL DEFAULT '0.000000',
  `reduction_percent`             DECIMAL(10, 2)      NOT NULL DEFAULT '0.00',
  `reduction_amount`              DECIMAL(20, 6)      NOT NULL DEFAULT '0.000000',
  `reduction_amount_tax_incl`     DECIMAL(20, 6)      NOT NULL DEFAULT '0.000000',
  `reduction_amount_tax_excl`     DECIMAL(20, 6)      NOT NULL DEFAULT '0.000000',
  `group_reduction`               DECIMAL(10, 2)      NOT NULL DEFAULT '0.000000',
  `product_quantity_discount`     DECIMAL(20, 6)      NOT NULL DEFAULT '0.000000',
  `product_ean13`                 VARCHAR(13)                  DEFAULT NULL,
  `product_upc`                   VARCHAR(12)                  DEFAULT NULL,
  `product_reference`             VARCHAR(32)                  DEFAULT NULL,
  `product_supplier_reference`    VARCHAR(32)                  DEFAULT NULL,
  `product_weight`                DECIMAL(20, 6)      NOT NULL,
  `id_tax_rules_group`            INT(11) UNSIGNED             DEFAULT '0',
  `tax_computation_method`        TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `tax_name`                      VARCHAR(16)         NOT NULL,
  `tax_rate`                      DECIMAL(10, 3)      NOT NULL DEFAULT '0.000',
  `ecotax`                        DECIMAL(21, 6)      NOT NULL DEFAULT '0.00',
  `ecotax_tax_rate`               DECIMAL(5, 3)       NOT NULL DEFAULT '0.000',
  `discount_quantity_applied`     TINYINT(1)          NOT NULL DEFAULT '0',
  `download_hash`                 VARCHAR(255)                 DEFAULT NULL,
  `download_nb`                   INT(11) UNSIGNED             DEFAULT '0',
  `download_deadline`             DATETIME                     DEFAULT NULL,
  `total_price_tax_incl`          DECIMAL(20, 6)      NOT NULL DEFAULT '0.000000',
  `total_price_tax_excl`          DECIMAL(20, 6)      NOT NULL DEFAULT '0.000000',
  `unit_price_tax_incl`           DECIMAL(20, 6)      NOT NULL DEFAULT '0.000000',
  `unit_price_tax_excl`           DECIMAL(20, 6)      NOT NULL DEFAULT '0.000000',
  `total_shipping_price_tax_incl` DECIMAL(20, 6)      NOT NULL DEFAULT '0.000000',
  `total_shipping_price_tax_excl` DECIMAL(20, 6)      NOT NULL DEFAULT '0.000000',
  `purchase_supplier_price`       DECIMAL(20, 6)      NOT NULL DEFAULT '0.000000',
  `original_product_price`        DECIMAL(20, 6)      NOT NULL DEFAULT '0.000000',
  `original_wholesale_price`      DECIMAL(20, 6)      NOT NULL DEFAULT '0.000000',
  PRIMARY KEY (`id_order_detail`),
  KEY `order_detail_order` (`id_order`),
  KEY `product_id` (`product_id`),
  KEY `product_attribute_id` (`product_attribute_id`),
  KEY `id_tax_rules_group` (`id_tax_rules_group`),
  KEY `id_order_id_order_detail` (`id_order`, `id_order_detail`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_order_cart_rule` (
  `id_order_cart_rule` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_order`           INT(11) UNSIGNED NOT NULL,
  `id_cart_rule`       INT(11) UNSIGNED NOT NULL,
  `id_order_invoice`   INT(11) UNSIGNED          DEFAULT '0',
  `name`               VARCHAR(254)     NOT NULL,
  `value`              DECIMAL(17, 2)   NOT NULL DEFAULT '0.00',
  `value_tax_excl`     DECIMAL(17, 2)   NOT NULL DEFAULT '0.00',
  `free_shipping`      TINYINT(1)       NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_order_cart_rule`),
  KEY `id_order` (`id_order`),
  KEY `id_cart_rule` (`id_cart_rule`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_order_history` (
  `id_order_history` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_employee`      INT(11) UNSIGNED NOT NULL,
  `id_order`         INT(11) UNSIGNED NOT NULL,
  `id_order_state`   INT(11) UNSIGNED NOT NULL,
  `date_add`         DATETIME         NOT NULL,
  PRIMARY KEY (`id_order_history`),
  KEY `order_history_order` (`id_order`),
  KEY `id_employee` (`id_employee`),
  KEY `id_order_state` (`id_order_state`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_order_message` (
  `id_order_message` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `date_add`         DATETIME         NOT NULL,
  PRIMARY KEY (`id_order_message`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_order_message_lang` (
  `id_order_message` INT(11) UNSIGNED NOT NULL,
  `id_lang`          INT(11) UNSIGNED NOT NULL,
  `name`             VARCHAR(128)     NOT NULL,
  `message`          TEXT             NOT NULL,
  PRIMARY KEY (`id_order_message`, `id_lang`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_order_return` (
  `id_order_return` INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `id_customer`     INT(11) UNSIGNED    NOT NULL,
  `id_order`        INT(11) UNSIGNED    NOT NULL,
  `state`           TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
  `question`        TEXT                NOT NULL,
  `date_add`        DATETIME            NOT NULL,
  `date_upd`        DATETIME            NOT NULL,
  PRIMARY KEY (`id_order_return`),
  KEY `order_return_customer` (`id_customer`),
  KEY `id_order` (`id_order`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_order_return_detail` (
  `id_order_return`  INT(11) UNSIGNED NOT NULL,
  `id_order_detail`  INT(11) UNSIGNED NOT NULL,
  `id_customization` INT(11) UNSIGNED NOT NULL DEFAULT '0',
  `product_quantity` INT(11) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_order_return`, `id_order_detail`, `id_customization`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_order_return_state` (
  `id_order_return_state` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `color`                 VARCHAR(32)               DEFAULT NULL,
  PRIMARY KEY (`id_order_return_state`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_order_return_state_lang` (
  `id_order_return_state` INT(11) UNSIGNED NOT NULL,
  `id_lang`               INT(11) UNSIGNED NOT NULL,
  `name`                  VARCHAR(64)      NOT NULL,
  PRIMARY KEY (`id_order_return_state`, `id_lang`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;


CREATE TABLE `PREFIX_order_slip` (
  `id_order_slip`           INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `conversion_rate`         DECIMAL(13, 6)      NOT NULL DEFAULT 1,
  `id_customer`             INT(11) UNSIGNED    NOT NULL,
  `id_order`                INT(11) UNSIGNED    NOT NULL,
  `total_products_tax_excl` DECIMAL(20, 6)      NULL,
  `total_products_tax_incl` DECIMAL(20, 6)      NULL,
  `total_shipping_tax_excl` DECIMAL(20, 6)      NULL,
  `total_shipping_tax_incl` DECIMAL(20, 6)      NULL,
  `shipping_cost`           TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
  `amount`                  DECIMAL(10, 2)      NOT NULL,
  `shipping_cost_amount`    DECIMAL(10, 2)      NOT NULL,
  `partial`                 TINYINT(1)          NOT NULL,
  `order_slip_type`         TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `date_add`                DATETIME            NOT NULL,
  `date_upd`                DATETIME            NOT NULL,
  PRIMARY KEY (`id_order_slip`),
  KEY `order_slip_customer` (`id_customer`),
  KEY `id_order` (`id_order`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_order_slip_detail` (
  `id_order_slip`        INT(11) UNSIGNED NOT NULL,
  `id_order_detail`      INT(11) UNSIGNED NOT NULL,
  `product_quantity`     INT(11) UNSIGNED NOT NULL DEFAULT '0',
  `unit_price_tax_excl`  DECIMAL(20, 6)   NULL,
  `unit_price_tax_incl`  DECIMAL(20, 6)   NULL,
  `total_price_tax_excl` DECIMAL(20, 6)   NULL,
  `total_price_tax_incl` DECIMAL(20, 6),
  `amount_tax_excl`      DECIMAL(20, 6)            DEFAULT NULL,
  `amount_tax_incl`      DECIMAL(20, 6)            DEFAULT NULL,
  PRIMARY KEY (`id_order_slip`, `id_order_detail`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_order_state` (
  `id_order_state` INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `invoice`        TINYINT(1) UNSIGNED          DEFAULT '0',
  `send_email`     TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `module_name`    VARCHAR(64)         NULL     DEFAULT NULL,
  `color`          VARCHAR(32)                  DEFAULT NULL,
  `unremovable`    TINYINT(1) UNSIGNED NOT NULL,
  `hidden`         TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `logable`        TINYINT(1)          NOT NULL DEFAULT '0',
  `delivery`       TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `shipped`        TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `paid`           TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `pdf_invoice`    TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `pdf_delivery`   TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `deleted`        TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_order_state`),
  KEY `module_name` (`module_name`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_order_state_lang` (
  `id_order_state` INT(11) UNSIGNED NOT NULL,
  `id_lang`        INT(11) UNSIGNED NOT NULL,
  `name`           VARCHAR(64)      NOT NULL,
  `template`       VARCHAR(64)      NOT NULL,
  PRIMARY KEY (`id_order_state`, `id_lang`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_pack` (
  `id_product_pack`           INT(11) UNSIGNED NOT NULL,
  `id_product_item`           INT(11) UNSIGNED NOT NULL,
  `id_product_attribute_item` INT(11) UNSIGNED NOT NULL,
  `quantity`                  INT(11) UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_product_pack`, `id_product_item`, `id_product_attribute_item`),
  KEY `product_item` (`id_product_item`, `id_product_attribute_item`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_page` (
  `id_page`      INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_page_type` INT(11) UNSIGNED NOT NULL,
  `id_object`    INT(11) UNSIGNED          DEFAULT NULL,
  PRIMARY KEY (`id_page`),
  KEY `id_page_type` (`id_page_type`),
  KEY `id_object` (`id_object`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_page_type` (
  `id_page_type` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`         VARCHAR(255)     NOT NULL,
  PRIMARY KEY (`id_page_type`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_page_viewed` (
  `id_page`       INT(11) UNSIGNED NOT NULL,
  `id_shop_group` INT UNSIGNED     NOT NULL DEFAULT '1',
  `id_shop`       INT UNSIGNED     NOT NULL DEFAULT '1',
  `id_date_range` INT(11) UNSIGNED NOT NULL,
  `counter`       INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id_page`, `id_date_range`, `id_shop`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_order_payment` (
  `id_order_payment` INT            NOT NULL AUTO_INCREMENT,
  `order_reference`  VARCHAR(9),
  `id_currency`      INT UNSIGNED   NOT NULL,
  `amount`           DECIMAL(10, 2) NOT NULL,
  `payment_method`   VARCHAR(255)   NOT NULL,
  `conversion_rate`  DECIMAL(13, 6) NOT NULL DEFAULT 1,
  `transaction_id`   VARCHAR(254)   NULL,
  `card_number`      VARCHAR(254)   NULL,
  `card_brand`       VARCHAR(254)   NULL,
  `card_expiration`  CHAR(7)        NULL,
  `card_holder`      VARCHAR(254)   NULL,
  `date_add`         DATETIME       NOT NULL,
  PRIMARY KEY (`id_order_payment`),
  KEY `order_reference`(`order_reference`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_product` (
  `id_product`                INT(11) UNSIGNED                           NOT NULL AUTO_INCREMENT,
  `id_supplier`               INT(11) UNSIGNED                                    DEFAULT NULL,
  `id_manufacturer`           INT(11) UNSIGNED                                    DEFAULT NULL,
  `id_category_default`       INT(11) UNSIGNED                                    DEFAULT NULL,
  `id_shop_default`           INT(11) UNSIGNED                           NOT NULL DEFAULT 1,
  `id_tax_rules_group`        INT(11) UNSIGNED                           NOT NULL,
  `on_sale`                   TINYINT(1) UNSIGNED                        NOT NULL DEFAULT '0',
  `online_only`               TINYINT(1) UNSIGNED                        NOT NULL DEFAULT '0',
  `ean13`                     VARCHAR(13)                                         DEFAULT NULL,
  `upc`                       VARCHAR(12)                                         DEFAULT NULL,
  `ecotax`                    DECIMAL(17, 6)                             NOT NULL DEFAULT '0.00',
  `quantity`                  INT(10)                                    NOT NULL DEFAULT '0',
  `minimal_quantity`          INT(11) UNSIGNED                           NOT NULL DEFAULT '1',
  `price`                     DECIMAL(20, 6)                             NOT NULL DEFAULT '0.000000',
  `wholesale_price`           DECIMAL(20, 6)                             NOT NULL DEFAULT '0.000000',
  `unity`                     VARCHAR(255)                                        DEFAULT NULL,
  `unit_price_ratio`          DECIMAL(20, 6)                             NOT NULL DEFAULT '0.000000',
  `additional_shipping_cost`  DECIMAL(20, 2)                             NOT NULL DEFAULT '0.00',
  `reference`                 VARCHAR(32)                                         DEFAULT NULL,
  `supplier_reference`        VARCHAR(32)                                         DEFAULT NULL,
  `location`                  VARCHAR(64)                                         DEFAULT NULL,
  `width`                     DECIMAL(20, 6)                             NOT NULL DEFAULT '0',
  `height`                    DECIMAL(20, 6)                             NOT NULL DEFAULT '0',
  `depth`                     DECIMAL(20, 6)                             NOT NULL DEFAULT '0',
  `weight`                    DECIMAL(20, 6)                             NOT NULL DEFAULT '0',
  `out_of_stock`              INT(11) UNSIGNED                           NOT NULL DEFAULT '2',
  `quantity_discount`         TINYINT(1)                                          DEFAULT '0',
  `customizable`              TINYINT(2)                                 NOT NULL DEFAULT '0',
  `uploadable_files`          TINYINT(4)                                 NOT NULL DEFAULT '0',
  `text_fields`               TINYINT(4)                                 NOT NULL DEFAULT '0',
  `active`                    TINYINT(1) UNSIGNED                        NOT NULL DEFAULT '0',
  `redirect_type`             ENUM ('', '404', '301', '302')             NOT NULL DEFAULT '',
  `id_product_redirected`     INT(11) UNSIGNED                           NOT NULL DEFAULT '0',
  `available_for_order`       TINYINT(1)                                 NOT NULL DEFAULT '1',
  `available_date`            DATE                                       NOT NULL DEFAULT '1970-01-01',
  `condition`                 ENUM ('new', 'used', 'refurbished')        NOT NULL DEFAULT 'new',
  `show_price`                TINYINT(1)                                 NOT NULL DEFAULT '1',
  `indexed`                   TINYINT(1)                                 NOT NULL DEFAULT '0',
  `visibility`                ENUM ('both', 'catalog', 'search', 'none') NOT NULL DEFAULT 'both',
  `cache_is_pack`             TINYINT(1)                                 NOT NULL DEFAULT '0',
  `cache_has_attachments`     TINYINT(1)                                 NOT NULL DEFAULT '0',
  `is_virtual`                TINYINT(1)                                 NOT NULL DEFAULT '0',
  `cache_default_attribute`   INT(11) UNSIGNED                                    DEFAULT NULL,
  `date_add`                  DATETIME                                   NOT NULL,
  `date_upd`                  DATETIME                                   NOT NULL,
  `advanced_stock_management` TINYINT(1) DEFAULT '0'                     NOT NULL,
  `pack_stock_type`           INT(11) UNSIGNED DEFAULT '3'               NOT NULL,
  PRIMARY KEY (`id_product`),
  KEY `product_supplier` (`id_supplier`),
  KEY `product_manufacturer` (`id_manufacturer`, `id_product`),
  KEY `id_category_default` (`id_category_default`),
  KEY `indexed` (`indexed`),
  KEY `date_add` (`date_add`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `PREFIX_product_shop` (
  `id_product`                INT(11) UNSIGNED                           NOT NULL,
  `id_shop`                   INT(11) UNSIGNED                           NOT NULL,
  `id_category_default`       INT(11) UNSIGNED                                    DEFAULT NULL,
  `id_tax_rules_group`        INT(11) UNSIGNED                           NOT NULL,
  `on_sale`                   TINYINT(1) UNSIGNED                        NOT NULL DEFAULT '0',
  `online_only`               TINYINT(1) UNSIGNED                        NOT NULL DEFAULT '0',
  `ecotax`                    DECIMAL(17, 6)                             NOT NULL DEFAULT '0.000000',
  `minimal_quantity`          INT(11) UNSIGNED                           NOT NULL DEFAULT '1',
  `price`                     DECIMAL(20, 6)                             NOT NULL DEFAULT '0.000000',
  `wholesale_price`           DECIMAL(20, 6)                             NOT NULL DEFAULT '0.000000',
  `unity`                     VARCHAR(255)                                        DEFAULT NULL,
  `unit_price_ratio`          DECIMAL(20, 6)                             NOT NULL DEFAULT '0.000000',
  `additional_shipping_cost`  DECIMAL(20, 2)                             NOT NULL DEFAULT '0.00',
  `customizable`              TINYINT(2)                                 NOT NULL DEFAULT '0',
  `uploadable_files`          TINYINT(4)                                 NOT NULL DEFAULT '0',
  `text_fields`               TINYINT(4)                                 NOT NULL DEFAULT '0',
  `active`                    TINYINT(1) UNSIGNED                        NOT NULL DEFAULT '0',
  `redirect_type`             ENUM ('', '404', '301', '302')             NOT NULL DEFAULT '',
  `id_product_redirected`     INT(11) UNSIGNED                           NOT NULL DEFAULT '0',
  `available_for_order`       TINYINT(1)                                 NOT NULL DEFAULT '1',
  `available_date`            DATE                                       NOT NULL DEFAULT '1970-01-01',
  `condition`                 ENUM ('new', 'used', 'refurbished')        NOT NULL DEFAULT 'new',
  `show_price`                TINYINT(1)                                 NOT NULL DEFAULT '1',
  `indexed`                   TINYINT(1)                                 NOT NULL DEFAULT '0',
  `visibility`                ENUM ('both', 'catalog', 'search', 'none') NOT NULL DEFAULT 'both',
  `cache_default_attribute`   INT(11) UNSIGNED                                    DEFAULT NULL,
  `advanced_stock_management` TINYINT(1) DEFAULT '0'                     NOT NULL,
  `date_add`                  DATETIME                                   NOT NULL,
  `date_upd`                  DATETIME                                   NOT NULL,
  `pack_stock_type`           INT(11) UNSIGNED DEFAULT '3'               NOT NULL,
  PRIMARY KEY (`id_product`, `id_shop`),
  KEY `id_category_default` (`id_category_default`),
  KEY `date_add` (`date_add`, `active`, `visibility`),
  KEY `indexed` (`indexed`, `active`, `id_product`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_product_attribute` (
  `id_product_attribute` INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `id_product`           INT(11) UNSIGNED    NOT NULL,
  `reference`            VARCHAR(32)                  DEFAULT NULL,
  `supplier_reference`   VARCHAR(32)                  DEFAULT NULL,
  `location`             VARCHAR(64)                  DEFAULT NULL,
  `ean13`                VARCHAR(13)                  DEFAULT NULL,
  `upc`                  VARCHAR(12)                  DEFAULT NULL,
  `wholesale_price`      DECIMAL(20, 6)      NOT NULL DEFAULT '0.000000',
  `price`                DECIMAL(20, 6)      NOT NULL DEFAULT '0.000000',
  `ecotax`               DECIMAL(17, 6)      NOT NULL DEFAULT '0.00',
  `quantity`             INT(10)             NOT NULL DEFAULT '0',
  `weight`               DECIMAL(20, 6)      NOT NULL DEFAULT '0',
  `unit_price_impact`    DECIMAL(20, 6)      NOT NULL DEFAULT '0.00',
  `default_on`           TINYINT(1) UNSIGNED NULL     DEFAULT NULL,
  `minimal_quantity`     INT(11) UNSIGNED    NOT NULL DEFAULT '1',
  `available_date`       DATE                NOT NULL DEFAULT '1970-01-01',
  PRIMARY KEY (`id_product_attribute`),
  KEY `product_attribute_product` (`id_product`),
  KEY `reference` (`reference`),
  KEY `supplier_reference` (`supplier_reference`),
  UNIQUE KEY `product_default` (`id_product`, `default_on`),
  KEY `id_product_id_product_attribute` (`id_product_attribute`, `id_product`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_product_attribute_shop` (
  `id_product`           INT(11) UNSIGNED    NOT NULL,
  `id_product_attribute` INT(11) UNSIGNED    NOT NULL,
  `id_shop`              INT(11) UNSIGNED    NOT NULL,
  `wholesale_price`      DECIMAL(20, 6)      NOT NULL DEFAULT '0.000000',
  `price`                DECIMAL(20, 6)      NOT NULL DEFAULT '0.000000',
  `ecotax`               DECIMAL(17, 6)      NOT NULL DEFAULT '0.00',
  `weight`               DECIMAL(20, 6)      NOT NULL DEFAULT '0',
  `unit_price_impact`    DECIMAL(20, 6)      NOT NULL DEFAULT '0.00',
  `default_on`           TINYINT(1) UNSIGNED NULL     DEFAULT NULL,
  `minimal_quantity`     INT(11) UNSIGNED    NOT NULL DEFAULT '1',
  `available_date`       DATE                NOT NULL DEFAULT '1970-01-01',
  PRIMARY KEY (`id_product_attribute`, `id_shop`),
  UNIQUE KEY `id_product` (`id_product`, `id_shop`, `default_on`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_product_attribute_combination` (
  `id_attribute`         INT(11) UNSIGNED NOT NULL,
  `id_product_attribute` INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id_attribute`, `id_product_attribute`),
  KEY `id_product_attribute` (`id_product_attribute`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_product_attribute_image` (
  `id_product_attribute` INT(11) UNSIGNED NOT NULL,
  `id_image`             INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id_product_attribute`, `id_image`),
  KEY `id_image` (`id_image`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_product_download` (
  `id_product_download` INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `id_product`          INT(11) UNSIGNED    NOT NULL,
  `display_filename`    VARCHAR(255)                 DEFAULT NULL,
  `filename`            VARCHAR(255)                 DEFAULT NULL,
  `date_add`            DATETIME            NOT NULL,
  `date_expiration`     DATETIME                     DEFAULT NULL,
  `nb_days_accessible`  INT(11) UNSIGNED             DEFAULT NULL,
  `nb_downloadable`     INT(11) UNSIGNED             DEFAULT '1',
  `active`              TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
  `is_shareable`        TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_product_download`),
  KEY `product_active` (`id_product`, `active`),
  UNIQUE KEY `id_product` (`id_product`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_product_lang` (
  `id_product`        INT(11) UNSIGNED NOT NULL,
  `id_shop`           INT(11) UNSIGNED NOT NULL DEFAULT '1',
  `id_lang`           INT(11) UNSIGNED NOT NULL,
  `description`       TEXT,
  `description_short` TEXT,
  `link_rewrite`      VARCHAR(128)     NOT NULL,
  `meta_description`  VARCHAR(255)              DEFAULT NULL,
  `meta_keywords`     VARCHAR(255)              DEFAULT NULL,
  `meta_title`        VARCHAR(128)              DEFAULT NULL,
  `name`              VARCHAR(128)     NOT NULL,
  `available_now`     VARCHAR(255)              DEFAULT NULL,
  `available_later`   VARCHAR(255)              DEFAULT NULL,
  PRIMARY KEY (`id_product`, `id_shop`, `id_lang`),
  KEY `id_lang` (`id_lang`),
  KEY `name` (`name`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_product_sale` (
  `id_product` INT(11) UNSIGNED NOT NULL,
  `quantity`   INT(11) UNSIGNED NOT NULL DEFAULT '0',
  `sale_nbr`   INT(11) UNSIGNED NOT NULL DEFAULT '0',
  `date_upd`   DATE             NOT NULL,
  PRIMARY KEY (`id_product`),
  KEY `quantity` (`quantity`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_product_tag` (
  `id_product` INT(11) UNSIGNED NOT NULL,
  `id_tag`     INT(11) UNSIGNED NOT NULL,
  `id_lang`    INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id_product`, `id_tag`),
  KEY `id_tag` (`id_tag`),
  KEY `id_lang` (`id_lang`, `id_tag`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_profile` (
  `id_profile` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id_profile`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_profile_lang` (
  `id_lang`    INT(11) UNSIGNED NOT NULL,
  `id_profile` INT(11) UNSIGNED NOT NULL,
  `name`       VARCHAR(128)     NOT NULL,
  PRIMARY KEY (`id_profile`, `id_lang`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_quick_access` (
  `id_quick_access` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `new_window`      TINYINT(1)       NOT NULL DEFAULT '0',
  `link`            VARCHAR(255)     NOT NULL,
  PRIMARY KEY (`id_quick_access`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_quick_access_lang` (
  `id_quick_access` INT(11) UNSIGNED NOT NULL,
  `id_lang`         INT(11) UNSIGNED NOT NULL,
  `name`            VARCHAR(32)      NOT NULL,
  PRIMARY KEY (`id_quick_access`, `id_lang`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_range_price` (
  `id_range_price` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_carrier`     INT(11) UNSIGNED NOT NULL,
  `delimiter1`     DECIMAL(20, 6)   NOT NULL,
  `delimiter2`     DECIMAL(20, 6)   NOT NULL,
  PRIMARY KEY (`id_range_price`),
  UNIQUE KEY `id_carrier` (`id_carrier`, `delimiter1`, `delimiter2`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_range_weight` (
  `id_range_weight` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_carrier`      INT(11) UNSIGNED NOT NULL,
  `delimiter1`      DECIMAL(20, 6)   NOT NULL,
  `delimiter2`      DECIMAL(20, 6)   NOT NULL,
  PRIMARY KEY (`id_range_weight`),
  UNIQUE KEY `id_carrier` (`id_carrier`, `delimiter1`, `delimiter2`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_referrer` (
  `id_referrer`             INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`                    VARCHAR(64)      NOT NULL,
  `passwd`                  VARCHAR(60)               DEFAULT NULL,
  `http_referer_regexp`     VARCHAR(64)               DEFAULT NULL,
  `http_referer_like`       VARCHAR(64)               DEFAULT NULL,
  `request_uri_regexp`      VARCHAR(64)               DEFAULT NULL,
  `request_uri_like`        VARCHAR(64)               DEFAULT NULL,
  `http_referer_regexp_not` VARCHAR(64)               DEFAULT NULL,
  `http_referer_like_not`   VARCHAR(64)               DEFAULT NULL,
  `request_uri_regexp_not`  VARCHAR(64)               DEFAULT NULL,
  `request_uri_like_not`    VARCHAR(64)               DEFAULT NULL,
  `base_fee`                DECIMAL(5, 2)    NOT NULL DEFAULT '0.00',
  `percent_fee`             DECIMAL(5, 2)    NOT NULL DEFAULT '0.00',
  `click_fee`               DECIMAL(5, 2)    NOT NULL DEFAULT '0.00',
  `date_add`                DATETIME         NOT NULL,
  PRIMARY KEY (`id_referrer`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_referrer_cache` (
  `id_connections_source` INT(11) UNSIGNED NOT NULL,
  `id_referrer`           INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id_connections_source`, `id_referrer`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_referrer_shop` (
  `id_referrer`         INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_shop`             INT(11) UNSIGNED NOT NULL DEFAULT '1',
  `cache_visitors`      INT(11)                   DEFAULT NULL,
  `cache_visits`        INT(11)                   DEFAULT NULL,
  `cache_pages`         INT(11)                   DEFAULT NULL,
  `cache_registrations` INT(11)                   DEFAULT NULL,
  `cache_orders`        INT(11)                   DEFAULT NULL,
  `cache_sales`         DECIMAL(17, 2)            DEFAULT NULL,
  `cache_reg_rate`      DECIMAL(5, 4)             DEFAULT NULL,
  `cache_order_rate`    DECIMAL(5, 4)             DEFAULT NULL,
  PRIMARY KEY (`id_referrer`, `id_shop`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `PREFIX_request_sql` (
  `id_request_sql` INT(11)      NOT NULL AUTO_INCREMENT,
  `name`           VARCHAR(200) NOT NULL,
  `sql`            TEXT         NOT NULL,
  PRIMARY KEY (`id_request_sql`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_scene` (
  `id_scene` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `active`   TINYINT(1)       NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_scene`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_scene_category` (
  `id_scene`    INT(11) UNSIGNED NOT NULL,
  `id_category` INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id_scene`, `id_category`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_scene_lang` (
  `id_scene` INT(11) UNSIGNED NOT NULL,
  `id_lang`  INT(11) UNSIGNED NOT NULL,
  `name`     VARCHAR(100)     NOT NULL,
  PRIMARY KEY (`id_scene`, `id_lang`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_scene_products` (
  `id_scene`    INT(11) UNSIGNED NOT NULL,
  `id_product`  INT(11) UNSIGNED NOT NULL,
  `x_axis`      INT(4)           NOT NULL,
  `y_axis`      INT(4)           NOT NULL,
  `zone_width`  INT(3)           NOT NULL,
  `zone_height` INT(3)           NOT NULL,
  PRIMARY KEY (`id_scene`, `id_product`, `x_axis`, `y_axis`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_search_engine` (
  `id_search_engine` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `server`           VARCHAR(64)      NOT NULL,
  `getvar`           VARCHAR(16)      NOT NULL,
  PRIMARY KEY (`id_search_engine`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_search_index` (
  `id_product` INT(11) UNSIGNED     NOT NULL,
  `id_word`    INT(11) UNSIGNED     NOT NULL,
  `weight`     SMALLINT(4) UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_word`, `id_product`),
  KEY `id_product` (`id_product`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_search_word` (
  `id_word` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_shop` INT(11) UNSIGNED NOT NULL DEFAULT 1,
  `id_lang` INT(11) UNSIGNED NOT NULL,
  `word`    VARCHAR(15)      NOT NULL,
  PRIMARY KEY (`id_word`),
  UNIQUE KEY `id_lang` (`id_lang`, `id_shop`, `word`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_specific_price` (
  `id_specific_price`      INT UNSIGNED                  NOT NULL AUTO_INCREMENT,
  `id_specific_price_rule` INT(11) UNSIGNED              NOT NULL,
  `id_cart`                INT(11) UNSIGNED              NOT NULL,
  `id_product`             INT UNSIGNED                  NOT NULL,
  `id_shop`                INT(11) UNSIGNED              NOT NULL DEFAULT '1',
  `id_shop_group`          INT(11) UNSIGNED              NOT NULL,
  `id_currency`            INT UNSIGNED                  NOT NULL,
  `id_country`             INT UNSIGNED                  NOT NULL,
  `id_group`               INT UNSIGNED                  NOT NULL,
  `id_customer`            INT UNSIGNED                  NOT NULL,
  `id_product_attribute`   INT UNSIGNED                  NOT NULL,
  `price`                  DECIMAL(20, 6)                NOT NULL,
  `from_quantity`          MEDIUMINT(8) UNSIGNED         NOT NULL,
  `reduction`              DECIMAL(20, 6)                NOT NULL,
  `reduction_tax`          TINYINT(1)                    NOT NULL DEFAULT 1,
  `reduction_type`         ENUM ('amount', 'percentage') NOT NULL,
  `from`                   DATETIME                      NOT NULL,
  `to`                     DATETIME                      NOT NULL,
  PRIMARY KEY (`id_specific_price`),
  KEY (`id_product`, `id_shop`, `id_currency`, `id_country`, `id_group`, `id_customer`, `from_quantity`, `from`, `to`),
  KEY `from_quantity` (`from_quantity`),
  KEY (`id_specific_price_rule`),
  KEY (`id_cart`),
  KEY `id_product_attribute` (`id_product_attribute`),
  KEY `id_shop` (`id_shop`),
  KEY `id_customer` (`id_customer`),
  KEY `from` (`from`),
  KEY `to` (`to`),
  UNIQUE KEY `id_product_2` (`id_product`, `id_product_attribute`, `id_customer`, `id_cart`, `from`, `to`, `id_shop`, `id_shop_group`, `id_currency`, `id_country`, `id_group`, `from_quantity`, `id_specific_price_rule`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_state` (
  `id_state`     INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_country`   INT(11) UNSIGNED NOT NULL,
  `id_zone`      INT(11) UNSIGNED NOT NULL,
  `name`         VARCHAR(64)      NOT NULL,
  `iso_code`     VARCHAR(7)       NOT NULL,
  `tax_behavior` SMALLINT(1)      NOT NULL DEFAULT '0',
  `active`       TINYINT(1)       NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_state`),
  KEY `id_country` (`id_country`),
  KEY `name` (`name`),
  KEY `id_zone` (`id_zone`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;


CREATE TABLE `PREFIX_supplier` (
  `id_supplier` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(64)      NOT NULL,
  `date_add`    DATETIME         NOT NULL,
  `date_upd`    DATETIME         NOT NULL,
  `active`      TINYINT(1)       NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_supplier`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_supplier_lang` (
  `id_supplier`      INT(11) UNSIGNED NOT NULL,
  `id_lang`          INT(11) UNSIGNED NOT NULL,
  `description`      TEXT,
  `meta_title`       VARCHAR(128) DEFAULT NULL,
  `meta_keywords`    VARCHAR(255) DEFAULT NULL,
  `meta_description` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id_supplier`, `id_lang`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_tab` (
  `id_tab`         INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_parent`      INT(11)          NOT NULL,
  `class_name`     VARCHAR(64)      NOT NULL,
  `module`         VARCHAR(64)      NULL,
  `position`       INT(11) UNSIGNED NOT NULL,
  `active`         TINYINT(1)       NOT NULL DEFAULT '1',
  `hide_host_mode` TINYINT(1)       NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_tab`),
  KEY `class_name` (`class_name`),
  KEY `id_parent` (`id_parent`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_tab_lang` (
  `id_tab`  INT(11) UNSIGNED NOT NULL,
  `id_lang` INT(11) UNSIGNED NOT NULL,
  `name`    VARCHAR(64) DEFAULT NULL,
  PRIMARY KEY (`id_tab`, `id_lang`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_tag` (
  `id_tag`  INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_lang` INT(11) UNSIGNED NOT NULL,
  `name`    VARCHAR(32)      NOT NULL,
  PRIMARY KEY (`id_tag`),
  KEY `tag_name` (`name`),
  KEY `id_lang` (`id_lang`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_tag_count` (
  `id_group` INT(11) UNSIGNED NOT NULL DEFAULT 0,
  `id_tag`   INT(11) UNSIGNED NOT NULL DEFAULT 0,
  `id_lang`  INT(11) UNSIGNED NOT NULL DEFAULT 0,
  `id_shop`  INT(11) UNSIGNED NOT NULL DEFAULT 0,
  `counter`  INT(11) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_group`, `id_tag`),
  KEY (`id_group`, `id_lang`, `id_shop`, `counter`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_tax` (
  `id_tax`  INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `rate`    DECIMAL(10, 3)      NOT NULL,
  `active`  TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
  `deleted` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_tax`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_tax_lang` (
  `id_tax`  INT(11) UNSIGNED NOT NULL,
  `id_lang` INT(11) UNSIGNED NOT NULL,
  `name`    VARCHAR(32)      NOT NULL,
  PRIMARY KEY (`id_tax`, `id_lang`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_timezone` (
  id_timezone INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  name        VARCHAR(32)      NOT NULL,
  PRIMARY KEY (`id_timezone`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_web_browser` (
  `id_web_browser` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`           VARCHAR(64)               DEFAULT NULL,
  PRIMARY KEY (`id_web_browser`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_zone` (
  `id_zone` INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `name`    VARCHAR(64)         NOT NULL,
  `active`  TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_zone`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_carrier_group` (
  `id_carrier` INT(11) UNSIGNED NOT NULL,
  `id_group`   INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id_carrier`, `id_group`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_store` (
  `id_store`   INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `id_country` INT(11) UNSIGNED    NOT NULL,
  `id_state`   INT(11) UNSIGNED             DEFAULT NULL,
  `name`       VARCHAR(128)        NOT NULL,
  `address1`   VARCHAR(128)        NOT NULL,
  `address2`   VARCHAR(128)                 DEFAULT NULL,
  `city`       VARCHAR(64)         NOT NULL,
  `postcode`   VARCHAR(12)         NOT NULL,
  `latitude`   DECIMAL(13, 8)               DEFAULT NULL,
  `longitude`  DECIMAL(13, 8)               DEFAULT NULL,
  `hours`      VARCHAR(254)                 DEFAULT NULL,
  `phone`      VARCHAR(16)                  DEFAULT NULL,
  `fax`        VARCHAR(16)                  DEFAULT NULL,
  `email`      VARCHAR(128)                 DEFAULT NULL,
  `note`       TEXT,
  `active`     TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `date_add`   DATETIME            NOT NULL,
  `date_upd`   DATETIME            NOT NULL,
  PRIMARY KEY (`id_store`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_webservice_account` (
  `id_webservice_account` INT(11)     NOT NULL AUTO_INCREMENT,
  `key`                   VARCHAR(32) NOT NULL,
  `description`           TEXT        NULL,
  `class_name`            VARCHAR(50) NOT NULL DEFAULT 'WebserviceRequest',
  `is_module`             TINYINT(2)  NOT NULL DEFAULT '0',
  `module_name`           VARCHAR(50) NULL     DEFAULT NULL,
  `active`                TINYINT(2)  NOT NULL,
  PRIMARY KEY (`id_webservice_account`),
  KEY `key` (`key`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_webservice_permission` (
  `id_webservice_permission` INT(11)                                       NOT NULL AUTO_INCREMENT,
  `resource`                 VARCHAR(50)                                   NOT NULL,
  `method`                   ENUM ('GET', 'POST', 'PUT', 'DELETE', 'HEAD') NOT NULL,
  `id_webservice_account`    INT(11)                                       NOT NULL,
  PRIMARY KEY (`id_webservice_permission`),
  UNIQUE KEY `resource_2` (`resource`, `method`, `id_webservice_account`),
  KEY `resource` (`resource`),
  KEY `method` (`method`),
  KEY `id_webservice_account` (`id_webservice_account`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_required_field` (
  `id_required_field` INT(11)     NOT NULL AUTO_INCREMENT,
  `object_name`       VARCHAR(32) NOT NULL,
  `field_name`        VARCHAR(32) NOT NULL,
  PRIMARY KEY (`id_required_field`),
  KEY `object_name` (`object_name`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_memcached_servers` (
  `id_memcached_server` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `ip`                  VARCHAR(254)     NOT NULL,
  `port`                INT(11) UNSIGNED NOT NULL,
  `weight`              INT(11) UNSIGNED NOT NULL
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_product_country_tax` (
  `id_product` INT(11) NOT NULL,
  `id_country` INT(11) NOT NULL,
  `id_tax`     INT(11) NOT NULL,
  PRIMARY KEY (`id_product`, `id_country`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_tax_rule` (
  `id_tax_rule`        INT(11)      NOT NULL AUTO_INCREMENT,
  `id_tax_rules_group` INT(11)      NOT NULL,
  `id_country`         INT(11)      NOT NULL,
  `id_state`           INT(11)      NOT NULL,
  `zipcode_from`       VARCHAR(12)  NOT NULL,
  `zipcode_to`         VARCHAR(12)  NOT NULL,
  `id_tax`             INT(11)      NOT NULL,
  `behavior`           INT(11)      NOT NULL,
  `description`        VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id_tax_rule`),
  KEY `id_tax_rules_group` (`id_tax_rules_group`),
  KEY `id_tax` (`id_tax`),
  KEY `category_getproducts` (`id_tax_rules_group`, `id_country`, `id_state`, `zipcode_from`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_tax_rules_group` (
  `id_tax_rules_group` INT                 NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name`               VARCHAR(50)         NOT NULL,
  `active`             INT                 NOT NULL,
  `deleted`            TINYINT(1) UNSIGNED NOT NULL,
  `date_add`           DATETIME            NOT NULL,
  `date_upd`           DATETIME            NOT NULL
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_specific_price_priority` (
  `id_specific_price_priority` INT         NOT NULL AUTO_INCREMENT,
  `id_product`                 INT         NOT NULL,
  `priority`                   VARCHAR(80) NOT NULL,
  PRIMARY KEY (`id_specific_price_priority`, `id_product`),
  UNIQUE KEY `id_product` (`id_product`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_log` (
  `id_log`      INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `severity`    TINYINT(1)       NOT NULL,
  `error_code`  INT(11)                   DEFAULT NULL,
  `message`     TEXT             NOT NULL,
  `object_type` VARCHAR(32)               DEFAULT NULL,
  `object_id`   INT(11) UNSIGNED          DEFAULT NULL,
  `id_employee` INT(11) UNSIGNED          DEFAULT NULL,
  `date_add`    DATETIME         NOT NULL,
  `date_upd`    DATETIME         NOT NULL,
  PRIMARY KEY (`id_log`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_import_match` (
  `id_import_match` INT(10)     NOT NULL AUTO_INCREMENT,
  `name`            VARCHAR(32) NOT NULL,
  `match`           TEXT        NOT NULL,
  `skip`            INT(2)      NOT NULL,
  PRIMARY KEY (`id_import_match`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `PREFIX_shop_group` (
  `id_shop_group`  INT(11) UNSIGNED   NOT NULL AUTO_INCREMENT,
  `name`           VARCHAR(64)
                   CHARACTER SET utf8 NOT NULL,
  `share_customer` TINYINT(1)         NOT NULL,
  `share_order`    TINYINT(1)         NOT NULL,
  `share_stock`    TINYINT(1)         NOT NULL,
  `active`         TINYINT(1)         NOT NULL DEFAULT '1',
  `deleted`        TINYINT(1)         NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_shop_group`),
  KEY `deleted` (`deleted`, `name`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `PREFIX_shop` (
  `id_shop`       INT(11) UNSIGNED   NOT NULL AUTO_INCREMENT,
  `id_shop_group` INT(11) UNSIGNED   NOT NULL,
  `name`          VARCHAR(64)
                  CHARACTER SET utf8 NOT NULL,
  `id_category`   INT(11) UNSIGNED   NOT NULL DEFAULT '1',
  `id_theme`      INT(1) UNSIGNED    NOT NULL,
  `active`        TINYINT(1)         NOT NULL DEFAULT '1',
  `deleted`       TINYINT(1)         NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_shop`),
  KEY `id_shop_group` (`id_shop_group`, `deleted`),
  KEY `id_category` (`id_category`),
  KEY `id_theme` (`id_theme`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `PREFIX_shop_url` (
  `id_shop_url`  INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_shop`      INT(11) UNSIGNED NOT NULL,
  `domain`       VARCHAR(150)     NOT NULL,
  `domain_ssl`   VARCHAR(150)     NOT NULL,
  `physical_uri` VARCHAR(64)      NOT NULL,
  `virtual_uri`  VARCHAR(64)      NOT NULL,
  `main`         TINYINT(1)       NOT NULL,
  `active`       TINYINT(1)       NOT NULL,
  PRIMARY KEY (`id_shop_url`),
  KEY `id_shop` (`id_shop`, `main`),
  UNIQUE KEY `full_shop_url` (`domain`, `physical_uri`, `virtual_uri`),
  UNIQUE KEY `full_shop_url_ssl` (`domain_ssl`, `physical_uri`, `virtual_uri`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `PREFIX_theme` (
  `id_theme`             INT(11)          NOT NULL AUTO_INCREMENT,
  `name`                 VARCHAR(64)      NOT NULL,
  `directory`            VARCHAR(64)      NOT NULL,
  `responsive`           TINYINT(1)       NOT NULL DEFAULT '0',
  `default_left_column`  TINYINT(1)       NOT NULL DEFAULT '0',
  `default_right_column` TINYINT(1)       NOT NULL DEFAULT '0',
  `product_per_page`     INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id_theme`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `PREFIX_theme_meta` (
  `id_theme_meta` INT(11)          NOT NULL AUTO_INCREMENT,
  `id_theme`      INT(11)          NOT NULL,
  `id_meta`       INT(11) UNSIGNED NOT NULL,
  `left_column`   TINYINT(1)       NOT NULL DEFAULT '1',
  `right_column`  TINYINT(1)       NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_theme_meta`),
  UNIQUE KEY `id_theme_2` (`id_theme`, `id_meta`),
  KEY `id_theme` (`id_theme`),
  KEY `id_meta` (`id_meta`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  AUTO_INCREMENT = 1;

CREATE TABLE IF NOT EXISTS `PREFIX_theme_specific` (
  `id_theme`  INT(11) UNSIGNED NOT NULL,
  `id_shop`   INT(11) UNSIGNED NOT NULL,
  `entity`    INT(11) UNSIGNED NOT NULL,
  `id_object` INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id_theme`, `id_shop`, `entity`, `id_object`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_country_shop` (
  `id_country` INT(11) UNSIGNED NOT NULL,
  `id_shop`    INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id_country`, `id_shop`),
  KEY `id_shop` (`id_shop`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_carrier_shop` (
  `id_carrier` INT(11) UNSIGNED NOT NULL,
  `id_shop`    INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id_carrier`, `id_shop`),
  KEY `id_shop` (`id_shop`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_address_format` (
  `id_country` INT(11) UNSIGNED NOT NULL,
  `format`     VARCHAR(255)     NOT NULL DEFAULT '',
  PRIMARY KEY (`id_country`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_cms_shop` (
  `id_cms`  INT(11) UNSIGNED NOT NULL,
  `id_shop` INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id_cms`, `id_shop`),
  KEY `id_shop` (`id_shop`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_lang_shop` (
  `id_lang` INT(11) UNSIGNED NOT NULL,
  `id_shop` INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id_lang`, `id_shop`),
  KEY `id_shop` (`id_shop`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_currency_shop` (
  `id_currency`     INT(11) UNSIGNED NOT NULL,
  `id_shop`         INT(11) UNSIGNED NOT NULL,
  `conversion_rate` DECIMAL(13, 6)   NOT NULL,
  PRIMARY KEY (`id_currency`, `id_shop`),
  KEY `id_shop` (`id_shop`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_contact_shop` (
  `id_contact` INT(11) UNSIGNED NOT NULL,
  `id_shop`    INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id_contact`, `id_shop`),
  KEY `id_shop` (`id_shop`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_image_shop` (
  `id_product` INT(11) UNSIGNED    NOT NULL,
  `id_image`   INT(11) UNSIGNED    NOT NULL,
  `id_shop`    INT(11) UNSIGNED    NOT NULL,
  `cover`      TINYINT(1) UNSIGNED NULL DEFAULT NULL,
  PRIMARY KEY (`id_image`, `id_shop`),
  UNIQUE KEY `id_product` (`id_product`, `id_shop`, `cover`),
  KEY `id_shop` (`id_shop`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_attribute_shop` (
  `id_attribute` INT(11) UNSIGNED NOT NULL,
  `id_shop`      INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id_attribute`, `id_shop`),
  KEY `id_shop` (`id_shop`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_feature_shop` (
  `id_feature` INT(11) UNSIGNED NOT NULL,
  `id_shop`    INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id_feature`, `id_shop`),
  KEY `id_shop` (`id_shop`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_group_shop` (
  `id_group` INT(11) UNSIGNED NOT NULL,
  `id_shop`  INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id_group`, `id_shop`),
  KEY `id_shop` (`id_shop`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_attribute_group_shop` (
  `id_attribute_group` INT(11) UNSIGNED NOT NULL,
  `id_shop`            INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id_attribute_group`, `id_shop`),
  KEY `id_shop` (`id_shop`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_tax_rules_group_shop` (
  `id_tax_rules_group` INT(11) UNSIGNED NOT NULL,
  `id_shop`            INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id_tax_rules_group`, `id_shop`),
  KEY `id_shop` (`id_shop`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_zone_shop` (
  `id_zone` INT(11) UNSIGNED NOT NULL,
  `id_shop` INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id_zone`, `id_shop`),
  KEY `id_shop` (`id_shop`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_manufacturer_shop` (
  `id_manufacturer` INT(11) UNSIGNED NOT NULL,
  `id_shop`         INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id_manufacturer`, `id_shop`),
  KEY `id_shop` (`id_shop`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_supplier_shop` (
  `id_supplier` INT(11) UNSIGNED NOT NULL,
  `id_shop`     INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id_supplier`, `id_shop`),
  KEY `id_shop` (`id_shop`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_store_shop` (
  `id_store` INT(11) UNSIGNED NOT NULL,
  `id_shop`  INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id_store`, `id_shop`),
  KEY `id_shop` (`id_shop`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_module_shop` (
  `id_module`     INT(11) UNSIGNED NOT NULL,
  `id_shop`       INT(11) UNSIGNED NOT NULL,
  `enable_device` TINYINT(1)       NOT NULL DEFAULT '7',
  PRIMARY KEY (`id_module`, `id_shop`),
  KEY `id_shop` (`id_shop`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_webservice_account_shop` (
  `id_webservice_account` INT(11) UNSIGNED NOT NULL,
  `id_shop`               INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id_webservice_account`, `id_shop`),
  KEY `id_shop` (`id_shop`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_scene_shop` (
  `id_scene` INT(11) UNSIGNED NOT NULL,
  `id_shop`  INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id_scene`, `id_shop`),
  KEY `id_shop` (`id_shop`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_stock_mvt` (
  `id_stock_mvt`        BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `id_stock`            INT(11) UNSIGNED NOT NULL,
  `id_order`            INT(11) UNSIGNED          DEFAULT NULL,
  `id_supply_order`     INT(11) UNSIGNED          DEFAULT NULL,
  `id_stock_mvt_reason` INT(11) UNSIGNED NOT NULL,
  `id_employee`         INT(11) UNSIGNED NOT NULL,
  `employee_lastname`   VARCHAR(32)               DEFAULT '',
  `employee_firstname`  VARCHAR(32)               DEFAULT '',
  `physical_quantity`   INT(11) UNSIGNED NOT NULL,
  `date_add`            DATETIME         NOT NULL,
  `sign`                TINYINT(1)       NOT NULL DEFAULT 1,
  `price_te`            DECIMAL(20, 6)            DEFAULT '0.000000',
  `last_wa`             DECIMAL(20, 6)            DEFAULT '0.000000',
  `current_wa`          DECIMAL(20, 6)            DEFAULT '0.000000',
  `referer`             BIGINT UNSIGNED           DEFAULT NULL,
  PRIMARY KEY (`id_stock_mvt`),
  KEY `id_stock` (`id_stock`),
  KEY `id_stock_mvt_reason` (`id_stock_mvt_reason`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_stock_mvt_reason` (
  `id_stock_mvt_reason` INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `sign`                TINYINT(1)          NOT NULL DEFAULT 1,
  `date_add`            DATETIME            NOT NULL,
  `date_upd`            DATETIME            NOT NULL,
  `deleted`             TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_stock_mvt_reason`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_stock_mvt_reason_lang` (
  `id_stock_mvt_reason` INT(11) UNSIGNED   NOT NULL,
  `id_lang`             INT(11) UNSIGNED   NOT NULL,
  `name`                VARCHAR(255)
                        CHARACTER SET utf8 NOT NULL,
  PRIMARY KEY (`id_stock_mvt_reason`, `id_lang`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_stock` (
  `id_stock`             INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_warehouse`         INT(11) UNSIGNED NOT NULL,
  `id_product`           INT(11) UNSIGNED NOT NULL,
  `id_product_attribute` INT(11) UNSIGNED NOT NULL,
  `reference`            VARCHAR(32)      NOT NULL,
  `ean13`                VARCHAR(13)               DEFAULT NULL,
  `upc`                  VARCHAR(12)               DEFAULT NULL,
  `physical_quantity`    INT(11) UNSIGNED NOT NULL,
  `usable_quantity`      INT(11) UNSIGNED NOT NULL,
  `price_te`             DECIMAL(20, 6)            DEFAULT '0.000000',
  PRIMARY KEY (`id_stock`),
  KEY `id_warehouse` (`id_warehouse`),
  KEY `id_product` (`id_product`),
  KEY `id_product_attribute` (`id_product_attribute`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_warehouse` (
  `id_warehouse`    INT(11) UNSIGNED            NOT NULL AUTO_INCREMENT,
  `id_currency`     INT(11) UNSIGNED            NOT NULL,
  `id_address`      INT(11) UNSIGNED            NOT NULL,
  `id_employee`     INT(11) UNSIGNED            NOT NULL,
  `reference`       VARCHAR(32)                          DEFAULT NULL,
  `name`            VARCHAR(45)                 NOT NULL,
  `management_type` ENUM ('WA', 'FIFO', 'LIFO') NOT NULL DEFAULT 'WA',
  `deleted`         TINYINT(1) UNSIGNED         NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_warehouse`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_warehouse_product_location` (
  `id_warehouse_product_location` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_product`                    INT(11) UNSIGNED NOT NULL,
  `id_product_attribute`          INT(11) UNSIGNED NOT NULL,
  `id_warehouse`                  INT(11) UNSIGNED NOT NULL,
  `location`                      VARCHAR(64)               DEFAULT NULL,
  PRIMARY KEY (`id_warehouse_product_location`),
  UNIQUE KEY `id_product` (`id_product`, `id_product_attribute`, `id_warehouse`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_warehouse_shop` (
  `id_shop`      INT(11) UNSIGNED NOT NULL,
  `id_warehouse` INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id_warehouse`, `id_shop`),
  KEY `id_warehouse` (`id_warehouse`),
  KEY `id_shop` (`id_shop`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_warehouse_carrier` (
  `id_carrier`   INT(11) UNSIGNED NOT NULL,
  `id_warehouse` INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id_warehouse`, `id_carrier`),
  KEY `id_warehouse` (`id_warehouse`),
  KEY `id_carrier` (`id_carrier`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_stock_available` (
  `id_stock_available`   INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `id_product`           INT(11) UNSIGNED    NOT NULL,
  `id_product_attribute` INT(11) UNSIGNED    NOT NULL,
  `id_shop`              INT(11) UNSIGNED    NOT NULL,
  `id_shop_group`        INT(11) UNSIGNED    NOT NULL,
  `quantity`             INT(10)             NOT NULL DEFAULT '0',
  `depends_on_stock`     TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `out_of_stock`         TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_stock_available`),
  KEY `id_shop` (`id_shop`),
  KEY `id_shop_group` (`id_shop_group`),
  KEY `id_product` (`id_product`),
  KEY `id_product_attribute` (`id_product_attribute`),
  UNIQUE `product_sqlstock` (`id_product`, `id_product_attribute`, `id_shop`, `id_shop_group`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_supply_order` (
  `id_supply_order`        INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_supplier`            INT(11) UNSIGNED NOT NULL,
  `supplier_name`          VARCHAR(64)      NOT NULL,
  `id_lang`                INT(11) UNSIGNED NOT NULL,
  `id_warehouse`           INT(11) UNSIGNED NOT NULL,
  `id_supply_order_state`  INT(11) UNSIGNED NOT NULL,
  `id_currency`            INT(11) UNSIGNED NOT NULL,
  `id_ref_currency`        INT(11) UNSIGNED NOT NULL,
  `reference`              VARCHAR(64)      NOT NULL,
  `date_add`               DATETIME         NOT NULL,
  `date_upd`               DATETIME         NOT NULL,
  `date_delivery_expected` DATETIME                  DEFAULT NULL,
  `total_te`               DECIMAL(20, 6)            DEFAULT '0.000000',
  `total_with_discount_te` DECIMAL(20, 6)            DEFAULT '0.000000',
  `total_tax`              DECIMAL(20, 6)            DEFAULT '0.000000',
  `total_ti`               DECIMAL(20, 6)            DEFAULT '0.000000',
  `discount_rate`          DECIMAL(20, 6)            DEFAULT '0.000000',
  `discount_value_te`      DECIMAL(20, 6)            DEFAULT '0.000000',
  `is_template`            TINYINT(1)                DEFAULT '0',
  PRIMARY KEY (`id_supply_order`),
  KEY `id_supplier` (`id_supplier`),
  KEY `id_warehouse` (`id_warehouse`),
  KEY `reference` (`reference`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_supply_order_detail` (
  `id_supply_order_detail`        INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_supply_order`               INT(11) UNSIGNED NOT NULL,
  `id_currency`                   INT(11) UNSIGNED NOT NULL,
  `id_product`                    INT(11) UNSIGNED NOT NULL,
  `id_product_attribute`          INT(11) UNSIGNED NOT NULL,
  `reference`                     VARCHAR(32)      NOT NULL,
  `supplier_reference`            VARCHAR(32)      NOT NULL,
  `name`                          VARCHAR(128)     NOT NULL,
  `ean13`                         VARCHAR(13)               DEFAULT NULL,
  `upc`                           VARCHAR(12)               DEFAULT NULL,
  `exchange_rate`                 DECIMAL(20, 6)            DEFAULT '0.000000',
  `unit_price_te`                 DECIMAL(20, 6)            DEFAULT '0.000000',
  `quantity_expected`             INT(11) UNSIGNED NOT NULL,
  `quantity_received`             INT(11) UNSIGNED NOT NULL,
  `price_te`                      DECIMAL(20, 6)            DEFAULT '0.000000',
  `discount_rate`                 DECIMAL(20, 6)            DEFAULT '0.000000',
  `discount_value_te`             DECIMAL(20, 6)            DEFAULT '0.000000',
  `price_with_discount_te`        DECIMAL(20, 6)            DEFAULT '0.000000',
  `tax_rate`                      DECIMAL(20, 6)            DEFAULT '0.000000',
  `tax_value`                     DECIMAL(20, 6)            DEFAULT '0.000000',
  `price_ti`                      DECIMAL(20, 6)            DEFAULT '0.000000',
  `tax_value_with_order_discount` DECIMAL(20, 6)            DEFAULT '0.000000',
  `price_with_order_discount_te`  DECIMAL(20, 6)            DEFAULT '0.000000',
  PRIMARY KEY (`id_supply_order_detail`),
  KEY `id_supply_order` (`id_supply_order`, `id_product`),
  KEY `id_product_attribute` (`id_product_attribute`),
  KEY `id_product_product_attribute` (`id_product`, `id_product_attribute`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_supply_order_history` (
  `id_supply_order_history` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_supply_order`         INT(11) UNSIGNED NOT NULL,
  `id_employee`             INT(11) UNSIGNED NOT NULL,
  `employee_lastname`       VARCHAR(32)               DEFAULT '',
  `employee_firstname`      VARCHAR(32)               DEFAULT '',
  `id_state`                INT(11) UNSIGNED NOT NULL,
  `date_add`                DATETIME         NOT NULL,
  PRIMARY KEY (`id_supply_order_history`),
  KEY `id_supply_order` (`id_supply_order`),
  KEY `id_employee` (`id_employee`),
  KEY `id_state` (`id_state`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_supply_order_state` (
  `id_supply_order_state` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `delivery_note`         TINYINT(1)       NOT NULL DEFAULT '0',
  `editable`              TINYINT(1)       NOT NULL DEFAULT '0',
  `receipt_state`         TINYINT(1)       NOT NULL DEFAULT '0',
  `pending_receipt`       TINYINT(1)       NOT NULL DEFAULT '0',
  `enclosed`              TINYINT(1)       NOT NULL DEFAULT '0',
  `color`                 VARCHAR(32)               DEFAULT NULL,
  PRIMARY KEY (`id_supply_order_state`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_supply_order_state_lang` (
  `id_supply_order_state` INT(11) UNSIGNED NOT NULL,
  `id_lang`               INT(11) UNSIGNED NOT NULL,
  `name`                  VARCHAR(128) DEFAULT NULL,
  PRIMARY KEY (`id_supply_order_state`, `id_lang`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_supply_order_receipt_history` (
  `id_supply_order_receipt_history` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_supply_order_detail`          INT(11) UNSIGNED NOT NULL,
  `id_employee`                     INT(11) UNSIGNED NOT NULL,
  `employee_lastname`               VARCHAR(32)               DEFAULT '',
  `employee_firstname`              VARCHAR(32)               DEFAULT '',
  `id_supply_order_state`           INT(11) UNSIGNED NOT NULL,
  `quantity`                        INT(11) UNSIGNED NOT NULL,
  `date_add`                        DATETIME         NOT NULL,
  PRIMARY KEY (`id_supply_order_receipt_history`),
  KEY `id_supply_order_detail` (`id_supply_order_detail`),
  KEY `id_supply_order_state` (`id_supply_order_state`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_product_supplier` (
  `id_product_supplier`        INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_product`                 INT(11) UNSIGNED NOT NULL,
  `id_product_attribute`       INT(11) UNSIGNED NOT NULL DEFAULT '0',
  `id_supplier`                INT(11) UNSIGNED NOT NULL,
  `product_supplier_reference` VARCHAR(32)               DEFAULT NULL,
  `product_supplier_price_te`  DECIMAL(20, 6)   NOT NULL DEFAULT '0.000000',
  `id_currency`                INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id_product_supplier`),
  UNIQUE KEY `id_product` (`id_product`, `id_product_attribute`, `id_supplier`),
  KEY `id_supplier` (`id_supplier`, `id_product`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_order_carrier` (
  `id_order_carrier`       INT(11)          NOT NULL AUTO_INCREMENT,
  `id_order`               INT(11) UNSIGNED NOT NULL,
  `id_carrier`             INT(11) UNSIGNED NOT NULL,
  `id_order_invoice`       INT(11) UNSIGNED          DEFAULT NULL,
  `weight`                 DECIMAL(20, 6)            DEFAULT NULL,
  `shipping_cost_tax_excl` DECIMAL(20, 6)            DEFAULT NULL,
  `shipping_cost_tax_incl` DECIMAL(20, 6)            DEFAULT NULL,
  `tracking_number`        VARCHAR(64)               DEFAULT NULL,
  `date_add`               DATETIME         NOT NULL,
  PRIMARY KEY (`id_order_carrier`),
  KEY `id_order` (`id_order`),
  KEY `id_carrier` (`id_carrier`),
  KEY `id_order_invoice` (`id_order_invoice`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `PREFIX_specific_price_rule` (
  `id_specific_price_rule` INT(11) UNSIGNED              NOT NULL AUTO_INCREMENT,
  `name`                   VARCHAR(255)                  NOT NULL,
  `id_shop`                INT(11) UNSIGNED              NOT NULL DEFAULT '1',
  `id_currency`            INT(11) UNSIGNED              NOT NULL,
  `id_country`             INT(11) UNSIGNED              NOT NULL,
  `id_group`               INT(11) UNSIGNED              NOT NULL,
  `from_quantity`          MEDIUMINT(8) UNSIGNED         NOT NULL,
  `price`                  DECIMAL(20, 6),
  `reduction`              DECIMAL(20, 6)                NOT NULL,
  `reduction_tax`          TINYINT(1)                    NOT NULL DEFAULT 1,
  `reduction_type`         ENUM ('amount', 'percentage') NOT NULL,
  `from`                   DATETIME                      NOT NULL,
  `to`                     DATETIME                      NOT NULL,
  PRIMARY KEY (`id_specific_price_rule`),
  KEY `id_product` (`id_shop`, `id_currency`, `id_country`, `id_group`, `from_quantity`, `from`, `to`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_specific_price_rule_condition_group` (
  `id_specific_price_rule_condition_group` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_specific_price_rule`                 INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id_specific_price_rule_condition_group`, `id_specific_price_rule`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_specific_price_rule_condition` (
  `id_specific_price_rule_condition`       INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_specific_price_rule_condition_group` INT(11) UNSIGNED NOT NULL,
  `type`                                   VARCHAR(255)     NOT NULL,
  `value`                                  VARCHAR(255)     NOT NULL,
  PRIMARY KEY (`id_specific_price_rule_condition`),
  INDEX (`id_specific_price_rule_condition_group`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `PREFIX_risk` (
  `id_risk` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `percent` TINYINT(3)       NOT NULL,
  `color`   VARCHAR(32)      NULL,
  PRIMARY KEY (`id_risk`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `PREFIX_risk_lang` (
  `id_risk` INT(11) UNSIGNED NOT NULL,
  `id_lang` INT(11) UNSIGNED NOT NULL,
  `name`    VARCHAR(20)      NOT NULL,
  PRIMARY KEY (`id_risk`, `id_lang`),
  KEY `id_risk` (`id_risk`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_category_shop` (
  `id_category` INT(11)          NOT NULL,
  `id_shop`     INT(11)          NOT NULL,
  `position`    INT(11) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_category`, `id_shop`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_module_preference` (
  `id_module_preference` INT(11)     NOT NULL AUTO_INCREMENT,
  `id_employee`          INT(11)     NOT NULL,
  `module`               VARCHAR(64) NOT NULL,
  `interest`             TINYINT(1)           DEFAULT NULL,
  `favorite`             TINYINT(1)           DEFAULT NULL,
  PRIMARY KEY (`id_module_preference`),
  UNIQUE KEY `employee_module` (`id_employee`, `module`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_tab_module_preference` (
  `id_tab_module_preference` INT(11)     NOT NULL AUTO_INCREMENT,
  `id_employee`              INT(11)     NOT NULL,
  `id_tab`                   INT(11)     NOT NULL,
  `module`                   VARCHAR(64) NOT NULL,
  PRIMARY KEY (`id_tab_module_preference`),
  UNIQUE KEY `employee_module` (`id_employee`, `id_tab`, `module`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_carrier_tax_rules_group_shop` (
  `id_carrier`         INT(11) UNSIGNED NOT NULL,
  `id_tax_rules_group` INT(11) UNSIGNED NOT NULL,
  `id_shop`            INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id_carrier`, `id_tax_rules_group`, `id_shop`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_order_invoice_payment` (
  `id_order_invoice` INT(11) UNSIGNED NOT NULL,
  `id_order_payment` INT(11) UNSIGNED NOT NULL,
  `id_order`         INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id_order_invoice`, `id_order_payment`),
  KEY `order_payment` (`id_order_payment`),
  KEY `id_order` (`id_order`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_smarty_cache` (
  `id_smarty_cache` CHAR(40)  NOT NULL,
  `name`            CHAR(40)  NOT NULL,
  `cache_id`        VARCHAR(64)        DEFAULT NULL,
  `modified`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `content`         LONGTEXT  NOT NULL,
  PRIMARY KEY (`id_smarty_cache`),
  KEY `name` (`name`),
  KEY `cache_id` (`cache_id`),
  KEY `modified` (`modified`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `PREFIX_order_slip_detail_tax` (
  `id_order_slip_detail` INT(11) UNSIGNED NOT NULL,
  `id_tax`               INT(11) UNSIGNED NOT NULL,
  `unit_amount`          DECIMAL(16, 6)   NOT NULL DEFAULT '0.000000',
  `total_amount`         DECIMAL(16, 6)   NOT NULL DEFAULT '0.000000',
  KEY (`id_order_slip_detail`),
  KEY `id_tax` (`id_tax`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `PREFIX_mail` (
  `id_mail`   INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `recipient` VARCHAR(126)     NOT NULL,
  `template`  VARCHAR(62)      NOT NULL,
  `subject`   VARCHAR(254)     NOT NULL,
  `id_lang`   INT(11) UNSIGNED NOT NULL,
  `date_add`  TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_mail`),
  KEY `recipient` (`recipient`(10))
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_smarty_lazy_cache` (
  `template_hash` VARCHAR(32)  NOT NULL DEFAULT '',
  `cache_id`      VARCHAR(64)  NOT NULL DEFAULT '',
  `compile_id`    VARCHAR(32)  NOT NULL DEFAULT '',
  `filepath`      VARCHAR(255) NOT NULL DEFAULT '',
  `last_update`   DATETIME     NOT NULL DEFAULT '1970-01-01 00:00:00',
  PRIMARY KEY (`template_hash`, `cache_id`, `compile_id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

CREATE TABLE `PREFIX_smarty_last_flush` (
  `type`       ENUM ('compile', 'template'),
  `last_flush` DATETIME NOT NULL DEFAULT '1970-01-01 00:00:00',
  PRIMARY KEY (`type`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

CREATE TABLE `PREFIX_modules_perfs` (
  `id_modules_perfs` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `session`          INT(11) UNSIGNED NOT NULL,
  `module`           VARCHAR(64)      NOT NULL,
  `method`           VARCHAR(126)     NOT NULL,
  `time_start`       DOUBLE UNSIGNED  NOT NULL,
  `time_end`         DOUBLE UNSIGNED  NOT NULL,
  `memory_start`     INT UNSIGNED     NOT NULL,
  `memory_end`       INT UNSIGNED     NOT NULL,
  PRIMARY KEY (`id_modules_perfs`),
  KEY (`session`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

CREATE TABLE IF NOT EXISTS `PREFIX_cms_role` (
  `id_cms_role` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(50)      NOT NULL,
  `id_cms`      INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id_cms_role`, `id_cms`),
  UNIQUE KEY `name` (`name`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

CREATE TABLE IF NOT EXISTS `PREFIX_cms_role_lang` (
  `id_cms_role` INT(11) UNSIGNED NOT NULL,
  `id_lang`     INT(11) UNSIGNED NOT NULL,
  `id_shop`     INT(11) UNSIGNED NOT NULL,
  `name`        VARCHAR(128) DEFAULT NULL,
  PRIMARY KEY (`id_cms_role`, `id_lang`, id_shop)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

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

CREATE TABLE `PREFIX_currency_module`
(
  `id_currency` INT(11) UNSIGNED NOT NULL,
  `id_module`   INT(11) UNSIGNED,
  CONSTRAINT `uc_id_currency` UNIQUE (`id_currency`)
)
  ENGINE = ENGINE_TYPE
  DEFAULT CHARSET = utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE TABLE `PREFIX_page_cache` (
  `cache_hash`    CHAR(32)         NOT NULL,
  `id_currency`   INT(11) UNSIGNED,
  `id_language`   INT(11) UNSIGNED,
  `id_country`    INT(11) UNSIGNED,
  `id_shop`       INT(11) UNSIGNED,
  `entity_type`   VARCHAR(12)      NOT NULL,
  `id_entity`     INT(11) UNSIGNED,
  PRIMARY KEY (`cache_hash`),
  INDEX `cache_combo` (`cache_hash`, `id_currency`, `id_language`, `id_country`, `id_shop`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE utf8mb4_unicode_ci;
