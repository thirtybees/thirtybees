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
 * Class Core_Business_Payment_PaymentOption
 */
class Core_Business_Payment_PaymentOption
{
    /**
     * @var string
     */
    protected $callToActionText;

    /**
     * @var string
     */
    protected $logo;

    /**
     * @var string
     */
    protected $action;

    /**
     * @var string
     */
    protected $method;

    /**
     * @var array
     */
    protected $inputs;

    /**
     * @var string
     */
    protected $form;

    /**
     * @var string
     */
    protected $moduleName;

    /**
     * Return Call to Action Text
     *
     * @return string
     */
    public function getCallToActionText()
    {
        return $this->callToActionText;
    }

    /**
     * Set Call To Action Text
     *
     * @param string $callToActionText
     *
     * @return static
     */
    public function setCallToActionText($callToActionText)
    {
        $this->callToActionText = $callToActionText;

        return $this;
    }

    /**
     * Return logo path
     *
     * @return string
     */
    public function getLogo()
    {
        return $this->logo;
    }

    /**
     * Set logo path
     *
     * @param string $logo
     *
     * @return static
     */
    public function setLogo($logo)
    {
        $this->logo = $logo;

        return $this;
    }

    /**
     * Return action to perform (POST/GET)
     *
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }


    /**
     * Set action to be performed by this option
     *
     * @param string $action
     *
     * @return static
     */
    public function setAction($action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param string $method
     *
     * @return static
     */
    public function setMethod($method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * Return inputs contained in this payment option
     *
     * @return array
     */
    public function getInputs()
    {
        return $this->inputs;
    }

    /**
     * Set inputs for this payment option
     *
     * @param array $inputs
     *
     * @return static
     */
    public function setInputs($inputs)
    {
        $this->inputs = $inputs;

        return $this;
    }

    /**
     * Get payment option form
     *
     * @return string
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * Set payment option form
     *
     * @param string $form
     *
     * @return static
     */
    public function setForm($form)
    {
        $this->form = $form;

        return $this;
    }

    /**
     * Get related module name to this payment option
     *
     * @return string
     */
    public function getModuleName()
    {
        return $this->moduleName;
    }

    /**
     * Set related module name to this payment option
     *
     * @param string $moduleName
     *
     * @return static
     */
    public function setModuleName($moduleName)
    {
        $this->moduleName = $moduleName;

        return $this;
    }

    /**
     * Legacy options were specified this way:
     * - either an array with a top level property 'cta_text'
     *    and then the other properties
     * - or a numerically indexed array or arrays as described above
     * Since this was a mess, this method is provided to convert them.
     * It takes as input a legacy option (in either form) and always
     * returns an array of instances of Core_Business_Payment_PaymentOption
     *
     * @param array $legacyOption
     *
     * @return array|null
     */
    public static function convertLegacyOption(array $legacyOption)
    {
        if (!$legacyOption) {
            return null;
        }

        if (array_key_exists('cta_text', $legacyOption)) {
            $legacyOption = [$legacyOption];
        }

        $newOptions = [];

        $defaults = [
            'action' => null,
            'form' => null,
            'method' => null,
            'inputs' => [],
            'logo' => null,
        ];

        foreach ($legacyOption as $option) {
            $option = array_merge($defaults, $option);

            $newOption = new Core_Business_Payment_PaymentOption();
            $newOption->setCallToActionText($option['cta_text'])
                      ->setAction($option['action'])
                      ->setForm($option['form'])
                      ->setInputs($option['inputs'])
                      ->setLogo($option['logo'])
                      ->setMethod($option['method']);

            $newOptions[] = $newOption;
        }

        return $newOptions;
    }
}
