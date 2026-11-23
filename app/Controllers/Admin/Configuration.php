<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ConfigurationModel;
use App\Models\IndicateurModel;
use App\Models\UserModel;

/**
 * app/Controllers/Admin/Configuration.php
 * Attribue à chaque utilisateur les KPI (nom_kpi) qu'il a le droit de renseigner.
 * Protection : filtre 'role:Administrateur' sur le groupe de routes.
 */
class Configuration extends BaseController
{
    private UserModel          $users;
    private IndicateurModel    $kpis;
    private ConfigurationModel $config;

    public function __construct()
    {
        $this->users  = new UserModel();
        $this->kpis   = new IndicateurModel();
        $this->config = new ConfigurationModel();
    }

    private function page(): string
    {
        return site_url('admin/configuration');
    }

    /* ============ GET admin/configuration ============ */
    public function index()
    {
        // Tous les comptes sauf les administrateurs (ce sont eux qui configurent, pas qui saisissent).
        $users = $this->users
            ->select('id, nom, prenom, email, role, statut')
            ->where('role !=', 'Administrateur')
            ->orderBy('nom', 'ASC')->orderBy('prenom', 'ASC')
            ->findAll();

        $kpis = $this->kpis
            ->select('id, nom, departement, sqcdp')
            ->orderBy('departement', 'ASC')->orderBy('nom', 'ASC')
            ->findAll();

        $assignedByUser = [];
        foreach ($users as $u) {
            $assignedByUser[(int) $u['id']] = $this->config->idsForUser((int) $u['id']);
        }

        $counts     = $this->config->countsByUser();
        $totalUsers = count($users);
        $sansKpi    = count(array_filter($users, static fn ($u) => ($counts[(int) $u['id']] ?? 0) === 0));

        $cards = [
            ['label' => 'Comptes à configurer', 'value' => $totalUsers, 'chip' => null, 'hint' => 'Hors administrateurs'],
            ['label' => 'KPI dans l\'inventaire', 'value' => count($kpis), 'chip' => null, 'hint' => 'Table nom_kpi'],
            ['label' => 'Comptes sans aucun KPI', 'value' => $sansKpi, 'chip' => $sansKpi > 0 ? 'À vérifier' : null, 'hint' => 'Formulaire de saisie vide'],
        ];

        return view('configuration/index', [
            'role'           => 'Administrateur',
            'title'          => 'Configuration des KPI',
            'heading'        => 'Configuration des KPI par utilisateur',
            'description'    => "Choisissez, pour chaque utilisateur, les KPI de l'inventaire qu'il est autorisé à renseigner.",
            'cards'          => $cards,
            'users'          => $users,
            'kpis'           => $kpis,
            'assignedByUser' => $assignedByUser,
            'counts'         => $counts,
            'departements'   => Indicateurs::DEPARTEMENTS,
        ]);
    }

    /* ============ POST admin/configuration/save/{id_user} ============ */
    public function save(int $userId)
    {
        $user = $this->users->find($userId);
        if (! $user) {
            return redirect()->to($this->page())->with('error', 'Utilisateur introuvable.');
        }

        // kpis[] contient les id_nom cochés ; aucun champ requis (une liste vide retire tout).
        $kpiIds = $this->request->getPost('kpis') ?? [];
        if (! is_array($kpiIds)) {
            $kpiIds = [];
        }

        try {
            $this->config->replaceForUser($userId, $kpiIds);
        } catch (\Throwable $e) {
            log_message('error', 'admin/configuration : ' . $e->getMessage());

            return redirect()->to($this->page())->with('error', 'Erreur base de données : ' . $e->getMessage());
        }

        $nb = count(array_unique(array_map('intval', $kpiIds)));

        return redirect()->to($this->page())->with(
            'success',
            esc($user['prenom'] . ' ' . $user['nom']) . " : {$nb} KPI attribué(s)."
        );
    }
}