SET NAMES 'utf8mb4';

/* Remove per-shop custom code which accidently got stored earlier. */
DELETE FROM `PREFIX_configuration`
WHERE `name` LIKE "%CUSTOMCODE%" AND `id_shop` NOT LIKE 0;

/* Add email subject template */
INSERT INTO `PREFIX_configuration`(`name`, `value`, `date_add`, `date_upd`)
VALUES ('TB_MAIL_SUBJECT_TEMPLATE', '[{shop_name}] {subject}', NOW(), NOW());
