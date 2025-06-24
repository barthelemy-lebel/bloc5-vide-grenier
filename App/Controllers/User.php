<?php

namespace App\Controllers;

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
     * ✅ Affiche la page de connexion et traite la soumission
     */
    public function loginAction()
    {
        $error = false; // Stocke une erreur éventuelle

        if (isset($_POST['submit'])) {
            $f = $_POST;

            // ✅ Tente la connexion
            if ($this->login($f)) {
                header('Location: /account');
                exit;
            } else {
                $error = "Email ou mot de passe incorrect";
            }
        }

        // ✅ Passe l'erreur (s'il y en a) à la vue Twig
        View::renderTemplate('User/login.html', ['error' => $error]);
    }

    /**
     * ✅ Affiche la page d'inscription
     */
    public function registerAction()
    {
        if (isset($_POST['submit'])) {
            $f = $_POST;

            if ($f['password'] !== $f['password-check']) {
                // TODO : gérer l'affichage d'une erreur utilisateur
            }

            $this->register($f);
            // TODO : appeler login() pour connecter directement
        }

        View::renderTemplate('User/register.html');
    }

    /**
     * ✅ Page du compte utilisateur
     */
    public function accountAction()
    {
        $articles = Articles::getByUser($_SESSION['user']['id']);

        View::renderTemplate('User/account.html', [
            'articles' => $articles
        ]);
    }

    /**
     * ✅ Méthode privée d’enregistrement d’un nouvel utilisateur
     */
    private function register($data)
    {
        try {
            $salt = Hash::generateSalt(32);

            \App\Models\User::createUser([
                "email" => $data['email'],
                "username" => $data['username'],
                "password" => Hash::generate($data['password'], $salt),
                "salt" => $salt
            ]);
        } catch (Exception $ex) {
            // TODO : gestion d'erreur
        }
    }

    /**
     * ✅ Méthode privée de connexion
     */
    private function login($data)
    {
        try {
            if (!isset($data['email']) || !isset($data['password'])) {
                throw new Exception("Champs manquants");
            }

            $user = \App\Models\User::getByLogin($data['email']);

            if (!$user || Hash::generate($data['password'], $user['salt']) !== $user['password']) {
                return false; // Mauvais mot de passe ou utilisateur non trouvé
            }

            $_SESSION['user'] = [
                'id' => $user['id'],
                'username' => $user['username'],
            ];

            return true;
        } catch (Exception $ex) {
            return false;
        }
    }

    /**
     * ✅ Déconnexion de l'utilisateur
     */
    public function logoutAction()
    {
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        session_destroy();
        header("Location: /");
        return true;
    }
}
