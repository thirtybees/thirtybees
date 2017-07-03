<?php
/**
 * 2007-2016 PrestaShop
 *
 * Thirty Bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017 Thirty Bees
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
 *  @author    Thirty Bees <contact@thirtybees.com>
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2017 Thirty Bees
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Class ModuleGraphEngineCore
 *
 * @since 1.0.0
 */
abstract class ModuleGraphEngineCore extends Module
{
    // @codingStandardsIgnoreStart
    protected $_type;
    // @codingStandardsIgnoreEnd

    /**
     * ModuleGraphEngineCore constructor.
     *
     * @param null|string $type
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function __construct($type)
    {
        $this->_type = $type;
    }

    /**
     * @return bool
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function install()
    {
        if (!parent::install()) {
            return false;
        }

        return Configuration::updateValue('PS_STATS_RENDER', $this->name);
    }

    /**
     * @return array
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getGraphEngines()
    {
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('m.`name`')
                ->from('module', 'm')
                ->leftJoin('hook_module', 'hm', 'hm.`id_module` = m.`id_module`')
                ->leftJoin('hook', 'h', 'hm.`id_hook` = h.`id_hook`')
                ->where('h.`name` = \'displayAdminStatsGraphEngine\'')
        );

        $arrayEngines = [];
        foreach ($result as $module) {
            $instance = Module::getInstanceByName($module['name']);
            if (!$instance) {
                continue;
            }
            $arrayEngines[$module['name']] = [$instance->displayName, $instance->description];
        }

        return $arrayEngines;
    }

    /**
     * @param mixed $values
     *
     * @return mixed
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    abstract public function createValues($values);

    /**
     * @param mixed $width
     * @param mixed $height
     *
     * @return mixed
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    abstract public function setSize($width, $height);

    /**
     * @param mixed $legend
     *
     * @return mixed
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    abstract public function setLegend($legend);

    /**
     * @param mixed $titles
     *
     * @return mixed
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    abstract public function setTitles($titles);

    /**
     * @return mixed
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    abstract public function draw();
}
