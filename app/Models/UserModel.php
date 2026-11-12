<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps    = false; // date_creation a un DEFAULT CURRENT_TIMESTAMP en base

    protected $allowedFields = [
        'nom', 'prenom', 'numero', 'poste_travail',
        'role', 'email', 'password', 'statut',
    ];

    // Hash automatique du mot de passe + e-mail en minuscules
    protected $beforeInsert = ['normalizeEmail', 'hashPassword'];
    protected $beforeUpdate = ['normalizeEmail', 'hashPassword'];

    /**
     * Page d'accueil de chaque rôle (4 rôles = 4 pages différentes).
     * Modifie ici si tu changes les URLs.
     */
    public const ROLE_HOME = [
        'Administrateur' => 'admin/dashboard',
        'Directeur'      => 'directeur/dashboard',
        'Validateur'     => 'validateur/dashboard',
        'Utilisateur'    => 'utilisateur/dashboard',
    ];

    public static function homeFor(string $role): ?string
    {
        return self::ROLE_HOME[$role] ?? null;
    }

    public function findByEmail(string $email): ?array
    {
        return $this->where('email', strtolower(trim($email)))->first();
    }

    /**
     * PostgreSQL renvoie les booléens sous forme de 't' / 'f'.
     */
    public function isActive(array $user): bool
    {
        return in_array($user['statut'], [true, 't', 'true', '1', 1], true);
    }

    // ---------------- Callbacks ----------------

    protected function hashPassword(array $data): array
    {
        if (! isset($data['data']['password'])) {
            return $data;
        }

        $password = $data['data']['password'];

        if ($password === '') {
            unset($data['data']['password']); // ne pas écraser le mot de passe existant
            return $data;
        }

        // Ne re-hash pas une valeur déjà hashée
        if (empty(password_get_info($password)['algo'])) {
            $data['data']['password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        return $data;
    }

    protected function normalizeEmail(array $data): array
    {
        if (isset($data['data']['email'])) {
            $data['data']['email'] = strtolower(trim($data['data']['email']));
        }

        return $data;
    }
}