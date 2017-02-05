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
 * @author    Thirty Bees <modules@thirtybees.com>
 * @author    Drew McLellan
 * @copyright 2017 Thirty Bees
 * @copyright 2013-2017 Drew McLellan
 * @license   https://opensource.org/licenses/MIT  Academic Free License (MIT)
 */

namespace ThirtyBees\MailChimp;

/**
 * A MailChimp Batch operation.
 * http://developer.mailchimp.com/documentation/mailchimp/reference/batches/
 *
 * @author Drew McLellan <drew.mclellan@gmail.com>
 */
class Batch
{
    private $MailChimp;

    private $operations = array();
    private $batch_id;

    public function __construct(MailChimp $mailChimp, $batchId = null)
    {
        $this->MailChimp = $mailChimp;
        $this->batch_id = $batchId;
    }

    /**
     * Add an HTTP DELETE request operation to the batch - for deleting data
     *
     * @param   string $id     ID for the operation within the batch
     * @param   string $method URL of the API request method
     *
     * @return  void
     */
    public function delete($id, $method)
    {
        $this->queueOperation('DELETE', $id, $method);
    }

    /**
     * Add an HTTP GET request operation to the batch - for retrieving data
     *
     * @param   string $id     ID for the operation within the batch
     * @param   string $method URL of the API request method
     * @param   array  $args   Assoc array of arguments (usually your data)
     *
     * @return  void
     */
    public function get($id, $method, $args = array())
    {
        $this->queueOperation('GET', $id, $method, $args);
    }

    /**
     * Add an HTTP PATCH request operation to the batch - for performing partial updates
     *
     * @param   string $id     ID for the operation within the batch
     * @param   string $method URL of the API request method
     * @param   array  $args   Assoc array of arguments (usually your data)
     *
     * @return  void
     */
    public function patch($id, $method, $args = array())
    {
        $this->queueOperation('PATCH', $id, $method, $args);
    }

    /**
     * Add an HTTP POST request operation to the batch - for creating and updating items
     *
     * @param   string $id     ID for the operation within the batch
     * @param   string $method URL of the API request method
     * @param   array  $args   Assoc array of arguments (usually your data)
     *
     * @return  void
     */
    public function post($id, $method, $args = array())
    {
        $this->queueOperation('POST', $id, $method, $args);
    }

    /**
     * Add an HTTP PUT request operation to the batch - for creating new items
     *
     * @param   string $id     ID for the operation within the batch
     * @param   string $method URL of the API request method
     * @param   array  $args   Assoc array of arguments (usually your data)
     *
     * @return  void
     */
    public function put($id, $method, $args = array())
    {
        $this->queueOperation('PUT', $id, $method, $args);
    }

    /**
     * Execute the batch request
     *
     * @param int $timeout Request timeout in seconds (optional)
     *
     * @return  array|false   Assoc array of API response, decoded from JSON
     */
    public function execute($timeout = 10)
    {
        $req = array('operations' => $this->operations);

        $result = $this->MailChimp->post('batches', $req, $timeout);

        if ($result && isset($result['id'])) {
            $this->batch_id = $result['id'];
        }

        return $result;
    }

    /**
     * Check the status of a batch request. If the current instance of the Batch object
     * was used to make the request, the batch_id is already known and is therefore optional.
     *
     * @param string $batchId ID of the batch about which to enquire
     *
     * @return  array|false   Assoc array of API response, decoded from JSON
     */
    public function checkStatus($batchId = null)
    {
        if ($batchId === null && $this->batch_id) {
            $batchId = $this->batch_id;
        }

        return $this->MailChimp->get('batches/'.$batchId);
    }

    /**
     *  Get operations
     *
     * @return array
     */
    public function getOperations()
    {
        return $this->operations;
    }

    /**
     * Add an operation to the internal queue.
     *
     * @param   string $httpVerb GET, POST, PUT, PATCH or DELETE
     * @param   string $id       ID for the operation within the batch
     * @param   string $method   URL of the API request method
     * @param   array  $args     Assoc array of arguments (usually your data)
     *
     * @return  void
     */
    private function queueOperation($httpVerb, $id, $method, $args = null)
    {
        $operation = array(
            'operation_id' => $id,
            'method' => $httpVerb,
            'path' => $method,
        );

        if ($args) {
            $key = ($httpVerb == 'GET' ? 'params' : 'body');
            $operation[$key] = json_encode($args);
        }

        $this->operations[] = $operation;
    }
}
