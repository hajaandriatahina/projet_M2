<?php
// app/Controllers/User/Dashboard.php

namespace App\Controllers\User;

use App\Controllers\BaseController;

class Dashboard extends BaseController
{
    private const SQCDP = ['S', 'Q', 'C', 'D', 'P'];
    private const FREQS = ['journalier', 'hebdomadaire', 'mensuel'];

    private const MOIS_C = ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];
    private const MOIS_L = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];

    public function index()
    {
        $db     = db_connect();
        // Toujours l'utilisateur en session (jamais un id venant de l'URL)
        $userId = (int) session()->get('id');

        // ---- Mois de référence (seul filtre qui recharge la page) ----
        $mois = (string) $this->request->getGet('mois');
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $mois)) {
            $mois = date('Y-m');
        }
        $premier = $mois . '-01';

        // ---- Filtres en cascade (appliqués côté navigateur, ici on garde juste la sélection) ----
        $filtres = [
            'departement' => trim((string) $this->request->getGet('departement')),
            'sqcdp'       => strtoupper((string) $this->request->getGet('sqcdp')),
            'frequence'   => (string) $this->request->getGet('frequence'),
            'kpi'         => (string) $this->request->getGet('kpi'),
            'mois'        => $mois,
        ];
        if (! in_array($filtres['sqcdp'], self::SQCDP, true))      $filtres['sqcdp'] = '';
        if (! in_array($filtres['frequence'], self::FREQS, true)) $filtres['frequence'] = '';

        // ---- Périodes affichées selon la fréquence ----
        $periodes = [
            'journalier'   => $this->periodeJours($premier),
            'hebdomadaire' => $this->periodeSemaines($premier),
            'mensuel'      => $this->periodeMois($premier),
        ];

        // ---- KPI attribués à l'utilisateur (table configuration) ----
        $rows = $db->table('configuration c')
            ->select('c.id AS id_configuration, n.nom, n.departement, n.sqcdp, n.unite, n.frequence')
            ->join('nom_kpi n', 'n.id = c.id_nom')
            ->where('c.id_user', $userId)
            ->orderBy('n.sqcdp')->orderBy('n.nom')
            ->get()->getResultArray();

        $kpis = [];
        $idsParFreq = ['journalier' => [], 'hebdomadaire' => [], 'mensuel' => []];
        foreach ($rows as $r) {
            $f = $this->normFreq((string) $r['frequence']);
            if ($f === null) continue;           // Trimestre / Annuel : pas de saisie dans Mes KPI
            $r['freq'] = $f;
            $id = (int) $r['id_configuration'];
            $kpis[$id] = $r;
            $idsParFreq[$f][] = $id;
        }

        // ---- Valeurs + objectifs des périodes affichées ----
        $data = []; // [id_configuration][type][date] => valeur
        foreach ($idsParFreq as $f => $ids) {
            if (! $ids) continue;
            $res = $db->table('saisie_kpi')
                ->select('id_configuration, type, date_saisie, valeur')
                ->whereIn('type', ['valeur', 'objectif'])
                ->whereIn('id_configuration', $ids)
                ->whereIn('date_saisie', $periodes[$f]['dates'])
                ->get()->getResultArray();
            foreach ($res as $s) {
                $data[(int) $s['id_configuration']][$s['type']][$s['date_saisie']] = (float) $s['valeur'];
            }
        }

        // ---- Séries d'évolution par KPI + cartes S/Q/C/D/P ----
        $cartes = [];
        foreach (self::SQCDP as $l) {
            $cartes[$l] = ['total' => 0, 'saisis' => 0];
        }

        $kpisJs = [];
        foreach ($kpis as $id => $k) {
            $p = $periodes[$k['freq']];
            $valeurs = $objectifs = [];
            $obj = null;
            $nb  = 0;
            foreach ($p['dates'] as $d) {
                $v = $data[$id]['valeur'][$d] ?? null;
                if ($v !== null) $nb++;
                $valeurs[] = $v;

                // l'objectif reste valable tant qu'un nouvel objectif n'est pas saisi
                if (isset($data[$id]['objectif'][$d])) $obj = $data[$id]['objectif'][$d];
                $objectifs[] = $obj;
            }

            $kpisJs[] = [
                'id'          => $id,
                'nom'         => $k['nom'],
                'departement' => $k['departement'],
                'sqcdp'       => $k['sqcdp'],
                'unite'       => $k['unite'],
                'freq'        => $k['freq'],
                'titrePeriode'=> $p['titre'],
                'labels'      => $p['labels'],
                'valeurs'     => $valeurs,
                'objectifs'   => $objectifs,
                'nb'          => $nb,
            ];

            if (isset($cartes[$k['sqcdp']])) {
                $cartes[$k['sqcdp']]['total']++;
                if ($nb > 0) $cartes[$k['sqcdp']]['saisis']++;
            }
        }

        $departements = array_values(array_unique(array_column($kpis, 'departement')));
        sort($departements);

        return view('user/dashboard', [
            'title'        => 'Tableau de bord',
            'notifCount'   => 0, // à remplacer par le vrai compteur
            'cartes'       => $cartes,
            'kpisJs'       => $kpisJs,
            'departements' => $departements,
            'filtres'      => $filtres,
            'moisLabel'    => self::MOIS_L[(int) substr($mois, 5, 2) - 1] . ' ' . substr($mois, 0, 4),
        ]);
    }

    // =====================================================================
    //  PÉRIODES
    // =====================================================================

    /** Journalier : tous les jours du mois (1 -> dernier jour) */
    private function periodeJours(string $premier): array
    {
        $ts = strtotime($premier);
        $nb = (int) date('t', $ts);
        $dates = $labels = [];
        for ($j = 1; $j <= $nb; $j++) {
            $dates[]  = date('Y-m-', $ts) . sprintf('%02d', $j);
            $labels[] = (string) $j;
        }
        return [
            'dates'  => $dates,
            'labels' => $labels,
            'titre'  => self::MOIS_L[(int) date('n', $ts) - 1] . ' ' . date('Y', $ts),
        ];
    }

    /** Hebdomadaire : les semaines des 3 derniers mois (ex. S36 ... S49) */
    private function periodeSemaines(string $premier): array
    {
        $ts       = strtotime($premier);
        $finMois  = strtotime(date('Y-m-t', $ts));
        $debutFen = strtotime('-2 month', $ts);
        $lundi    = strtotime('monday this week', $debutFen);

        $dates = $labels = [];
        while ($lundi <= $finMois) {
            $dates[]  = date('Y-m-d', $lundi);
            $labels[] = 'S' . date('W', $lundi);
            $lundi    = strtotime('+7 day', $lundi);
        }

        $titre = self::MOIS_C[(int) date('n', $debutFen) - 1] . ' – ' . self::MOIS_C[(int) date('n', $ts) - 1] . ' ' . date('Y', $ts);
        return ['dates' => $dates, 'labels' => $labels, 'titre' => $titre];
    }

    /** Mensuel : les 12 mois de l'année */
    private function periodeMois(string $premier): array
    {
        $annee = (int) substr($premier, 0, 4);
        $dates = $labels = [];
        for ($m = 1; $m <= 12; $m++) {
            $dates[]  = sprintf('%04d-%02d-01', $annee, $m);
            $labels[] = self::MOIS_C[$m - 1];
        }
        return ['dates' => $dates, 'labels' => $labels, 'titre' => (string) $annee];
    }

    private function normFreq(string $f): ?string
    {
        $f = mb_strtolower(trim($f));
        if (str_starts_with($f, 'jour') || str_starts_with($f, 'quot')) return 'journalier';
        if (str_starts_with($f, 'hebdo'))                              return 'hebdomadaire';
        if (str_starts_with($f, 'mens'))                               return 'mensuel';
        return null;
    }
}