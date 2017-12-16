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
 * Class Core_Business_CMS_CMSRoleRepository
 *
 * @since 1.0.0
 */
// @codingStandardsIgnoreStart
class Core_Business_CMS_CMSRoleRepository extends Core_Foundation_Database_EntityRepository
{
    // @codingStandardsIgnoreEnd

    /**
     * Return all CMSRoles which are already associated
     *
     * @return array|null
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function getCMSRolesAssociated()
    {
        $sql = '
			SELECT *
			FROM `'.$this->getTableNameWithPrefix().'`
			WHERE `id_cms` != 0';

        return $this->hydrateMany($this->db->select($sql));
    }
}
