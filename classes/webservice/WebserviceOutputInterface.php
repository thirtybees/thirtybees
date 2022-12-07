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
 * Interface WebserviceOutputInterface
 */
interface WebserviceOutputInterface
{
    /**
     * @param array $languages
     */
    public function __construct($languages = []);

    /**
     * @param string $url
     * @return static
     */
    public function setWsUrl($url);

    /**
     * @return string
     */
    public function getWsUrl();

    /**
     * @return string
     */
    public function getContentType();

    /**
     * @param string $schema
     * @return static
     */
    public function setSchemaToDisplay($schema);

    /**
     * @return string
     */
    public function getSchemaToDisplay();

    /**
     * @param array $field
     * @return string
     */
    public function renderField($field);

    /**
     * @param string $obj
     * @param array $params
     * @param array|null $moreAttr
     * @param bool $hasChild
     * @return string
     */
    public function renderNodeHeader($obj, $params, $moreAttr = null, $hasChild = true);

    /**
     * @param string $obj
     * @param array $params
     * @return string
     */
    public function renderNodeFooter($obj, $params);

    /**
     * @param ObjectModel $obj
     * @param array $params
     * @param string $assocName
     * @param bool $closedTags
     * @return string
     */
    public function renderAssociationHeader($obj, $params, $assocName, $closedTags = false);

    /**
     * @param ObjectModel $obj
     * @param array $params
     * @param string $assocName
     * @return string
     */
    public function renderAssociationFooter($obj, $params, $assocName);

    /**
     * @param string $content
     * @return string
     */
    public function overrideContent($content);

    /**
     * @return string
     */
    public function renderErrorsHeader();

    /**
     * @return string
     */
    public function renderErrorsFooter();

    /**
     * @param string $message
     * @param int|null $code
     * @param array $extra
     * @return string
     */
    public function renderErrors($message, $code = null, $extra = []);
}
