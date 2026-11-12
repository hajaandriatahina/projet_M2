<?php

namespace App\Database\Seeds;

use App\Models\UserModel;
use CodeIgniter\Database\Seeder;

/**
 * Crée un compte de test par rôle.
 * Lancer :  php spark db:seed UserSeeder
 * Mot de passe de test : Socolait@2026  (à changer ensuite !)
 */
class UserSeeder extends Seeder
{
    public function run()
    {
        $model = new UserModel(); // le mot de passe est hashé automatiquement

        $comptes = [
            ['Admin',       'Socolait', 'Administrateur', 'Administrateur système', 'admin@socolait.com'],
            ['Directeur',   'Socolait', 'Directeur',      'Directeur',              'directeur@socolait.com'],
            ['Validateur',  'Socolait', 'Validateur',     'Validateur KPI',         'validateur@socolait.com'],
            ['Utilisateur', 'Socolait', 'Utilisateur',    'Responsable de saisie',  'utilisateur@socolait.com'],
        ];

        foreach ($comptes as [$nom, $prenom, $role, $poste, $email]) {
            if ($model->findByEmail($email) !== null) {
                continue;
            }

            $model->insert([
                'nom'           => $nom,
                'prenom'        => $prenom,
                'role'          => $role,
                'poste_travail' => $poste,
                'email'         => $email,
                'password'      => 'Socolait@2026',
                'statut'        => true,
            ]);
        }
    }
}