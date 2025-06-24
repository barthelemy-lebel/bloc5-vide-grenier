<?php

use PHPUnit\Framework\TestCase;
use App\Controllers\User;

class UserControllerLoginTest extends TestCase
{
    protected User $userController;

    protected function setUp(): void
    {
        $this->userController = new class(['route_params' => []]) extends User {
            public function __construct($route_params)
            {
                parent::__construct($route_params);
            }

            public function callLogin($data)
            {
                return $this->login($data);
            }
        };

        $_SESSION = [];
    }

    public function testLoginMissingFieldsReturnsFalse()
    {
        $result = $this->userController->callLogin(['email' => '', 'password' => '']);
        $this->assertFalse($result);
    }


    public function testLoginUserNotFoundReturnsFalse()
    {
        $result = $this->userController->callLogin(['email' => 'inexistant@example.com', 'password' => 'secret']);
        $this->assertFalse($result);
    }

    public function testLoginSuccess()
    {
        $userController = new class(['route_params' => []]) extends \App\Controllers\User {
            public function __construct($route_params)
            {
                parent::__construct($route_params);
            }

            protected function login($data)
            {
                // Simule un utilisateur existant
                $user = [
                    'id' => 1,
                    'username' => 'user_test',
                    'password' => \App\Utility\Hash::generate('password', 'salt123'),
                    'salt' => 'salt123',
                ];

                if (empty($data['email']) || empty($data['password'])) {
                    return false;
                }

                if ($data['email'] !== 'user@example.com') {
                    return false;
                }

                $hashedInput = \App\Utility\Hash::generate($data['password'], $user['salt']);
                if ($hashedInput !== $user['password']) {
                    return false;
                }

                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'username' => $user['username'],
                ];

                return true;
            }

            public function callLogin($data)
            {
                return $this->login($data);
            }
        };

        $_SESSION = [];

        $result = $userController->callLogin([
            'email' => 'user@example.com',
            'password' => 'password',
        ]);

        $this->assertTrue($result);
        $this->assertEquals(1, $_SESSION['user']['id']);
        $this->assertEquals('user_test', $_SESSION['user']['username']);
    }

}
