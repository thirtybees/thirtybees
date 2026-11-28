<?php

namespace Tests\Integration;

use Codeception\Test\Unit;
use Configuration;
use Customer;
use PasswordControllerCore;
use PrestaShopException;
use ReflectionMethod;
use Tools;

class PasswordResetTest extends Unit
{
    public function testResetLinkCanChangePasswordOnlyOnce()
    {
        $customer = new Customer();
        $customer->firstname = 'Reset';
        $customer->lastname = 'Test';
        $customer->email = 'reset-'.bin2hex(random_bytes(8)).'@example.invalid';
        $customer->passwd = Tools::hash('old password');
        $customer->id_default_group = (int)Configuration::get('PS_CUSTOMER_GROUP');
        $customer->active = 1;

        $originalPost = $_POST;
        try {
            $this->assertTrue($customer->add());
            $secureKey = $customer->secure_key;

            $create = new ReflectionMethod(PasswordControllerCore::class, 'createResetToken');
            $resolve = new ReflectionMethod(PasswordControllerCore::class, 'resolveCustomer');
            $create->setAccessible(true);
            $resolve->setAccessible(true);
            $token = $create->invoke(null, $customer);
            $_POST = ['token' => $token, 'id_customer' => $customer->id];
            $this->assertSame((int)$customer->id, (int)$resolve->invoke(null)->id);

            $controller = new PasswordControllerCore();
            $controller->setNewPassword(new Customer($customer->id), 'new password');
            $this->assertEmpty($controller->errors);

            $updated = new Customer($customer->id);
            $this->assertTrue(password_verify('new password', $updated->passwd));
            $this->assertSame($secureKey, $updated->secure_key);

            $refresh = new ReflectionMethod(PasswordControllerCore::class, 'refreshCustomerForReset');
            $refresh->setAccessible(true);
            $this->assertNotSame($updated->passwd, $customer->passwd);
            $this->assertTrue($refresh->invoke(null, $customer, $customer->email));
            $this->assertSame($updated->passwd, $customer->passwd);
            $_POST['token'] = $create->invoke(null, $customer);
            $this->assertSame((int)$customer->id, (int)$resolve->invoke(null)->id);
            $_POST['token'] = $token;

            $replay = new PasswordControllerCore();
            $replay->setNewPassword(new Customer($customer->id), 'replayed password');
            $this->assertNotEmpty($replay->errors);
            $this->assertSame($updated->passwd, (new Customer($customer->id))->passwd);
            $this->assertResetLinkRejected($resolve);

            $_POST['token'] = $secureKey;
            $this->assertResetLinkRejected($resolve);
            $legacy = new PasswordControllerCore();
            $legacy->setNewPassword(new Customer($customer->id), 'legacy password');
            $this->assertNotEmpty($legacy->errors);
            $this->assertSame($updated->passwd, (new Customer($customer->id))->passwd);
        } finally {
            $_POST = $originalPost;
            if ($customer->id) {
                (new Customer($customer->id))->delete();
            }
        }
    }

    private function assertResetLinkRejected(ReflectionMethod $resolve)
    {
        try {
            $resolve->invoke(null);
            $this->fail('A stale reset link was accepted');
        } catch (PrestaShopException $e) {
            $this->assertNotEmpty($e->getMessage());
        }
    }
}
