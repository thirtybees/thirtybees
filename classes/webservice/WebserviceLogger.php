<?php
/**
 * Copyright (C) 2021-2021 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @copyright 2021-2021 thirty bees
 * @license   Open Software License (OSL 3.0)
 */

/**
 * Class WebserviceLoggerCore
 */
class WebserviceLoggerCore
{
    /**
     * @var bool
     */
    protected $enabled;

    /**
     * @var string
     */
    protected $correlationId;

    /**
     * @var string
     */
    protected $key = null;

    /**
     * @var string
     */
    protected $fileTime;

    /**
     * WebserviceLoggerCore constructor.
     */
    public function __construct()
    {
        $this->enabled = static::resolveLogEnabledSettings();
        $this->correlationId = Tools::passwdGen(12);
        $this->fileTime = date('Ymd');
    }

    /**
     * Associates webservice key with this logger instance
     *
     * @param WebserviceKey $key
     */
    public function setKey(WebserviceKey $key)
    {
        $this->key = $key->key;

        // TODO: add option to enable logging per webservice account
        //
        //  $this->enabled = static::resolveLogEnabledSettings() && $key->log_enabled;
    }

    /**
     * Logs webservice request
     *
     * @param string $method
     * @param string $url
     * @param array $headers
     * @param string $payload
     */
    public function logRequest($method, $url, $headers, $payload)
    {
        // check that logging is enabled
        if (! $this->enabled) {
            return;
        }

        $filename = $this->getLogFilename();
        $formattedHeaders = '';
        if ($headers) {
            $strHeaders = [];
            foreach ($headers as $key => $value) {
                $strHeaders[] = "$key=$value";
            }
            $formattedHeaders = ' [' . implode(", ", $strHeaders) . ']';
        }
        $url = preg_replace('#//+#', "/", $url);
        $prefix = $this->getPrefix('REQUEST');
        $formattedMessage = $prefix . $method . ' ' . $url . $formattedHeaders . "\n";
        $formattedMessage .= $this->formatPayload($prefix, $payload);
        @file_put_contents($filename, $formattedMessage, FILE_APPEND);
    }

    /**
     * Logs response
     *
     * @param string $content
     * @param array $errors
     * @param string $time
     */
    public function logResponse($content, $errors, $time)
    {
        // check that logging is enabled
        if (! $this->enabled) {
            return;
        }

        $filename = $this->getLogFilename();
        $prefix = $this->getPrefix('RESPONSE');
        $formattedMessage = $prefix;
        if ($errors) {
            $formattedMessage .= "Error response generated in " . $time . " seconds. Errors: \n";
            foreach ($errors as $error) {
                $formattedMessage .= $prefix . "  code " . $error[0] . ": " . $error[1] . "\n";
            }
        } else {
            $formattedMessage .= "Success response generated in " . $time . " seconds\n";
            $formattedMessage .= $this->formatPayload($prefix, $content);
        }
        @file_put_contents($filename, $formattedMessage, FILE_APPEND);
    }

    /**
     * Returns true, if logging is allowed by global settings
     *
     * @return bool
     */
    private static function resolveLogEnabledSettings()
    {
        try {
            return (bool)Configuration::getGlobalValue('WEBSERVICE_LOG_ENABLED');
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Returns directory for log files
     *
     * @return string
     */
    public static function getDirectory()
    {
        $dir = _PS_ROOT_DIR_.'/log/webservice/';
        if (! is_dir($dir)) {
            @mkdir($dir);
        }
        return $dir;
    }

    /**
     * Returns log file
     *
     * @return string
     */
    protected function getLogFilename()
    {
        $dir = static::getDirectory();
        if (is_null($this->key)) {
            return $dir . 'webservice_' . $this->fileTime . '.log';
        } else {
            $name = preg_replace( '/[^a-zA-Z0-9_-]/', '_', $this->key);
            return $dir . $name . '_' . $this->fileTime . '.log';
        }
    }

    /**
     * Returns log line prefix
     *
     * @param string $type log line type
     * @return string
     */
    protected function getPrefix($type)
    {
        $padding = str_repeat(' ', 8 - strlen($type));
        return date('Y/m/d H:i:s') . ' ['.$this->correlationId.'] [' . $type . $padding .'] ';
    }

    /**
     * Formats payload (request, response)
     *
     * @param string $prefix
     * @param string $payload
     * @return string
     */
    protected function formatPayload($prefix, $payload)
    {
        $formattedPayload = '';
        $payload = trim((string)$payload);
        if ($payload) {
            $payload = preg_replace("#\n#", "\n$prefix", $payload);
            $formattedPayload = $prefix . $payload . "\n";
        }
        return $formattedPayload;
    }
}
