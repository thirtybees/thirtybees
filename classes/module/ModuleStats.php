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
 * Class ModuleGraphCore
 *
 * @since 1.0.0
 */
abstract class ModuleStatsCore extends Module
{
    // @codingStandardsIgnoreStart
    /** @var Employee $_employee */
    protected $_employee;
    /** @var int[] graph data */
    protected $_values = [];
    /** @var string[] graph legends (X axis) */
    protected $_legend = [];
    /**@var string[] graph titles */
    protected $_titles = ['main' => null, 'x' => null, 'y' => null];
    /** @var ModuleGraphEngine graph engine */
    protected $_render;
    /** @var int total number of values **/
    protected $_totalCount = 0;
    /**@var string graph titles */
    protected $_title;
    /**@var int start */
    protected $_start;
    /**@var int limit */
    protected $_limit;
    /**@var string column name on which to sort */
    protected $_sort = null;
    /**@var string sort direction DESC/ASC */
    protected $_direction = null;
    // @codingStandardsIgnoreEnd

    /**
     * @param int $idEmployee
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function setEmployee($idEmployee)
    {
        $this->_employee = new Employee($idEmployee);
    }

    /**
     * @param $id_lang
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function setLang($id_lang)
    {
        $this->_id_lang = $id_lang;
    }

    /**
     * @param      $layers
     * @param bool $legend
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    protected function setDateGraph($layers, $legend = false)
    {
        // Get dates in a manageable format
        $fromArray = getdate(strtotime($this->_employee->stats_date_from));
        $toArray = getdate(strtotime($this->_employee->stats_date_to));

        // If the granularity is inferior to 1 day
        if ($this->_employee->stats_date_from == $this->_employee->stats_date_to) {
            if ($legend) {
                for ($i = 0; $i < 24; $i++) {
                    if ($layers == 1) {
                        $this->_values[$i] = 0;
                    } else {
                        for ($j = 0; $j < $layers; $j++) {
                            $this->_values[$j][$i] = 0;
                        }
                    }
                    $this->_legend[$i] = ($i % 2) ? '' : sprintf('%02dh', $i);
                }
            }
            if (is_callable([$this, 'setDayValues'])) {
                $this->setDayValues($layers);
            }
        } elseif (strtotime($this->_employee->stats_date_to) - strtotime($this->_employee->stats_date_from) <= 2678400) {
            // If the granularity is inferior to 1 month
            // @TODO : change to manage 28 to 31 days
            if ($legend) {
                $days = [];
                if ($fromArray['mon'] == $toArray['mon']) {
                    for ($i = $fromArray['mday']; $i <= $toArray['mday']; ++$i) {
                        $days[] = $i;
                    }
                } else {
                    $imax = date('t', mktime(0, 0, 0, $fromArray['mon'], 1, $fromArray['year']));
                    for ($i = $fromArray['mday']; $i <= $imax; ++$i) {
                        $days[] = $i;
                    }
                    for ($i = 1; $i <= $toArray['mday']; ++$i) {
                        $days[] = $i;
                    }
                }
                foreach ($days as $i) {
                    if ($layers == 1) {
                        $this->_values[$i] = 0;
                    } else {
                        for ($j = 0; $j < $layers; $j++) {
                            $this->_values[$j][$i] = 0;
                        }
                    }
                    $this->_legend[$i] = ($i % 2) ? '' : sprintf('%02d', $i);
                }
            }
            if (is_callable([$this, 'setMonthValues'])) {
                $this->setMonthValues($layers);
            }
        } elseif (strtotime('-1 year', strtotime($this->_employee->stats_date_to)) < strtotime($this->_employee->stats_date_from)) {
            // If the granularity is less than 1 year
            if ($legend) {
                $months = [];
                if ($fromArray['year'] == $toArray['year']) {
                    for ($i = $fromArray['mon']; $i <= $toArray['mon']; ++$i) {
                        $months[] = $i;
                    }
                } else {
                    for ($i = $fromArray['mon']; $i <= 12; ++$i) {
                        $months[] = $i;
                    }
                    for ($i = 1; $i <= $toArray['mon']; ++$i) {
                        $months[] = $i;
                    }
                }
                foreach ($months as $i) {
                    if ($layers == 1) {
                        $this->_values[$i] = 0;
                    } else {
                        for ($j = 0; $j < $layers; $j++) {
                            $this->_values[$j][$i] = 0;
                        }
                    }
                    $this->_legend[$i] = sprintf('%02d', $i);
                }
            }
            if (is_callable([$this, 'setYearValues'])) {
                $this->setYearValues($layers);
            }
        } else {
            // If the granularity is greater than 1 year
            if ($legend) {
                $years = [];
                for ($i = $fromArray['year']; $i <= $toArray['year']; ++$i) {
                    $years[] = $i;
                }
                foreach ($years as $i) {
                    if ($layers == 1) {
                        $this->_values[$i] = 0;
                    } else {
                        for ($j = 0; $j < $layers; $j++) {
                            $this->_values[$j][$i] = 0;
                        }
                    }
                    $this->_legend[$i] = sprintf('%04d', $i);
                }
            }
            if (is_callable([$this, 'setAllTimeValues'])) {
                $this->setAllTimeValues($layers);
            }
        }
    }

    /**
     * @param $datas
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    protected function csvExportGraph($datas)
    {
        $context = Context::getContext();

        $this->setEmployee($context->employee->id);
        $this->setLang($context->language->id);

        $layers = isset($datas['layers']) ?  $datas['layers'] : 1;
        if (isset($datas['option'])) {
            $this->setOption($datas['option'], $layers);
        }
        $this->getData($layers);

        // @todo use native CSV PHP functions ?
        // Generate first line (column titles)
        if (is_array($this->_titles['main'])) {
            for ($i = 0, $totalMain = count($this->_titles['main']); $i <= $totalMain; $i++) {
                if ($i > 0) {
                    $this->_csv .= ';';
                }
                if (isset($this->_titles['main'][$i])) {
                    $this->_csv .= $this->_titles['main'][$i];
                }
            }
        } else { // If there is only one column title, there is in fast two column (the first without title)
            $this->_csv .= ';'.$this->_titles['main'];
        }
        $this->_csv .= "\n";
        if (count($this->_legend)) {
            $total = 0;
            if ($datas['type'] == 'pie') {
                foreach ($this->_legend as $key => $legend) {
                    for ($i = 0, $totalMain = (is_array($this->_titles['main']) ? count($this->_values) : 1); $i < $totalMain; ++$i) {
                        $total += (is_array($this->_values[$i])  ? $this->_values[$i][$key] : $this->_values[$key]);
                    }
                }
            }
            foreach ($this->_legend as $key => $legend) {
                $this->_csv .= $legend.';';
                for ($i = 0, $totalMain = (is_array($this->_titles['main']) ? count($this->_values) : 1); $i < $totalMain; ++$i) {
                    if (!isset($this->_values[$i]) || !is_array($this->_values[$i])) {
                        if (isset($this->_values[$key])) {
                            // We don't want strings to be divided. Example: product name
                            if (is_numeric($this->_values[$key])) {
                                $this->_csv .= $this->_values[$key] / (($datas['type'] == 'pie') ? $total : 1);
                            } else {
                                $this->_csv .= $this->_values[$key];
                            }
                        } else {
                            $this->_csv .= '0';
                        }
                    } else {
                        // We don't want strings to be divided. Example: product name
                        if (is_numeric($this->_values[$i][$key])) {
                            $this->_csv .= $this->_values[$i][$key] / (($datas['type'] == 'pie') ? $total : 1);
                        } else {
                            $this->_csv .= $this->_values[$i][$key];
                        }
                    }
                    $this->_csv .= ';';
                }
                $this->_csv .= "\n";
            }
        }
        $this->_displayCsv();
    }

    protected function csvExportGrid($datas)
    {
        $this->_sort = $datas['defaultSortColumn'];
        $this->setLang(Context::getContext()->language->id);
        $this->getData();

        $layers = isset($datas['layers']) ?  $datas['layers'] : 1;

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
     * @since 1.0.0
     * @version 1.0.0 Initial version
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
     * @param mixed $render
     * @param mixed $type
     * @param mixed $width
     * @param mixed $height
     * @param mixed $layers
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function createGraph($render, $type, $width, $height, $layers)
    {
        if (!file_exists($file = _PS_ROOT_DIR_.'/modules/'.$render.'/'.$render.'.php')) {
            $render = 'ModuleGraphEngine';
        } else {
            require_once($file);
        }

        $this->_render = new $render($type);

        $this->getData($layers);
        $this->_render->createValues($this->_values);
        $this->_render->setSize($width, $height);
        $this->_render->setLegend($this->_legend);
        $this->_render->setTitles($this->_titles);
    }

    public function createGrid($render, $type, $width, $height, $start, $limit, $sort, $dir)
    {
        if (!file_exists($file = _PS_ROOT_DIR_.'/modules/'.$render.'/'.$render.'.php')) {
            $render = 'ModuleGridEngine';
        } else {
            require_once($file);
        }
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
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function draw()
    {
        $this->_render->draw();
    }

    /**
     * @param mixed $option
     * @param int   $layers
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function setOption($option, $layers = 1)
    {
    }

    /**
     * @param array $params
     *
     * @return array|mixed|string
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function engineGraph($params)
    {
        $context = Context::getContext();
        $render = Configuration::get('PS_STATS_RENDER');
        $idEmployee = (int) $context->employee->id;
        $idLang = (int) $context->language->id;

        if (!isset($params['layers'])) {
            $params['layers'] = 1;
        }
        if (!isset($params['type'])) {
            $params['type'] = 'column';
        }
        if (!isset($params['width'])) {
            $params['width'] = '100%';
        }
        if (!isset($params['height'])) {
            $params['height'] = 270;
        }

        $urlParams = $params;
        $urlParams['render'] = $render;
        $urlParams['module'] = Tools::getValue('module');
        $urlParams['engine'] = 'graph';
        $urlParams['id_employee'] = $idEmployee;
        $urlParams['id_lang'] = $idLang;
        $drawer = 'drawer.php?'.http_build_query(array_map('Tools::safeOutput', $urlParams), '', '&');

        if (file_exists(_PS_ROOT_DIR_.'/modules/'.$render.'/'.$render.'.php')) {
            require_once(_PS_ROOT_DIR_.'/modules/'.$render.'/'.$render.'.php');

            return call_user_func([$render, 'hookGraphEngine'], $params, $drawer);
        } else {
            return (new ModuleGraphEngine(isset($params['type']) ? $params['type'] : null))->hookGraphEngine($params, $drawer);
        }
    }

    public function engineGrid($params)
    {
        $render = Configuration::get('PS_STATS_GRID_RENDER');
        $grider = 'grider.php?render='.$render.'&engine=grid&module='.Tools::safeOutput(Tools::getValue('module'));

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
        if (isset($params['start']) && Validate::IsUnsignedInt($params['start'])) {
            $grider .= '&start='.$params['start'];
        }
        if (isset($params['limit']) && Validate::IsUnsignedInt($params['limit'])) {
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

        if (file_exists(_PS_ROOT_DIR_.'/modules/'.$render.'/'.$render.'.php')) {
            require_once(_PS_ROOT_DIR_.'/modules/'.$render.'/'.$render.'.php');

            return call_user_func([$render, 'hookGridEngine'], $params, $grider);
        } else {
            return (new ModuleGridEngine(isset($params['type']) ? $params['type'] : null))->hookGridEngine($params, $grider);
        }
    }

    /**
     * @param null         $employee
     * @param Context|null $context
     *
     * @return bool|Employee|null
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    protected static function getEmployee($employee = null, Context $context = null)
    {
        if (!Validate::isLoadedObject($employee)) {
            if (!$context) {
                $context = Context::getContext();
            }
            if (!Validate::isLoadedObject($context->employee)) {
                return false;
            }
            $employee = $context->employee;
        }

        if (empty($employee->stats_date_from) || empty($employee->stats_date_to)
            || $employee->stats_date_from == '0000-00-00' || $employee->stats_date_to == '0000-00-00') {
            if (empty($employee->stats_date_from) || $employee->stats_date_from == '0000-00-00') {
                $employee->stats_date_from = date('Y').'-01-01';
            }
            if (empty($employee->stats_date_to) || $employee->stats_date_to == '0000-00-00') {
                $employee->stats_date_to = date('Y').'-12-31';
            }
            $employee->update();
        }

        return $employee;
    }

    /**
     * @return string
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function getDate()
    {
        return ModuleGraph::getDateBetween($this->_employee);
    }

    /**
     * @param null $employee
     *
     * @return string
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getDateBetween($employee = null)
    {
        if ($employee = ModuleGraph::getEmployee($employee)) {
            return ' \''.$employee->stats_date_from.' 00:00:00\' AND \''.$employee->stats_date_to.' 23:59:59\' ';
        }

        return ' \''.date('Y-m').'-01 00:00:00\' AND \''.date('Y-m-t').' 23:59:59\' ';
    }

    /**
     * @return mixed
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function getLang()
    {
        return $this->_id_lang;
    }

    /**
     * @param $layers
     *
     * @return mixed
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    abstract protected function getData($layers);
}
