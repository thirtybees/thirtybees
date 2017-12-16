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

ob_start();

require_once(dirname(__FILE__).'/../config/config.inc.php');

// Cart is needed for some requests
Context::getContext()->cart = new Cart();

//set http auth headers for apache+php-cgi work around
if (isset($_SERVER['HTTP_AUTHORIZATION']) && preg_match('/Basic\s+(.*)$/i', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
    list($name, $password) = explode(':', base64_decode($matches[1]));
    $_SERVER['PHP_AUTH_USER'] = strip_tags($name);
}

//set http auth headers for apache+php-cgi work around if variable gets renamed by apache
if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) && preg_match('/Basic\s+(.*)$/i', $_SERVER['REDIRECT_HTTP_AUTHORIZATION'], $matches)) {
    list($name, $password) = explode(':', base64_decode($matches[1]));
    $_SERVER['PHP_AUTH_USER'] = strip_tags($name);
}

// Use for image management (using the POST method of the browser to simulate the PUT method)
$method = isset($_REQUEST['ps_method']) ? $_REQUEST['ps_method'] : $_SERVER['REQUEST_METHOD'];

if (isset($_SERVER['PHP_AUTH_USER'])) {
    $key = $_SERVER['PHP_AUTH_USER'];
} elseif (isset($_GET['ws_key'])) {
    $key = $_GET['ws_key'];
} else {
    header($_SERVER['SERVER_PROTOCOL'].' 401 Unauthorized');
    header('WWW-Authenticate: Basic realm="Welcome to PrestaShop Webservice, please enter the authentication key as the login. No password required."');
    die('401 Unauthorized');
}


$inputXml = null;

// if a XML is in PUT or in POST
if (($_SERVER['REQUEST_METHOD'] == 'PUT') || ($_SERVER['REQUEST_METHOD'] == 'POST')) {
    $inputXml = file_get_contents('php://input');
    if (isset($_SERVER['HTTP_CONTENT_ENCODING']) && $_SERVER['HTTP_CONTENT_ENCODING'] == 'gzip') {
        $inputXml = zlib_decode($inputXml);
    }
}
if (isset($inputXml) && strncmp($inputXml, 'xml=', 4) == 0) {
    $inputXml = substr($inputXml, 4);
}

$params = $_GET;
unset($params['url']);

$className = WebserviceKey::getClassFromKey($key);
$badClassName = false;
if (!class_exists($className)) {
    $badClassName = $className;
    $className = 'WebserviceRequest';
}
// fetch the request
WebserviceRequest::$ws_current_classname = $className;
$request = call_user_func([$className, 'getInstance']);

$result = $request->fetch($key, $method, $_GET['url'], $params, $badClassName, $inputXml);

// display result
if (ob_get_length() != 0) {
    header('Content-Type: application/javascript');
} // Useful for debug...

// Manage cache
if (isset($_SERVER['HTTP_LOCAL_CONTENT_SHA1']) && $_SERVER['HTTP_LOCAL_CONTENT_SHA1'] == $result['content_sha1']) {
    $result['status'] = $_SERVER['SERVER_PROTOCOL'].' 304 Not Modified';
}

if (is_array($result['headers'])) {
    foreach ($result['headers'] as $paramValue) {
        header($paramValue);
    }
}
if (isset($result['type'])) {
    //	header($result['content_sha1']);
    if (!isset($_SERVER['HTTP_LOCAL_CONTENT_SHA1']) || $_SERVER['HTTP_LOCAL_CONTENT_SHA1'] != $result['content_sha1']) {
        echo $result['content'];
    }
}
ob_end_flush();
