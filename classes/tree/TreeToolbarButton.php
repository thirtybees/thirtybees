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
 * Class TreeToolbarButtonCore
 */
abstract class TreeToolbarButtonCore
{
    const DEFAULT_TEMPLATE_DIRECTORY = 'helpers/tree';

    /**
     * @var array
     */
    protected $_attributes;

    /**
     * @var Context
     */
    private $_context;

    /**
     * @var string
     */
    protected $_template;

    /**
     * @var string
     */
    protected $_template_directory;

    /**
     * TreeToolbarButtonCore constructor.
     *
     * @param string $label
     * @param int|null $id
     * @param string|null $name
     * @param string|null $class
     */
    public function __construct($label, $id = null, $name = null, $class = null)
    {
        $this->setLabel($label);
        $this->setId($id);
        $this->setName($name);
        $this->setClass($class);
    }

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
     * @param string $name
     * @param mixed $value
     *
     * @return static
     */
    public function setAttribute($name, $value)
    {
        if (!isset($this->_attributes)) {
            $this->_attributes = [];
        }

        $this->_attributes[$name] = $value;

        return $this;
    }

    /**
     * @param string $name
     *
     * @return mixed|null
     */
    public function getAttribute($name)
    {
        return $this->hasAttribute($name) ? $this->_attributes[$name] : null;
    }

    /**
     * @param array $value
     *
     * @return static
     * @throws PrestaShopException
     */
    public function setAttributes($value)
    {
        if (!is_array($value) && !$value instanceof Traversable) {
            throw new PrestaShopException('Data value must be an traversable array');
        }

        $this->_attributes = $value;

        return $this;
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        if (!isset($this->_attributes)) {
            $this->_attributes = [];
        }

        return $this->_attributes;
    }

    /**
     * @param string $value
     *
     * @return TreeToolbarButtonCore
     */
    public function setClass($value)
    {
        return $this->setAttribute('class', $value);
    }

    /**
     * @return string|null
     */
    public function getClass()
    {
        return $this->getAttribute('class');
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
     * @param string|int $value
     *
     * @return TreeToolbarButtonCore
     */
    public function setId($value)
    {
        return $this->setAttribute('id', $value);
    }

    /**
     * @return string|int|null
     */
    public function getId()
    {
        return $this->getAttribute('id');
    }

    /**
     * @param string $value
     *
     * @return TreeToolbarButtonCore
     */
    public function setLabel($value)
    {
        return $this->setAttribute('label', $value);
    }

    /**
     * @return string|null
     */
    public function getLabel()
    {
        return $this->getAttribute('label');
    }

    /**
     * @param string $value
     *
     * @return TreeToolbarButtonCore
     */
    public function setName($value)
    {
        return $this->setAttribute('name', $value);
    }

    /**
     * @return string|null
     */
    public function getName()
    {
        return $this->getAttribute('name');
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
            $this->_template_directory = $this->_normalizeDirectory(static::DEFAULT_TEMPLATE_DIRECTORY);
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
     * @param string $name
     *
     * @return bool
     */
    public function hasAttribute($name)
    {
        return (isset($this->_attributes)
            && array_key_exists($name, $this->_attributes));
    }

    /**
     * @return string
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function render()
    {
        return $this->getContext()->smarty->createTemplate(
            $this->getTemplateFile($this->getTemplate()),
            $this->getContext()->smarty
        )->assign($this->getAttributes())->fetch();
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
