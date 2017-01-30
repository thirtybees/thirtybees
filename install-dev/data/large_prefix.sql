ALTER TABLE `PREFIX_url_rewrite` ROW_FORMAT=DYNAMIC;
CREATE INDEX lookup ON `PREFIX_url_rewrite` (`rewrite`, `id_lang`, `id_shop`);
CREATE INDEX reverse_lookup ON `PREFIX_url_rewrite` (`id_entity`, `entity`, `id_lang`, `id_shop`, `redirect`);
