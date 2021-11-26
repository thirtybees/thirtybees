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
UPDATE `PREFIX_configuration` SET value = '42 Bee Lane\n12345 The Hive\nthe Netherlands' WHERE name = 'BLOCKCONTACTINFOS_ADDRESS';

UPDATE `PREFIX_configuration` SET value = '4' WHERE name = 'PS_BLOCK_BESTSELLERS_TO_DISPLAY';
UPDATE `PREFIX_configuration` SET value = '4' WHERE name = 'HOME_FEATURED_NBR';

/* Home Slider Changes for Niara theme */
UPDATE `PREFIX_configuration` SET value = '1140' WHERE name = 'HOMESLIDER_WIDTH';
UPDATE `PREFIX_homeslider_slides_lang` SET `image` = 'sample-4.jpg' WHERE `id_homeslider_slides` = 1;
UPDATE `PREFIX_homeslider_slides_lang` SET `image` = 'sample-5.jpg' WHERE `id_homeslider_slides` = 2;
UPDATE `PREFIX_homeslider_slides_lang` SET `image` = 'sample-6.jpg' WHERE `id_homeslider_slides` = 3;
UPDATE `PREFIX_homeslider_slides_lang` SET `description` = '<h3 style="float:left;clear:both;font-size:30px;">Shop Tea</h3>
<p style="text-align:center;margin-top:20px;"><button class="btn btn-default" type="button">Shop now !</button></p>' WHERE `id_homeslider_slides` = 1;
UPDATE `PREFIX_homeslider_slides_lang` SET `description` = '<h3 style="float:left;clear:both;font-size:30px;">View All Soaps</h3>
<p style="text-align:center;margin-top:20px;"><button class="btn btn-default" type="button">Shop now !</button></p>' WHERE `id_homeslider_slides` = 2;
UPDATE `PREFIX_homeslider_slides_lang` SET `description` = '<h3 style="float:left;clear:both;font-size:30px;">Shop Gifts</h3>
<p style="text-align:center;margin-top:20px;"><button class="btn btn-default" type="button">Shop now !</button></p>' WHERE `id_homeslider_slides` = 3;