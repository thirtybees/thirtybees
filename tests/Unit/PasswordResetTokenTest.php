<?php

namespace Tests\Unit;

use Codeception\Test\Unit;
use Customer;
use PasswordControllerCore;
use ReflectionClass;
use ReflectionMethod;
use Tools;

class PasswordResetTokenTest extends Unit
{
    public function testResetTokenIsShortLivedAndBoundToCurrentPassword()
    {
        $customer = (new ReflectionClass(Customer::class))->newInstanceWithoutConstructor();
        $customer->id = 123;
        $customer->passwd = Tools::hash('old password');
        $customer->secure_key = str_repeat('a', 32);

        $create = new ReflectionMethod(PasswordControllerCore::class, 'createResetToken');
        $validate = new ReflectionMethod(PasswordControllerCore::class, 'isValidResetToken');
        $create->setAccessible(true);
        $validate->setAccessible(true);

        $token = $create->invoke(null, $customer);
        $this->assertTrue($validate->invoke(null, $token, $customer->id, $customer->passwd));
        $this->assertFalse($validate->invoke(null, $customer->secure_key, $customer->id, $customer->passwd));
        $this->assertFalse($validate->invoke(null, $token, $customer->id + 1, $customer->passwd));
        $this->assertFalse($validate->invoke(null, $token.'x', $customer->id, $customer->passwd));

        $newHash = Tools::hash('new password');
        $this->assertFalse($validate->invoke(null, $token, $customer->id, $newHash));

        $issuedAt = time() - PasswordControllerCore::RESET_TOKEN_LIFETIME - 1;
        $nonce = str_repeat('b', 32);
        $data = 'password-reset.'.$customer->id.'.'.$issuedAt.'.'.$nonce.'.'.$customer->passwd;
        $expiredToken = $issuedAt.'.'.$nonce.'.'.Tools::signature($data);
        $this->assertFalse($validate->invoke(null, $expiredToken, $customer->id, $customer->passwd));

        $issuedAt = time() + 60;
        $data = 'password-reset.'.$customer->id.'.'.$issuedAt.'.'.$nonce.'.'.$customer->passwd;
        $futureToken = $issuedAt.'.'.$nonce.'.'.Tools::signature($data);
        $this->assertFalse($validate->invoke(null, $futureToken, $customer->id, $customer->passwd));
    }
}
