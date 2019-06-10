SET NAMES 'utf8';

/* Values overriding module defaults, only. */
UPDATE `PREFIX_configuration` SET value = '1' WHERE name = 'MANUFACTURER_DISPLAY_FORM';
UPDATE `PREFIX_configuration` SET value = '1' WHERE name = 'SUPPLIER_DISPLAY_FORM';
UPDATE `PREFIX_configuration` SET value = '0_3|0_4' WHERE name = 'FOOTER_CMS';
UPDATE `PREFIX_configuration` SET value = '0_3|0_4' WHERE name = 'FOOTER_BLOCK_ACTIVATION';
UPDATE `PREFIX_configuration` SET value = 'CAT3,CAT8,CAT5,LNK1' WHERE name = 'MOD_BLOCKTOPMENU_ITEMS';
UPDATE `PREFIX_configuration` SET value = '0' WHERE name = 'MOD_BLOCKTOPMENU_SEARCH';

UPDATE `PREFIX_configuration` SET value = '0123-456-789' WHERE name = 'BLOCKCONTACT_TELNUMBER';
UPDATE `PREFIX_configuration` SET value = 'sales@yourcompany.com' WHERE name = 'BLOCKCONTACT_EMAIL';
UPDATE `PREFIX_configuration` SET value = '0123-456-789' WHERE name = 'BLOCKCONTACTINFOS_PHONE';
UPDATE `PREFIX_configuration` SET value = 'sales@yourcompany.com' WHERE name = 'BLOCKCONTACTINFOS_EMAIL';
UPDATE `PREFIX_configuration` SET value = 'Your Company' WHERE name = 'BLOCKCONTACTINFOS_COMPANY';
UPDATE `PREFIX_configuration` SET value = '42 Bee Lane\n12345 The Hive\nThe Netherlands' WHERE name = 'BLOCKCONTACTINFOS_ADDRESS';
