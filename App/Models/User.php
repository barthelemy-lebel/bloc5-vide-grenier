<?php

namespace App\Models;

use App\Utility\Hash;
use Core\Model;
use App\Core;
use Exception;
use App\Utility;

/**
 * Modèle User : gestion des utilisateurs
 */
class User extends Model
{
    /**
     * Crée un utilisateur dans la base de données
     */
    public static function createUser($data)
    {
        $db = static::getDB();

        $stmt = $db->prepare('
            INSERT INTO users (username, email, password, salt)
            VALUES (:username, :email, :password, :salt)
        ');

        $stmt->bindParam(':username', $data['username']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':password', $data['password']);
        $stmt->bindParam(':salt', $data['salt']);

        $stmt->execute();

        return $db->lastInsertId();
    }

    /**
     * Récupère un utilisateur par son email (connexion)
     */
    public static function getByLogin($email)
    {
        $db = static::getDB();

        $stmt = $db->prepare('
            SELECT * FROM users WHERE email = :email LIMIT 1
        ');

        $stmt->bindParam(':email', $email);
        $stmt->execute();

        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Stocke un token "remember me" pour un utilisateur
     */
    public static function storeRememberToken($userId, $token)
    {
        $db = static::getDB();

        $stmt = $db->prepare('
            UPDATE users SET remember_token = :token WHERE id = :id
        ');

        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':id', $userId);
        $stmt->execute();
    }

    /**
     * Récupère un utilisateur via son token "remember me"
     */
    public static function getByRememberToken($token)
    {
        $db = static::getDB();

        $stmt = $db->prepare('
            SELECT * FROM users WHERE remember_token = :token LIMIT 1
        ');

        $stmt->bindParam(':token', $token);
        $stmt->execute();

        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
}