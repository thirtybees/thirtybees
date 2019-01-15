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
 * Class WebserviceOutputBuilderCore
 *
 * @since 1.0.0
 */
class WebserviceOutputBuilderCore
{
    /**
     * @var int constant
     */
    const VIEW_LIST = 1;
    const VIEW_DETAILS = 2;

    // @codingStandardsIgnoreStart
    protected $wsUrl;
    protected $output;

    /** @var WebserviceOutputInterface|WebserviceOutputXML|WebserviceOutputJSON */
    public $objectRender;
    protected $wsResource;
    protected $depth = 0;
    protected $schemaToDisplay;
    protected $fieldsToDisplay;
    protected $specificFields = [];
    protected $virtualFields = [];
    protected $statusInt;
    protected $wsParamOverrides;

    protected static $_cache_ws_parameters = [];

    /* Header properties */
    protected $headerParams = [
        'Access-Time'  => 0,
        'X-Powered-By' => 0,
        'PSWS-Version' => 0,
        'Content-Type' => 0,
    ];
    // @codingStandardsIgnoreEnd

    /**
     * @var string Status header sent at return
     */
    protected $status;

    /**
     * WebserviceOutputBuilderCore constructor.
     *
     * @param $wsUrl
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function __construct($wsUrl)
    {
        $this->statusInt = 200;
        $this->status = $_SERVER['SERVER_PROTOCOL'].' 200 OK';
        $this->wsUrl = $wsUrl;
        $this->wsParamOverrides = [];
    }

    /**
     * Set the render object for set the output format.
     * Set the Content-type for the http header.
     *
     * @param WebserviceOutputInterface $objRender
     *
     * @throw   WebserviceException if the object render is not an instance of WebserviceOutputInterface
     *
     * @return WebserviceOutputBuilder
     * @throws WebserviceException
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function setObjectRender(WebserviceOutputInterface $objRender)
    {
        if (!$objRender instanceof WebserviceOutputInterface) {
            throw new WebserviceException('Obj_render param must be an WebserviceOutputInterface object type', [83, 500]);
        }

        $this->objectRender = $objRender;
        $this->objectRender->setWsUrl($this->wsUrl);
        if ($this->objectRender->getContentType()) {
            $this->setHeaderParams('Content-Type', $this->objectRender->getContentType());
        }

        return $this;
    }

    /**
     * getter
     *
     * @return WebserviceOutputInterface
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getObjectRender()
    {
        return $this->objectRender;
    }

    /**
     * Need to have the resource list to get the class name for an entity,
     * To build
     *
     * @param array $resources
     *
     * @return WebserviceOutputBuilder
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function setWsResources($resources)
    {
        $this->wsResource = $resources;

        return $this;
    }

    /**
     * This method return an array with each http header params for a content.
     * This check each required params.
     *
     * If this method is overrided don't forget to check required specific params (for xml etc...)
     *
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function buildHeader()
    {
        $return = [];
        $return[] = $this->status;
        foreach ($this->headerParams as $key => $param) {
            $return[] = trim($key).': '.$param;
        }

        return $return;
    }

    /**
     * @param string $key The normalized key expected for an http response
     * @param string $value
     *
     * @return WebserviceOutputBuilder
     * @throws WebserviceException If the key or the value are corrupted (use Validate::isCleanHtml method)
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function setHeaderParams($key, $value)
    {
        if (!Validate::isCleanHtml($key) || !Validate::isCleanHtml($value)) {
            throw new WebserviceException('the key or your value is corrupted.', [94, 500]);
        }
        $this->headerParams[$key] = $value;

        return $this;
    }

    /**
     * @param null|string $key if null get all header params otherwise the params specified by the key
     *
     * @throw WebserviceException if the key is corrupted (use Validate::isCleanHtml method)
     * @throw WebserviceException if the asked key does'nt exists.
     * @return array|string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getHeaderParams($key = null)
    {
        if (!is_null($key)) {
            if (!Validate::isCleanHtml($key)) {
                throw new WebserviceException('the key you write is a corrupted text.', [95, 500]);
            }
            if (!array_key_exists($key, $this->headerParams)) {
                throw new WebserviceException(sprintf('The key %s does\'nt exist', $key), [96, 500]);
            }
            $return = $this->headerParams[$key];
        } else {
            $return = $this->headerParams;
        }

        return $return;
    }

    /**
     * Delete all Header parameters previously set.
     *
     * @return WebserviceOutputBuilder
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function resetHeaderParams()
    {
        $this->headerParams = [];

        return $this;
    }

    /**
     * @return string the normalized status for http request
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return int
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getStatusInt()
    {
        return $this->statusInt;
    }

    /**
     * Set the return header status
     *
     * @param int $num the Http status code
     *
     * @return void
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function setStatus($num)
    {
        $this->statusInt = (int) $num;
        switch ($num) {
            case 200 :
                $this->status = $_SERVER['SERVER_PROTOCOL'].' 200 OK';
                break;
            case 201 :
                $this->status = $_SERVER['SERVER_PROTOCOL'].' 201 Created';
                break;
            case 204 :
                $this->status = $_SERVER['SERVER_PROTOCOL'].' 204 No Content';
                break;
            case 304 :
                $this->status = $_SERVER['SERVER_PROTOCOL'].' 304 Not Modified';
                break;
            case 400 :
                $this->status = $_SERVER['SERVER_PROTOCOL'].' 400 Bad Request';
                break;
            case 401 :
                $this->status = $_SERVER['SERVER_PROTOCOL'].' 401 Unauthorized';
                break;
            case 403 :
                $this->status = $_SERVER['SERVER_PROTOCOL'].' 403 Forbidden';
                break;
            case 404 :
                $this->status = $_SERVER['SERVER_PROTOCOL'].' 404 Not Found';
                break;
            case 405 :
                $this->status = $_SERVER['SERVER_PROTOCOL'].' 405 Method Not Allowed';
                break;
            case 500 :
                $this->status = $_SERVER['SERVER_PROTOCOL'].' 500 Internal Server Error';
                break;
            case 501 :
                $this->status = $_SERVER['SERVER_PROTOCOL'].' 501 Not Implemented';
                break;
            case 503 :
                $this->status = $_SERVER['SERVER_PROTOCOL'].' 503 Service Unavailable';
                break;
        }
    }

    /**
     * Build errors output using an error array
     *
     * @param array $errors
     *
     * @return string output in the format specified by WebserviceOutputBuilder::objectRender
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getErrors($errors)
    {
        if (!empty($errors)) {
            if (isset($this->objectRender)) {
                $strOutput = $this->objectRender->renderErrorsHeader();
                foreach ($errors as $error) {
                    if (is_array($error)) {
                        $strOutput .= $this->objectRender->renderErrors($error[1], $error[0]);
                    } else {
                        $strOutput .= $this->objectRender->renderErrors($error);
                    }
                }
                $strOutput .= $this->objectRender->renderErrorsFooter();
                $strOutput = $this->objectRender->overrideContent($strOutput);
            } else {
                $strOutput = '<pre>'.print_r($errors, true).'</pre>';
            }
        }

        return $strOutput;
    }

    /**
     * Build the resource list in the output format specified by WebserviceOutputBuilder::objectRender
     *
     * @param $keyPermissions
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getResourcesList($keyPermissions)
    {
        if (is_null($this->wsResource)) {
            throw new WebserviceException('You must set web service resource for get the resources list.', [82, 500]);
        }
        $output = '';
        $moreAttr = ['shopName' => htmlspecialchars(Configuration::get('PS_SHOP_NAME'))];
        $output .= $this->objectRender->renderNodeHeader('api', [], $moreAttr);
        foreach ($this->wsResource as $resourceName => $resource) {
            if (in_array($resourceName, array_keys($keyPermissions))) {
                $moreAttr = [
                    'xlink_resource' => $this->wsUrl.$resourceName,
                    'get'            => (in_array('GET', $keyPermissions[$resourceName]) ? 'true' : 'false'),
                    'put'            => (in_array('PUT', $keyPermissions[$resourceName]) ? 'true' : 'false'),
                    'post'           => (in_array('POST', $keyPermissions[$resourceName]) ? 'true' : 'false'),
                    'delete'         => (in_array('DELETE', $keyPermissions[$resourceName]) ? 'true' : 'false'),
                    'head'           => (in_array('HEAD', $keyPermissions[$resourceName]) ? 'true' : 'false'),
                ];
                $output .= $this->objectRender->renderNodeHeader($resourceName, [], $moreAttr);

                $output .= $this->objectRender->renderNodeHeader('description', [], $moreAttr);
                $output .= $resource['description'];
                $output .= $this->objectRender->renderNodeFooter('description', []);

                if (!isset($resource['specific_management']) || !$resource['specific_management']) {
                    $moreAttrSchema = [
                        'xlink_resource' => $this->wsUrl.$resourceName.'?schema=blank',
                        'type'           => 'blank',
                    ];
                    $output .= $this->objectRender->renderNodeHeader('schema', [], $moreAttrSchema, false);
                    $moreAttrSchema = [
                        'xlink_resource' => $this->wsUrl.$resourceName.'?schema=synopsis',
                        'type'           => 'synopsis',
                    ];
                    $output .= $this->objectRender->renderNodeHeader('schema', [], $moreAttrSchema, false);
                }
                $output .= $this->objectRender->renderNodeFooter($resourceName, []);
            }
        }
        $output .= $this->objectRender->renderNodeFooter('api', []);
        $output = $this->objectRender->overrideContent($output);

        return $output;
    }

    /**
     * @param $wsrObject
     * @param $method
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function registerOverrideWSParameters($wsrObject, $method)
    {
        $this->wsParamOverrides[] = ['object' => $wsrObject, 'method' => $method];
    }

    /**
     * Method is used for each content type
     * Different content types are :
     *        - list of entities,
     *        - tree diagram of entity details (full or minimum),
     *        - schema (synopsis & blank),
     *
     * @param array        $objects         each object created by entity asked
     *
     * @see WebserviceOutputBuilder::executeEntityGetAndHead
     *
     * @param null|string  $schemaToDisplay if null display the entities list or entity details.
     * @param string|array $fieldsToDisplay the fields allow for the output
     * @param int          $depth           depth for the tree diagram output.
     * @param int          $typeOfView      use the 2 constants WebserviceOutputBuilder::VIEW_LIST WebserviceOutputBuilder::VIEW_DETAILS
     *
     * @return string in the output format specified by WebserviceOutputBuilder::objectRender
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getContent($objects, $schemaToDisplay = null, $fieldsToDisplay = 'minimum', $depth = 0, $typeOfView = self::VIEW_LIST, $override = true)
    {
        $this->fieldsToDisplay = $fieldsToDisplay;
        $this->depth = $depth;
        $output = '';

        if ($schemaToDisplay != null) {
            $this->schemaToDisplay = $schemaToDisplay;
            $this->objectRender->setSchemaToDisplay($this->schemaToDisplay);

            // If a shema is asked the view must be an details type
            $typeOfView = static::VIEW_DETAILS;
        }

        $class = get_class($objects['empty']);
        if (!isset(WebserviceOutputBuilder::$_cache_ws_parameters[$class])) {
            WebserviceOutputBuilder::$_cache_ws_parameters[$class] = $objects['empty']->getWebserviceParameters();
        }
        $wsParams = WebserviceOutputBuilder::$_cache_ws_parameters[$class];

        foreach ($this->wsParamOverrides as $p) {
            $object = $p['object'];
            $method = $p['method'];

            $wsParams = $object->$method($wsParams);
        }

        // If a list is asked, need to wrap with a plural node
        if ($typeOfView === static::VIEW_LIST) {
            $output .= $this->setIndent($depth).$this->objectRender->renderNodeHeader($wsParams['objectsNodeName'], $wsParams);
        }

        if (is_null($this->schemaToDisplay)) {
            foreach ($objects as $key => $object) {
                if ($key !== 'empty') {
                    if ($this->fieldsToDisplay === 'minimum') {
                        $output .= $this->renderEntityMinimum($object, $depth);
                    } else {
                        $output .= $this->renderEntity($object, $depth);
                    }
                }
            }
        } else {
            $output .= $this->renderSchema($objects['empty'], $wsParams);
        }

        // If a list is asked, need to wrap with a plural node
        if ($typeOfView === static::VIEW_LIST) {
            $output .= $this->setIndent($depth).$this->objectRender->renderNodeFooter($wsParams['objectsNodeName'], $wsParams);
        }

        if ($override) {
            $output = $this->objectRender->overrideContent($output);
        }

        return $output;
    }

    /**
     * Create the tree diagram with no details
     *
     * @param ObjectModel $object create by the entity
     * @param int         $depth  the depth for the tree diagram
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function renderEntityMinimum($object, $depth)
    {
        $class = get_class($object);
        if (!isset(WebserviceOutputBuilder::$_cache_ws_parameters[$class])) {
            WebserviceOutputBuilder::$_cache_ws_parameters[$class] = $object->getWebserviceParameters();
        }
        $wsParams = WebserviceOutputBuilder::$_cache_ws_parameters[$class];

        $moreAttr['id'] = $object->id;
        $moreAttr['xlink_resource'] = $this->wsUrl.$wsParams['objectsNodeName'].'/'.$object->id;
        $output = $this->setIndent($depth).$this->objectRender->renderNodeHeader($wsParams['objectNodeName'], $wsParams, $moreAttr, false);

        return $output;
    }

    /**
     * Build a schema blank or synopsis
     *
     * @param ObjectModel $object   create by the entity
     * @param array       $wsParams webserviceParams from the entity
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function renderSchema($object, $wsParams)
    {
        $output = $this->objectRender->renderNodeHeader($wsParams['objectNodeName'], $wsParams);
        foreach ($wsParams['fields'] as $fieldName => $field) {
            $output .= $this->renderField($object, $wsParams, $fieldName, $field, 0);
        }
        if (isset($wsParams['associations']) && count($wsParams['associations']) > 0) {
            $this->fieldsToDisplay = 'full';
            $output .= $this->renderAssociations($object, 0, $wsParams['associations'], $wsParams);
        }
        $output .= $this->objectRender->renderNodeFooter($wsParams['objectNodeName'], $wsParams);

        return $output;
    }

    /**
     * Build the entity detail.
     *
     * @param ObjectModel $object create by the entity
     * @param int         $depth  the depth for the tree diagram
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function renderEntity($object, $depth)
    {
        $output = '';

        $class = get_class($object);
        if (!isset(WebserviceOutputBuilder::$_cache_ws_parameters[$class])) {
            WebserviceOutputBuilder::$_cache_ws_parameters[$class] = $object->getWebserviceParameters();
        }
        $wsParams = WebserviceOutputBuilder::$_cache_ws_parameters[$class];

        foreach ($this->wsParamOverrides as $p) {
            $o = $p['object'];
            $method = $p['method'];
            $wsParams = $o->$method($wsParams);
        }
        $output .= $this->setIndent($depth).$this->objectRender->renderNodeHeader($wsParams['objectNodeName'], $wsParams);

        if ($object->id != 0) {
            // This to add virtual Fields for a particular entity.
            $virtualFields = $this->addVirtualFields($wsParams['objectsNodeName'], $object);
            if (!empty($virtualFields)) {
                $wsParams['fields'] = array_merge($wsParams['fields'], $virtualFields);
            }

            foreach ($wsParams['fields'] as $fieldName => $field) {
                if ($this->fieldsToDisplay === 'full' || array_key_exists($fieldName, $this->fieldsToDisplay)) {
                    $field['object_id'] = $object->id;
                    $field['entity_name'] = $wsParams['objectNodeName'];
                    $field['entities_name'] = $wsParams['objectsNodeName'];
                    $output .= $this->renderField($object, $wsParams, $fieldName, $field, $depth);
                }
            }
        }
        $subexists = false;
        if (is_array($this->fieldsToDisplay)) {
            foreach ($this->fieldsToDisplay as $fields) {
                if (is_array($fields)) {
                    $subexists = true;
                }
            }
        }

        if (isset($wsParams['associations'])
            && ($this->fieldsToDisplay == 'full'
                || $subexists)
        ) {
            $output .= $this->renderAssociations($object, $depth, $wsParams['associations'], $wsParams);
        }

        $output .= $this->setIndent($depth).$this->objectRender->renderNodeFooter($wsParams['objectNodeName'], $wsParams);

        return $output;
    }

    /**
     * Build a field and use recursivity depend on the depth parameter.
     *
     * @param ObjectModel $object   create by the entity
     * @param array       $wsParams webserviceParams from the entity
     * @param string      $fieldName
     * @param array       $field
     * @param int         $depth
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function renderField($object, $wsParams, $fieldName, $field, $depth)
    {
        $output = '';
        $showField = true;

        if (isset($wsParams['hidden_fields']) && in_array($fieldName, $wsParams['hidden_fields'])) {
            return;
        }

        if ($this->schemaToDisplay === 'synopsis') {
            $field['synopsis_details'] = $this->getSynopsisDetails($field);
            if ($fieldName === 'id') {
                $showField = false;
            }
        }
        if ($this->schemaToDisplay === 'blank') {
            if (isset($field['setter']) && !$field['setter']) {
                $showField = false;
            }
        }

        // don't set any value for a schema
        if (isset($field['synopsis_details']) || $this->schemaToDisplay === 'blank') {
            $field['value'] = '';
            if (isset($field['xlink_resource'])) {
                unset($field['xlink_resource']);
            }
        } elseif (isset($field['getter']) && $object != null && method_exists($object, $field['getter'])) {
            $field_getter = $field['getter'];
            $field['value'] = $object->$field_getter();
        } elseif (!isset($field['value'])) {
            $field['value'] = $object->$fieldName;
        }

        // this apply specific function for a particular field on a choosen entity
        $field = $this->overrideSpecificField($wsParams['objectsNodeName'], $fieldName, $field, $object, $wsParams);

        // don't display informations for a not existant id
        if (substr($field['sqlId'], 0, 3) == 'id_' && !$field['value']) {
            if ($field['value'] === null) {
                $field['value'] = '';
            }
            // delete the xlink except for schemas
            if (isset($field['xlink_resource']) && is_null($this->schemaToDisplay)) {
                unset($field['xlink_resource']);
            }
        }
        // set "id" for each node name which display the id of the entity
        if ($fieldName === 'id') {
            $field['sqlId'] = 'id';
        }

        // don't display the node id for a synopsis schema
        if ($showField) {
            $output .= $this->setIndent($depth - 1).$this->objectRender->renderField($field);
        }

        return $output;
    }

    /**
     *
     *
     * @param $object
     * @param $depth
     * @param $associations
     * @param $wsParams
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function renderAssociations($object, $depth, $associations, $wsParams)
    {
        $output = $this->objectRender->renderAssociationWrapperHeader();
        foreach ($associations as $assocName => $association) {
            if ($this->fieldsToDisplay == 'full' || is_array($this->fieldsToDisplay) && array_key_exists($assocName, $this->fieldsToDisplay)) {
                $getter = $association['getter'];
                $objectsAssoc = [];

                $fieldsAssoc = [];
                if (isset($association['fields'])) {
                    $fieldsAssoc = $association['fields'];
                }

                $parentDetails = [
                    'object_id'     => $object->id,
                    'entity_name'   => $wsParams['objectNodeName'],
                    'entities_name' => $wsParams['objectsNodeName'],
                ];

                if (is_array($getter)) {
                    $associationResources = call_user_func($getter, $object);
                    if (is_array($associationResources) && !empty($associationResources)) {
                        foreach ($associationResources as $associationResource) {
                            $objectsAssoc[] = $associationResource;
                        }
                    }
                } else {
                    if (method_exists($object, $getter) && is_null($this->schemaToDisplay)) {
                        $associationResources = $object->$getter();
                        if (is_array($associationResources) && !empty($associationResources)) {
                            foreach ($associationResources as $associationResource) {
                                $objectsAssoc[] = $associationResource;
                            }
                        }
                    } else {
                        $objectsAssoc[] = '';
                    }
                }

                $className = null;
                if (isset($this->wsResource[$assocName]['class']) && class_exists($this->wsResource[$assocName]['class'], true)) {
                    $className = $this->wsResource[$assocName]['class'];
                }
                $outputDetails = '';
                foreach ($objectsAssoc as $objectAssoc) {
                    if ($depth == 0 || $className === null) {
                        $value = null;
                        if (!empty($objectAssoc)) {
                            $value = $objectAssoc;
                        }
                        if (empty($fieldsAssoc)) {
                            $fieldsAssoc = [['id' => $value['id']]];
                        }
                        $outputDetails .= $this->renderFlatAssociation($object, $depth, $assocName, $association['resource'], $fieldsAssoc, $value, $parentDetails);
                    } else {
                        foreach ($objectAssoc as $id) {
                            if ($className !== null) {
                                $child_object = new $className($id);
                                $outputDetails .= $this->renderEntity($child_object, ($depth - 2 ? 0 : $depth - 2));
                            }
                        }
                    }
                }
                if ($outputDetails != '') {
                    $output .= $this->setIndent($depth).$this->objectRender->renderAssociationHeader($object, $wsParams, $assocName);
                    $output .= $outputDetails;
                    $output .= $this->setIndent($depth).$this->objectRender->renderAssociationFooter($object, $wsParams, $assocName);
                } else {
                    $output .= $this->setIndent($depth).$this->objectRender->renderAssociationHeader($object, $wsParams, $assocName, true);
                }
            }
        }
        $output .= $this->objectRender->renderAssociationWrapperFooter();

        return $output;
    }

    /**
     * @param $object
     * @param $depth
     * @param $assocName
     * @param $resourceName
     * @param $fieldsAssoc
     * @param $objectAssoc
     * @param $parentDetails
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function renderFlatAssociation($object, $depth, $assocName, $resourceName, $fieldsAssoc, $objectAssoc, $parentDetails)
    {
        $output = '';
        $moreAttr = [];
        if (isset($this->wsResource[$assocName]) && is_null($this->schemaToDisplay)) {
            if ($assocName == 'images') {
                if ($parentDetails['entities_name'] == 'combinations') {
                    $moreAttr['xlink_resource'] = $this->wsUrl.$assocName.'/products/'.$object->id_product.'/'.$objectAssoc['id'];
                } else {
                    $moreAttr['xlink_resource'] = $this->wsUrl.$assocName.'/'.$parentDetails['entities_name'].'/'.$parentDetails['object_id'].'/'.$objectAssoc['id'];
                }
            } else {
                $moreAttr['xlink_resource'] = $this->wsUrl.$assocName.'/'.$objectAssoc['id'];
            }
        }
        $output .= $this->setIndent($depth - 1).$this->objectRender->renderNodeHeader($resourceName, [], $moreAttr);

        foreach ($fieldsAssoc as $fieldName => $field) {
            if (!is_array($this->fieldsToDisplay) || in_array($fieldName, $this->fieldsToDisplay[$assocName])) {
                if ($fieldName == 'id' && !isset($field['sqlId'])) {
                    $field['sqlId'] = 'id';
                    $field['value'] = $objectAssoc['id'];
                } elseif (!isset($field['sqlId'])) {
                    $field['sqlId'] = $fieldName;
                    $field['value'] = $objectAssoc[$fieldName];
                }
                $field['entities_name'] = $assocName;
                $field['entity_name'] = $resourceName;

                if (!is_null($this->schemaToDisplay)) {
                    $field['synopsis_details'] = $this->getSynopsisDetails($field);
                }
                $field['is_association'] = true;
                $output .= $this->setIndent($depth - 1).$this->objectRender->renderField($field);
            }
        }
        $output .= $this->setIndent($depth - 1).$this->objectRender->renderNodeFooter($resourceName, []);

        return $output;
    }

    /**
     * @param $depth
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function setIndent($depth)
    {
        $string = '';
        $number_of_tabs = $this->depth - $depth;
        for ($i = 0; $i < $number_of_tabs; $i++) {
            $string .= "\t";
        }

        return $string;
    }

    /**
     * @param $field
     *
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getSynopsisDetails($field)
    {
        $arrDetails = [];
        if (array_key_exists('required', $field) && $field['required']) {
            $arrDetails['required'] = 'true';
        }
        if (array_key_exists('maxSize', $field) && $field['maxSize']) {
            $arrDetails['maxSize'] = $field['maxSize'];
        }
        if (array_key_exists('validateMethod', $field) && $field['validateMethod']) {
            $arrDetails['format'] = $field['validateMethod'];
        }
        if (array_key_exists('setter', $field) && !$field['setter']) {
            $arrDetails['readOnly'] = 'true';
        }

        return $arrDetails;
    }

    /**
     * @param string|object $object
     * @param string        $method
     * @param               $fieldName
     * @param               $entityName
     *
     * @return WebserviceOutputBuilder
     * @throws Exception
     * @throws WebserviceException
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function setSpecificField($object, $method, $fieldName, $entityName)
    {
        try {
            $this->validateObjectAndMethod($object, $method);
        } catch (WebserviceException $e) {
            throw $e;
        }

        $this->specificFields[$fieldName] = ['entity' => $entityName, 'object' => $object, 'method' => $method, 'type' => gettype($object)];

        return $this;
    }

    /**
     * @param $object
     * @param $method
     *
     * @throws WebserviceException
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function validateObjectAndMethod($object, $method)
    {
        if (is_string($object) && !class_exists($object)) {
            throw new WebserviceException('The object you want to set in '.__METHOD__.' is not allowed.', [98, 500]);
        }
        if (!method_exists($object, $method)) {
            throw new WebserviceException('The method you want to set in '.__METHOD__.' is not allowed.', [99, 500]);
        }
    }

    /**
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getSpecificField()
    {
        return $this->specificFields;
    }

    /**
     * @param $entityName
     * @param $fieldName
     * @param $field
     * @param $entityObject
     * @param $wsParams
     *
     * @return mixed
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function overrideSpecificField($entityName, $fieldName, $field, $entityObject, $wsParams)
    {
        if (array_key_exists($fieldName, $this->specificFields)) {
            $specificFieldsFieldName = $this->specificFields[$fieldName];
            if ($specificFieldsFieldName['entity'] == $entityName) {

                if ($specificFieldsFieldName['type'] == 'string') {
                    $object = new $specificFieldsFieldName['object']();
                } elseif ($specificFieldsFieldName['type'] == 'object') {
                    $object = $specificFieldsFieldName['object'];
                }

                if (isset($object)) {
                    $method = $specificFieldsFieldName['method'];
                    $field = $object->$method($field, $entityObject, $wsParams);
                }
            }
        }

        return $field;
    }

    /**
     * @param $object
     * @param $method
     * @param $entityName
     * @param $parameters
     *
     * @throws WebserviceException
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function setVirtualField($object, $method, $entityName, $parameters)
    {
        try {
            $this->validateObjectAndMethod($object, $method);
        } catch (WebserviceException $e) {
            throw $e;
        }

        $this->virtualFields[$entityName][] = ['parameters' => $parameters, 'object' => $object, 'method' => $method, 'type' => gettype($object)];
    }

    /**
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getVirtualFields()
    {
        return $this->virtualFields;
    }

    /**
     * @param $entityName
     * @param $entityObject
     *
     * @return array
     * @throws WebserviceException
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function addVirtualFields($entityName, $entityObject)
    {
        $arrReturn = [];
        $virtualFields = $this->getVirtualFields();
        if (array_key_exists($entityName, $virtualFields)) {
            foreach ($virtualFields[$entityName] as $functionInfos) {
                if ($functionInfos['type'] == 'string') {
                    $object = new $functionInfos['object']();
                } elseif ($functionInfos['type'] == 'object') {
                    $object = $functionInfos['object'];
                }

                $method = $functionInfos['method'];
                $return_fields = $object->$method($entityObject, $functionInfos['parameters']);
                foreach ($return_fields as $field_name => $value) {
                    if (Validate::isConfigName($field_name)) {
                        $arrReturn[$field_name] = $value;
                    } else {
                        throw new WebserviceException('Name for the virtual field is not allow', [128, 400]);
                    }
                }
            }
        }

        return $arrReturn;
    }

    /**
     * @param $fields
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function setFieldsToDisplay($fields)
    {
        $this->fieldsToDisplay = $fields;
    }
}
