<?php


/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = null)
 *
 * @SuppressWarnings(PHPMD)
*/
class AcceptanceTester extends \Codeception\Actor
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
