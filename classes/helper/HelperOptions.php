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
 * Use this helper to generate preferences forms, with values stored in the configuration table
 */
class HelperOptionsCore extends Helper
{
    /**
     * @var bool $required
     */
    public $required = false;

    /**
     * @var int $id
     */
    public $id;

    /**
     * HelperOptionsCore constructor.
     */
    public function __construct()
    {
        $this->base_folder = 'helpers/options/';
        $this->base_tpl = 'options.tpl';
        parent::__construct();
    }

    /**
     * Generate a form for options
     *
     * @param array $optionList
     *
     * @return string html
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function generateOptions($optionList)
    {
        $this->tpl = $this->createTemplate($this->base_tpl);
        $tab = Tab::getTab($this->context->language->id, $this->id);
        $languages = Language::getLanguages(false);

        $useMultishop = false;
        $hideMultishopCheckbox = Shop::getTotalShops(false, null) < 2;
        foreach ($optionList as $category => $categoryData) {
            if (!is_array($categoryData)) {
                continue;
            }

            if (!isset($categoryData['image']) && $tab) {
                $categoryData['image'] = (!empty($tab['module']) && file_exists($_SERVER['DOCUMENT_ROOT']._MODULE_DIR_.$tab['module'].'/'.$tab['class_name'].'.gif') ? _MODULE_DIR_.$tab['module'].'/' : '../img/t/').$tab['class_name'].'.gif';
            }

            if (!isset($categoryData['fields'])) {
                $categoryData['fields'] = [];
            }

            $categoryData['hide_multishop_checkbox'] = true;

            if (isset($categoryData['tabs'])) {
                $tabs[$category] = $categoryData['tabs'];
                $tabs[$category]['misc'] = $this->l('Miscellaneous');
            }

            foreach ($categoryData['fields'] as $key => $field) {
                if (empty($field['no_multishop_checkbox']) && !$hideMultishopCheckbox) {
                    $categoryData['hide_multishop_checkbox'] = false;
                }

                // Set field value unless explicitly denied
                if (!isset($field['auto_value']) || $field['auto_value']) {
                    $field['value'] = $this->getOptionValue($key, $field);
                }

                // Check if var is invisible (can't edit it in current shop context), or disable (use default value for multishop)
                $isDisabled = $isInvisible = false;
                if (Shop::isFeatureActive()) {
                    if (isset($field['visibility']) && $field['visibility'] > Shop::getContext()) {
                        $isDisabled = true;
                        $isInvisible = true;
                    } elseif (Shop::getContext() != Shop::CONTEXT_ALL && !Configuration::isOverridenByCurrentContext($key)) {
                        $isDisabled = true;
                    }
                }
                $field['is_disabled'] = $isDisabled;
                $field['is_invisible'] = $isInvisible;

                $field['required'] = $field['required'] ?? $this->required;

                $controller = $this->getController();
                if ($field['type'] === 'color') {
                    $controller->addJqueryPlugin('colorpicker');
                }

                if ($field['type'] === 'textarea' || $field['type'] === 'textareaLang') {
                    $controller->addJqueryPlugin('autosize');
                }

                if ($field['type'] === 'code') {
                    $controller->addJS(_PS_JS_DIR_.'ace/ace.js');
                    $controller->addJS(_PS_JS_DIR_.'ace/ext-language_tools.js');
                    $controller->addJS(_PS_JS_DIR_.'ace/snippets/'.$field['mode'].'.js');
                    $controller->addCSS(_PS_JS_DIR_.'ace/aceinput.css');
                }

                if ($field['type'] == 'tags') {
                    $controller->addJqueryPlugin('tagify');
                }

                if ($field['type'] == 'file') {
                    $uploader = new HelperUploader();
                    $uploader->setId($field['id'] ?? null);
                    $uploader->setName($field['name']);
                    $uploader->setUrl($field['url'] ?? null);
                    $uploader->setMultiple($field['multiple'] ?? false);
                    $uploader->setUseAjax($field['ajax'] ?? false);
                    $uploader->setMaxFiles($field['max_files'] ?? null);

                    if (isset($field['files']) && $field['files']) {
                        $uploader->setFiles($field['files']);
                    } elseif (isset($field['image']) && $field['image']) { // Use for retrocompatibility
                        $uploader->setFiles(
                            [
                                0 => [
                                    'type'       => HelperUploader::TYPE_IMAGE,
                                    'image'      => $field['image'],
                                    'size'       => $field['size'] ?? null,
                                    'delete_url' => $field['delete_url'] ?? null,
                                ],
                            ]
                        );
                    }

                    if (isset($field['file']) && $field['file']) { // Use for retrocompatibility
                        $uploader->setFiles(
                            [
                                0 => [
                                    'type'         => HelperUploader::TYPE_FILE,
                                    'size'         => $field['size'] ?? null,
                                    'delete_url'   => $field['delete_url'] ?? null,
                                    'download_url' => $field['file'],
                                ],
                            ]
                        );
                    }

                    if (isset($field['thumb']) && $field['thumb']) { // Use for retrocompatibility
                        $uploader->setFiles(
                            [
                                0 => [
                                    'type'  => HelperUploader::TYPE_IMAGE,
                                    'image' => '<img src="'.$field['thumb'].'" alt="'.$field['title'].'" title="'.$field['title'].'" />',
                                ],
                            ]
                        );
                    }

                    $uploader->setTitle($field['title'] ?? null);
                    $field['file'] = $uploader->render();
                }

                // Cast options values if specified
                if ($field['type'] == 'select' && isset($field['cast'])) {
                    foreach ($field['list'] as $optionKey => $option) {
                        $field['list'][$optionKey][$field['identifier']] = Tools::castInput($field['cast'], $option[$field['identifier']]);
                    }
                }

                // Fill values for all languages for all lang fields
                if (substr($field['type'], -4) == 'Lang') {
                    foreach ($languages as $language) {
                        if ($field['type'] == 'textLang') {
                            $value = Tools::getValue($key.'_'.$language['id_lang'], Configuration::get($key, $language['id_lang']));
                        } elseif ($field['type'] == 'textareaLang') {
                            $value = Configuration::get($key, $language['id_lang']);
                        } elseif ($field['type'] == 'selectLang') {
                            $value = Configuration::get($key, $language['id_lang']);
                        }
                        $field['languages'][$language['id_lang']] = $value ?? '';
                        if (!is_array($field['value'])) {
                            $field['value'] = [];
                        }
                        $field['value'][$language['id_lang']] = $this->getOptionValue($key.'_'.strtoupper($language['iso_code']), $field);
                    }
                }

                // Multishop default value
                $field['multishop_default'] = false;
                if (Shop::isFeatureActive() && Shop::getContext() != Shop::CONTEXT_ALL && !$isInvisible) {
                    $field['multishop_default'] = true;
                    $useMultishop = true;
                }

                // Assign the modifications back to parent array
                $categoryData['fields'][$key] = $field;

                // Is at least one required field present?
                if (isset($field['required']) && $field['required']) {
                    $categoryData['required_fields'] = true;
                }
            }
            // Assign the modifications back to parent array
            $optionList[$category] = $categoryData;
        }

        $this->tpl->assign(
            [
                'title' => $this->title,
                'toolbar_btn' => $this->toolbar_btn,
                'show_toolbar' => $this->show_toolbar,
                'toolbar_scroll' => $this->toolbar_scroll,
                'current' => $this->currentIndex,
                'table' => $this->table,
                'token' => $this->token,
                'tabs' => $tabs ?? null,
                'option_list' => $optionList,
                'current_id_lang' => $this->context->language->id,
                'languages' => $languages ?? null,
                'currency_left_sign' => $this->context->currency->getSign('left'),
                'currency_right_sign' => $this->context->currency->getSign('right'),
                'use_multishop' => $useMultishop,
            ]
        );

        return parent::generate();
    }

    /**
     * Type = image
     *
     * @param string $key
     * @param array $field
     * @param string $value
     */
    public function displayOptionTypeImage($key, $field, $value)
    {
        echo '<table cellspacing="0" cellpadding="0">';
        echo '<tr>';

        $i = 0;
        foreach ($field['list'] as $theme) {
            echo '<td class="center" style="width: 180px; padding:0px 20px 20px 0px;">';
            echo '<input type="radio" name="'.$key.'" id="'.$key.'_'.$theme['name'].'_on" style="vertical-align: text-bottom;" value="'.$theme['name'].'"'.(_THEME_NAME_ == $theme['name'] ? 'checked="checked"' : '').' />';
            echo '<label class="t" for="'.$key.'_'.$theme['name'].'_on"> '.mb_strtolower($theme['name']).'</label>';
            echo '<br />';
            echo '<label class="t" for="'.$key.'_'.$theme['name'].'_on">';
            echo '<img src="../themes/'.$theme['name'].'/preview.jpg" alt="'.mb_strtolower($theme['name']).'">';
            echo '</label>';
            echo '</td>';
            if (isset($field['max']) && ($i + 1) % $field['max'] == 0) {
                echo '</tr><tr>';
            }
            $i++;
        }
        echo '</tr>';
        echo '</table>';
    }

    /**
     * @param string $key
     * @param array $field
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    public function getOptionValue($key, $field)
    {
        if ($field['type'] === 'code') {
            // don't perform any value sanitization and preprocessing for code fields
            $value = Tools::getValueRaw($key, Configuration::get($key));

            if (isset($field['defaultValue']) && !$value) {
                return $field['defaultValue'];
            }

            return $value;
        }

        $value = Tools::getValue($key, Configuration::get($key));
        if (!Validate::isCleanHtml($value)) {
            $value = Configuration::get($key);
        }

        if (isset($field['defaultValue']) && !$value) {
            $value = $field['defaultValue'];
        }

        return Tools::purifyHTML($value);
    }
}
