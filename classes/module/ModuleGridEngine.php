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
 * Class ModuleGridEngineCore
 *
 * @since 1.0.0
 */
class ModuleGridEngineCore extends Module
{
    // @codingStandardsIgnoreStart
    protected $_type;
    private $_values;
    private static $_columns;
    // @codingStandardsIgnoreEnd

    /**
     * ModuleGridEngineCore constructor.
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

        return Configuration::updateValue('PS_STATS_GRID_RENDER', $this->name);
    }

    /**
     * @return array
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getGridEngines()
    {
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('m.`name`')
                ->from('module', 'm')
                ->leftJoin('module', 'm')
                ->leftJoin('hook', 'h', 'hm.`id_hook` = h.`id_hook`')
                ->where('h.`name` = \'displayAdminStatsGridEngine\'')
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

    public static function hookGridEngine($params, $grider)
    {
        self::$_columns = $params['columns'];
        if (!isset($params['emptyMsg']))
            $params['emptyMsg'] = 'Empty';
        $customParams = '';
        if (isset($params['customParams'])) {
            foreach ($params['customParams'] as $name => $value) {
                $customParams .= '&'.$name.'='.urlencode($value);
            }
        }
        $html = '
		<table class="table" id="grid_1">
			<thead>
				<tr>';
        foreach ($params['columns'] as $column)
            $html .= '<th class="center"><span class="title_box active">'.$column['header'].'</span></th>';
        $html .= '</tr>
			</thead>
			<tbody></tbody>
			<tfoot><tr><th colspan="'.count($params['columns']).'"></th></tr></tfoot>
		</table>
		<script type="text/javascript">
			function getGridData(url)
			{
				$("#grid_1 tbody").html("<tr><td style=\"text-align:center\" colspan=\"" + '.count($params['columns']).' + "\"><img src=\"../img/loadingAnimation.gif\" /></td></tr>");
				$.get(url, "", function(json) {
					$("#grid_1 tbody").html("");
					var array = $.parseJSON(json);
					$("#grid_1 tfoot tr th").html("'.addslashes($params['pagingMessage']).'");
					$("#grid_1 tfoot tr th").html($("#grid_1 tfoot tr th").html().replace("{0}", array["from"]));
					$("#grid_1 tfoot tr th").html($("#grid_1 tfoot tr th").html().replace("{1}", array["to"]));
					$("#grid_1 tfoot tr th").html($("#grid_1 tfoot tr th").html().replace("{2}", array["total"]));
					if (array["from"] > 1)
						$("#grid_1 tfoot tr th").html($("#grid_1 tfoot tr th").html() + " &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a style=\\"cursor:pointer;text-decoration:none\\" onclick=\\"gridPrevPage(\'"+ url +"\');\\">&lt;&lt;</a>");
					if (array["to"] < array["total"])
						$("#grid_1 tfoot tr th").html($("#grid_1 tfoot tr th").html() + " | <a style=\\"cursor:pointer;text-decoration:none\\" onclick=\\"gridNextPage(\'"+ url +"\');\\">&gt;&gt;</a>");
					var values = array["values"];
					if (values.length > 0)
						$.each(values, function(index, row){
							var newLine = "<tr>";';
        foreach ($params['columns'] as $column)
            $html .= '	newLine += "<td'.(isset($column['align']) ? ' align=\"'.$column['align'].'\"' : '').'>" + row["'.$column['dataIndex'].'"] + "</td>";';
        if (!isset($params['defaultSortColumn']))
            $params['defaultSortColumn'] = false;
        if (!isset($params['defaultSortDirection']))
            $params['defaultSortDirection'] = false;
        $html .= '		$("#grid_1 tbody").append(newLine);
						});
					else
						$("#grid_1 tbody").append("<tr><td class=\"center\" colspan=\"" + '.count($params['columns']).' + "\">'.$params['emptyMsg'].'</td></tr>");
				});
			}
			
			function gridNextPage(url)
			{
				var from = url.match(/&start=[0-9]+/i);
				if (from && from[0] && parseInt(from[0].replace("&start=", "")) > 0)
					from = "&start=" + (parseInt(from[0].replace("&start=", "")) + 40);
				else
					from = "&start=40";
				url = url.replace(/&start=[0-9]+/i, "") + from;
				getGridData(url);
			}
			
			function gridPrevPage(url)
			{
				var from = url.match(/&start=[0-9]+/i);
				if (from && from[0] && parseInt(from[0].replace("&start=", "")) > 0)
				{
					var fromInt = parseInt(from[0].replace("&start=", "")) - 40;
					if (fromInt > 0)
						from = "&start=" + fromInt;
					else
						from = "&start=0";
				}
				else
					from = "&start=0";
				url = url.replace(/&start=[0-9]+/i, "") + from;
				getGridData(url);
			}
			$(document).ready(function(){getGridData("'.$grider.'&sort='.urlencode($params['defaultSortColumn']).'&dir='.urlencode($params['defaultSortDirection']).$customParams.'");});
		</script>';
        return $html;
    }

    public function setColumnsInfos(&$infos)
    {
    }

    public function setValues($values)
    {
        $this->_values = $values;
    }

    public function setTitle($title)
    {
        $this->_title = $title;
    }

    public function setSize($width, $height)
    {
        $this->_width = $width;
        $this->_height = $height;
    }

    public function setTotalCount($totalCount)
    {
        $this->_totalCount = $totalCount;
    }

    public function setLimit($start, $limit)
    {
        $this->_start = (int)$start;
        $this->_limit = (int)$limit;
    }

    public function render()
    {
        echo json_encode([
            'total' => $this->_totalCount,
            'from' => min($this->_start + 1, $this->_totalCount),
            'to' => min($this->_start + $this->_limit, $this->_totalCount),
            'values' => $this->_values
        ]);
        exit;
    }
}
