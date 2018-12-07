SET NAMES 'utf8mb4';

/* Remove per-shop custom code which accidently got stored earlier. */;
DELETE FROM `PREFIX_configuration`
WHERE `name` LIKE "%CUSTOMCODE%" AND `id_shop` NOT LIKE 0;

/* Add email subject template */;
INSERT INTO `PREFIX_configuration`(`name`, `value`, `date_add`, `date_upd`)
SELECT 'TB_MAIL_SUBJECT_TEMPLATE', '[{shop_name}] {subject}', NOW(), NOW()
WHERE (
    SELECT COUNT(*)
    FROM `PREFIX_configuration`
    WHERE `name` = 'TB_MAIL_SUBJECT_TEMPLATE'
) = 0;

/* Delete tabs coming with PrestaShop but no longer used in thirty bees. */;
/* PHP:deleteTab('AdminMarketing'); */;

/* Add tabs new in thirty bees. */;
/* PHP:addTab('AdminDuplicateUrls', 'Duplicate URLs', 'AdminParentPreferences', 'AdminMeta'); */;
/* PHP:addTab('AdminCustomCode', 'Custom Code', 'AdminParentPreferences'); */;
/* PHP:addTab('AdminAddonsCatalog', 'Modules & Themes Catalog', 'AdminParentModules', 'AdminModules'); */;

/* Convert single-language URL routes to multi-language ones. */;
/* PHP:configSingleLangToMultiLang('PS_ROUTE_product_rule'); */;
/* PHP:configSingleLangToMultiLang('PS_ROUTE_category_rule'); */;
/* PHP:configSingleLangToMultiLang('PS_ROUTE_layered_rule'); */;
/* PHP:configSingleLangToMultiLang('PS_ROUTE_supplier_rule'); */;
/* PHP:configSingleLangToMultiLang('PS_ROUTE_manufacturer_rule'); */;
/* PHP:configSingleLangToMultiLang('PS_ROUTE_cms_rule'); */;
/* PHP:configSingleLangToMultiLang('PS_ROUTE_cms_category_rule'); */;
