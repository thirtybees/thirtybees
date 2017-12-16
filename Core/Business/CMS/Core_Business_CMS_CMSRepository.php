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
 *  @author    thirty bees <contact@thirtybees.com>
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2017-2018 thirty bees
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Class Core_Business_CMS_CMSRepository
 *
 * @since 1.0.0
 */
// @codingStandardsIgnoreStart
class Core_Business_CMS_CMSRepository extends Core_Foundation_Database_EntityRepository
{
    // @codingStandardsIgnoreEnd

    /**
     * Return all CMSRepositories depending on $id_lang/$id_shop tuple
     *
     * @param int $idLang
     * @param int $idShop
     *
     * @return array|null
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function i10nFindAll($idLang, $idShop)
    {
        $sql = '
			SELECT *
			FROM `'.$this->getTableNameWithPrefix().'` c
			JOIN `'.$this->getPrefix().'cms_lang` cl ON c.`id_cms`= cl.`id_cms`
			WHERE cl.`id_lang` = '.(int) $idLang.'
			AND cl.`id_shop` = '.(int) $idShop.'

		';

        return $this->hydrateMany($this->db->select($sql));
    }

    /**
     * Return all CMSRepositories depending on $id_lang/$id_shop tuple
     *
     * @param int $idCms
     * @param int $idLang
     * @param int $idShop
     *
     * @return CMS|null
     * @throws Core_Foundation_Database_Exception
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function i10nFindOneById($idCms, $idLang, $idShop)
    {
        $sql = '
			SELECT *
			FROM `'.$this->getTableNameWithPrefix().'` c
			JOIN `'.$this->getPrefix().'cms_lang` cl ON c.`id_cms`= cl.`id_cms`
			WHERE c.`id_cms` = '.(int) $idCms.'
			AND cl.`id_lang` = '.(int) $idLang.'
			AND cl.`id_shop` = '.(int) $idShop.'
			LIMIT 0 , 1
		';

        return $this->hydrateOne($this->db->select($sql));
    }

    /**
     * Return CMSRepository lang associative table name
     *
     * @return string
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    protected function getLanguageTableNameWithPrefix()
    {
        return $this->getTableNameWithPrefix().'_lang';
    }
}
