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
 * Class WebserviceOutputJSON
 *
 * @since 1.0.0
 */
class WebserviceOutputJSONCore implements WebserviceOutputInterface
{
    // @codingStandardsIgnoreStart
    public $docUrl = '';
    public $languages = [];
    protected $wsUrl;
    protected $schemaToDisplay;

    /**
     * Current entity
     */
    protected $currentEntity;

    /**
     * Current association
     */
    protected $currentAssociatedEntity;

    /**
     * Json content
     */
    protected $content = [];
    // @codingStandardsIgnoreEnd

    /**
     * WebserviceOutputJSON constructor.
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
        return 'application/json';
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
        $this->content['errors'][] = ['code' => $code, 'message' => $message];

        return '';
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
        $isAssociation = (isset($field['is_association']) && $field['is_association'] == true);

        if (is_array($field['value'])) {
            $tmp = [];
            foreach ($this->languages as $idLang) {
                $tmp[] = ['id' => $idLang, 'value' => $field['value'][$idLang]];
            }
            if (count($tmp) == 1) {
                $field['value'] = $tmp[0]['value'];
            } else {
                $field['value'] = $tmp;
            }
        }
        // Case 1 : fields of the current entity (not an association)
        if (!$isAssociation) {
            $this->currentEntity[$field['sqlId']] = $field['value'];
        } else { // Case 2 : fields of an associated entity to the current one
            $this->currentAssociatedEntity[] = ['name' => $field['entities_name'], 'key' => $field['sqlId'], 'value' => $field['value']];
        }

        return '';
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
        // api ?
        static $isAPICall = false;
        if ($nodeName == 'api' && ($isAPICall == false)) {
            $isAPICall = true;
        }
        if ($isAPICall && !in_array($nodeName, ['description', 'schema', 'api'])) {
            $this->content[] = $nodeName;
        }
        if (isset($moreAttr, $moreAttr['id'])) {
            $this->content[$params['objectsNodeName']][] = ['id' => $moreAttr['id']];
        }

        return '';
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
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function renderNodeFooter($nodeName, $params)
    {
        if (isset($params['objectNodeName']) && $params['objectNodeName'] == $nodeName) {
            if (array_key_exists('display', $_GET)) {
                $this->content[$params['objectsNodeName']][] = $this->currentEntity;
            } else {
                $this->content[$params['objectNodeName']] = $this->currentEntity;
            }
            $this->currentEntity = [];
        }
        if (count($this->currentAssociatedEntity)) {
            $current = [];
            foreach ($this->currentAssociatedEntity as $element) {
                $current[$element['key']] = $element['value'];
            }
            //$this->currentEntity['associations'][$element['name']][][$element['key']] = $element['value'];
            $this->currentEntity['associations'][$element['name']][] = $current;
            $this->currentAssociatedEntity = [];
        }
    }

    /**
     * @param $content
     *
     * @return mixed|string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function overrideContent($content)
    {
        $options = 0;
        if (Tools::getValue('pretty') === 'true') {
            $options += JSON_PRETTY_PRINT;
        }

        $content = '';
        $content .= json_encode($this->content, $options);
        $content = preg_replace_callback(
            "/\\\\u([a-f0-9]{4})/",
            function () {
                return iconv('UCS-4LE', 'UTF-8', pack('V', hexdec('U$1')));
            },
            $content
        );

        return $content;
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
    public function renderAssociationWrapperHeader()
    {
        return '';
    }

    /**
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function renderAssociationWrapperFooter()
    {
        return '';
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
        return '';
    }

    /**
     * @param $obj
     * @param $params
     * @param $assocName
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function renderAssociationFooter($obj, $params, $assocName)
    {
        return;
    }

    /**
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function renderErrorsHeader()
    {
        return '';
    }

    /**
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function renderErrorsFooter()
    {
        return '';
    }

    /**
     * @param $field
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function renderAssociationField($field)
    {
        return '';
    }

    /**
     * @param $field
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function renderi18nField($field)
    {
        return '';
    }
}
