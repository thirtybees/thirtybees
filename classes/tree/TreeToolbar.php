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
class TreeToolbarCore implements ITreeToolbarCore
{
    const DEFAULT_TEMPLATE_DIRECTORY = 'helpers/tree';
    const DEFAULT_TEMPLATE = 'tree_toolbar.tpl';

    /**
     * @var ITreeToolbarButtonCore[]
     */
    protected $_actions;

    /**
     * @var Context
     */
    protected $_context;

    /**
     * @var array
     */
    protected $_data;

    /**
     * @var string
     */
    protected $_template;

    /**
     * @var string
     */
    protected $_template_directory;

    /**
     * @return string
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function __toString()
    {
        return $this->render();
    }

    /**
     * @param ITreeToolbarButtonCore[] $actions
     *
     * @return static
     *
     * @throws PrestaShopException
     */
    public function setActions($actions)
    {
        if (!is_array($actions) && !$actions instanceof Traversable) {
            throw new PrestaShopException('Action value must be an traversable array');
        }

        foreach ($actions as $action) {
            $this->addAction($action);
        }

        return $this;
    }

    /**
     * @return ITreeToolbarButtonCore[]
     */
    public function getActions()
    {
        if (!isset($this->_actions)) {
            $this->_actions = [];
        }

        return $this->_actions;
    }

    /**
     * @param Context $value
     *
     * @return static
     */
    public function setContext($value)
    {
        $this->_context = $value;

        return $this;
    }

    /**
     * @return Context
     */
    public function getContext()
    {
        if (!isset($this->_context)) {
            $this->_context = Context::getContext();
        }

        return $this->_context;
    }

    /**
     * @param array $value
     *
     * @return static
     * @throws PrestaShopException
     */
    public function setData($value)
    {
        if (!is_array($value) && !$value instanceof Traversable) {
            throw new PrestaShopException('Data value must be an traversable array');
        }

        $this->_data = $value;

        return $this;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->_data;
    }

    /**
     * @param string $value
     *
     * @return static
     */
    public function setTemplate($value)
    {
        $this->_template = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getTemplate()
    {
        if (!isset($this->_template)) {
            $this->setTemplate(static::DEFAULT_TEMPLATE);
        }

        return $this->_template;
    }

    /**
     * @param string $value
     *
     * @return static
     */
    public function setTemplateDirectory($value)
    {
        $this->_template_directory = $this->_normalizeDirectory($value);

        return $this;
    }

    /**
     * @return string
     */
    public function getTemplateDirectory()
    {
        if (!isset($this->_template_directory)) {
            $this->_template_directory = $this->_normalizeDirectory(
                static::DEFAULT_TEMPLATE_DIRECTORY
            );
        }

        return $this->_template_directory;
    }

    /**
     * @param string $template
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    public function getTemplateFile($template)
    {
        if (preg_match_all('/((?:^|[A-Z])[a-z]+)/', get_class($this->getContext()->controller), $matches) !== false) {
            $controllerName = strtolower($matches[0][1]);
        }

        if ($this->getContext()->controller instanceof ModuleAdminController && file_exists(
                $this->_normalizeDirectory(
                    $this->getContext()->controller->getTemplatePath()
                ).$this->getTemplateDirectory().$template
            )
        ) {
            return $this->_normalizeDirectory($this->getContext()->controller->getTemplatePath())
                .$this->getTemplateDirectory().$template;
        } elseif ($this->getContext()->controller instanceof AdminController && isset($controllerName)
            && file_exists(
                $this->_normalizeDirectory($this->getContext()->smarty->getTemplateDir(0)).'controllers'
                .DIRECTORY_SEPARATOR.$controllerName.DIRECTORY_SEPARATOR.$this->getTemplateDirectory().$template
            )
        ) {
            return $this->_normalizeDirectory($this->getContext()->smarty->getTemplateDir(0)).'controllers'
                .DIRECTORY_SEPARATOR.$controllerName.DIRECTORY_SEPARATOR.$this->getTemplateDirectory().$template;
        } elseif (file_exists(
            $this->_normalizeDirectory($this->getContext()->smarty->getTemplateDir(1))
            .$this->getTemplateDirectory().$template
        )) {
            return $this->_normalizeDirectory($this->getContext()->smarty->getTemplateDir(1))
                .$this->getTemplateDirectory().$template;
        } elseif (file_exists(
            $this->_normalizeDirectory($this->getContext()->smarty->getTemplateDir(0))
            .$this->getTemplateDirectory().$template
        )) {
            return $this->_normalizeDirectory($this->getContext()->smarty->getTemplateDir(0))
                .$this->getTemplateDirectory().$template;
        } else {
            return $this->getTemplateDirectory().$template;
        }
    }

    /**
     * @param ITreeToolbarButtonCore $action
     *
     * @return static
     * @throws PrestaShopException
     */
    public function addAction($action)
    {
        if (!is_object($action)) {
            throw new PrestaShopException('Action must be a class object');
        }

        $reflection = new ReflectionClass($action);

        if (!$reflection->implementsInterface('ITreeToolbarButtonCore')) {
            throw new PrestaShopException('Action class must implements ITreeToolbarButtonCore interface');
        }

        if (!isset($this->_actions)) {
            $this->_actions = [];
        }

        if (isset($this->_template_directory)) {
            $action->setTemplateDirectory($this->getTemplateDirectory());
        }

        $this->_actions[] = $action;

        return $this;
    }

    /**
     * @return static
     */
    public function removeActions()
    {
        $this->_actions = null;

        return $this;
    }

    /**
     * @return string
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function render()
    {
        foreach ($this->getActions() as $action) {
            $action->setAttribute('data', $this->getData());
        }

        return $this->getContext()->smarty->createTemplate(
            $this->getTemplateFile($this->getTemplate()),
            $this->getContext()->smarty
        )->assign('actions', $this->getActions())->fetch();
    }

    /**
     * @param string $directory
     *
     * @return string
     *
     * @deprecated 2.0.0
     */
    protected function _normalizeDirectory($directory)
    {
        $last = $directory[strlen($directory) - 1];

        if (in_array($last, ['/', '\\'])) {
            $directory[strlen($directory) - 1] = DIRECTORY_SEPARATOR;

            return $directory;
        }

        $directory .= DIRECTORY_SEPARATOR;

        return $directory;
    }
}
