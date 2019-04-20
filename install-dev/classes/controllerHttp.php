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
abstract class InstallControllerHttp
{
    /**
     * @var array List of installer steps
     */
    protected static $steps = ['welcome', 'license', 'system', 'configure', 'database', 'process'];
    protected static $instances = [];
    /**
     * @var string Current step
     */
    public $step;

    public $lastStep;

    /**
     * @var array List of errors
     */
    public $errors = [];
    public $controller;
    /**
     * @var InstallSession
     */
    public $session;
    /**
     * @var InstallLanguages
     */
    public $language;
    /**
     * @var bool If false, disable next button access
     */
    public $nextButton = true;
    /**
     * @var bool If false, disable previous button access
     */
    public $previousButton = true;
    /**
     * @var InstallAbstractModel
     */
    public $model;
    protected $phone;
    /**
     * @var array Magic vars
     */
    protected $vars = [];

    /**
     * InstallControllerHttp constructor.
     *
     * @param string $step
     *
     * @since 1.0.0
     */
    final public function __construct($step)
    {
        $this->step = $step;
        $this->session = InstallSession::getInstance();

        // Set current language
        $this->language = InstallLanguages::getInstance();
        $detectLanguage = $this->language->detectLanguage();
        if (isset($this->session->lang)) {
            $lang = $this->session->lang;
        } else {
            $lang = (isset($detectLanguage['primarytag'])) ? $detectLanguage['primarytag'] : false;
        }

        if (!in_array($lang, $this->language->getIsoList())) {
            $lang = 'en';
        }
        $this->language->setLanguage($lang);

        $this->init();
    }

    /**
     * @since 1.0.0
     */
    public function init()
    {
    }

    /**
     * @throws PrestashopInstallerException
     *
     * @since 1.0.0
     */
    final public static function execute()
    {
        $session = InstallSession::getInstance();
        if (!$session->lastStep || $session->lastStep == 'welcome') {
            Tools::generateIndex();
        }

        // Include all controllers
        foreach (self::$steps as $step) {
            if (!file_exists(_PS_INSTALL_CONTROLLERS_PATH_.'http/'.$step.'.php')) {
                throw new PrestashopInstallerException("Controller file 'http/{$step}.php' not found");
            }

            require_once _PS_INSTALL_CONTROLLERS_PATH_.'http/'.$step.'.php';
            $classname = 'InstallControllerHttp'.$step;
            self::$instances[$step] = new $classname($step);
        }

        if (!$session->lastStep || !in_array($session->lastStep, self::$steps)) {
            $session->lastStep = self::$steps[0];
        }

        // Set timezone
        if ($session->shopTimezone) {
            @date_default_timezone_set($session->shopTimezone);
        }

        // Get current step (check first if step is changed, then take it from session)
        if (Tools::getValue('step')) {
            $currentStep = Tools::getValue('step');
            $session->step = $currentStep;
        } else {
            $currentStep = (isset($session->step)) ? $session->step : self::$steps[0];
        }

        if (!in_array($currentStep, self::$steps)) {
            $currentStep = self::$steps[0];
        }

        // Validate all steps until current step. If a step is not valid, use it as current step.
        foreach (self::$steps as $checkStep) {
            // Do not validate current step
            if ($checkStep == $currentStep) {
                break;
            }

            if (!self::$instances[$checkStep]->validate()) {
                $currentStep = $checkStep;
                $session->step = $currentStep;
                $session->lastStep = $currentStep;
                break;
            }
        }

        // Submit form to go to next step
        if (Tools::getValue('submitNext')) {
            self::$instances[$currentStep]->processNextStep();

            // If current step is validated, let's go to next step
            if (self::$instances[$currentStep]->validate()) {
                $currentStep = self::$instances[$currentStep]->findNextStep();
            }
            $session->step = $currentStep;

            // Change last step
            if (self::getStepOffset($currentStep) > self::getStepOffset($session->lastStep)) {
                $session->lastStep = $currentStep;
            }
        } elseif (Tools::getValue('submitPrevious') && $currentStep != self::$steps[0]) {
            // Go to previous step
            $currentStep = self::$instances[$currentStep]->findPreviousStep($currentStep);
            $session->step = $currentStep;
        }

        self::$instances[$currentStep]->process();
        self::$instances[$currentStep]->display();
    }

    /**
     * Find offset of a step by name
     *
     * @param string $step Step name
     *
     * @return int
     *
     * @since 1.0.0
     */
    public static function getStepOffset($step)
    {
        static $flip = null;

        if (is_null($flip)) {
            $flip = array_flip(self::$steps);
        }

        return $flip[$step];
    }

    /**
     * Process form to go to next step
     */
    abstract public function processNextStep();

    /**
     * Validate current step
     */
    abstract public function validate();

    /**
     * Display current step view
     */
    abstract public function display();

    /**
     * @since 1.0.0
     */
    public function process()
    {
    }

    /**
     * Get steps list
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function getSteps()
    {
        return self::$steps;
    }

    /**
     * Make a HTTP redirection to a step
     *
     * @param string $step
     */
    public function redirect($step)
    {
        header('location: index.php?step='.$step);
        exit;
    }

    /**
     * Get translated string
     *
     * @param string $str String to translate
     * @param ... All other params will be used with sprintf
     *
     * @return string
     */
    public function l($str)
    {
        $args = func_get_args();

        return call_user_func_array([$this->language, 'l'], $args);
    }

    /**
     * Find previous step
     *
     * @param string $step
     */
    public function findPreviousStep()
    {
        return (isset(self::$steps[$this->getStepOffset($this->step) - 1])) ? self::$steps[$this->getStepOffset($this->step) - 1] : false;
    }

    /**
     * Find next step
     *
     * @param string $step
     */
    public function findNextStep()
    {
        $nextStep = (isset(self::$steps[$this->getStepOffset($this->step) + 1])) ? self::$steps[$this->getStepOffset($this->step) + 1] : false;
        if ($nextStep == 'system' && self::$instances[$nextStep]->validate()) {
            $nextStep = self::$instances[$nextStep]->findNextStep();
        }

        return $nextStep;
    }

    /**
     * Check if current step is first step in list of steps
     *
     * @return bool
     */
    public function isFirstStep()
    {
        return self::getStepOffset($this->step) == 0;
    }

    /**
     * Check if current step is last step in list of steps
     *
     * @return bool
     */
    public function isLastStep()
    {
        return self::getStepOffset($this->step) == (count(self::$steps) - 1);
    }

    /**
     * Check is given step is already finished
     *
     * @param string $step
     *
     * @return bool
     */
    public function isStepFinished($step)
    {
        return self::getStepOffset($step) < self::getStepOffset($this->getLastStep());
    }

    /**
     * @return mixed|null
     *
     * @since 1.0.0
     */
    public function getLastStep()
    {
        return $this->session->lastStep;
    }

    /**
     * Get telephone used for this language
     *
     * @return string
     */
    public function getPhone()
    {
        return '';
    }

    /**
     * Get link to documentation for this language
     *
     * Enter description here ...
     */
    public function getDocumentationLink()
    {
        return $this->language->getInformation('documentation');
    }

    /**
     * Get link to tailored help for this language
     *
     * Enter description here ...
     */
    public function getTailoredHelp()
    {
        return $this->language->getInformation('tailored_help');
    }

    /**
     * Get link to forum for this language
     *
     * Enter description here ...
     */
    public function getForumLink()
    {
        return $this->language->getInformation('forum');
    }

    /**
     * Get link to blog for this language
     *
     * Enter description here ...
     */
    public function getBlogLink()
    {
        return $this->language->getInformation('blog');
    }

    /**
     * Get link to support for this language
     *
     * Enter description here ...
     */
    public function getSupportLink()
    {
        return $this->language->getInformation('support');
    }

    /**
     * Send AJAX response in JSON format {success: bool, message: string}
     *
     * @param bool  $success
     * @param array $message Messages array
     *
     * @since 1.0.0
     */
    public function ajaxJsonAnswer($success, $message = [])
    {
        if (!$success && empty($message)) {
            $message = print_r(@error_get_last(), true);
        }
        die(
        json_encode(
            [
                'success' => (bool) $success,
                'message' => $message,
                // 'memory' => round(memory_get_peak_usage()/1024/1024, 2).' Mo',
            ]
        )
        );
    }

    /**
     * Display a template
     *
     * @param string $template  Template name
     * @param bool   $getOutput Is true, return template html
     *
     * @param null   $path
     *
     * @return string
     * @throws PrestashopInstallerException
     * @since 1.0.0
     */
    public function displayTemplate($template, $getOutput = false, $path = null)
    {
        if (!$path) {
            $path = _TB_INSTALL_PATH_.'theme/views/';
        }

        if (!file_exists($path.$template.'.phtml')) {
            throw new PrestashopInstallerException("Template '{$template}.phtml' not found");
        }

        if ($getOutput) {
            ob_start();
        }

        include($path.$template.'.phtml');

        if ($getOutput) {
            $content = ob_get_contents();
            if (ob_get_level() && ob_get_length() > 0) {
                ob_end_clean();
            }

            return $content;
        }

        return '';
    }
}
