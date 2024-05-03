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

namespace Thirtybees\Core\Tracking;

use Configuration;
use Db;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use PrestaShopDatabaseException;
use PrestaShopException;
use Thirtybees\Core\InitializationCallback;
use Thirtybees\Core\WorkQueue\ScheduledTask;
use Thirtybees\Core\WorkQueue\WorkQueueContext;
use Thirtybees\Core\WorkQueue\WorkQueueTaskCallable;

/**
 * Class TrackingTaskCore
 *
 * Work queue task that collects information and sends them to thirty bees api server
 */
class TrackingTaskCore implements WorkQueueTaskCallable, InitializationCallback
{

    /**
     * Task execution method
     *
     * Collect data using all extractors that store owner gave consent. If any data are available,
     * send them to thirty bees api server
     *
     * @param WorkQueueContext $context
     * @param array $parameters
     *
     * @return string
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     * @throws GuzzleException
     */
    public function execute(WorkQueueContext $context, array $parameters)
    {
        $allowedExtractors = Consent::getAllowedExtractors();
        $dataset = [];
        foreach ($allowedExtractors as $extractorId) {
            $extractor = DataExtractor::getExtractor($extractorId);
            $value = $extractor->extractValue();
            if ($this->valueChanged($extractor, $value)) {
                $dataset[] = [
                    'type' => $extractorId,
                    'value' => $value
                ];
            }
        }
        if ($dataset) {
            $this->send($dataset);
            return "Sent " . count($dataset) . ' items';
        }

        return "Nothing to send";
    }

    /**
     * Method returns true, if the value changes since the last delivery event
     *
     * @param DataExtractor $extractor
     * @param mixed $value
     *
     * @return bool
     */
    protected function valueChanged($extractor, $value)
    {
        // currently not implemented, send always
        return true;
    }

    /**
     * Sends payload with collected information to thirty bees api server
     *
     * @param array $dataset
     *
     * @throws PrestaShopException
     * @throws GuzzleException
     */
    protected function send($dataset)
    {
        $guzzle = new Client([
            'base_uri'    => Configuration::getApiServer(),
            'timeout'     => 15,
            'verify'      => Configuration::getSslTrustStore()
        ]);
        $guzzle->post(
            '/collect/v1.php',
            [
                'json' => [
                    'ts' => time(),
                    'data' => $dataset
                ],
                'headers' => [
                    'X-SID' => Configuration::getServerTrackingId()
                ]
            ]
        );
    }

    /**
     * Callback method to initialize class
     *
     * @param Db $conn
     * @return void
     * @throws PrestaShopException
     */
    public static function initializationCallback(Db $conn)
    {
        $task = str_replace("TrackingTaskCore", "TrackingTask", static::class);
        $trackingTasks = ScheduledTask::getTasksForCallable($task);
        if (! $trackingTasks) {
            $scheduledTask = new ScheduledTask();
            $scheduledTask->frequency = rand(0, 59) . ' ' . rand(0, 23) . ' * * *';
            $scheduledTask->name = 'Thirty bees data collection task';
            $scheduledTask->description = 'Sends various information to thirty bees server';
            $scheduledTask->task = $task;
            $scheduledTask->active = true;
            $scheduledTask->add();
        }
    }
}
