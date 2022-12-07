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
 * Class HelperUploaderCore
 */
class HelperUploaderCore extends Uploader
{
    const DEFAULT_TEMPLATE_DIRECTORY = 'helpers/uploader';
    const DEFAULT_TEMPLATE           = 'simple.tpl';
    const DEFAULT_AJAX_TEMPLATE      = 'ajax.tpl';

    const TYPE_IMAGE                 = 'image';
    const TYPE_FILE                  = 'file';

    /**
     * @var Context
     */
    private $_context;

    /**
     * @var string
     */
    private $_drop_zone;

    /**
     * @var string
     */
    private $_id;

    /**
     * @var array
     */
    private $_files;

    /**
     * @var string
     */
    private $_name;

    /**
     * @var int
     */
    private $_max_files;

    /**
     * @var bool
     */
    private $_multiple;

    /**
     * @var string
     */
    protected $_template;

    /**
     * @var string
     */
    private $_template_directory;

    /**
     * @var string
     */
    private $_title;

    /**
     * @var string
     */
    private $_url;

    /**
     * @var bool
     */
    private $_use_ajax;

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
     * @param string $value
     *
     * @return static
     */
    public function setDropZone($value)
    {
        $this->_drop_zone = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getDropZone()
    {
        if (!isset($this->_drop_zone)) {
            $this->setDropZone("$('#".$this->getId()."-add-button')");
        }

        return $this->_drop_zone;
    }

    /**
     * @param int $value
     *
     * @return static
     */
    public function setId($value)
    {
        $this->_id = (string) $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getId()
    {
        if (!isset($this->_id) || trim($this->_id) === '') {
            $this->_id = $this->getName();
        }

        return $this->_id;
    }

    /**
     * @param array[] $value
     *
     * @return static
     */
    public function setFiles($value)
    {
        $this->_files = $value;

        return $this;
    }

    /**
     * @return array[]
     */
    public function getFiles()
    {
        if (!isset($this->_files)) {
            $this->_files = [];
        }

        return $this->_files;
    }

    /**
     * @param int $value
     *
     * @return static
     */
    public function setMaxFiles($value)
    {
        $this->_max_files = isset($value) ? intval($value) : $value;

        return $this;
    }

    /**
     * @return int
     */
    public function getMaxFiles()
    {
        return $this->_max_files;
    }

    /**
     * @param bool $value
     *
     * @return static
     */
    public function setMultiple($value)
    {
        $this->_multiple = (bool) $value;

        return $this;
    }

    /**
     * @param string $value
     *
     * @return static
     */
    public function setName($value)
    {
        $this->_name = (string) $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * @param int $value
     *
     * @return static
     */
    public function setPostMaxSize($value)
    {
        $this->setMaxSize($value);

        return $this;
    }

    /**
     * @return int
     *
     * @deprecated 1.4.0 Not used anymore
     */
    public function getPostMaxSize()
    {
        return $this->getMaxSize();
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
        $this->_template_directory = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getTemplateDirectory()
    {
        if (!isset($this->_template_directory)) {
            $this->_template_directory = static::DEFAULT_TEMPLATE_DIRECTORY;
        }

        return $this->_normalizeDirectory($this->_template_directory);
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

        if ($this->getContext()->controller instanceof ModuleAdminController &&
            file_exists($this->_normalizeDirectory($this->getContext()->controller->getTemplatePath($template)).$this->getTemplateDirectory().$template)) {
            return $this->_normalizeDirectory($this->getContext()->controller->getTemplatePath($template)).$this->getTemplateDirectory().$template;
        } elseif ($this->getContext()->controller instanceof AdminController && isset($controllerName)
            && file_exists($this->_normalizeDirectory($this->getContext()->smarty->getTemplateDir(0)).'controllers'.DIRECTORY_SEPARATOR.$controllerName.DIRECTORY_SEPARATOR.$this->getTemplateDirectory().$template)) {
            return $this->_normalizeDirectory($this->getContext()->smarty->getTemplateDir(0)).'controllers'.DIRECTORY_SEPARATOR.$controllerName.DIRECTORY_SEPARATOR.$this->getTemplateDirectory().$template;
        } elseif (file_exists($this->_normalizeDirectory($this->getContext()->smarty->getTemplateDir(1)).$this->getTemplateDirectory().$template)) {
            return $this->_normalizeDirectory($this->getContext()->smarty->getTemplateDir(1)).$this->getTemplateDirectory().$template;
        } elseif (file_exists($this->_normalizeDirectory($this->getContext()->smarty->getTemplateDir(0)).$this->getTemplateDirectory().$template)) {
            return $this->_normalizeDirectory($this->getContext()->smarty->getTemplateDir(0)).$this->getTemplateDirectory().$template;
        } else {
            return $this->getTemplateDirectory().$template;
        }
    }

    /**
     * @param string $value
     *
     * @return static
     */
    public function setTitle($value)
    {
        $this->_title = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->_title;
    }

    /**
     * @param string $value
     *
     * @return static
     */
    public function setUrl($value)
    {
        $this->_url = (string) $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->_url;
    }

    /**
     * @param bool $value
     *
     * @return static
     */
    public function setUseAjax($value)
    {
        $this->_use_ajax = (bool) $value;

        return $this;
    }

    /**
     * @return bool
     */
    public function isMultiple()
    {
        return (isset($this->_multiple) && $this->_multiple);
    }

    /**
     * @return string
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function render()
    {
        $adminWebpath = str_ireplace(_PS_CORE_DIR_, '', _PS_ADMIN_DIR_);
        $adminWebpath = preg_replace('/^'.preg_quote(DIRECTORY_SEPARATOR, '/').'/', '', $adminWebpath);
        $boTheme = ((Validate::isLoadedObject($this->getContext()->employee)
            && $this->getContext()->employee->bo_theme) ? $this->getContext()->employee->bo_theme : 'default');

        if (!file_exists(_PS_BO_ALL_THEMES_DIR_.$boTheme.DIRECTORY_SEPARATOR.'template')) {
            $boTheme = 'default';
        }

        $this->getContext()->controller->addJs(__PS_BASE_URI__.$adminWebpath.'/themes/'.$boTheme.'/js/jquery.iframe-transport.js');
        $this->getContext()->controller->addJs(__PS_BASE_URI__.$adminWebpath.'/themes/'.$boTheme.'/js/jquery.fileupload.js');
        $this->getContext()->controller->addJs(__PS_BASE_URI__.$adminWebpath.'/themes/'.$boTheme.'/js/jquery.fileupload-process.js');
        $this->getContext()->controller->addJs(__PS_BASE_URI__.$adminWebpath.'/themes/'.$boTheme.'/js/jquery.fileupload-validate.js');
        $this->getContext()->controller->addJs(__PS_BASE_URI__.'js/vendor/spin.js');
        $this->getContext()->controller->addJs(__PS_BASE_URI__.'js/vendor/ladda.js');

        if ($this->useAjax() && !isset($this->_template)) {
            $this->setTemplate(static::DEFAULT_AJAX_TEMPLATE);
        }

        $template = $this->getContext()->smarty->createTemplate(
            $this->getTemplateFile($this->getTemplate()),
            $this->getContext()->smarty
        );

        $template->assign(
            [
                'id'            => $this->getId(),
                'name'          => $this->getName(),
                'url'           => $this->getUrl(),
                'multiple'      => $this->isMultiple(),
                'files'         => $this->getFiles(),
                'title'         => $this->getTitle(),
                'max_files'     => $this->getMaxFiles(),
                'post_max_size' => $this->getPostMaxSizeBytes(),
                'drop_zone'     => $this->getDropZone(),
            ]
        );

        return $template->fetch();
    }

    /**
     * @return bool
     */
    public function useAjax()
    {
        return (isset($this->_use_ajax) && $this->_use_ajax);
    }
}
