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
 * Interface ITreeToolbarButtonCore
 */
interface ITreeToolbarButtonCore
{
    /**
     * @return string
     */
    public function __toString();

    /**
     * @param string $name
     * @param mixed $value
     * @return static
     */
    public function setAttribute($name, $value);

    /**
     * @param string $name
     * @return mixed
     */
    public function getAttribute($name);

    /**
     * @param array $value
     * @return mixed
     */
    public function setAttributes($value);

    /**
     * @return array
     */
    public function getAttributes();

    /**
     * @param string $value
     * @return static
     */
    public function setClass($value);

    /**
     * @return string
     */
    public function getClass();

    /**
     * @param string $value
     * @return static
     */
    public function setContext($value);

    /**
     * @return string
     */
    public function getContext();

    /**
     * @param string $value
     * @return static
     */
    public function setId($value);

    /**
     * @return string
     */
    public function getId();

    /**
     * @param string $value
     * @return static
     */
    public function setLabel($value);

    /**
     * @return string
     */
    public function getLabel();

    /**
     * @param string $value
     * @return static
     */
    public function setName($value);

    /**
     * @return string
     */
    public function getName();

    /**
     * @param string $value
     * @return static
     */
    public function setTemplate($value);

    /**
     * @return string
     */
    public function getTemplate();

    /**
     * @param string $value
     * @return static
     */
    public function setTemplateDirectory($value);

    /**
     * @return string
     */
    public function getTemplateDirectory();

    /**
     * @param string $name
     * @return bool
     */
    public function hasAttribute($name);

    /**
     * @return string
     */
    public function render();
}
