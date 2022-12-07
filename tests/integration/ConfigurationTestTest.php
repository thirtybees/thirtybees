<?php

class ConfigurationTestTest extends \Codeception\Test\Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @return void
     */
    public function testCheck()
    {
        $this->assertEquals(['PdoMysql' => 'ok'], ConfigurationTest::check(['PdoMysql' => false]));
    }

    /**
     * @return void
     */
    public function testRun()
    {
        $this->assertEquals('ok', ConfigurationTest::run('PdoMysql'));
    }

    /**
     * @return array
     */
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
     * @param string $test
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
}
