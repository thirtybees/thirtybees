<?php

use AspectMock\Test as test;

class ExtractEmailSubjectTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    public function testSingleSubject()
    {
        $this->emailSubject([
            'template' => ['subject']
        ], "
            ...;
            Mail::Send(1, 'template', Mail::l('subject'), ...);
            ..."
        );
    }

    public function testMultipleSubjects()
    {
        $this->emailSubject([
            'template' => ['subject', 'subject 2'],
            'template2' => ['subject 3']
        ], "
            ...;
            Mail::Send(1, 'template', Mail::l('subject'), ...);
            ...;
            Mail::Send(1, 'template', Mail::l('subject 2'), ...);
            ...;
            Mail::Send(1, 'template2', Mail::l('subject 3'), ...);
            "
        );
    }

    public function testWhitespace()
    {
        $this->emailSubject([
            't1' => ['s1'],
            't2' => ['s2'],
            't3' => ['s3']
        ], "
            ...;
            Mail::Send(1, 't1', Mail::l('s1'), ...);
            ...;
            Mail::Send(
              1,
              't2',
              Mail::l(
                's2'
              ),
              ...);
            ...
            Mail::Send(1, 't3'  , Mail::l(   's3'  , 1), ...);
            ...
            "
        );
    }

    public function testIgnoreLiteralSubjects()
    {
        $this->emailSubject([
          // empty
        ], "
            ...;
            Mail::Send(1, 'template', 'subject', ...);
            Mail::Send(1, 'template', \$subject, ...);
            Mail::Send(1, 'template', static::getSubject('whatever'), ...);
            ...;
            "
        );
    }

    public function testIgnoreExpressionsInSubject()
    {
        $this->emailSubject([
          // empty
        ], "
            ...;
            Mail::Send(1, 'template', Mail::l(sprintf('subject %s', \$var)), ...);
            ...;
            "
        );
    }

    public function testExpressionAroundSubject()
    {
        $this->emailSubject([
          't1' => ['subject %s'],
          't2' => ['subject']
        ], "
            ...;
            Mail::Send(1, 't1', sprintf(Mail::l('subject %s'), \$var)), ...);
            Mail::Send(1, 't2', 'prefix' . Mail::l('subject'), ...);
            ...;
            "
        );
    }

    private function emailSubject($expected, $content)
    {
        $actual = [];
        $this->tester->invokeStaticMethod('AdminTranslationsController', 'extractMailSubjects', [ $content, &$actual ]);
        $this->assertEquals($expected, $actual);
    }

}
