SET NAMES 'utf8mb4';

ALTER TABLE `PREFIX_employee`
ALTER COLUMN `last_connection_date`
SET DEFAULT '1970-01-01';
UPDATE `PREFIX_employee`
SET `last_connection_date` = '1970-01-01'
WHERE CAST(`last_connection_date` AS CHAR(20)) = '0000-00-00 00:00:00';

ALTER TABLE `PREFIX_product`
ALTER COLUMN `available_date`
SET DEFAULT '1970-01-01';
UPDATE `PREFIX_product`
SET `available_date` = '1970-01-01'
WHERE CAST(`available_date` AS CHAR(20)) = '0000-00-00 00:00:00';

ALTER TABLE `PREFIX_product_shop`
ALTER COLUMN `available_date`
SET DEFAULT '1970-01-01';
UPDATE `PREFIX_product_shop`
SET `available_date` = '1970-01-01'
WHERE CAST(`available_date` AS CHAR(20)) = '0000-00-00 00:00:00';

ALTER TABLE `PREFIX_product_attribute`
ALTER COLUMN `available_date`
SET DEFAULT '1970-01-01';
UPDATE `PREFIX_product_attribute`
SET `available_date` = '1970-01-01'
WHERE CAST(`available_date` AS CHAR(20)) = '0000-00-00 00:00:00';

ALTER TABLE `PREFIX_product_attribute_shop`
ALTER COLUMN `available_date`
SET DEFAULT '1970-01-01';
UPDATE `PREFIX_product_attribute_shop`
SET `available_date` = '1970-01-01'
WHERE CAST(`available_date` AS CHAR(20)) = '0000-00-00 00:00:00';

DROP TABLE IF EXISTS `PREFIX_url_rewrite`;

INSERT IGNORE INTO `PREFIX_configuration` (`name`, `value`, `date_add`, `date_upd`)
VALUES ('PS_ROUTE_layered_rule', '{categories:/}{rewrite}{/:selected_filters}', 'NOW()', 'NOW()');

INSERT INTO `PREFIX_hook` (`name`, `title`) VALUES ('actionRegisterAutoloader', 'actionRegisterAutoloader');
INSERT INTO `PREFIX_hook` (`name`, `title`) VALUES ('actionRegisterErrorHandlers', 'actionRegisterErrorHandlers');
INSERT INTO `PREFIX_hook` (`name`, `title`) VALUES ('actionRetrieveCurrencyRates', 'actionRetrieveCurrencyRates');
