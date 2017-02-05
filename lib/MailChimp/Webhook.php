<?php
/**
 * 2017 Thirty Bees
 * 2013-2017 Drew McLellan
 *
 * NOTICE OF LICENSE
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the "Software"), to deal in
 * the Software without restriction, including without limitation the rights to
 * use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
 * the Software, and to permit persons to whom the Software is furnished to do so,
 * subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
 * FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 * COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
 * IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
 * CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 *  @author    Thirty Bees <modules@thirtybees.com>
 *  @author    Drew McLellan
 *  @copyright 2017 Thirty Bees
 *  @copyright 2013-2017 Drew McLellan
 *  @license   https://opensource.org/licenses/MIT  Academic Free License (MIT)
 */

namespace ThirtyBees\MailChimp;

/**
 * A MailChimp Webhook request.
 * How to Set Up Webhooks: http://eepurl.com/bs-j_T
 *
 * @author Drew McLellan <drew.mclellan@gmail.com>
 */
class Webhook
{
    private static $eventSubscriptions = array();
    private static $receivedWebhook = null;

    /**
     * Subscribe to an incoming webhook request. The callback will be invoked when a matching webhook is received.
     *
     * @param string   $event    Name of the webhook event, e.g. subscribe, unsubscribe, campaign
     * @param callable $callback A callable function to invoke with the data from the received webhook
     *
     * @return void
     */
    public static function subscribe($event, callable $callback)
    {
        if (!isset(self::$eventSubscriptions[$event])) {
            self::$eventSubscriptions[$event] = array();
        }
        self::$eventSubscriptions[$event][] = $callback;

        self::receive();
    }

    /**
     * Retrieve the incoming webhook request as sent.
     *
     * @param string $input An optional raw POST body to use instead of php://input - mainly for unit testing.
     *
     * @return array|false    An associative array containing the details of the received webhook
     */
    public static function receive($input = null)
    {
        if (is_null($input)) {
            if (self::$receivedWebhook !== null) {
                $input = self::$receivedWebhook;
            } else {
                $input = file_get_contents("php://input");
            }
        }

        if (!is_null($input) && $input != '') {
            return self::processWebhook($input);
        }

        return false;
    }

    /**
     * Process the raw request into a PHP array and dispatch any matching subscription callbacks
     *
     * @param string $input The raw HTTP POST request
     *
     * @return array|false    An associative array containing the details of the received webhook
     */
    private static function processWebhook($input)
    {
        self::$receivedWebhook = $input;
        parse_str($input, $result);
        if ($result && isset($result['type'])) {
            self::dispatchWebhookEvent($result['type'], $result['data']);

            return $result;
        }

        return false;
    }

    /**
     * Call any subscribed callbacks for this event
     *
     * @param string $event The name of the callback event
     * @param array  $data  An associative array of the webhook data
     *
     * @return void
     */
    private static function dispatchWebhookEvent($event, $data)
    {
        if (isset(self::$eventSubscriptions[$event])) {
            foreach (self::$eventSubscriptions[$event] as $callback) {
                $callback($data);
            }
            // reset subscriptions
            self::$eventSubscriptions[$event] = array();
        }
    }
}
