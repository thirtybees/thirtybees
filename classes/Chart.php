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

/**
 * Class ChartCore
 *
 * @since 1.0.0
 */
class ChartCore
{
    // @codingStandardsIgnoreStart
    /** @var int $poolId */
    protected static $poolId = 0;
    /** @var int $width */
    protected $width = 600;
    /** @var int $height */
    protected $height = 300;

    /* Time mode */
    /** @var bool $timeMode */
    protected $timeMode = false;
    protected $from;
    protected $to;
    protected $format;
    protected $granularity;
    /** @var array $curves */
    protected $curves = [];
    // @codingStandardsIgnoreEnd

    /**
     * ChartCore constructor.
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function __construct()
    {
        ++static::$poolId;
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function init()
    {
        if (!static::$poolId) {
            ++static::$poolId;

            return true;
        }
    }

    /**
     * @param int $width
     * @param int $height
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function setSize($width, $height)
    {
        $this->width = (int) $width;
        $this->height = (int) $height;
    }

    /**
     * @param string $from
     * @param string $to
     * @param string $granularity
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function setTimeMode($from, $to, $granularity)
    {
        $this->granularity = $granularity;

        if (Validate::isDate($from)) {
            $from = strtotime($from);
        }
        $this->from = $from;
        if (Validate::isDate($to)) {
            $to = strtotime($to);
        }
        $this->to = $to;

        if ($granularity == 'd') {
            $this->format = '%d/%m/%y';
        }
        if ($granularity == 'w') {
            $this->format = '%d/%m/%y';
        }
        if ($granularity == 'm') {
            $this->format = '%m/%y';
        }
        if ($granularity == 'y') {
            $this->format = '%y';
        }

        $this->timeMode = true;
    }

    /**
     * @param $i
     *
     * @return mixed
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getCurve($i)
    {
        if (!array_key_exists($i, $this->curves)) {
            $this->curves[$i] = new Curve();
        }

        return $this->curves[$i];
    }

    /**
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function display()
    {
        echo $this->fetch();
    }

    /**
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function fetch()
    {
        if ($this->timeMode) {
            $options = 'xaxis:{mode:"time",timeformat:\''.addslashes($this->format).'\',min:'.$this->from.'000,max:'.$this->to.'000}';
            if ($this->granularity == 'd') {
                foreach ($this->curves as $curve) {
                    /** @var Curve $curve */
                    for ($i = $this->from; $i <= $this->to; $i = strtotime('+1 day', $i)) {
                        if (!$curve->getPoint($i)) {
                            $curve->setPoint($i, 0);
                        }
                    }
                }
            }
        }

        $jsCurves = [];
        foreach ($this->curves as $curve) {
            $jsCurves[] = $curve->getValues($this->timeMode);
        }

        if (count($jsCurves)) {
            return '
			<div id="flot'.static::$poolId.'" style="width:'.$this->width.'px;height:'.$this->height.'px"></div>
			<script type="text/javascript">
				$(function () {
					$.plot($(\'#flot'.static::$poolId.'\'), ['.implode(',', $jsCurves).'], {'.$options.'});
				});
			</script>';
        }
    }
}

/**
 * Class Curve
 *
 * @since 1.0.0
 */
class Curve
{
    protected $values = [];
    protected $label;
    protected $type;

    /**
     * @param bool $time_mode
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getValues($time_mode = false)
    {
        ksort($this->values);
        $string = '';
        foreach ($this->values as $key => $value) {
            $string .= '['.addslashes((string) $key).($time_mode ? '000' : '').','.(float) $value.'],';
        }

        return '{data:['.rtrim($string, ',').']'.(!empty($this->label) ? ',label:"'.$this->label.'"' : '').''.(!empty($this->type) ? ','.$this->type : '').'}';
    }

    /**
     * @param $values
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function setValues($values)
    {
        $this->values = $values;
    }

    /**
     * @param $x
     * @param $y
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function setPoint($x, $y)
    {
        $this->values[(string) $x] = (float) $y;
    }

    /**
     * @param $label
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * @param $type
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function setType($type)
    {
        $this->type = '';
        if ($type == 'bars') {
            $this->type = 'bars:{show:true,lineWidth:10}';
        }
        if ($type == 'steps') {
            $this->type = 'lines:{show:true,steps:true}';
        }
    }

    /**
     * @param $x
     *
     * @return mixed
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getPoint($x)
    {
        if (array_key_exists((string) $x, $this->values)) {
            return $this->values[(string) $x];
        }
    }
}
