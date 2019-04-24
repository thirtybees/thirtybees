<?php

class ConfigurationTestTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

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
            'System'                  => [
                'fopen', 'fclose', 'fread', 'fwrite',
                'rename', 'file_exists', 'unlink', 'rmdir', 'mkdir',
                'getcwd', 'chdir', 'chmod',
            ],
            'PhpVersion'              => false,
            'Fopen'                   => false,
            'ConfigDir'               => 'config',
            'Files'                   => false,
            'MailsDir'                => 'mails',
            'MaxExecutionTime'        => false,
            'MysqlVersion'            => false,
            'Bcmath'                  => false,
            'Gd'                      => false,
            'Json'                    => false,
            'Mbstring'                => false,
            'OpenSSL'                 => false,
            'PdoMysql'                => false,
            'Xml'                     => false,
            'Zip'                     => false,
        ];

        $this->assertEquals($expected, ConfigurationTest::getDefaultTests());
    }

    public function testGetDefaultTestsOption()
    {
        $expected = [
            'Gz'              => false,
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
            ['Bcmath', false],
            ['CacheDir', 'cache'],
            ['ConfigDir', 'config'],
            ['CustomizableProductsDir', 'upload'],
            ['Files', false],
            ['Gd', false],
            ['ImgDir', 'img'],
            ['Json', false],
            ['LogDir', 'log'],
            ['MailsDir', 'mails'],
            ['MaxExecutionTime', false],
            ['Mbstring', false],
            ['ModuleDir', 'modules'],
            ['OpenSSL', false],
            ['PdoMysql', false],
            ['PhpVersion', false],
            ['System', ['fopen', 'fclose', 'fread', 'fwrite', 'rename', 'file_exists', 'unlink', 'rmdir', 'mkdir', 'getcwd', 'chdir', 'chmod']],
            ['ThemeLangDir', 'themes/'._THEME_NAME_.'/lang/'],
            ['ThemePdfLangDir', 'themes/'._THEME_NAME_.'/pdf/lang/'],
            ['ThemeCacheDir', 'themes/'._THEME_NAME_.'/cache/'],
            ['TranslationsDir', 'translations'],
            ['Upload', false],
            ['VirtualProductsDir', 'download'],
            ['Xml', false],
            ['Zip', false],
        ];
    }

    /**
     * @dataProvider checkProvider
     *
     * @param string     $test
     * @param array|null $args (string|null?)
     */
    public function testTestsShouldBeOk($test, $args)
    {
        if ($args) {
            // Use call_user_func_array() rather than call_user_func()
            // to allow $args to be a reference.
            $this->assertTrue(
                (bool) call_user_func_array(['ConfigurationTest', 'test'.$test],
                                            [$args])
            );
        } else {
            $this->assertTrue(
                (bool) call_user_func(['ConfigurationTest', 'test'.$test])
            );
        }
    }

    protected function _before()
    {
    }

    protected function _after()
    {
    }
}
