<?php
/**
 * 2007-2016 PrestaShop
 *
 * thirty bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017-2024 thirty bees
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
 *  @copyright 2017-2024 thirty bees
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Class ModuleGridCore
 */
abstract class ModuleGridCore extends Module
{
    /**
     * @var Employee
     */
    protected $_employee;

    /**
     * @var array of strings graph data
     */
    protected $_values = [];

    /**
     * @var int total number of values *
     */
    protected $_totalCount = 0;

    /**
     * @var string graph titles
     */
    protected $_title;

    /**
     * @var int start
     */
    protected $_start;

    /**
     * @var int limit
     */
    protected $_limit;

    /**
     * @var string column name on which to sort
     */
    protected $_sort = null;

    /**
     * @var string sort direction DESC/ASC
     */
    protected $_direction = null;

    /**
     * @var ModuleGridEngine grid engine
     */
    protected $_render;

    /**
     * @var string csv content
     */
    protected $_csv = '';

    /**
     * @var int language context
     */
    protected $_id_lang;

    /**
     * @return void
     */
    abstract protected function getData();

    /**
     * @param int $idEmployee
     *
     * @throws PrestaShopException
     */
    public function setEmployee($idEmployee)
    {
        $this->_employee = new Employee((int)$idEmployee);
    }

    /**
     * @param int $idLang
     */
    public function setLang($idLang)
    {
        $this->_id_lang = (int)$idLang;
    }

    /**
     * @param string $render
     * @param string|null $type
     * @param int $width
     * @param int $height
     * @param int $start
     * @param int $limit
     * @param int $sort
     * @param string $dir
     *
     * @throws PrestaShopException
     */
    public function create($render, $type, $width, $height, $start, $limit, $sort, $dir)
    {
        if (!Validate::isModuleName($render)) {
            throw new PrestaShopException("Failed to resolve renderer module");
        }
        if (!file_exists($file = _PS_ROOT_DIR_.'/modules/'.$render.'/'.$render.'.php')) {
            throw new PrestaShopException("Invalid renderer module: " . $render);
        }
        require_once($file);
        $this->_render = new $render($type);

        $this->_start = $start;
        $this->_limit = $limit;
        $this->_sort = $sort;
        $this->_direction = $dir;

        $this->getData();

        $this->_render->setTitle($this->_title);
        $this->_render->setSize($width, $height);
        $this->_render->setValues($this->_values);
        $this->_render->setTotalCount($this->_totalCount);
        $this->_render->setLimit($this->_start, $this->_limit);
    }

    /**
     * @return void
     */
    public function render()
    {
        $this->_render->render();
    }

    /**
     * @param array $params
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    public function engine($params)
    {
        if (!($render = Configuration::get('PS_STATS_GRID_RENDER'))) {
            return Tools::displayError('No grid engine selected');
        }
        if (!Validate::isModuleName($render)) {
            return Tools::displayError('Invalid grid engine.');
        }
        if (!file_exists(_PS_ROOT_DIR_.'/modules/'.$render.'/'.$render.'.php')) {
            return Tools::displayError('Grid engine selected is unavailable.');
        }

        $grider = 'grider.php?render='.$render.'&module='.Tools::safeOutput(Tools::getValue('module'));

        $context = Context::getContext();
        $grider .= '&id_employee='.(int) $context->employee->id;
        $grider .= '&id_lang='.(int) $context->language->id;

        if (!isset($params['width']) || !Validate::IsUnsignedInt($params['width'])) {
            $params['width'] = 600;
        }
        if (!isset($params['height']) || !Validate::IsUnsignedInt($params['height'])) {
            $params['height'] = 920;
        }
        if (!isset($params['start']) || !Validate::IsUnsignedInt($params['start'])) {
            $params['start'] = 0;
        }
        if (!isset($params['limit']) || !Validate::IsUnsignedInt($params['limit'])) {
            $params['limit'] = 40;
        }

        $grider .= '&width='.$params['width'];
        $grider .= '&height='.$params['height'];
        if (Validate::IsUnsignedInt($params['start'])) {
            $grider .= '&start='.$params['start'];
        }
        if (Validate::IsUnsignedInt($params['limit'])) {
            $grider .= '&limit='.$params['limit'];
        }
        if (isset($params['type']) && Validate::IsName($params['type'])) {
            $grider .= '&type='.$params['type'];
        }
        if (isset($params['option']) && Validate::IsGenericName($params['option'])) {
            $grider .= '&option='.$params['option'];
        }
        if (isset($params['sort']) && Validate::IsName($params['sort'])) {
            $grider .= '&sort='.$params['sort'];
        }
        if (isset($params['dir']) && Validate::isSortDirection($params['dir'])) {
            $grider .= '&dir='.$params['dir'];
        }

        require_once(_PS_ROOT_DIR_.'/modules/'.$render.'/'.$render.'.php');

        return call_user_func([$render, 'hookGridEngine'], $params, $grider);
    }

    /**
     * @param array $datas
     */
    protected function csvExport($datas)
    {
        $this->_sort = $datas['defaultSortColumn'];
        $this->setLang(Context::getContext()->language->id);
        $this->getData();

        $layers = $datas['layers'] ?? 1;

        if (isset($datas['option'])) {
            $this->setOption($datas['option'], $layers);
        }

        if (count($datas['columns'])) {
            foreach ($datas['columns'] as $column) {
                $this->_csv .= $column['header'].';';
            }
            $this->_csv = rtrim($this->_csv, ';')."\n";

            foreach ($this->_values as $value) {
                foreach ($datas['columns'] as $column) {
                    $this->_csv .= $value[$column['dataIndex']].';';
                }
                $this->_csv = rtrim($this->_csv, ';')."\n";
            }
        }
        $this->_displayCsv();
    }

    /**
     * @return void
     */
    protected function _displayCsv()
    {
        if (ob_get_level() && ob_get_length() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.$this->displayName.' - '.time().'.csv"');
        echo $this->_csv;
        exit;
    }

    /**
     * @return string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getDate()
    {
        return ModuleGraph::getDateBetween($this->_employee);
    }

    /**
     * @return int
     */
    public function getLang()
    {
        return (int)$this->_id_lang;
    }

    /**
     * @param mixed $option
     * @param int $layers
     */
    public function setOption($option, $layers = 1)
    {
    }
}
