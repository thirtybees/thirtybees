<?php

namespace Tests\Support;

use Codeception\Actor;


class AcceptanceTester extends Actor
{
    use _generated\AcceptanceTesterActions;

   /**
    * Define custom actions here
    *
    * @return void
    */
    public function withoutErrors()
    {
        $result = json_decode($this->executeJS('return JSON.stringify(window.phpMessages || [])', 30), true);
        if ($result) {
            $messages = implode("\n", array_map(function($msg) {
                $ret = "  - " .$msg['type'].': '.$msg['message'] . ' in file ' . $msg['file'];
                if ($msg['line']) {
                    $ret .= ' at line '.$msg['line'];
                }
                return $ret;
            }, $result));
            $this->fail("Page contains PHP messages:\n$messages\n");
        }
    }
}
