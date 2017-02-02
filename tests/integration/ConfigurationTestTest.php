<?php


class ConfigurationTestTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
    }

    protected function _after()
    {
    }

    public function testGetDefaultTests()
    {
        $expected = [
            'Upload'                  => false,
            'CacheDir'                => 'cache',
            'LogDir'                  => 'log',
            'ImgDir'                  => 'img',
            'ModuleDir'               => 'modules',
            'ThemeLangDir'            => 'themes/'._THEME_NAME_.'/lang/',
            'ThemePdfLangDir'         => 'themes/'._THEME_NAME_.'/pdf/lang/',
            'ThemeCacheDir'           => 'themes/'._THEME_NAME_.'/cache/',
            'TranslationsDir'         => 'translations',
            'CustomizableProductsDir' => 'upload',
            'VirtualProductsDir'      => 'download',
            'System'        => [
                'fopen', 'fclose', 'fread', 'fwrite',
                'rename', 'file_exists', 'unlink', 'rmdir', 'mkdir',
                'getcwd', 'chdir', 'chmod',
            ],
            'PhpVersion' => false,
            'Gd'         => false,
            'ConfigDir'  => 'config',
            'Files'      => false,
            'MailsDir'   => 'mails',
            'PdoMysql'   => false,
            'Bcmath'     => false,
            'Xml'        => false,
            'Json'       => false,
            'Zip'        => false,
        ];

        $this->assertEquals($expected, ConfigurationTest::getDefaultTests());
    }

    public function testGetDefaultTestsOption()
    {
        $expected = [
            'NewPhpVersion'   => false,
            'RegisterGlobals' => false,
            'Gz'               => false,
            'Mbstring'         => false,
            'Tlsv12'          => false,
        ];

        $this->assertEquals($expected, ConfigurationTest::getDefaultTestsOp());
    }

    public function testCheck()
    {
        $this->assertEquals(['PhpVersion' => 'ok'], ConfigurationTest::check(['PhpVersion' => false]));
    }

    public function testRun()
    {
        $this->assertEquals('ok', ConfigurationTest::run('PhpVersion'));
    }

    public function checkProvider()
    {
        return [
            ['Upload', false],
            ['CacheDir', 'cache'],
            ['LogDir', 'log'],
            ['ImgDir', 'img'],
            ['ModuleDir', 'modules'],
            ['ThemeLangDir', 'themes/'._THEME_NAME_.'/lang/'],
            ['ThemePdfLangDir', 'themes/'._THEME_NAME_.'/pdf/lang/'],
            ['ThemeCacheDir', 'themes/'._THEME_NAME_.'/cache/'],
            ['TranslationsDir', 'translations'],
            ['CustomizableProductsDir', 'upload'],
            ['VirtualProductsDir', 'download'],
            [
                'System',
                [
                    'fopen', 'fclose', 'fread', 'fwrite',
                    'rename', 'file_exists', 'unlink', 'rmdir', 'mkdir',
                    'getcwd', 'chdir', 'chmod',
                ],
            ],
            ['ConfigDir', 'config'],
            ['MailsDir', 'mails'],
            ['PhpVersion', false],
            ['Gd', false],
            ['Files', false],
            ['PdoMysql', false],
            ['Bcmath', false],
            ['Xml', false],
            ['Json', false],
            ['Zip', false],
        ];
    }

    /**
     * @dataProvider checkProvider
     *
     * @param string     $test
     * @param array|null $args
     */
    public function testTestsShouldBeOk($test, $args)
    {
        $this->assertTrue((bool) call_user_func(['ConfigurationTest', 'test'.$test], $args ? $args : null));
    }
}
