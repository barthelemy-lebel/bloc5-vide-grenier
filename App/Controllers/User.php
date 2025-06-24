<?php

namespace App\Controllers;

use App\Models\User as UserModel;
use App\Config;
use App\Model\UserRegister;
use App\Models\Articles;
use App\Utility\Hash;
use App\Utility\Session;
use \Core\View;
use Exception;
use http\Env\Request;
use http\Exception\InvalidArgumentException;

class User extends \Core\Controller
{
    /**
     * Affiche la page de connexion et traite le login
     */
    public function loginAction()
    {
        $error = false;

        if (isset($_POST['submit'])) {
            $f = $_POST;

            if ($this->login($f)) {
                header('Location: /account');
                exit;
            } else {
                $error = "Email ou mot de passe incorrect";
            }
        }

        View::renderTemplate('User/login.html', ['error' => $error]);
    }

    /**
     * Affiche la page d'inscription et traite l'enregistrement
     */
    public function registerAction()
    {
        $error = false;

        if (isset($_POST['submit'])) {
            $f = $_POST;

            if ($f['password'] !== $f['password-check']) {
                $error = "Les mots de passe ne correspondent pas.";
            } else {
                $salt = Hash::generateSalt(32);
                $passwordHashed = Hash::generate($f['password'], $salt);

                try {
                    $id = UserModel::createUser([
                        "email" => $f['email'],
                        "username" => $f['username'],
                        "password" => $passwordHashed,
                        "salt" => $salt
                    ]);
                    
                    // Récupère l'utilisateur pour le connecter
                    $user = UserModel::getByLogin($f['email']);
                    
                    $_SESSION['user'] = [
                        'id' => $user['id'],
                        'username' => $user['username']
                    ];
                    
                    // Redirige directement vers le compte
                    header('Location: /account');
                    exit;
                } catch (Exception $e) {
                    $error = "Erreur lors de l'inscription.";
                }
            }
        }

        View::renderTemplate('User/register.html', ['error' => $error]);
    }

    /**
     * Affiche la page de compte (nécessite session active)
     */
    public function accountAction()
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit;
        }

        View::renderTemplate('User/account.html', [
            'username' => $_SESSION['user']['username']
        ]);
    }

    /**
     * Déconnecte l'utilisateur et supprime les données
     */
    public function logoutAction()
    {
        if (isset($_COOKIE['remember_me'])) {
            UserModel::storeRememberToken($_SESSION['user']['id'], null);
            setcookie('remember_me', '', time() - 3600, "/");
        }

        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        session_destroy();
        header("Location: /login");
        exit;
    }

    /**
     * Méthode privée : connecte l'utilisateur et gère "se souvenir de moi"
     */
    private function login($data)
    {
        try {
            if (empty($data['email']) || empty($data['password'])) {
                throw new Exception("Champs requis manquants");
            }

            $user = UserModel::getByLogin($data['email']);

            if (!$user) {
                return false;
            }

            $hashedInput = Hash::generate($data['password'], $user['salt']);

            if ($hashedInput !== $user['password']) {
                return false;
            }

            $_SESSION['user'] = [
                'id' => $user['id'],
                'username' => $user['username'],
            ];

            // Se souvenir de moi
            if (!empty($data['remember_me'])) {
                $token = bin2hex(random_bytes(32));
                UserModel::storeRememberToken($user['id'], $token);
                setcookie('remember_me', $token, time() + (30 * 24 * 60 * 60), "/", "", false, true);
            }

            return true;
        } catch (Exception $ex) {
            return false;
        }
    }
}