SET NAMES 'utf8mb4';

/* Remove per-shop custom code which accidently got stored earlier. */
DELETE FROM `PREFIX_configuration`
WHERE `name` LIKE "%CUSTOMCODE%" AND `id_shop` NOT LIKE 0;
