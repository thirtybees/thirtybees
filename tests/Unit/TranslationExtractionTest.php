<?php

use PHPUnit\Framework\TestCase;

if (!defined('_PS_DEFAULT_THEME_NAME_')) {
    define('_PS_DEFAULT_THEME_NAME_', 'community-theme-default');
}

class TranslationExtractionTest extends TestCase
{
    public function testExtractsSingleAndDoubleQuotedStrings(): void
    {
        $content = '<?php $this->l("double quote"); $this->l(\'single quote\');';

        $refClass = new ReflectionClass(AdminTranslationsControllerCore::class);
        $controller = $refClass->newInstanceWithoutConstructor();
        $method = $refClass->getMethod('userParseFile');
        $method->setAccessible(true);
        $result = $method->invoke($controller, $content, 'back', 'php');

        $this->assertContains('double quote', $result);
        $this->assertContains('single quote', $result);
    }
}
