<?php

namespace Tests\Support;

use Codeception\Actor;

class FunctionalTester extends Actor
{
    use _generated\FunctionalTesterActions;

   /**
    * Define custom actions here
    */

    /**
     * Helper method to log in to back office
     *
     * @return void
     */
   function amLoggedInToBackOffice()
   {
       $this->amOnPage('/admin-dev/index.php?controller=AdminLogin');
       $this->fillField('#email', 'test@thirty.bees');
       $this->fillField('#passwd', 'thirtybees');
       $this->click('submitLogin');
       $this->see('Dashboard');
   }

    /**
     * Helper method to log in to front office
     *
     * @return void
     */
    public function amLoggedInToFrontOffice()
    {
        $this->amOnPage('/index.php?controller=authentication');
        $this->fillField(['css' => '#email'], 'pub@thirtybees.com');
        $this->fillField(['css' => '#passwd'], '123456789');
        $this->click('#SubmitLogin');
        $this->see("John DOE");
    }

    /**
     * Define custom actions here
     *
     * @return void
     */
    public function withoutErrors()
    {
        $result = $this->grabPageSource();
        $result = explode("window.phpMessages=", $result);
        if (count($result) > 1) {
            $messages = explode("</script>", $result[1])[0];
            $messages = trim($messages, "; \t\n\r\0\x0B");
            $messages = json_decode($messages, true);
            $messages = implode("\n", array_map(function($msg) {
                $ret = "  - " .$msg['type'].': '.$msg['message'] . ' in file ' . $msg['file'];
                if ($msg['line']) {
                    $ret .= ' at line '.$msg['line'];
                }
                return $ret;
            }, $messages));
            $this->fail("Page contains PHP messages:\n$messages\n");
        }
    }
}
