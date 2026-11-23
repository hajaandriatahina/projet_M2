<?php
// app/Controllers/User/Dashboard.php

namespace App\Controllers\User;

use App\Controllers\BaseController;

class Dashboard extends BaseController
{
    private const SQCDP      = ['S', 'Q', 'C', 'D', 'P'];
    private const FREQUENCES = ['Journ', 'Hebdo', 'Mensuel', 'Trimestre', 'Annuel'];

    public function index()
    {
        $db     = db_connect();
        // Le KPI affiché dépend TOUJOURS de l'utilisateur en session (jamais d'un id venant de l'URL)
        $userId = session()->get('id');

        // ---- Filtres (liste blanche + validation) ----
        $departement = trim((string) $this->request->getGet('departement'));
        $sqcdp       = strtoupper((string) $this->request->getGet('sqcdp'));
        $frequence   = (string) $this->request->getGet('frequence');
        $mois        = (string) $this->request->getGet('mois');

        if (! in_array($sqcdp, self::SQCDP, true))          $sqcdp = '';
        if (! in_array($frequence, self::FREQUENCES, true)) $frequence = '';
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $mois)) $mois = date('Y-m');

        $debut = $mois . '-01';
        $fin   = date('Y-m-t', strtotime($debut));

        // ---- Liste des départements des KPI attribués (pour le filtre) ----
        $departements = array_column(
            $db->table('configuration c')
               ->select('n.departement')->distinct()
               ->join('nom_kpi n', 'n.id = c.id_nom')
               ->where('c.id_user', $userId)
               ->orderBy('n.departement')
               ->get()->getResultArray(),
            'departement'
        );

        // ---- KPI attribués à l'utilisateur (table configuration) ----
        $b = $db->table('configuration c')
            ->select('c.id AS id_configuration, n.nom, n.departement, n.sqcdp, n.unite, n.frequence')
            ->join('nom_kpi n', 'n.id = c.id_nom')
            ->where('c.id_user', $userId);

        if ($departement !== '') $b->where('n.departement', $departement);
        if ($sqcdp !== '')       $b->where('n.sqcdp', $sqcdp);
        if ($frequence !== '')   $b->where('n.frequence', $frequence);

        $kpis = $b->orderBy('n.sqcdp')->orderBy('n.nom')->get()->getResultArray();
        $ids  = array_column($kpis, 'id_configuration');

        // ---- Saisies du mois (table saisie_kpi : id, id_configuration, valeur, date_saisie) ----
        $saisies = [];
        if ($ids) {
            $saisies = $db->table('saisie_kpi')
                ->select('id_configuration, valeur, date_saisie')
                ->where('type', 'valeur') 
                ->whereIn('id_configuration', $ids)
                ->where('date_saisie >=', $debut)
                ->where('date_saisie <=', $fin)
                ->orderBy('date_saisie', 'ASC')
                ->get()->getResultArray();
        }

        // Dernière saisie par KPI + nombre de saisies + série par jour
        $derniere = [];
        $nbSaisies = [];
        $parJour = [];
        foreach ($saisies as $s) {
            $derniere[$s['id_configuration']] = $s;
            $nbSaisies[$s['id_configuration']] = ($nbSaisies[$s['id_configuration']] ?? 0) + 1;
            $j = (int) date('j', strtotime($s['date_saisie']));
            $parJour[$j] = ($parJour[$j] ?? 0) + 1;
        }

        // ---- Cartes S / Q / C / D / P : total de KPI dans la base + saisis ce mois ----
        $cartes = [];
        foreach (self::SQCDP as $l) {
            $cartes[$l] = ['total' => 0, 'saisis' => 0];
        }
        foreach ($kpis as &$k) {
            $id = $k['id_configuration'];
            $k['derniere_valeur'] = $derniere[$id]['valeur'] ?? null;
            $k['derniere_date']   = $derniere[$id]['date_saisie'] ?? null;
            $k['nb_saisies']      = $nbSaisies[$id] ?? 0;
            if (isset($cartes[$k['sqcdp']])) {
                $cartes[$k['sqcdp']]['total']++;
                if ($k['nb_saisies'] > 0) $cartes[$k['sqcdp']]['saisis']++;
            }
        }
        unset($k);

        // ---- Données des graphiques ----
        $nbJours = (int) date('t', strtotime($debut));
        $serieJour = [];
        for ($j = 1; $j <= $nbJours; $j++) {
            $serieJour[] = $parJour[$j] ?? 0;
        }

        return view('user/dashboard', [
            'title'        => 'Tableau de bord',
            'notifCount'   => 0, // à remplacer par le vrai compteur
            'cartes'       => $cartes,
            'kpis'         => $kpis,
            'departements' => $departements,
            'frequences'   => self::FREQUENCES,
            'filtres'      => compact('departement', 'sqcdp', 'frequence', 'mois'),
            'serieJour'    => $serieJour,
            'nbJours'      => $nbJours,
        ]);
    }
}