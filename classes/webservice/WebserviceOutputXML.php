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
 * Class WebserviceOutputXMLCore
 *
 * @since 1.0.0
 */
class WebserviceOutputXMLCore implements WebserviceOutputInterface
{
    public $docUrl = '';
    public $languages = [];
    protected $wsUrl;
    protected $schemaToDisplay;

    /**
     * @param $schema
     *
     * @return $this
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function setSchemaToDisplay($schema)
    {
        if (is_string($schema)) {
            $this->schemaToDisplay = $schema;
        }

        return $this;
    }

    /**
     * @return mixed
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getSchemaToDisplay()
    {
        return $this->schemaToDisplay;
    }

    /**
     * @param $url
     *
     * @return $this
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function setWsUrl($url)
    {
        $this->wsUrl = $url;

        return $this;
    }

    /**
     * @return mixed
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getWsUrl()
    {
        return $this->wsUrl;
    }

    /**
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getContentType()
    {
        return 'text/xml';
    }

    /**
     * WebserviceOutputXMLCore constructor.
     *
     * @param array $languages
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function __construct($languages = [])
    {
        $this->languages = $languages;
    }

    /**
     * @param $languages
     *
     * @return $this
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function setLanguages($languages)
    {
        $this->languages = $languages;

        return $this;
    }

    /**
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function renderErrorsHeader()
    {
        return '<errors>'."\n";
    }

    /**
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function renderErrorsFooter()
    {
        return '</errors>'."\n";
    }

    /**
     * @param      $message
     * @param null $code
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function renderErrors($message, $code = null)
    {
        $strOutput = '<error>'."\n";
        if ($code !== null) {
            $strOutput .= '<code><![CDATA['.$code.']]></code>'."\n";
        }
        $strOutput .= '<message><![CDATA['.$message.']]></message>'."\n";
        $strOutput .= '</error>'."\n";

        return $strOutput;
    }

    /**
     * @param $field
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function renderField($field)
    {
        $ret = '';
        $nodeContent = '';
        $ret .= '<'.$field['sqlId'];
        // display i18n fields
        if (isset($field['i18n']) && $field['i18n']) {
            foreach ($this->languages as $language) {
                $more_attr = '';
                if (isset($field['synopsis_details']) || (isset($field['value']) && is_array($field['value']))) {
                    $more_attr .= ' xlink:href="'.$this->getWsUrl().'languages/'.$language.'"';
                    if (isset($field['synopsis_details']) && $this->schemaToDisplay != 'blank') {
                        $more_attr .= ' format="isUnsignedId" ';
                    }
                }
                $nodeContent .= '<language id="'.$language.'"'.$more_attr.'>';
                if (isset($field['value']) && is_array($field['value']) && isset($field['value'][$language])) {
                    $nodeContent .= '<![CDATA['.$field['value'][$language].']]>';
                }
                $nodeContent .= '</language>';
            }
        } // display not i18n fields value
        else {
            if (array_key_exists('xlink_resource', $field) && $this->schemaToDisplay != 'blank') {
                if (!is_array($field['xlink_resource'])) {
                    $ret .= ' xlink:href="'.$this->getWsUrl().$field['xlink_resource'].'/'.$field['value'].'"';
                } else {
                    $ret .= ' xlink:href="'.$this->getWsUrl().$field['xlink_resource']['resourceName'].'/'.
                        (isset($field['xlink_resource']['subResourceName']) ? $field['xlink_resource']['subResourceName'].'/'.$field['object_id'].'/' : '').$field['value'].'"';
                }
            }

            if (isset($field['getter']) && $this->schemaToDisplay != 'blank') {
                $ret .= ' notFilterable="true"';
            }

            if (isset($field['setter']) && $field['setter'] == false && $this->schemaToDisplay == 'synopsis') {
                $ret .= ' read_only="true"';
            }

            if ($field['value'] != '') {
                $nodeContent .= '<![CDATA['.$field['value'].']]>';
            }
        }

        if (isset($field['encode'])) {
            $ret .= ' encode="'.$field['encode'].'"';
        }

        if (isset($field['synopsis_details']) && !empty($field['synopsis_details']) && $this->schemaToDisplay !== 'blank') {
            foreach ($field['synopsis_details'] as $name => $detail) {
                $ret .= ' '.$name.'="'.(is_array($detail) ? implode(' ', $detail) : $detail).'"';
            }
        }
        $ret .= '>';
        $ret .= $nodeContent;
        $ret .= '</'.$field['sqlId'].'>'."\n";

        return $ret;
    }

    /**
     * @param      $nodeName
     * @param      $params
     * @param null $moreAttr
     * @param bool $hasChild
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function renderNodeHeader($nodeName, $params, $moreAttr = null, $hasChild = true)
    {
        $stringAttr = '';
        if (is_array($moreAttr)) {
            foreach ($moreAttr as $key => $attr) {
                if ($key === 'xlink_resource') {
                    $stringAttr .= ' xlink:href="'.$attr.'"';
                } else {
                    $stringAttr .= ' '.$key.'="'.$attr.'"';
                }
            }
        }
        $end_tag = (!$hasChild) ? '/>' : '>';

        return '<'.$nodeName.$stringAttr.$end_tag."\n";
    }

    /**
     * @param $params
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getNodeName($params)
    {
        $nodeName = '';
        if (isset($params['objectNodeName'])) {
            $nodeName = $params['objectNodeName'];
        }

        return $nodeName;
    }

    /**
     * @param $nodeName
     * @param $params
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function renderNodeFooter($nodeName, $params)
    {
        return '</'.$nodeName.'>'."\n";
    }

    /**
     * @param $content
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function overrideContent($content)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<prestashop xmlns:xlink="http://www.w3.org/1999/xlink">'."\n";
        $xml .= $content;
        $xml .= '</prestashop>'."\n";

        return $xml;
    }

    /**
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function renderAssociationWrapperHeader()
    {
        return '<associations>'."\n";
    }

    /**
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function renderAssociationWrapperFooter()
    {
        return '</associations>'."\n";
    }

    /**
     * @param      $obj
     * @param      $params
     * @param      $assocName
     * @param bool $closedTags
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function renderAssociationHeader($obj, $params, $assocName, $closedTags = false)
    {
        $endTag = ($closedTags) ? '/>' : '>';
        $more = '';
        if ($this->schemaToDisplay != 'blank') {
            if (array_key_exists('setter', $params['associations'][$assocName]) && !$params['associations'][$assocName]['setter']) {
                $more .= ' readOnly="true"';
            }
            $more .= ' nodeType="'.$params['associations'][$assocName]['resource'].'"';
            if (isset($params['associations'][$assocName]['virtual_entity']) && $params['associations'][$assocName]['virtual_entity']) {
                $more .= ' virtualEntity="true"';
            } else {
                if (isset($params['associations'][$assocName]['api'])) {
                    $more .= ' api="'.$params['associations'][$assocName]['api'].'"';
                } else {
                    $more .= ' api="'.$assocName.'"';
                }
            }
        }

        return '<'.$assocName.$more.$endTag."\n";
    }

    /**
     * @param $obj
     * @param $params
     * @param $assocName
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function renderAssociationFooter($obj, $params, $assocName)
    {
        return '</'.$assocName.'>'."\n";
    }
}
