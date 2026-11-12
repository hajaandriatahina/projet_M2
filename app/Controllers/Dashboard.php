<?php

namespace App\Controllers;

use App\Models\UserModel;

/**
 * Une méthode par rôle (routes déjà protégées par RoleFilter).
 * Les rôles Directeur / Validateur / Utilisateur affichent pour l'instant
 * des cartes vides ("—") : à brancher sur tes tables KPI.
 */
class Dashboard extends BaseController
{
    public function administrateur()
    {
        $model = new UserModel();

        $total    = $model->countAllResults();
        $actifs   = $model->where('statut = TRUE', null, false)->countAllResults();
        $inactifs = max(0, $total - $actifs);

        $liste = $model
            ->select('id, nom, prenom, email, poste_travail, role, statut, date_creation')
            ->orderBy('date_creation', 'DESC')
            ->findAll(200);

        $cards = [
            ['label' => 'Total utilisateurs',  'value' => $total,    'chip' => null,
             'hint'  => 'Comptes enregistrés'],
            ['label' => 'Comptes actifs',      'value' => $actifs,
             'chip'  => $total > 0 ? round($actifs / $total * 100) . ' % du total' : null,
             'hint'  => 'Peuvent se connecter'],
            ['label' => 'Comptes désactivés',  'value' => $inactifs, 'chip' => null,
             'hint'  => 'Accès bloqué'],
        ];

        return $this->render(
            'Administrateur',
            'Utilisateurs',
            'Ajoutez, recherchez et gérez les comptes de l\'application au même endroit.',
            'Ajouter un utilisateur',
            $cards,
            $liste
        );
    }

    public function directeur()
    {
        return $this->render(
            'Directeur',
            'Vue d\'ensemble',
            'Suivez la performance globale de vos départements.',
            'Voir les rapports',
            $this->emptyCards('Indicateurs suivis', 'En attente de validation', 'Départements')
        );
    }

    public function validateur()
    {
        return $this->render(
            'Validateur',
            'Validation des indicateurs',
            'Consultez et validez les indicateurs saisis par les utilisateurs.',
            'Voir les validations',
            $this->emptyCards('À valider', 'Validés ce mois', 'Rejetés')
        );
    }

    public function utilisateur()
    {
        return $this->render(
            'Utilisateur',
            'Mon espace',
            'Saisissez vos indicateurs de performance et suivez leur avancement.',
            'Nouvelle saisie',
            $this->emptyCards('Mes indicateurs', 'Saisies du mois', 'En attente')
        );
    }

    // ------------------------------------------------------------------

    private function emptyCards(string $a, string $b, string $c): array
    {
        $hint = 'À connecter aux tables KPI';

        return [
            ['label' => $a, 'value' => '—', 'chip' => null, 'hint' => $hint],
            ['label' => $b, 'value' => '—', 'chip' => null, 'hint' => $hint],
            ['label' => $c, 'value' => '—', 'chip' => null, 'hint' => $hint],
        ];
    }

    private function render(
        string $role,
        string $heading,
        string $description,
        string $ctaLabel,
        array $cards,
        ?array $users = null
    ): string {
        helper('layout');

        return view('dashboard/index', [
            'title'       => $heading,
            'role'        => $role,
            'heading'     => $heading,
            'description' => $description,
            'ctaLabel'    => $ctaLabel,
            'cards'       => $cards,
            'users'       => $users,
        ]);
    }
}