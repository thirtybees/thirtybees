<?php
/**
 * Copyright (C) 2017-2024 thirty bees
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
 * @copyright 2017-2024 thirty bees
 * @license   Open Software License (OSL 3.0)
 */


use Thirtybees\Core\Error\ErrorDescription;
use Thirtybees\Core\Error\Response\ErrorResponseInterface;

class WebserviceFatalErrorResponseCore implements ErrorResponseInterface
{

    /**
     * @var WebserviceOutputBuilder
     */
    protected $outputBuilder;

    /**
     * @var bool
     */
    protected $sendErrorMessage;

    /**
     * @var float
     */
    protected $startTime;

    /**
     * @var WebserviceLogger
     */
    protected $logger;

    /**
     * @param WebserviceOutputBuilder $outputBuilder
     * @param WebserviceLogger $logger
     * @param bool $sendErrorMessage
     * @param float $startTime
     */
    public function __construct(
        WebserviceOutputBuilder $outputBuilder,
        WebserviceLogger $logger,
        bool $sendErrorMessage,
        float $startTime
    ) {
        $this->outputBuilder = $outputBuilder;
        $this->logger = $logger;
        $this->sendErrorMessage = $sendErrorMessage;
        $this->startTime = $startTime;
    }

    /**
     * @param ErrorDescription $errorDescription
     * @return void
     * @throws PrestaShopException
     * @throws WebserviceException
     */
    public function sendResponse(ErrorDescription $errorDescription)
    {
        $time = round(microtime(true) - $this->startTime, 3);
        $this->outputBuilder->setStatus(500);
        $this->outputBuilder->setHeaderParams('Execution-Time', $time);
        foreach ($this->outputBuilder->buildHeader() as $header) {
            header($header);
        }

        //clean any output buffer there might be
        while (ob_get_level()) {
            ob_end_clean();
        }

        $extra = [];
        if ($this->sendErrorMessage) {
            $message = $errorDescription->getExtendedMessage();
            foreach ($errorDescription->getExtraSections() as $section) {
                $extra[$section['label']] = $section['content'];
            }
            $extra['stacktrace'] = $errorDescription->getTraceAsString();
        } else {
            $message = Tools::displayError('Internal server error');
            $extra['notice'] = Tools::displayError('You can decrypt error message in the back office');
            $extra['encrypted_error'] = $errorDescription->encrypt();
        }

        $content = $this->outputBuilder->getErrors([[2, $message, $extra]]);
        echo $content;

        // log error
        $this->logger->logResponse('', [[2, $errorDescription->getExtendedMessage()]], $time);
        exit;
    }
}