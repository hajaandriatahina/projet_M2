<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * app/Models/UserModel.php
 * Table PostgreSQL : users
 */
class UserModel extends Model
{
    protected $table         = 'users';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false; // date_creation est remplie par PostgreSQL (DEFAULT NOW())

    protected $allowedFields = [
        'nom', 'prenom', 'numero', 'poste_travail',
        'role', 'email', 'password', 'statut',
    ];

    // Le mot de passe est haché automatiquement avant INSERT / UPDATE
    protected $beforeInsert = ['hashPassword'];
    protected $beforeUpdate = ['hashPassword'];

    /**
     * Le compte est-il actif (colonne booléenne statut) ?
     * Accepte un tableau utilisateur (ex. résultat de findByEmail) ou un id.
     */
    public function isActive($user): bool
    {
        if (is_array($user)) {
            return in_array($user['statut'] ?? false, [true, 't', 'true', '1', 1], true);
        }

        $row = $this->find((int) $user);

        return $row !== null && in_array($row['statut'] ?? false, [true, 't', 'true', '1', 1], true);
    }

    /**
     * Retrouve un utilisateur par son e-mail (utilisé par la connexion).
     * Retourne la ligne (tableau) ou null si l'e-mail n'existe pas.
     */
    public function findByEmail(string $email): ?array
    {
        return $this->where('email', strtolower(trim($email)))->first();
    }

    /**
     * Page d'accueil selon le rôle (utilisée par layout_helper et après la connexion).
     * Adapte les routes si les tiennes sont différentes.
     */
    public static function homeFor(?string $role): ?string
    {
        return [
            'Administrateur' => 'admin/dashboard',
            'Directeur'      => 'directeur/dashboard',
            'Validateur'     => 'validateur/dashboard',
            'Utilisateur'    => 'utilisateur/dashboard',
        ][$role] ?? null;
    }

    protected function hashPassword(array $data): array
    {
        if (! empty($data['data']['password'])) {
            $data['data']['password'] = password_hash($data['data']['password'], PASSWORD_DEFAULT);
        } else {
            // mot de passe vide (modification) => on ne change pas l'ancien
            unset($data['data']['password']);
        }

        return $data;
    }
}