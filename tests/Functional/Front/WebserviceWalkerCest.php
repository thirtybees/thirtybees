<?php

namespace Functional\Front;

use Codeception\Example;
use PrestaShopCollection;
use PrestaShopException;
use Tests\Support\FunctionalTester;
use Validate;
use WebserviceRequest;

class WebserviceWalkerCest
{
    /**
     * This tests walks through front office category pages and ensures that no error is thrown
     *
     * @param FunctionalTester $I
     * @param Example $example
     *
     * @return void
     *
     * @dataProvider getResourceToTest
     */
    public function testResource(FunctionalTester $I, Example $example)
    {
        $key = WEBSERVICE_TEST_KEY;
        $url = str_replace('?', '&', ltrim($example['url'], '/'));
        $request = "/webservice/dispatcher.php?ws_key=$key&url=$url";
        $I->amOnPage($request);
        $this->verifyNoError($I);
    }

    /**
     * @return array
     * @throws PrestaShopException
     */
    protected function getResourceToTest()
    {
        // index pages
        $toTest = [
            ['url' => '/']
        ];

        foreach (WebserviceRequest::getResources() as $key => $resource) {
            $url = '/' . $key;
            if ($key === 'search') {
                $url .= '?query=candle&language=1';
            }

            // index page
            $toTest[] = [
                'url' => $url
            ];

            // one detail page
            if (isset($resource['class'])) {
                $class = $resource['class'];
                $obj = (new PrestaShopCollection($class))->getFirst();
                if (Validate::isLoadedObject($obj)) {
                    $toTest[] = [
                        'url' => $url . '/' . $obj->id
                    ];
                }
            }
        }

        // images index page
        $toTest[] = ['url' => '/images/general'];
        $toTest[] = ['url' => '/images/products'];
        $toTest[] = ['url' => '/images/categories'];
        $toTest[] = ['url' => '/images/manufacturers'];
        $toTest[] = ['url' => '/images/suppliers'];
        $toTest[] = ['url' => '/images/stores'];
        $toTest[] = ['url' => '/images/customizations'];

        usort($toTest, function($a, $b) {
            return strcmp($a['url'], $b['url']);
        });

        return $toTest;
    }

    /**
     * @param FunctionalTester $I
     *
     * @return void
     */
    protected function verifyNoError(FunctionalTester $I)
    {
        $source = $I->grabPageSource();
        if (! $source) {
            $I->fail("Empty response");
        }
        $result = simplexml_load_string($source);
        if (! $result) {
            $I->fail("Failed to parse response XML: $source");
        }
        if (isset($result->errors)) {
            foreach ($result->errors as $error) {
                foreach ($error as $c1) {
                    foreach ($c1 as $c2) {
                        if ($c2->getName() === "message") {
                            $I->fail($c2 . "\n\n" . $source);
                            return;
                        }
                    }
                }
            }
            $I->fail($source);
        }
    }
}
