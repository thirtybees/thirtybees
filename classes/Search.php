<?php
/**
 * 2007-2016 PrestaShop
 *
 * thirty bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017-2018 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://www.thirtybees.com for more information.
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017-2018 thirty bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

define('PS_SEARCH_MAX_WORD_LENGTH', 15);

/* Copied from Drupal search module, except for \x{0}-\x{2f} that has been replaced by \x{0}-\x{2c}\x{2e}-\x{2f} in order to keep the char '-' */
define(
    'PREG_CLASS_SEARCH_EXCLUDE',
    '\x{0}-\x{2c}\x{2e}-\x{2f}\x{3a}-\x{40}\x{5b}-\x{60}\x{7b}-\x{bf}\x{d7}\x{f7}\x{2b0}-'.
    '\x{385}\x{387}\x{3f6}\x{482}-\x{489}\x{559}-\x{55f}\x{589}-\x{5c7}\x{5f3}-'.
    '\x{61f}\x{640}\x{64b}-\x{65e}\x{66a}-\x{66d}\x{670}\x{6d4}\x{6d6}-\x{6ed}'.
    '\x{6fd}\x{6fe}\x{700}-\x{70f}\x{711}\x{730}-\x{74a}\x{7a6}-\x{7b0}\x{901}-'.
    '\x{903}\x{93c}\x{93e}-\x{94d}\x{951}-\x{954}\x{962}-\x{965}\x{970}\x{981}-'.
    '\x{983}\x{9bc}\x{9be}-\x{9cd}\x{9d7}\x{9e2}\x{9e3}\x{9f2}-\x{a03}\x{a3c}-'.
    '\x{a4d}\x{a70}\x{a71}\x{a81}-\x{a83}\x{abc}\x{abe}-\x{acd}\x{ae2}\x{ae3}'.
    '\x{af1}-\x{b03}\x{b3c}\x{b3e}-\x{b57}\x{b70}\x{b82}\x{bbe}-\x{bd7}\x{bf0}-'.
    '\x{c03}\x{c3e}-\x{c56}\x{c82}\x{c83}\x{cbc}\x{cbe}-\x{cd6}\x{d02}\x{d03}'.
    '\x{d3e}-\x{d57}\x{d82}\x{d83}\x{dca}-\x{df4}\x{e31}\x{e34}-\x{e3f}\x{e46}-'.
    '\x{e4f}\x{e5a}\x{e5b}\x{eb1}\x{eb4}-\x{ebc}\x{ec6}-\x{ecd}\x{f01}-\x{f1f}'.
    '\x{f2a}-\x{f3f}\x{f71}-\x{f87}\x{f90}-\x{fd1}\x{102c}-\x{1039}\x{104a}-'.
    '\x{104f}\x{1056}-\x{1059}\x{10fb}\x{10fc}\x{135f}-\x{137c}\x{1390}-\x{1399}'.
    '\x{166d}\x{166e}\x{1680}\x{169b}\x{169c}\x{16eb}-\x{16f0}\x{1712}-\x{1714}'.
    '\x{1732}-\x{1736}\x{1752}\x{1753}\x{1772}\x{1773}\x{17b4}-\x{17db}\x{17dd}'.
    '\x{17f0}-\x{180e}\x{1843}\x{18a9}\x{1920}-\x{1945}\x{19b0}-\x{19c0}\x{19c8}'.
    '\x{19c9}\x{19de}-\x{19ff}\x{1a17}-\x{1a1f}\x{1d2c}-\x{1d61}\x{1d78}\x{1d9b}-'.
    '\x{1dc3}\x{1fbd}\x{1fbf}-\x{1fc1}\x{1fcd}-\x{1fcf}\x{1fdd}-\x{1fdf}\x{1fed}-'.
    '\x{1fef}\x{1ffd}-\x{2070}\x{2074}-\x{207e}\x{2080}-\x{2101}\x{2103}-\x{2106}'.
    '\x{2108}\x{2109}\x{2114}\x{2116}-\x{2118}\x{211e}-\x{2123}\x{2125}\x{2127}'.
    '\x{2129}\x{212e}\x{2132}\x{213a}\x{213b}\x{2140}-\x{2144}\x{214a}-\x{2b13}'.
    '\x{2ce5}-\x{2cff}\x{2d6f}\x{2e00}-\x{3005}\x{3007}-\x{303b}\x{303d}-\x{303f}'.
    '\x{3099}-\x{309e}\x{30a0}\x{30fb}\x{30fd}\x{30fe}\x{3190}-\x{319f}\x{31c0}-'.
    '\x{31cf}\x{3200}-\x{33ff}\x{4dc0}-\x{4dff}\x{a015}\x{a490}-\x{a716}\x{a802}'.
    '\x{e000}-\x{f8ff}\x{fb29}\x{fd3e}-\x{fd3f}\x{fdfc}-\x{fdfd}'.
    '\x{fd3f}\x{fdfc}-\x{fe6b}\x{feff}-\x{ff0f}\x{ff1a}-\x{ff20}\x{ff3b}-\x{ff40}'.
    '\x{ff5b}-\x{ff65}\x{ff70}\x{ff9e}\x{ff9f}\x{ffe0}-\x{fffd}'
);

define(
    'PREG_CLASS_NUMBERS',
    '\x{30}-\x{39}\x{b2}\x{b3}\x{b9}\x{bc}-\x{be}\x{660}-\x{669}\x{6f0}-\x{6f9}'.
    '\x{966}-\x{96f}\x{9e6}-\x{9ef}\x{9f4}-\x{9f9}\x{a66}-\x{a6f}\x{ae6}-\x{aef}'.
    '\x{b66}-\x{b6f}\x{be7}-\x{bf2}\x{c66}-\x{c6f}\x{ce6}-\x{cef}\x{d66}-\x{d6f}'.
    '\x{e50}-\x{e59}\x{ed0}-\x{ed9}\x{f20}-\x{f33}\x{1040}-\x{1049}\x{1369}-'.
    '\x{137c}\x{16ee}-\x{16f0}\x{17e0}-\x{17e9}\x{17f0}-\x{17f9}\x{1810}-\x{1819}'.
    '\x{1946}-\x{194f}\x{2070}\x{2074}-\x{2079}\x{2080}-\x{2089}\x{2153}-\x{2183}'.
    '\x{2460}-\x{249b}\x{24ea}-\x{24ff}\x{2776}-\x{2793}\x{3007}\x{3021}-\x{3029}'.
    '\x{3038}-\x{303a}\x{3192}-\x{3195}\x{3220}-\x{3229}\x{3251}-\x{325f}\x{3280}-'.
    '\x{3289}\x{32b1}-\x{32bf}\x{ff10}-\x{ff19}'
);

define(
    'PREG_CLASS_PUNCTUATION',
    '\x{21}-\x{23}\x{25}-\x{2a}\x{2c}-\x{2f}\x{3a}\x{3b}\x{3f}\x{40}\x{5b}-\x{5d}'.
    '\x{5f}\x{7b}\x{7d}\x{a1}\x{ab}\x{b7}\x{bb}\x{bf}\x{37e}\x{387}\x{55a}-\x{55f}'.
    '\x{589}\x{58a}\x{5be}\x{5c0}\x{5c3}\x{5f3}\x{5f4}\x{60c}\x{60d}\x{61b}\x{61f}'.
    '\x{66a}-\x{66d}\x{6d4}\x{700}-\x{70d}\x{964}\x{965}\x{970}\x{df4}\x{e4f}'.
    '\x{e5a}\x{e5b}\x{f04}-\x{f12}\x{f3a}-\x{f3d}\x{f85}\x{104a}-\x{104f}\x{10fb}'.
    '\x{1361}-\x{1368}\x{166d}\x{166e}\x{169b}\x{169c}\x{16eb}-\x{16ed}\x{1735}'.
    '\x{1736}\x{17d4}-\x{17d6}\x{17d8}-\x{17da}\x{1800}-\x{180a}\x{1944}\x{1945}'.
    '\x{2010}-\x{2027}\x{2030}-\x{2043}\x{2045}-\x{2051}\x{2053}\x{2054}\x{2057}'.
    '\x{207d}\x{207e}\x{208d}\x{208e}\x{2329}\x{232a}\x{23b4}-\x{23b6}\x{2768}-'.
    '\x{2775}\x{27e6}-\x{27eb}\x{2983}-\x{2998}\x{29d8}-\x{29db}\x{29fc}\x{29fd}'.
    '\x{3001}-\x{3003}\x{3008}-\x{3011}\x{3014}-\x{301f}\x{3030}\x{303d}\x{30a0}'.
    '\x{30fb}\x{fd3e}\x{fd3f}\x{fe30}-\x{fe52}\x{fe54}-\x{fe61}\x{fe63}\x{fe68}'.
    '\x{fe6a}\x{fe6b}\x{ff01}-\x{ff03}\x{ff05}-\x{ff0a}\x{ff0c}-\x{ff0f}\x{ff1a}'.
    '\x{ff1b}\x{ff1f}\x{ff20}\x{ff3b}-\x{ff3d}\x{ff3f}\x{ff5b}\x{ff5d}\x{ff5f}-'.
    '\x{ff65}'
);

/**
 * Matches all CJK characters that are candidates for auto-splitting
 * (Chinese, Japanese, Korean).
 * Contains kana and BMP ideographs.
 */
define('PREG_CLASS_CJK', '\x{3041}-\x{30ff}\x{31f0}-\x{31ff}\x{3400}-\x{4db5}\x{4e00}-\x{9fbb}\x{f900}-\x{fad9}');

class SearchCore
{
    /**
     * @param              $idLang
     * @param              $expr
     * @param int          $pageNumber
     * @param int          $pageSize
     * @param string       $orderBy
     * @param string       $orderWay
     * @param bool         $ajax
     * @param bool         $use_cookie
     * @param Context|null $context
     *
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function find(
        $idLang,
        $expr,
        $pageNumber = 1,
        $pageSize = 1,
        $orderBy = 'position',
        $orderWay = 'desc',
        $ajax = false,
        $use_cookie = true,
        Context $context = null
    ) {
        if (!$context) {
            $context = Context::getContext();
        }
        $db = Db::getInstance(_PS_USE_SQL_SLAVE_);

        // TODO : smart page management
        if ($pageNumber < 1) {
            $pageNumber = 1;
        }
        if ($pageSize < 1) {
            $pageSize = 1;
        }

        if (!Validate::isOrderBy($orderBy) || !Validate::isOrderWay($orderWay)) {
            return false;
        }

        $intersectArray = [];
        $scoreArray = [];
        $words = explode(' ', Search::sanitize($expr, $idLang, false, $context->language->iso_code));

        foreach ($words as $key => $word) {
            if (!empty($word) && strlen($word) >= (int) Configuration::get('PS_SEARCH_MINWORDLEN')) {
                $word = str_replace(['%', '_'], ['\\%', '\\_'], $word);
                $startSearch = Configuration::get('PS_SEARCH_START') ? '%' : '';
                $endSearch = Configuration::get('PS_SEARCH_END') ? '' : '%';

                $intersectArray[] = 'SELECT DISTINCT si.id_product
					FROM '._DB_PREFIX_.'search_word sw
					LEFT JOIN '._DB_PREFIX_.'search_index si ON sw.id_word = si.id_word
					WHERE sw.id_lang = '.(int) $idLang.'
						AND sw.id_shop = '.$context->shop->id.'
						AND sw.word LIKE
					'.($word[0] == '-'
                        ? ' \''.$startSearch.pSQL(mb_substr($word, 1, PS_SEARCH_MAX_WORD_LENGTH)).$endSearch.'\''
                        : ' \''.$startSearch.pSQL(mb_substr($word, 0, PS_SEARCH_MAX_WORD_LENGTH)).$endSearch.'\''
                    );

                if ($word[0] != '-') {
                    $scoreArray[] = 'sw.word LIKE \''.$startSearch.pSQL(mb_substr($word, 0, PS_SEARCH_MAX_WORD_LENGTH)).$endSearch.'\'';
                }
            } else {
                unset($words[$key]);
            }
        }

        if (!count($words)) {
            return ($ajax ? [] : ['total' => 0, 'result' => []]);
        }

        $score = '';
        if (is_array($scoreArray) && !empty($scoreArray)) {
            $score = ',(
				SELECT SUM(weight)
				FROM '._DB_PREFIX_.'search_word sw
				LEFT JOIN '._DB_PREFIX_.'search_index si ON sw.id_word = si.id_word
				WHERE sw.id_lang = '.(int) $idLang.'
					AND sw.id_shop = '.$context->shop->id.'
					AND si.id_product = p.id_product
					AND ('.implode(' OR ', $scoreArray).')
			) position';
        }

        $searchSql = (new DbQuery())
            ->select("DISTINCT `cp`.`id_product`")
            ->from('category_product', 'cp')
            ->innerJoin('category', 'c', '`cp`.`id_category` = `c`.`id_category`')
            ->innerJoinMultishop('product', 'p', 'ps', '`cp`.`id_product` = `p`.`id_product`')
            ->where('`c`.`active` = 1')
            ->where('`ps`.`active` = 1')
            ->where('`ps`.`visibility` IN ("both", "search")')
            ->where('`ps`.`indexed` = 1');

        if (Group::isFeatureActive()) {
            $groups = FrontController::getCurrentCustomerGroups();
            $on = '`cp`.`id_category` = `cg`.`id_category` AND `cg`.`id_group` '.($groups ? 'IN ('.implode(',', $groups).')' : '= 1');
            $searchSql->innerJoin('category_group', 'cg', $on);
        }

        if ((int) Configuration::get('PS_SEARCH_INSTOCK')) {
            $on = '`cp`.`id_product` = `sa`.`id_product` '.StockAvailable::addSqlShopRestriction(null, null, 'sa');
            if ((int) Configuration::get('PS_ORDER_OUT_OF_STOCK')) {
                $on .= ' AND (sa.quantity > 0 OR sa.out_of_stock = 1 OR sa.out_of_stock = 2)';
            } else {
                $on .= ' AND (sa.quantity > 0 OR sa.out_of_stock = 1)';
            }
            $searchSql->innerJoin('stock_available', 'sa', $on);
        }

        $results = $db->executeS($searchSql, true, false);

        $eligibleProducts = [];
        foreach ($results as $row) {
            $eligibleProducts[] = $row['id_product'];
        }

        $eligibleProducts2 = [];
        foreach ($intersectArray as $query) {
            foreach ($db->executeS($query, true, false) as $row) {
                $eligibleProducts2[] = $row['id_product'];
            }
        }
        $eligibleProducts = array_unique(array_intersect($eligibleProducts, array_unique($eligibleProducts2)));
        if (!count($eligibleProducts)) {
            return ($ajax ? [] : ['total' => 0, 'result' => []]);
        }

        $productPool = '';
        foreach ($eligibleProducts as $idProduct) {
            if ($idProduct) {
                $productPool .= (int) $idProduct.',';
            }
        }
        if (empty($productPool)) {
            return ($ajax ? [] : ['total' => 0, 'result' => []]);
        }
        $productPool = ((strpos($productPool, ',') === false) ? (' = '.(int) $productPool.' ') : (' IN ('.rtrim($productPool, ',').') '));

        if ($ajax) {
            $sql = 'SELECT DISTINCT p.id_product, pl.name pname, cl.name cname,
						cl.link_rewrite crewrite, pl.link_rewrite prewrite '.$score.'
					FROM '._DB_PREFIX_.'product p
					INNER JOIN `'._DB_PREFIX_.'product_lang` pl ON (
						p.`id_product` = pl.`id_product`
						AND pl.`id_lang` = '.(int) $idLang.Shop::addSqlRestrictionOnLang('pl').'
					)
					'.Shop::addSqlAssociation('product', 'p').'
					INNER JOIN `'._DB_PREFIX_.'category_lang` cl ON (
						product_shop.`id_category_default` = cl.`id_category`
						AND cl.`id_lang` = '.(int) $idLang.Shop::addSqlRestrictionOnLang('cl').'
					)
					WHERE p.`id_product` '.$productPool.'
					ORDER BY position DESC LIMIT 10';

            return $db->executeS($sql, true, false);
        }

        if (strpos($orderBy, '.') > 0) {
            $orderBy = explode('.', $orderBy);
            $orderBy = pSQL($orderBy[0]).'.`'.pSQL($orderBy[1]).'`';
        }
        $alias = '';
        if ($orderBy == 'price') {
            $alias = 'product_shop.';
        } elseif (in_array($orderBy, ['date_upd', 'date_add'])) {
            $alias = 'p.';
        }
        $sql = 'SELECT p.*, product_shop.*, stock.out_of_stock, IFNULL(stock.quantity, 0) as quantity,
				pl.`description_short`, pl.`available_now`, pl.`available_later`, pl.`link_rewrite`, pl.`name`,
			 image_shop.`id_image` id_image, il.`legend`, m.`name` manufacturer_name '.$score.',
				DATEDIFF(
					p.`date_add`,
					DATE_SUB(
						"'.date('Y-m-d').' 00:00:00",
						INTERVAL '.(Validate::isUnsignedInt(Configuration::get('PS_NB_DAYS_NEW_PRODUCT')) ? Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20).' DAY
					)
				) > 0 new'.(Combination::isFeatureActive() ? ', product_attribute_shop.minimal_quantity AS product_attribute_minimal_quantity, IFNULL(product_attribute_shop.`id_product_attribute`,0) id_product_attribute' : '').'
				FROM '._DB_PREFIX_.'product p
				'.Shop::addSqlAssociation('product', 'p').'
				INNER JOIN `'._DB_PREFIX_.'product_lang` pl ON (
					p.`id_product` = pl.`id_product`
					AND pl.`id_lang` = '.(int) $idLang.Shop::addSqlRestrictionOnLang('pl').'
				)
				'.(Combination::isFeatureActive() ? 'LEFT JOIN `'._DB_PREFIX_.'product_attribute_shop` product_attribute_shop
				ON (p.`id_product` = product_attribute_shop.`id_product` AND product_attribute_shop.`default_on` = 1 AND product_attribute_shop.id_shop='.(int) $context->shop->id.')' : '').'
				'.Product::sqlStock('p', 0).'
				LEFT JOIN `'._DB_PREFIX_.'manufacturer` m ON m.`id_manufacturer` = p.`id_manufacturer`
				LEFT JOIN `'._DB_PREFIX_.'image_shop` image_shop
					ON (image_shop.`id_product` = p.`id_product` AND image_shop.cover=1 AND image_shop.id_shop='.(int) $context->shop->id.')
				LEFT JOIN `'._DB_PREFIX_.'image_lang` il ON (image_shop.`id_image` = il.`id_image` AND il.`id_lang` = '.(int) $idLang.')
				WHERE p.`id_product` '.$productPool.'
				GROUP BY product_shop.id_product
				'.($orderBy ? 'ORDER BY  '.$alias.$orderBy : '').($orderWay ? ' '.$orderWay : '').'
				LIMIT '.(int) (($pageNumber - 1) * $pageSize).','.(int) $pageSize;
        $result = $db->executeS($sql, true, false);

        $sql = 'SELECT COUNT(*)
				FROM '._DB_PREFIX_.'product p
				'.Shop::addSqlAssociation('product', 'p').'
				INNER JOIN `'._DB_PREFIX_.'product_lang` pl ON (
					p.`id_product` = pl.`id_product`
					AND pl.`id_lang` = '.(int) $idLang.Shop::addSqlRestrictionOnLang('pl').'
				)
				LEFT JOIN `'._DB_PREFIX_.'manufacturer` m ON m.`id_manufacturer` = p.`id_manufacturer`
				WHERE p.`id_product` '.$productPool;
        $total = $db->getValue($sql, false);

        if (!$result) {
            $resultProperties = false;
        } else {
            $resultProperties = Product::getProductsProperties((int) $idLang, $result);
        }

        return ['total' => $total, 'result' => $resultProperties];
    }

    /**
     * @param string $string
     * @param int    $idLang
     * @param bool   $indexation
     * @param bool   $isoCode
     *
     * @return bool|mixed|string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function sanitize($string, $idLang, $indexation = false, $isoCode = false)
    {
        $string = trim($string);
        if (empty($string)) {
            return '';
        }

        $string = mb_strtolower(strip_tags($string));
        $string = html_entity_decode($string, ENT_NOQUOTES, 'utf-8');

        $string = preg_replace('/(['.PREG_CLASS_NUMBERS.']+)['.PREG_CLASS_PUNCTUATION.']+(?=['.PREG_CLASS_NUMBERS.'])/u', '\1', $string);
        $string = preg_replace('/['.PREG_CLASS_SEARCH_EXCLUDE.']+/u', ' ', $string);

        if ($indexation) {
            $string = preg_replace('/[._-]+/', ' ', $string);
        } else {
            $words = explode(' ', $string);
            $processedWords = [];
            // search for aliases for each word of the query
            foreach ($words as $word) {
                $alias = new Alias(null, $word);
                if (Validate::isLoadedObject($alias)) {
                    $processedWords[] = $alias->search;
                } else {
                    $processedWords[] = $word;
                }
            }
            $string = implode(' ', $processedWords);
            $string = preg_replace('/[._]+/', '', $string);
            $string = ltrim(preg_replace('/([^ ])-/', '$1 ', ' '.$string));
            $string = preg_replace('/[._]+/', '', $string);
            $string = preg_replace('/[^\s]-+/', '', $string);
            $string = preg_replace('/[._-]+/', ' ', $string);
        }

        $blacklist = mb_strtolower(Configuration::get('PS_SEARCH_BLACKLIST', $idLang));
        if (!empty($blacklist)) {
            $string = preg_replace('/(?<=\s)('.$blacklist.')(?=\s)/Su', '', $string);
            $string = preg_replace('/^('.$blacklist.')(?=\s)/Su', '', $string);
            $string = preg_replace('/(?<=\s)('.$blacklist.')$/Su', '', $string);
            $string = preg_replace('/^('.$blacklist.')$/Su', '', $string);
        }

        // If the language is constituted with symbol and there is no "words", then split every chars
        if (in_array($isoCode, ['zh', 'tw', 'ja']) && function_exists('mb_strlen')) {
            // Cut symbols from letters
            $symbols = '';
            $letters = '';
            foreach (explode(' ', $string) as $mbWord) {
                if (strlen(Tools::replaceAccentedChars($mbWord)) == mb_strlen(Tools::replaceAccentedChars($mbWord))) {
                    $letters .= $mbWord.' ';
                } else {
                    $symbols .= $mbWord.' ';
                }
            }

            if (preg_match_all('/./u', $symbols, $matches)) {
                $symbols = implode(' ', $matches[0]);
            }

            $string = $letters.$symbols;
        } elseif ($indexation) {
            $minWordLen = (int) Configuration::get('PS_SEARCH_MINWORDLEN');
            if ($minWordLen > 1) {
                $minWordLen -= 1;
                $string = preg_replace('/(?<=\s)[^\s]{1,'.$minWordLen.'}(?=\s)/Su', ' ', $string);
                $string = preg_replace('/^[^\s]{1,'.$minWordLen.'}(?=\s)/Su', '', $string);
                $string = preg_replace('/(?<=\s)[^\s]{1,'.$minWordLen.'}$/Su', '', $string);
                $string = preg_replace('/^[^\s]{1,'.$minWordLen.'}$/Su', '', $string);
            }
        }

        $string = Tools::replaceAccentedChars(trim(preg_replace('/\s+/', ' ', $string)));

        return $string;
    }

    /**
     * @param bool     $full
     * @param int|bool $idProduct
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function indexation($full = false, $idProduct = false)
    {
        $db = Db::getInstance();

        if ($idProduct) {
            $full = false;
        }

        if ($full && Context::getContext()->shop->getContext() == Shop::CONTEXT_SHOP) {
            $db->execute(
                'DELETE si, sw FROM `'._DB_PREFIX_.'search_index` si
				INNER JOIN `'._DB_PREFIX_.'product` p ON (p.id_product = si.id_product)
				'.Shop::addSqlAssociation('product', 'p').'
				INNER JOIN `'._DB_PREFIX_.'search_word` sw ON (sw.id_word = si.id_word AND product_shop.id_shop = sw.id_shop)
				WHERE product_shop.`visibility` IN ("both", "search")
				AND product_shop.`active` = 1'
            );
            $db->execute(
                'UPDATE `'._DB_PREFIX_.'product` p
				'.Shop::addSqlAssociation('product', 'p').'
				SET p.`indexed` = 0, product_shop.`indexed` = 0
				WHERE product_shop.`visibility` IN ("both", "search")
				AND product_shop.`active` = 1
				'
            );
        } elseif ($full) {
            $db->execute('TRUNCATE '._DB_PREFIX_.'search_index');
            $db->execute('TRUNCATE '._DB_PREFIX_.'search_word');
            ObjectModel::updateMultishopTable('Product', ['indexed' => 0]);
        } else {
            $db->execute(
                'DELETE si FROM `'._DB_PREFIX_.'search_index` si
				INNER JOIN `'._DB_PREFIX_.'product` p ON (p.id_product = si.id_product)
				'.Shop::addSqlAssociation('product', 'p', true, null, true).'
				WHERE product_shop.`visibility` IN ("both", "search")
				AND product_shop.`active` = 1
				AND '.($idProduct ? 'p.`id_product` = '.(int) $idProduct : 'product_shop.`indexed` = 0')
            );

            $db->execute(
                'UPDATE `'._DB_PREFIX_.'product` p
				'.Shop::addSqlAssociation('product', 'p', true, null, true).'
				SET p.`indexed` = 0, product_shop.`indexed` = 0
				WHERE product_shop.`visibility` IN ("both", "search")
				AND product_shop.`active` = 1
				AND '.($idProduct ? 'p.`id_product` = '.(int) $idProduct : 'product_shop.`indexed` = 0')
            );
        }

        // Every fields are weighted according to the configuration in the backend
        $weightArray = [
            'pname'                 => Configuration::get('PS_SEARCH_WEIGHT_PNAME'),
            'reference'             => Configuration::get('PS_SEARCH_WEIGHT_REF'),
            'pa_reference'          => Configuration::get('PS_SEARCH_WEIGHT_REF'),
            'supplier_reference'    => Configuration::get('PS_SEARCH_WEIGHT_REF'),
            'pa_supplier_reference' => Configuration::get('PS_SEARCH_WEIGHT_REF'),
            'ean13'                 => Configuration::get('PS_SEARCH_WEIGHT_REF'),
            'pa_ean13'              => Configuration::get('PS_SEARCH_WEIGHT_REF'),
            'upc'                   => Configuration::get('PS_SEARCH_WEIGHT_REF'),
            'pa_upc'                => Configuration::get('PS_SEARCH_WEIGHT_REF'),
            'description_short'     => Configuration::get('PS_SEARCH_WEIGHT_SHORTDESC'),
            'description'           => Configuration::get('PS_SEARCH_WEIGHT_DESC'),
            'cname'                 => Configuration::get('PS_SEARCH_WEIGHT_CNAME'),
            'mname'                 => Configuration::get('PS_SEARCH_WEIGHT_MNAME'),
            'tags'                  => Configuration::get('PS_SEARCH_WEIGHT_TAG'),
            'attributes'            => Configuration::get('PS_SEARCH_WEIGHT_ATTRIBUTE'),
            'features'              => Configuration::get('PS_SEARCH_WEIGHT_FEATURE'),
        ];

        // Those are kind of global variables required to save the processed data in the database every X occurrences, in order to avoid overloading MySQL
        $countWords = 0;
        $queryArray3 = [];

        // Retrieve the number of languages
        $totalLanguages = count(Language::getIDs(false));

        $sqlAttribute = Search::getSQLProductAttributeFields($weightArray);
        // Products are processed 50 by 50 in order to avoid overloading MySQL
        while (($products = Search::getProductsToIndex($totalLanguages, $idProduct, 50, $weightArray)) && (count($products) > 0)) {
            $productsArray = [];
            // Now each non-indexed product is processed one by one, langage by langage
            foreach ($products as $product) {
                if ((int) $weightArray['tags']) {
                    $product['tags'] = Search::getTags($db, (int) $product['id_product'], (int) $product['id_lang']);
                }
                if ((int) $weightArray['attributes']) {
                    $product['attributes'] = Search::getAttributes($db, (int) $product['id_product'], (int) $product['id_lang']);
                }
                if ((int) $weightArray['features']) {
                    $product['features'] = Search::getFeatures($db, (int) $product['id_product'], (int) $product['id_lang']);
                }
                if ($sqlAttribute) {
                    $attributeFields = Search::getAttributesFields($db, (int) $product['id_product'], $sqlAttribute);
                    if ($attributeFields) {
                        $product['attributes_fields'] = $attributeFields;
                    }
                }

                // Data must be cleaned of html, bad characters, spaces and anything, then if the resulting words are long enough, they're added to the array
                $productArray = [];
                foreach ($product as $key => $value) {
                    if ($key == 'attributes_fields') {
                        foreach ($value as $paArray) {
                            foreach ($paArray as $paKey => $paValue) {
                                Search::fillProductArray($productArray, $weightArray, $paKey, $paValue, $product['id_lang'], $product['iso_code']);
                            }
                        }
                    } else {
                        Search::fillProductArray($productArray, $weightArray, $key, $value, $product['id_lang'], $product['iso_code']);
                    }
                }

                // If we find words that need to be indexed, they're added to the word table in the database
                if (is_array($productArray) && !empty($productArray)) {
                    $queryArray = $queryArray2 = [];
                    foreach ($productArray as $word => $weight) {
                        if ($weight) {
                            $queryArray[$word] = '('.(int) $product['id_lang'].', '.(int) $product['id_shop'].', \''.pSQL($word).'\')';
                            $queryArray2[] = '\''.pSQL($word).'\'';
                        }
                    }

                    if (is_array($queryArray) && !empty($queryArray)) {
                        // The words are inserted...
                        $db->execute(
                            '
						INSERT IGNORE INTO '._DB_PREFIX_.'search_word (id_lang, id_shop, word)
						VALUES '.implode(',', $queryArray), false
                        );
                    }
                    $wordIdsByWord = [];
                    if (is_array($queryArray2) && !empty($queryArray2)) {
                        // ...then their IDs are retrieved
                        $addedWords = $db->executeS(
                            '
						SELECT sw.id_word, sw.word
						FROM '._DB_PREFIX_.'search_word sw
						WHERE sw.word IN ('.implode(',', $queryArray2).')
						AND sw.id_lang = '.(int) $product['id_lang'].'
						AND sw.id_shop = '.(int) $product['id_shop'], true, false
                        );
                        foreach ($addedWords as $wordId) {
                            $wordIdsByWord['_'.$wordId['word']] = (int) $wordId['id_word'];
                        }
                    }
                }

                foreach ($productArray as $word => $weight) {
                    if (!$weight) {
                        continue;
                    }
                    if (!isset($wordIdsByWord['_'.$word])) {
                        continue;
                    }
                    $idWord = $wordIdsByWord['_'.$word];
                    if (!$idWord) {
                        continue;
                    }
                    $queryArray3[] = '('.(int) $product['id_product'].','.
                        (int) $idWord.','.(int) $weight.')';
                    // Force save every 200 words in order to avoid overloading MySQL
                    if (++$countWords % 200 == 0) {
                        Search::saveIndex($queryArray3);
                    }
                }

                $productsArray[] = (int) $product['id_product'];
            }
            $productsArray = array_unique($productsArray);
            Search::setProductsAsIndexed($productsArray);

            // One last save is done at the end in order to save what's left
            Search::saveIndex($queryArray3);
        }

        return true;
    }

    /**
     * @param $weightArray
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected static function getSQLProductAttributeFields(&$weightArray)
    {
        $sql = '';
        if (is_array($weightArray)) {
            foreach ($weightArray as $key => $weight) {
                if ((int) $weight) {
                    switch ($key) {
                        case 'pa_reference':
                            $sql .= ', pa.reference AS pa_reference';
                            break;
                        case 'pa_supplier_reference':
                            $sql .= ', pa.supplier_reference AS pa_supplier_reference';
                            break;
                        case 'pa_ean13':
                            $sql .= ', pa.ean13 AS pa_ean13';
                            break;
                        case 'pa_upc':
                            $sql .= ', pa.upc AS pa_upc';
                            break;
                    }
                }
            }
        }

        return $sql;
    }

    /**
     * @param       $totalLanguages
     * @param bool  $idProduct
     * @param int   $limit
     * @param array $weightArray
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected static function getProductsToIndex($totalLanguages, $idProduct = false, $limit = 50, $weightArray = [])
    {
        $ids = null;
        if (!$idProduct) {
            // Limit products for each step but be sure that each attribute is taken into account
            $sql = 'SELECT p.id_product FROM '._DB_PREFIX_.'product p
				'.Shop::addSqlAssociation('product', 'p', true, null, true).'
				WHERE product_shop.`indexed` = 0
				AND product_shop.`visibility` IN ("both", "search")
				AND product_shop.`active` = 1
				ORDER BY product_shop.`id_product` ASC
				LIMIT '.(int) $limit;

            $res = Db::getInstance()->executeS($sql, false);
            while ($row = Db::getInstance()->nextRow($res)) {
                $ids[] = $row['id_product'];
            }
        }

        // Now get every attribute in every language
        $sql = 'SELECT p.id_product, pl.id_lang, pl.id_shop, l.iso_code';

        if (is_array($weightArray)) {
            foreach ($weightArray as $key => $weight) {
                if ((int) $weight) {
                    switch ($key) {
                        case 'pname':
                            $sql .= ', pl.name pname';
                            break;
                        case 'reference':
                            $sql .= ', p.reference';
                            break;
                        case 'supplier_reference':
                            $sql .= ', p.supplier_reference';
                            break;
                        case 'ean13':
                            $sql .= ', p.ean13';
                            break;
                        case 'upc':
                            $sql .= ', p.upc';
                            break;
                        case 'description_short':
                            $sql .= ', pl.description_short';
                            break;
                        case 'description':
                            $sql .= ', pl.description';
                            break;
                        case 'cname':
                            $sql .= ', cl.name cname';
                            break;
                        case 'mname':
                            $sql .= ', m.name mname';
                            break;
                    }
                }
            }
        }

        $sql .= ' FROM '._DB_PREFIX_.'product p
			LEFT JOIN '._DB_PREFIX_.'product_lang pl
				ON p.id_product = pl.id_product
			'.Shop::addSqlAssociation('product', 'p', true, null, true).'
			LEFT JOIN '._DB_PREFIX_.'category_lang cl
				ON (cl.id_category = product_shop.id_category_default AND pl.id_lang = cl.id_lang AND cl.id_shop = product_shop.id_shop)
			LEFT JOIN '._DB_PREFIX_.'manufacturer m
				ON m.id_manufacturer = p.id_manufacturer
			LEFT JOIN '._DB_PREFIX_.'lang l
				ON l.id_lang = pl.id_lang
			WHERE product_shop.indexed = 0
			AND product_shop.visibility IN ("both", "search")
			'.($idProduct ? 'AND p.id_product = '.(int) $idProduct : '').'
			'.($ids ? 'AND p.id_product IN ('.implode(',', array_map('intval', $ids)).')' : '').'
			AND product_shop.`active` = 1
			AND pl.`id_shop` = product_shop.`id_shop`';

        return Db::getInstance()->executeS($sql, true, false);
    }

    /**
     * @param Db  $db
     * @param int $idProduct
     * @param int $idLang
     *
     * @return string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getTags($db, $idProduct, $idLang)
    {
        $tags = '';
        $tagsArray = $db->executeS(
            '
		SELECT t.name FROM '._DB_PREFIX_.'product_tag pt
		LEFT JOIN '._DB_PREFIX_.'tag t ON (pt.id_tag = t.id_tag AND t.id_lang = '.(int) $idLang.')
		WHERE pt.id_product = '.(int) $idProduct, true, false
        );
        foreach ($tagsArray as $tag) {
            $tags .= $tag['name'].' ';
        }

        return $tags;
    }

    /**
     * @param Db  $db
     * @param int $idProduct
     * @param int $idLang
     *
     * @return string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getAttributes($db, $idProduct, $idLang)
    {
        if (!Combination::isFeatureActive()) {
            return '';
        }

        $attributes = '';
        $attributesArray = $db->executeS(
            '
		SELECT al.name FROM '._DB_PREFIX_.'product_attribute pa
		INNER JOIN '._DB_PREFIX_.'product_attribute_combination pac ON pa.id_product_attribute = pac.id_product_attribute
		INNER JOIN '._DB_PREFIX_.'attribute_lang al ON (pac.id_attribute = al.id_attribute AND al.id_lang = '.(int) $idLang.')
		'.Shop::addSqlAssociation('product_attribute', 'pa').'
		WHERE pa.id_product = '.(int) $idProduct, true, false
        );
        foreach ($attributesArray as $attribute) {
            $attributes .= $attribute['name'].' ';
        }

        return $attributes;
    }

    /**
     * @param Db  $db
     * @param int $idProduct
     * @param int $idLang
     *
     * @return string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getFeatures($db, $idProduct, $idLang)
    {
        if (!Feature::isFeatureActive()) {
            return '';
        }

        $features = '';
        $featuresArray = $db->executeS(
            '
		SELECT fvl.value FROM '._DB_PREFIX_.'feature_product fp
		LEFT JOIN '._DB_PREFIX_.'feature_value_lang fvl ON (fp.id_feature_value = fvl.id_feature_value AND fvl.id_lang = '.(int) $idLang.')
		WHERE fp.id_product = '.(int) $idProduct, true, false
        );
        foreach ($featuresArray as $feature) {
            $features .= $feature['value'].' ';
        }

        return $features;
    }

    /**
     * @param Db     $db
     * @param int    $idProduct
     * @param string $sqlAttribute
     *
     * @return array|null
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected static function getAttributesFields($db, $idProduct, $sqlAttribute)
    {
        return $db->executeS(
            'SELECT id_product '.$sqlAttribute.' FROM '.
            _DB_PREFIX_.'product_attribute pa WHERE pa.id_product = '.(int) $idProduct, true, false
        );
    }

    /**
     * @param $productArray
     * @param $weightArray
     * @param $key
     * @param $value
     * @param $idLang
     * @param $isoCode
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    protected static function fillProductArray(&$productArray, $weightArray, $key, $value, $idLang, $isoCode)
    {
        if (strncmp($key, 'id_', 3) && isset($weightArray[$key])) {
            $words = explode(' ', Search::sanitize($value, (int) $idLang, true, $isoCode));
            foreach ($words as $word) {
                if (!empty($word)) {
                    $word = mb_substr($word, 0, PS_SEARCH_MAX_WORD_LENGTH);

                    if (!isset($productArray[$word])) {
                        $productArray[$word] = 0;
                    }
                    $productArray[$word] += $weightArray[$key];
                }
            }
        }
    }

    /**
     * @param array $queryArray3
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    protected static function saveIndex(&$queryArray3)
    {
        if (is_array($queryArray3) && !empty($queryArray3)) {
            Db::getInstance()->execute(
                'INSERT INTO '._DB_PREFIX_.'search_index (id_product, id_word, weight)
				VALUES '.implode(',', $queryArray3).'
				ON DUPLICATE KEY UPDATE weight = weight + VALUES(weight)', false
            );
        }
        $queryArray3 = [];
    }

    /**
     * @param $products
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    protected static function setProductsAsIndexed(&$products)
    {
        if (is_array($products) && !empty($products)) {
            ObjectModel::updateMultishopTable('Product', ['indexed' => 1], 'a.id_product IN ('.implode(',', $products).')');
        }
    }

    /**
     * @param $products
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public static function removeProductsSearchIndex($products)
    {
        if (is_array($products) && !empty($products)) {
            Db::getInstance()->execute('DELETE FROM '._DB_PREFIX_.'search_index WHERE id_product IN ('.implode(',', array_map('intval', $products)).')');
            ObjectModel::updateMultishopTable('Product', ['indexed' => 0], 'a.id_product IN ('.implode(',', array_map('intval', $products)).')');
        }
    }

    /**
     * @param int          $idLang
     * @param              $tag
     * @param bool         $count
     * @param int          $pageNumber
     * @param int          $pageSize
     * @param bool         $orderBy
     * @param bool         $orderWay
     * @param bool         $useCookie
     * @param Context|null $context
     *
     * @return array|bool|int
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function searchTag(
        $idLang,
        $tag,
        $count = false,
        $pageNumber = 0,
        $pageSize = 10,
        $orderBy = false,
        $orderWay = false,
        $useCookie = true,
        Context $context = null
    ) {
        if (!$context) {
            $context = Context::getContext();
        }

        // Only use cookie if id_customer is not present
        if ($useCookie) {
            $idCustomer = (int) $context->customer->id;
        } else {
            $idCustomer = 0;
        }

        if (!is_numeric($pageNumber) || !is_numeric($pageSize) || !Validate::isBool($count) || !Validate::isValidSearch($tag)
            || $orderBy && !$orderWay || ($orderBy && !Validate::isOrderBy($orderBy)) || ($orderWay && !Validate::isOrderBy($orderWay))
        ) {
            return false;
        }

        if ($pageNumber < 1) {
            $pageNumber = 1;
        }
        if ($pageSize < 1) {
            $pageSize = 10;
        }

        $id = Context::getContext()->shop->id;
        $idShop = $id ? $id : Configuration::get('PS_SHOP_DEFAULT');

        $sqlGroups = '';
        if (Group::isFeatureActive()) {
            $groups = FrontController::getCurrentCustomerGroups();
            $sqlGroups = 'AND cg.`id_group` '.(count($groups) ? 'IN ('.implode(',', $groups).')' : '= 1');
        }

        if ($count) {
            return (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                'SELECT COUNT(DISTINCT pt.`id_product`) nb
			FROM
			`'._DB_PREFIX_.'tag` t
			STRAIGHT_JOIN `'._DB_PREFIX_.'product_tag` pt ON (pt.`id_tag` = t.`id_tag` AND t.`id_lang` = '.(int) $idLang.')
			STRAIGHT_JOIN `'._DB_PREFIX_.'product` p ON (p.`id_product` = pt.`id_product`)
			'.Shop::addSqlAssociation('product', 'p').'
			LEFT JOIN `'._DB_PREFIX_.'category_product` cp ON (cp.`id_product` = p.`id_product`)
			LEFT JOIN `'._DB_PREFIX_.'category_shop` cs ON (cp.`id_category` = cs.`id_category` AND cs.`id_shop` = '.(int) $idShop.')
			'.(Group::isFeatureActive() ? 'LEFT JOIN `'._DB_PREFIX_.'category_group` cg ON (cg.`id_category` = cp.`id_category`)' : '').'
			WHERE product_shop.`active` = 1
			AND p.visibility IN (\'both\', \'search\')
			AND cs.`id_shop` = '.(int) Context::getContext()->shop->id.'
			'.$sqlGroups.'
			AND t.`name` LIKE \'%'.pSQL($tag).'%\''
            );
        }

        $sql = 'SELECT DISTINCT p.*, product_shop.*, stock.out_of_stock, IFNULL(stock.quantity, 0) as quantity, pl.`description_short`, pl.`link_rewrite`, pl.`name`, pl.`available_now`, pl.`available_later`,
					MAX(image_shop.`id_image`) id_image, il.`legend`, m.`name` manufacturer_name, 1 position,
					DATEDIFF(
						p.`date_add`,
						DATE_SUB(
							"'.date('Y-m-d').' 00:00:00",
							INTERVAL '.(Validate::isUnsignedInt(Configuration::get('PS_NB_DAYS_NEW_PRODUCT')) ? Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20).' DAY
						)
					) > 0 new
				FROM
				`'._DB_PREFIX_.'tag` t
				STRAIGHT_JOIN `'._DB_PREFIX_.'product_tag` pt ON (pt.`id_tag` = t.`id_tag` AND t.`id_lang` = '.(int) $idLang.')
				STRAIGHT_JOIN `'._DB_PREFIX_.'product` p ON (p.`id_product` = pt.`id_product`)
				INNER JOIN `'._DB_PREFIX_.'product_lang` pl ON (
					p.`id_product` = pl.`id_product`
					AND pl.`id_lang` = '.(int) $idLang.Shop::addSqlRestrictionOnLang('pl').'
				)
				'.Shop::addSqlAssociation('product', 'p', false).'
				LEFT JOIN `'._DB_PREFIX_.'product_attribute_shop` product_attribute_shop
				ON (p.`id_product` = product_attribute_shop.`id_product` AND product_attribute_shop.`default_on` = 1 AND product_attribute_shop.id_shop='.(int) $context->shop->id.')
				LEFT JOIN `'._DB_PREFIX_.'image_shop` image_shop
					ON (image_shop.`id_product` = p.`id_product` AND image_shop.cover=1 AND image_shop.id_shop='.(int) $context->shop->id.')
				LEFT JOIN `'._DB_PREFIX_.'image_lang` il ON (image_shop.`id_image` = il.`id_image` AND il.`id_lang` = '.(int) $idLang.')
				LEFT JOIN `'._DB_PREFIX_.'manufacturer` m ON (m.`id_manufacturer` = p.`id_manufacturer`)
				LEFT JOIN `'._DB_PREFIX_.'category_product` cp ON (cp.`id_product` = p.`id_product`)
				'.(Group::isFeatureActive() ? 'LEFT JOIN `'._DB_PREFIX_.'category_group` cg ON (cg.`id_category` = cp.`id_category`)' : '').'
				LEFT JOIN `'._DB_PREFIX_.'category_shop` cs ON (cp.`id_category` = cs.`id_category` AND cs.`id_shop` = '.(int) $idShop.')
				'.Product::sqlStock('p', 0).'
				WHERE product_shop.`active` = 1
					AND cs.`id_shop` = '.(int) Context::getContext()->shop->id.'
					'.$sqlGroups.'
					AND t.`name` LIKE \'%'.pSQL($tag).'%\'
					GROUP BY product_shop.id_product
				ORDER BY position DESC'.($orderBy ? ', '.$orderBy : '').($orderWay ? ' '.$orderWay : '').'
				LIMIT '.(int) (($pageNumber - 1) * $pageSize).','.(int) $pageSize;
        if (!$result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql, true, false)) {
            return false;
        }

        return Product::getProductsProperties((int) $idLang, $result);
    }
}
