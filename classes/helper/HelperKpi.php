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
 * Class HelperKpiCore
 */
class HelperKpiCore extends Helper
{
    /**
     * @var string $base_folder
     */
    public $base_folder = 'helpers/kpi/';

    /**
     * @var string $base_tpl
     */
    public $base_tpl = 'kpi.tpl';

    /**
     * @var int $id
     */
    public $id;

    /**
     * @var string
     */
    public $icon;

    /**
     * @var bool
     */
    public $chart;

    /**
     * @var string
     */
    public $color;

    /**
     * @var string
     */
    public $title;

    /**
     * @var string
     */
    public $subtitle;

    /**
     * @var int|float
     */
    public $value;

    /**
     * @var string
     */
    public $data;

    /**
     * @var string
     */
    public $source;

    /**
     * @var bool
     */
    public $refresh = true;

    /**
     * @var string
     */
    public $href;

    /**
     * @var string
     */
    public $tooltip;

    /**
     * @return false|string
     *
     * @throws SmartyException
     * @throws PrestaShopException
     */
    public function generate()
    {
        $this->tpl = $this->createTemplate($this->base_tpl);

        $this->tpl->assign(
            [
                'id'       => $this->id,
                'icon'     => $this->icon,
                'chart'    => (bool) $this->chart,
                'color'    => $this->color,
                'title'    => $this->title,
                'subtitle' => $this->subtitle,
                'value'    => $this->value,
                'data'     => $this->data,
                'source'   => $this->source,
                'refresh'  => $this->refresh,
                'href'     => $this->href,
                'tooltip'  => $this->tooltip,
            ]
        );

        return $this->tpl->fetch();
    }
}
