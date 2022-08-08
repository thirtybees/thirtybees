<?php
/**
 * Copyright (C) 2022 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @copyright 2019 thirty bees
 * @license   Open Software License (OSL 3.0)
 */

namespace Thirtybees\Core\Error\Response;

use Thirtybees\Core\Error\ErrorDescription;

/**
 * Class AbstractErrorPageCore
 *
 * @since 1.4.0
 */
abstract class AbstractErrorPageCore implements ErrorResponseInterface
{

    /**
     * @var string | null
     */
    private $contentType;

    /**
     * @param ErrorDescription $errorDescription
     * @return void
     */
    public function sendResponse(ErrorDescription $errorDescription)
    {
        // get error page content
        $content = $this->renderError($errorDescription);

        // output content
        $this->beforeRender($errorDescription);
        if (!headers_sent()) {
            if (! $this->contentType) {
                $this->contentType = $this->getContentType();
            }
            header('HTTP/1.1 500 Internal Server Error');
            header('Content-Type: ' . $this->contentType);
        }
        //clean any output buffer there might be
        while (ob_get_level()) {
            ob_end_clean();
        }
        // render error page content
        echo $content;
        $this->afterRender($errorDescription);
        exit;
    }

    /**
     * @param ErrorDescription $errorDescription
     * @return string
     */
    public function getPageContent(ErrorDescription $errorDescription)
    {
        try{
            return $this->renderError($errorDescription);
        } catch (\Throwable $t) {
            // It's very unlikely that exception will be thrown during error message rendering. If that happen,
            // simply give up
            $this->contentType = 'text/plain';
            if (_PS_MODE_DEV_) {
                $message = "Failed to display exception:\n";
                $message .= $errorDescription->getMessage();
                $message .= "\n\nFailure reason:\n";
                $message .= $t;
                return $message;
            } else {
                return "Fatal error";
            }
        }
    }

    /**
     * Display a phtml template file
     *
     * @param string $file
     * @param array $params
     *
     * @return string Content
     */
    protected function displayErrorTemplate($file, $params)
    {
        foreach ($params as $name => $param) {
            $$name = $param;
        }

        ob_start();

        include($file);

        $content = ob_get_contents();
        if (ob_get_level() && ob_get_length() > 0) {
            ob_end_clean();
        }

        return $content;
    }

    /**
     * Called at the start of error page rendering, before content is sent to client.
     * Subclasses can use it to add its own content to server response
     *
     * @param ErrorDescription $errorDescription
     * @return void
     */
    protected function beforeRender(ErrorDescription $errorDescription)
    {
        // noop
    }

    /**
     * Called at the end of error page rendering, after content was send to client.
     * Subclasses can use this to implement various logging, cleanup, etc.
     *
     * @param ErrorDescription $errorDescription
     * @return void
     */
    protected function afterRender(ErrorDescription $errorDescription)
    {
        // noop
    }

    /**
     * @return string
     */
    protected abstract function getContentType();

    /**
     * @param ErrorDescription $errorDescription
     * @return string
     */
    protected abstract function renderError(ErrorDescription $errorDescription);

}
