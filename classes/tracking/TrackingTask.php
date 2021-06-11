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

namespace Thirtybees\Core\Tracking;


use Adapter_Exception;
use Configuration;
use Db;
use Exception;
use PrestaShopException;
use Thirtybees\Core\InitializationCallback;
use Thirtybees\Core\WorkQueue\ScheduledTask;
use Thirtybees\Core\WorkQueue\WorkQueueContext;
use Thirtybees\Core\WorkQueue\WorkQueueTaskCallable;

/**
 * Class TrackingTaskCore
 *
 * Work queue task that collects information and sends them to thirty bees api server
 *
 * @since 1.3.0
 */
class TrackingTaskCore implements WorkQueueTaskCallable, InitializationCallback
{

    /**
     * Task execution method
     *
     * Collect data using all extractors that store owner gave consent. If any data are available,
     * send them to thirty bees api server
     *
     *
     * @param WorkQueueContext $context
     * @param array $parameters
     *
     * @throws Exception
     * @return mixed
     */
    public function execute(WorkQueueContext $context, array $parameters)
    {
        $allowedExtractors = Consent::getAllowedExtractors();
        $dataset = [];
        foreach ($allowedExtractors as $extractorId) {
            /** @var DataExtractor $extractor */
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
     * @throws PrestaShopException
     */
    protected function send($dataset)
    {
        $guzzle = new \GuzzleHttp\Client([
            'base_uri'    => Configuration::getApiServer(),
            'timeout'     => 15,
            'verify'      => _PS_TOOL_DIR_.'cacert.pem'
        ]);
        $guzzle->post(
            '/collect/v1.php',
            [
                'json' => [
                    'sid' => Configuration::getServerTrackingId(),
                    'ts' => time(),
                    'data' => $dataset
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
     * @throws Adapter_Exception
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
