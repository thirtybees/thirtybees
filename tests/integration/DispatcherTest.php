<?php


class DispatcherTest extends \Codeception\Test\Unit
{

    public function getUnfriendlyRoutes()
    {
        return [
            // test for no-friendly urls
            'Rewrite off | category controller' => [false, 'index.php?controller=category&id_category=1', 'category', ['controller' => 'category', 'id_category' => '1']],
            'Rewrite off | supplier controller' => [false, 'index.php?controller=supplier&id_supplier=1', 'supplier', ['controller' => 'supplier', 'id_supplier' => '1']],
            'Rewrite off | manufacturer controller' => [false, 'index.php?controller=manufacturer&id_manufacturer=1', 'manufacturer', ['controller' => 'manufacturer', 'id_manufacturer' => '1']],
            'Rewrite off | cms controller' => [false, 'index.php?controller=cms&id_cms=1', 'cms', ['controller' => 'cms', 'id_cms' => '1']],
            'Rewrite off | cms controller (category)' => [false, 'index.php?controller=cms&id_cms_category=1', 'cms', ['controller' => 'cms', 'id_cms_category' => '1']],
            'Rewrite off | product controller' => [false, 'index.php?controller=product&id_product=1', 'product', ['controller' => 'product', 'id_product' => '1']],
            'Rewrite off | module controller' => [false, 'index.php?controller=modcontroller&fc=module&module=mod', 'modcontroller', ['controller' => 'modcontroller', 'fc' => 'module', 'module' => 'mod']],
            'Rewrite off | my-account controller' => [false, 'index.php?controller=myaccount', 'myaccount', ['controller' => 'myaccount']],
            'Rewrite off | beesblog post controller' => [false, 'index.php?fc=module&module=beesblog&blog_rewrite=organic-gifts&controller=post', 'post', ['controller' => 'post', 'module' => 'beesblog', 'blog_rewrite' => 'organic-gift', 'fc' => 'module']],

            // friendly url enabled, non-friendly urls
            'Rewrite on | old url | category controller' => [true, 'index.php?controller=category&id_category=1', 'category', ['controller' => 'category', 'id_category' => '1']],
            'Rewrite on | old url | supplier controller' => [true, 'index.php?controller=supplier&id_supplier=1', 'supplier', ['controller' => 'supplier', 'id_supplier' => '1']],
            'Rewrite on | old url | manufacturer controller' => [true, 'index.php?controller=manufacturer&id_manufacturer=1', 'manufacturer', ['controller' => 'manufacturer', 'id_manufacturer' => '1']],
            'Rewrite on | old url | cms controller' => [true, 'index.php?controller=cms&id_cms=1', 'cms', ['controller' => 'cms', 'id_cms' => '1']],
            'Rewrite on | old url | cms controller (category)' => [true, 'index.php?controller=cms&id_cms_category=1', 'cms', ['controller' => 'cms', 'id_cms_category' => '1']],
            'Rewrite on | old url | product controller' => [true, 'index.php?controller=product&id_product=1', 'product', ['controller' => 'product', 'id_product' => '1']],
            'Rewrite on | old url | module controller' => [true, 'index.php?controller=modcontroller&fc=module&module=mod', 'modcontroller', ['controller' => 'modcontroller', 'fc' => 'module', 'module' => 'mod']],
            'Rewrite on | old url | my-account controller' => [false, 'index.php?controller=myaccount', 'myaccount', ['controller' => 'myaccount']],
            'Rewrite on | old url | beesblog post controller' => [false, 'index.php?fc=module&module=beesblog&blog_rewrite=organic-gifts&controller=post', 'post', ['controller' => 'post', 'module' => 'beesblog', 'blog_rewrite' => 'organic-gift', 'fc' => 'module']],
        ];
    }

    /**
     * @return array
     */
    public function urlDefaultRoutes()
    {
        return array_merge($this->getUnfriendlyRoutes(), [
            // friendly url
            'Rewrite on | category controller' => [true, '/tea', 'category', ['controller' => 'category', 'id_category' => '5']],
            'Rewrite on | supplier controller' => [true, '/bee-keeper', 'supplier', ['controller' => 'supplier', 'id_supplier' => '1']],
            'Rewrite on | manufacturer controller' => [true, '/bee-hive', 'manufacturer', ['controller' => 'manufacturer', 'id_manufacturer' => '1']],
            'Rewrite on | cms controller' => [true, '/info/about-us', 'cms', ['controller' => 'cms', 'id_cms' => '4']],
            'Rewrite on | product controller' => [true, '/gifts/candle', 'product', ['controller' => 'product', 'id_product' => '1']],
            'Rewrite on | module controller' => [true, '/module/mod/modcontroller', 'modcontroller', ['controller' => 'modcontroller', 'module' => 'mod', 'fc' => 'module']],
            'Rewrite on | my-account controller' => [true, '/my-account', 'myaccount', ['controller' => 'myaccount']],
            'Rewrite on | beesblog post controller' => [true, '/blog/organic-gifts', 'post', ['controller' => 'post', 'module' => 'beesblog', 'fc' => 'module', 'blog_rewrite' => 'organic-gifts']],

            // not found
            'Rewrite on  | invalid url' => [true, '/gifts/whatever', 'pagenotfound', ['controller' => 'pagenotfound']],

            // this should probably be fixed
            'Rewrite on | invalid url (old)' => [true, 'index.php?controller=product&id_product=100000', 'product', ['controller' => 'product', 'id_product' => '100000']],
            'Rewrite off | invalid url (old)' => [false, 'index.php?controller=product&id_product=100000', 'product', ['controller' => 'product', 'id_product' => '100000']],
        ]);
    }

    /**
     * @return array
     */
    public function urlCustomRoutes()
    {
        return array_merge($this->urlDefaultRoutes(), [
            // modified default routes
            'Rewrite on | category controller' => [true, '/coffee-and-tea/tea', 'category', ['controller' => 'category', 'id_category' => '5']],
            'Rewrite on | product controller' => [true, '/gifts/1-candle', 'product', ['controller' => 'product', 'id_product' => '1']],
            'Rewrite on | product controller - wrong rewrite' => [true, '/gifts/2-not-existing-rewrite', 'product', ['controller' => 'product', 'id_product' => '2']],
            'Rewrite on | custom controller | backwards support' => [true, '/custom-url/2/friendly.html', 'controllername', [
                'fc' => 'module',
                'module' => 'samplemodule',
                'controller' => 'controllername',
                'rewrite' => 'friendly',
                // for backwards compatibility we populate 'keyword' as well as param
                'id_key' => '2',
                'id_param' => '2',
            ]],
        ]);
    }

    /**
     * Data provider for testCreateUrl
     */
    public function creteUrlData()
    {
        return [
            [ 'product_rule', [ 'categories' => 'category', 'id' => '1', 'rewrite' => 'product-rewrite' ], 'category/1-product-rewrite' ],
            [ 'module-samplemodule-controllername', [ 'id_key' => '1', 'rewrite' => 'r' ], 'custom-url/1/r.html' ],
            [ 'module-samplemodule-controllername', [ 'id_param' => '1', 'rewrite' => 'r' ], 'custom-url/1/r.html' ],
            [ 'module-samplemodule-controllername', [ 'id_param' => '1', 'id_key' => '2', 'rewrite' => 'r' ], 'custom-url/1/r.html' ],
            [ 'module-samplemodule-controllername', [ 'id_param' => '1', 'id_key' => '2', 'extra' => 'e', 'rewrite' => 'r' ], 'custom-url/1/r.html?extra=e' ],
        ];
    }

    /**
     * Returns custom routes for tests
     * @return array[]
     */
    public function getCustomRoutes()
    {
        return [
            [
                'id' => 'category_rule',
                'controller' => 'category',
                'rule' => '{categories:/}{rewrite}',
                'keywords' => [
                    'rewrite' => [
                        'regexp' => '[_a-zA-Z0-9\pL\pS-]*',
                        'param' => 'rewrite',
                    ],
                    'categories' => [
                        'regexp' => '[/_a-zA-Z0-9-\pL]*',
                    ],
                ],
            ],
            [
                'id' => 'product_rule',
                'controller' => 'product',
                'rule' => '{categories:/}{id}-{rewrite}',
                'keywords' => [
                    'id' => [
                        'regexp' => '[0-9]+',
                        'alias'  => 'id_product',
                    ],
                    'rewrite' => [
                        'regexp' => '[_a-zA-Z0-9\pL\pS-]*',
                        'param' => 'rewrite',
                    ],
                    'categories' => [
                        'regexp' => '[/_a-zA-Z0-9-\pL]*',
                    ],
                ],
            ],
            'module-samplemodule-controllername' => [
                'id' => 'module-samplemodule-controllername',
                'controller' => 'controllername',
                'rule' =>'custom-url/{id_key}/{rewrite}.html',
                'keywords' => [
                    'id_key' => ['regexp' => '[0-9]+', 'param' => 'id_param'],
                    'rewrite' => ['regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'rewrite'],
                    'module' => ['regexp' => '[_a-zA-Z0-9_-]+', 'param' => 'module'],
                    'controller' => ['regexp' => '[_a-zA-Z0-9_-]+', 'param' => 'controller'],
                ],
                'params' => [
                    'fc' => 'module',
                    'module' => 'samplemodule',
                    'controller' => 'controllername',
                ],
            ]
        ];
    }

    /**
     * @dataProvider urlDefaultRoutes
     *
     * @param bool $useFriendlyUrl
     * @param string $uri
     * @param string $expected
     * @param array $expectedGet
     * @throws PrestaShopException
     */
    public function testDefaultRoutes($useFriendlyUrl, $uri, $expected, $expectedGet)
    {
        $this->performTest($useFriendlyUrl, $uri, [], $expected, $expectedGet);
    }

    /**
     * @dataProvider urlCustomRoutes
     *
     * @param bool $useFriendlyUrl
     * @param string $uri
     * @param string $expected
     * @param array $expectedGet
     * @throws PrestaShopException
     */
    public function testCustomRoutes($useFriendlyUrl, $uri, $expected, $expectedGet)
    {
        $this->performTest($useFriendlyUrl, $uri, $this->getCustomRoutes(), $expected, $expectedGet);
    }

    /**
     * @dataProvider  creteUrlData
     * @throws PrestaShopException
     */
    public function testCreateUrl($routeId, $params, $expectedUrl)
    {
        $dispatcher = $this->getDispatcher(true, '', $this->getCustomRoutes());
        $actualUrl = $dispatcher->createUrl($routeId, 1, $params);
        static::assertEquals($expectedUrl, $actualUrl, "createURL does not match");
    }

    /**
     * @param bool $useFriendlyUrl
     * @param string $uri
     * @param array $additionalRoutes
     * @param string $expected
     * @param array $expectedGet
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function performTest($useFriendlyUrl, $uri, $additionalRoutes, $expected, $expectedGet)
    {
        $dispatcher = $this->getDispatcher($useFriendlyUrl, $uri, $additionalRoutes);

        // resolve controller
        static::assertEquals($expected, $dispatcher->getController(), "URI $uri should be resolved as $expected controller");

        // verify that $_GET array is populated correctly
        foreach ($_GET as $key => $value) {
            if (! array_key_exists($key, $expectedGet)) {
                static::fail('Unexpected variable \'' . $key .'\' with value \'' . $value . '\' found in $_GET array');
            }
        }

        // verify that $_GET array is populated correctly
        foreach ($expectedGet as $key => $value) {
            if (! array_key_exists($key, $_GET)) {
                static::fail('Expected variable \'' . $key .'\' with value \'' . $value . '\' NOT found in $_GET array');
            }
        }

        Dispatcher::$instance = null;
    }

    /**
     * This tests verifies that module route can use the same controller name as core does.
     *
     * For more info and background, see issue https://github.com/thirtybees/thirtybees/issues/1108
     *
     * @throws PrestaShopException
     */
    public function testGetControllerWithSameNameAsCore()
    {
        $uri = '/mod/category-name';
        $dispatcher = $this->getDispatcher(true, $uri, [
            [
                'id' => 'module-mod-category',
                'rule' => 'mod/{rewrite}',
                'controller' => 'category',
                'keywords' => [
                    'rewrite' => [
                        'required' => true,
                        'regexp' => '[a-z-]*',
                        'param' => 'rewrite',
                    ]
                ],
                'params' => [
                    'fc' => 'module',
                    'module' => 'mod',
                ]
            ]
        ]);
        static::assertEquals('category', $dispatcher->getController(), "URI $uri should be resolved as category controller");
    }

    /**
     * @param boolean $useFriendlyUrl
     * @param string $uri
     * @param array $additionalRoutes
     * @return Dispatcher
     * @throws PrestaShopException
     */
    private function getDispatcher($useFriendlyUrl, $uri, $additionalRoutes = [])
    {
        Dispatcher::$instance = null;
        Configuration::set('PS_REWRITING_SETTINGS', $useFriendlyUrl ? 1 : 0);
        $data = parse_url($uri);
        if (isset($data['query']) && $data['query']) {
            parse_str($data['query'], $output);
            $_GET = array_merge($_GET, $output);
        }
        $_SERVER['REQUEST_URI'] = $uri;
        $instance = Dispatcher::getInstance();
        foreach ($additionalRoutes as $route) {
            $instance->addRoute(
                isset($route['id']) ? $route['id'] : 'additional-' . md5($route['rule']),
                $route['rule'],
                $route['controller'],
                isset($route['id_lang']) ? $route['id_lang'] : null,
                isset($route['keywords']) ? $route['keywords'] : [],
                isset($route['params']) ? $route['params'] : [],
                isset($route['id_shop']) ? $route['id_shop'] : null
            );
        }
        return $instance;
    }
}
