SET NAMES 'utf8mb4';

INSERT IGNORE INTO `PREFIX_configuration` (`name`, `value`, `date_add`, `date_upd`)
VALUES ('PS_ROUTE_layered_rule', '{categories:/}{rewrite}{/:selected_filters}', 'NOW()', 'NOW()');
