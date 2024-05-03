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
 * @author    thirty bees <contact@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017-2024 thirty bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Class WebserviceSpecificManagementSearchCore
 */
class WebserviceSpecificManagementSearchCore implements WebserviceSpecificManagementInterface
{
    /**
     * @var WebserviceOutputBuilder
     */
    protected $objOutput;

    /**
     * @var string
     */
    protected $output;

    /**
     * @var WebserviceRequest
     */
    protected $wsObject;

    /* ------------------------------------------------
     * GETTERS & SETTERS
     * ------------------------------------------------ */

    /**
     * @param WebserviceOutputBuilderCore $obj
     *
     * @return static
     */
    public function setObjectOutput(WebserviceOutputBuilderCore $obj)
    {
        $this->objOutput = $obj;

        return $this;
    }

    /**
     * @param WebserviceRequestCore $obj
     *
     * @return static
     */
    public function setWsObject(WebserviceRequestCore $obj)
    {
        $this->wsObject = $obj;

        return $this;
    }

    /**
     * @return WebserviceRequest
     */
    public function getWsObject()
    {
        return $this->wsObject;
    }

    /**
     * @return WebserviceOutputBuilder
     */
    public function getObjectOutput()
    {
        return $this->objOutput;
    }

    /**
     * WebserviceRequestCore
     *
     * @throws WebserviceException
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function manage()
    {
        if (!isset($this->wsObject->urlFragments['query']) || !isset($this->wsObject->urlFragments['language'])) {
            throw new WebserviceException('You have to set both the \'language\' and \'query\' parameters to get a result', [100, 400]);
        }
        $objectsProducts = [];
        $objectsCategories = [];
        $objectsProducts['empty'] = new Product();
        $objectsCategories['empty'] = new Category();

        if (!$this->wsObject->setFieldsToDisplay()) {
            return false;
        }

        $results = Search::find($this->wsObject->urlFragments['language'], $this->wsObject->urlFragments['query'], 1, 1, 'position', 'desc', true, false);
        $categories = [];
        foreach ($results as $result) {
            $current = new Product($result['id_product']);
            $objectsProducts[] = $current;
            $categoriesResult = $current->getWsCategories();
            foreach ($categoriesResult as $category_result) {
                foreach ($category_result as $id) {
                    $categories[] = $id;
                }
            }
        }
        $categories = array_unique($categories);
        foreach ($categories as $id) {
            $objectsCategories[] = new Category($id);
        }

        $this->output .= $this->objOutput->getContent($objectsProducts, null, $this->wsObject->fieldsToDisplay, $this->wsObject->depth, WebserviceOutputBuilder::VIEW_LIST, false);
        $this->output .= $this->objOutput->getContent($objectsCategories, null, $this->wsObject->fieldsToDisplay, $this->wsObject->depth, WebserviceOutputBuilder::VIEW_LIST, false);
    }

    /**
     * This must be return a string with specific values as WebserviceRequest expects.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->objOutput->getObjectRender()->overrideContent($this->output);
    }
}
