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

/* Blog Data Population */
INSERT INTO `PREFIX_bees_blog_category` (`id_bees_blog_category`, `id_parent`, `position`, `active`, `date_add`, `date_upd`) VALUES
(1, 0, 1, 1, '2019-06-25 02:32:42', '2019-06-25 02:32:42');

INSERT INTO `PREFIX_bees_blog_category_lang` (`id_bees_blog_category`, `title`, `description`, `link_rewrite`, `meta_title`, `meta_description`, `meta_keywords`, `id_lang`) VALUES
(1, 'News', 'thirty bees news', 'news', 'thirty bees news', 'news about thirty bees', '', 1);

INSERT INTO `PREFIX_bees_blog_category_shop` (`id_bees_blog_category`, `id_shop`) VALUES
(1, 1);


INSERT INTO `PREFIX_bees_blog_post` (`id_bees_blog_post`, `active`, `comments_enabled`, `date_add`, `date_upd`, `published`, `id_category`, `id_employee`, `image`, `position`, `post_type`, `viewed`) VALUES
(1, 1, 1, '2019-06-25 02:34:00', '2019-06-25 02:34:00', '2019-06-25 02:34:00', 1, 1, '', 1, '0', 0),
(2, 1, 1, '2019-06-25 02:42:08', '2019-06-25 02:42:08', '2019-06-25 02:42:08', 1, 1, '', 1, '0', 0),
(3, 1, 1, '2019-06-25 02:42:56', '2019-06-25 02:42:56', '2019-06-25 02:42:56', 1, 1, '', 1, '0', 0);

INSERT INTO `PREFIX_bees_blog_post_lang` (`id_bees_blog_post`, `title`, `content`, `link_rewrite`, `meta_title`, `meta_description`, `meta_keywords`, `lang_active`, `id_lang`) VALUES
(1, 'Organic Roasted Coffee', '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum libero neque, convallis at sem sit amet, auctor gravida lacus. Vestibulum ipsum nisl, cursus sed faucibus ac, lobortis vel tortor. Phasellus nec justo eget est fermentum facilisis a at tellus. In sit amet dignissim arcu, ut congue enim. Pellentesque tincidunt porttitor leo eget molestie. Cras ullamcorper at mi sit amet blandit. Fusce eleifend lorem vel lacus fermentum pellentesque. Donec sit amet eros enim. Donec id quam ut nisi fermentum venenatis a a justo. Mauris magna erat, iaculis id eros sed, commodo aliquet magna. Sed nec placerat turpis. Orci varius natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Nulla efficitur suscipit lobortis. Aenean convallis dui convallis condimentum ullamcorper. Nulla tempus nisi sit amet tellus congue porta.</p>\r\n<p>Nunc condimentum lectus vitae ex malesuada posuere. Phasellus rutrum ante elit, eget consectetur justo mollis at. Donec eget dui eu mi dapibus posuere eget et ante. Vivamus ornare, metus eget iaculis porttitor, velit quam cursus quam, faucibus congue elit sapien quis sem. Maecenas vulputate pulvinar maximus. Etiam non aliquam dui, nec mattis nisi. Pellentesque mi magna, faucibus in pretium ac, laoreet eu libero. Proin non neque vitae justo lobortis cursus vel tincidunt augue. Pellentesque sed augue elit.</p>\r\n<p>Nullam laoreet id dolor vitae porttitor. Integer sagittis diam eget lectus ullamcorper lacinia. Aenean lobortis sapien mauris, vel rutrum est porta in. Nulla molestie mi quam, et efficitur libero rutrum quis. Curabitur sagittis purus eu dolor lacinia interdum. Quisque a lacinia dolor. Curabitur justo metus, imperdiet sed sem et, accumsan euismod leo. Nulla quis varius nunc. Phasellus a nunc sed justo interdum mattis sed id elit.</p>', 'organic-coffee', 'Organic Roasted Coffee', 'thirty bees organic roasted coffee', '', 1, 1),
(2, 'Hand Picked Teas', '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum libero neque, convallis at sem sit amet, auctor gravida lacus. Vestibulum ipsum nisl, cursus sed faucibus ac, lobortis vel tortor. Phasellus nec justo eget est fermentum facilisis a at tellus. In sit amet dignissim arcu, ut congue enim. Pellentesque tincidunt porttitor leo eget molestie. Cras ullamcorper at mi sit amet blandit. Fusce eleifend lorem vel lacus fermentum pellentesque. Donec sit amet eros enim. Donec id quam ut nisi fermentum venenatis a a justo. Mauris magna erat, iaculis id eros sed, commodo aliquet magna. Sed nec placerat turpis. Orci varius natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Nulla efficitur suscipit lobortis. Aenean convallis dui convallis condimentum ullamcorper. Nulla tempus nisi sit amet tellus congue porta.</p>\r\n<p>Nunc condimentum lectus vitae ex malesuada posuere. Phasellus rutrum ante elit, eget consectetur justo mollis at. Donec eget dui eu mi dapibus posuere eget et ante. Vivamus ornare, metus eget iaculis porttitor, velit quam cursus quam, faucibus congue elit sapien quis sem. Maecenas vulputate pulvinar maximus. Etiam non aliquam dui, nec mattis nisi. Pellentesque mi magna, faucibus in pretium ac, laoreet eu libero. Proin non neque vitae justo lobortis cursus vel tincidunt augue. Pellentesque sed augue elit.</p>\r\n<p>Nullam laoreet id dolor vitae porttitor. Integer sagittis diam eget lectus ullamcorper lacinia. Aenean lobortis sapien mauris, vel rutrum est porta in. Nulla molestie mi quam, et efficitur libero rutrum quis. Curabitur sagittis purus eu dolor lacinia interdum. Quisque a lacinia dolor. Curabitur justo metus, imperdiet sed sem et, accumsan euismod leo. Nulla quis varius nunc. Phasellus a nunc sed justo interdum mattis sed id elit.</p>', 'hand-picked-teas', 'Hand Picked Teas', 'Hand picked teas from thirty bees', '', 1, 1),
(3, 'Organic Gifts', '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum libero neque, convallis at sem sit amet, auctor gravida lacus. Vestibulum ipsum nisl, cursus sed faucibus ac, lobortis vel tortor. Phasellus nec justo eget est fermentum facilisis a at tellus. In sit amet dignissim arcu, ut congue enim. Pellentesque tincidunt porttitor leo eget molestie. Cras ullamcorper at mi sit amet blandit. Fusce eleifend lorem vel lacus fermentum pellentesque. Donec sit amet eros enim. Donec id quam ut nisi fermentum venenatis a a justo. Mauris magna erat, iaculis id eros sed, commodo aliquet magna. Sed nec placerat turpis. Orci varius natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Nulla efficitur suscipit lobortis. Aenean convallis dui convallis condimentum ullamcorper. Nulla tempus nisi sit amet tellus congue porta.</p>\r\n<p>Nunc condimentum lectus vitae ex malesuada posuere. Phasellus rutrum ante elit, eget consectetur justo mollis at. Donec eget dui eu mi dapibus posuere eget et ante. Vivamus ornare, metus eget iaculis porttitor, velit quam cursus quam, faucibus congue elit sapien quis sem. Maecenas vulputate pulvinar maximus. Etiam non aliquam dui, nec mattis nisi. Pellentesque mi magna, faucibus in pretium ac, laoreet eu libero. Proin non neque vitae justo lobortis cursus vel tincidunt augue. Pellentesque sed augue elit.</p>\r\n<p>Nullam laoreet id dolor vitae porttitor. Integer sagittis diam eget lectus ullamcorper lacinia. Aenean lobortis sapien mauris, vel rutrum est porta in. Nulla molestie mi quam, et efficitur libero rutrum quis. Curabitur sagittis purus eu dolor lacinia interdum. Quisque a lacinia dolor. Curabitur justo metus, imperdiet sed sem et, accumsan euismod leo. Nulla quis varius nunc. Phasellus a nunc sed justo interdum mattis sed id elit.</p>', 'organic-gifts', 'Organic Gifts', 'Organice gifts from thirty bees', '', 1, 1);

INSERT INTO `PREFIX_bees_blog_post_shop` (`id_bees_blog_post`, `id_shop`) VALUES
(1, 1),
(2, 1),
(3, 1);


/*Delete Theme Configurator Entry */
DELETE FROM `PREFIX_themeconfigurator` WHERE `id_item` = 1;

/*Store information HTML*/
INSERT INTO `PREFIX_tbhtmlblock` (`id_block`, `name`, `active`) VALUES
(1, 'Store Information', 1);

INSERT INTO `PREFIX_tbhtmlblock_hook` (`id_block`, `hook_name`, `position`) VALUES
(1, 'displayFooter', 0);

INSERT INTO `PREFIX_tbhtmlblock_lang` (`id_block`, `id_lang`, `content`) VALUES
(1,1, '<section id="blockcontactinfos" class="col-xs-12 col-sm-3"><h2 class="footer-title section-title-footer">Store Information</h2><address><ul class="list-unstyled"><li><b>Your Company</b></li><li>42 Bee Lane<br /> 12345 The Hive<br /> the Netherlands</li><li><i class="icon icon-phone"></i> <a href="tel:0123-456-789">0123-456-789</a></li><li><i class="icon icon-envelope-alt"></i> <a href="mailto:%73%61%6c%65%73@%79%6f%75%72%63%6f%6d%70%61%6e%79.%63%6f%6d">sales@yourcompany.com</a></li></ul></address></section>');
