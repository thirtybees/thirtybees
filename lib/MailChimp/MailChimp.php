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
 * Super-simple, minimum abstraction MailChimp API v3 wrapper
 * MailChimp API v3: http://developer.mailchimp.com
 * This wrapper: https://github.com/drewm/mailchimp-api
 *
 * @author  Drew McLellan <drew.mclellan@gmail.com>
 * @version 2.3
 */
class MailChimp
{
    private $apiKey;
    private $apiEndpoint = 'https://<dc>.api.mailchimp.com/3.0';

    /*  SSL Verification
        Read before disabling:
        http://snippets.webaware.com.au/howto/stop-turning-off-curlopt_ssl_verifypeer-and-fix-your-php-config/
    */
    public $verifySsl = true;

    private $requestSuccessful = false;
    private $lastError = '';
    private $lastResponse = array();
    private $lastRequest = array();

    /**
     * Create a new instance
     *
     * @param string $apiKey Your MailChimp API key
     *
     * @throws \Exception
     */
    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;

        if (strpos($this->apiKey, '-') === false) {
            throw new \Exception("Invalid MailChimp API key `{$apiKey}` supplied.");
        }

        list(, $dataCenter) = explode('-', $this->apiKey);
        $this->apiEndpoint = str_replace('<dc>', $dataCenter, $this->apiEndpoint);

        $this->lastResponse = array('headers' => null, 'body' => null);
    }

    /**
     * Create a new instance of a Batch request. Optionally with the ID of an existing batch.
     *
     * @param string $batchId Optional ID of an existing batch, if you need to check its status for example.
     *
     * @return Batch            New Batch object.
     */
    public function newBatch($batchId = null)
    {
        return new Batch($this, $batchId);
    }

    /**
     * Convert an email address into a 'subscriber hash' for identifying the subscriber in a method URL
     *
     * @param   string $email The subscriber's email address
     *
     * @return  string          Hashed version of the input
     */
    public function subscriberHash($email)
    {
        return md5(strtolower($email));
    }

    /**
     * Was the last request successful?
     *
     * @return bool  True for success, false for failure
     */
    public function success()
    {
        return $this->requestSuccessful;
    }

    /**
     * Get the last error returned by either the network transport, or by the API.
     * If something didn't work, this should contain the string describing the problem.
     *
     * @return  array|false  describing the error
     */
    public function getLastError()
    {
        return $this->lastError ?: false;
    }

    /**
     * Get an array containing the HTTP headers and the body of the API response.
     *
     * @return array  Assoc array with keys 'headers' and 'body'
     */
    public function getLastResponse()
    {
        return $this->lastResponse;
    }

    /**
     * Get an array containing the HTTP headers and the body of the API request.
     *
     * @return array  Assoc array
     */
    public function getLastRequest()
    {
        return $this->lastRequest;
    }

    /**
     * Make an HTTP DELETE request - for deleting data
     *
     * @param   string $method  URL of the API request method
     * @param   array  $args    Assoc array of arguments (if any)
     * @param   int    $timeout Timeout limit for request in seconds
     *
     * @return  array|false   Assoc array of API response, decoded from JSON
     */
    public function delete($method, $args = array(), $timeout = 10)
    {
        return $this->makeRequest('delete', $method, $args, $timeout);
    }

    /**
     * Make an HTTP GET request - for retrieving data
     *
     * @param   string $method  URL of the API request method
     * @param   array  $args    Assoc array of arguments (usually your data)
     * @param   int    $timeout Timeout limit for request in seconds
     *
     * @return  array|false   Assoc array of API response, decoded from JSON
     */
    public function get($method, $args = array(), $timeout = 10)
    {
        return $this->makeRequest('get', $method, $args, $timeout);
    }

    /**
     * Make an HTTP PATCH request - for performing partial updates
     *
     * @param   string $method  URL of the API request method
     * @param   array  $args    Assoc array of arguments (usually your data)
     * @param   int    $timeout Timeout limit for request in seconds
     *
     * @return  array|false   Assoc array of API response, decoded from JSON
     */
    public function patch($method, $args = array(), $timeout = 10)
    {
        return $this->makeRequest('patch', $method, $args, $timeout);
    }

    /**
     * Make an HTTP POST request - for creating and updating items
     *
     * @param   string $method  URL of the API request method
     * @param   array  $args    Assoc array of arguments (usually your data)
     * @param   int    $timeout Timeout limit for request in seconds
     *
     * @return  array|false   Assoc array of API response, decoded from JSON
     */
    public function post($method, $args = array(), $timeout = 10)
    {
        return $this->makeRequest('post', $method, $args, $timeout);
    }

    /**
     * Make an HTTP PUT request - for creating new items
     *
     * @param   string $method  URL of the API request method
     * @param   array  $args    Assoc array of arguments (usually your data)
     * @param   int    $timeout Timeout limit for request in seconds
     *
     * @return  array|false   Assoc array of API response, decoded from JSON
     */
    public function put($method, $args = array(), $timeout = 10)
    {
        return $this->makeRequest('put', $method, $args, $timeout);
    }

    /**
     * Performs the underlying HTTP request. Not very exciting.
     *
     * @param  string $httpVerb The HTTP verb to use: get, post, put, patch, delete
     * @param  string $method   The API method to be called
     * @param  array  $args     Assoc array of parameters to be passed
     * @param int     $timeout
     *
     * @return array|false Assoc array of decoded result
     * @throws \Exception
     */
    private function makeRequest($httpVerb, $method, $args = array(), $timeout = 10)
    {
        $url = $this->apiEndpoint.'/'.$method;

        $this->lastError = '';
        $this->requestSuccessful = false;
        $response = array('headers' => null, 'body' => null);
        $this->lastResponse = $response;

        $this->lastRequest = array(
            'method' => $httpVerb,
            'path' => $method,
            'url' => $url,
            'body' => '',
            'timeout' => $timeout,
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            array(
                'Accept: application/vnd.api+json',
                'Content-Type: application/vnd.api+json',
                'Authorization: apikey '.$this->apiKey,
            )
        );
        curl_setopt($ch, CURLOPT_USERAGENT, 'thirty bees MailChimp APIv3 (github.com/thirtybees/thirtybees)');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->verifySsl);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);

        switch ($httpVerb) {
            case 'post':
                curl_setopt($ch, CURLOPT_POST, true);
                $this->attachRequestPayload($ch, $args);
                break;

            case 'get':
                $query = http_build_query($args, '', '&');
                curl_setopt($ch, CURLOPT_URL, $url.'?'.$query);
                break;

            case 'delete':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;

            case 'patch':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
                $this->attachRequestPayload($ch, $args);
                break;

            case 'put':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                $this->attachRequestPayload($ch, $args);
                break;
        }

        $response['body'] = curl_exec($ch);
        $response['headers'] = curl_getinfo($ch);

        if (isset($response['headers']['request_header'])) {
            $this->lastRequest['headers'] = $response['headers']['request_header'];
        }

        if ($response['body'] === false) {
            $this->lastError = curl_error($ch);
        }

        curl_close($ch);

        $formattedResponse = $this->formatResponse($response);

        $this->determineSuccess($response, $formattedResponse);

        return $formattedResponse;
    }

    /**
     * @return string The url to the API endpoint
     */
    public function getApiEndpoint()
    {
        return $this->apiEndpoint;
    }

    /**
     * Encode the data and attach it to the request
     *
     * @param   resource $ch   cURL session handle, used by reference
     * @param   array    $data Assoc array of data to attach
     */
    private function attachRequestPayload(&$ch, $data)
    {
        $encoded = json_encode($data);
        $this->lastRequest['body'] = $encoded;
        curl_setopt($ch, CURLOPT_POSTFIELDS, $encoded);
    }

    /**
     * Decode the response and format any error messages for debugging
     *
     * @param array $response The response from the curl request
     *
     * @return array|false    The JSON decoded into an array
     */
    private function formatResponse($response)
    {
        $this->lastResponse = $response;

        if (!empty($response['body'])) {
            return json_decode($response['body'], true);
        }

        return false;
    }

    /**
     * Check if the response was successful or a failure. If it failed, store the error.
     *
     * @param array       $response          The response from the curl request
     * @param array|false $formattedResponse The response body payload from the curl request
     *
     * @return bool     If the request was successful
     */
    private function determineSuccess($response, $formattedResponse)
    {
        $status = $this->findHTTPStatus($response, $formattedResponse);

        if ($status >= 200 && $status <= 299) {
            $this->requestSuccessful = true;

            return true;
        }

        if (isset($formattedResponse['detail'])) {
            $this->lastError = sprintf('%d: %s', $formattedResponse['status'], $formattedResponse['detail']);

            return false;
        }

        $this->lastError = 'Unknown error, call getLastResponse() to find out what happened.';

        return false;
    }

    /**
     * Find the HTTP status code from the headers or API response body
     *
     * @param array       $response          The response from the curl request
     * @param array|false $formattedResponse The response body payload from the curl request
     *
     * @return int  HTTP status code
     */
    private function findHTTPStatus($response, $formattedResponse)
    {
        if (!empty($response['headers']) && isset($response['headers']['http_code'])) {
            return (int) $response['headers']['http_code'];
        }

        if (!empty($response['body']) && isset($formattedResponse['status'])) {
            return (int) $formattedResponse['status'];
        }

        return 418;
    }
}
