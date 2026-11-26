<?php
// app/Controllers/User/Rapport.php

namespace App\Controllers\User;

use App\Controllers\BaseController;

class Rapport extends BaseController
{
    private const FREQS = ['journalier', 'hebdomadaire', 'mensuel'];

    private const MOIS_C = ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];
    private const MOIS_L = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];

    public function index()
    {
        $db     = db_connect();
        // Toujours l'utilisateur en session (jamais un id venant de l'URL)
        $userId = (int) session()->get('id');

        // ---- Filtres : mois (recharge la page), fréquence et KPI (gardés dans l'URL) ----
        $mois = (string) $this->request->getGet('mois');
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $mois)) {
            $mois = date('Y-m');
        }
        $premier = $mois . '-01';
        $dernier = date('Y-m-t', strtotime($premier));

        $freq = (string) $this->request->getGet('freq');
        $kpi  = (string) $this->request->getGet('kpi');

        // ---- Périodes du graphique selon la fréquence (comme le dashboard) ----
        $periodes = [
            'journalier'   => $this->periodeJours($premier),
            'hebdomadaire' => $this->periodeSemaines($premier),
            'mensuel'      => $this->periodeMois($premier),
        ];

        // ---- KPI attribués à l'utilisateur ----
        $rows = $db->table('configuration c')
            ->select('c.id AS id_configuration, n.nom, n.departement, n.sqcdp, n.unite, n.frequence')
            ->join('nom_kpi n', 'n.id = c.id_nom')
            ->where('c.id_user', $userId)
            ->orderBy('n.departement')->orderBy('n.nom')
            ->get()->getResultArray();

        $kpis = [];
        $idsParFreq = ['journalier' => [], 'hebdomadaire' => [], 'mensuel' => []];
        foreach ($rows as $r) {
            $f = $this->normFreq((string) $r['frequence']);
            if ($f === null) continue;
            $r['freq'] = $f;
            $id = (int) $r['id_configuration'];
            $kpis[$id] = $r;
            $idsParFreq[$f][] = $id;
        }

        // Onglet par défaut : Mensuel s'il a des KPI, sinon le premier onglet qui en a
        if (! in_array($freq, self::FREQS, true)) {
            $freq = 'mensuel';
            if (! $idsParFreq['mensuel']) {
                foreach (self::FREQS as $f) {
                    if ($idsParFreq[$f]) { $freq = $f; break; }
                }
            }
        }

        // ---- Valeurs + objectifs des périodes affichées ----
        $data = [];
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

        $kpisJs = [];
        foreach ($kpis as $id => $k) {
            $p = $periodes[$k['freq']];
            $valeurs = $objectifs = [];
            $obj = null;
            foreach ($p['dates'] as $d) {
                $valeurs[] = $data[$id]['valeur'][$d] ?? null;
                // l'objectif reste valable tant qu'un nouvel objectif n'est pas saisi
                if (isset($data[$id]['objectif'][$d])) $obj = $data[$id]['objectif'][$d];
                $objectifs[] = $obj;
            }
            $kpisJs[] = [
                'id'           => $id,
                'nom'          => $k['nom'],
                'departement'  => $k['departement'],
                'unite'        => $k['unite'],
                'freq'         => $k['freq'],
                'titrePeriode' => $p['titre'],
                'labels'       => $p['labels'],
                'longs'        => $p['longs'],
                'valeurs'      => $valeurs,
                'objectifs'    => $objectifs,
            ];
        }

        // ---- Plans d'action du MOIS filtré (échéance dans le mois) pour les KPI de l'utilisateur ----
        $plans = $db->table('plan_action p')
            ->select('p.id, p.id_configuration, p.observation, p.action, p.responsable, p.echeance, n.nom AS nom_kpi, n.departement')
            ->join('configuration c', 'c.id = p.id_configuration')
            ->join('nom_kpi n', 'n.id = c.id_nom')
            ->where('c.id_user', $userId)
            ->where('p.echeance >=', $premier)
            ->where('p.echeance <=', $dernier)
            ->orderBy('p.echeance', 'ASC')
            ->orderBy('p.id', 'ASC')
            ->get()->getResultArray();

        return view('user/rapport', [
            'title'      => 'Rapport',
            'notifCount' => 0,
            'kpisJs'     => $kpisJs,
            'plans'      => $plans,
            'filtres'    => ['freq' => $freq, 'kpi' => $kpi, 'mois' => $mois],
            'moisLabel'  => self::MOIS_L[(int) substr($mois, 5, 2) - 1] . ' ' . substr($mois, 0, 4),
        ]);
    }

    // =====================================================================
    //  PÉRIODES (identiques au dashboard)
    // =====================================================================

    /** Journalier : tous les jours du mois (1 -> dernier jour) */
    private function periodeJours(string $premier): array
    {
        $ts = strtotime($premier);
        $nb = (int) date('t', $ts);
        $mL = self::MOIS_L[(int) date('n', $ts) - 1] . ' ' . date('Y', $ts);
        $dates = $labels = $longs = [];
        for ($j = 1; $j <= $nb; $j++) {
            $dates[]  = date('Y-m-', $ts) . sprintf('%02d', $j);
            $labels[] = (string) $j;
            $longs[]  = $j . ' ' . $mL;
        }
        return ['dates' => $dates, 'labels' => $labels, 'longs' => $longs, 'titre' => $mL];
    }

    /** Hebdomadaire : les semaines des 3 derniers mois (ex. S36 ... S49) */
    private function periodeSemaines(string $premier): array
    {
        $ts       = strtotime($premier);
        $finMois  = strtotime(date('Y-m-t', $ts));
        $debutFen = strtotime('-2 month', $ts);
        $lundi    = strtotime('monday this week', $debutFen);

        $dates = $labels = $longs = [];
        while ($lundi <= $finMois) {
            $dim      = strtotime('+6 day', $lundi);
            $dates[]  = date('Y-m-d', $lundi);
            $labels[] = 'S' . date('W', $lundi);
            $longs[]  = 'S' . date('W', $lundi) . ' (' . $this->dCourt($lundi) . ' – ' . $this->dCourt($dim) . ')';
            $lundi    = strtotime('+7 day', $lundi);
        }

        $titre = self::MOIS_C[(int) date('n', $debutFen) - 1] . ' – ' . self::MOIS_C[(int) date('n', $ts) - 1] . ' ' . date('Y', $ts);
        return ['dates' => $dates, 'labels' => $labels, 'longs' => $longs, 'titre' => $titre];
    }

    /** Mensuel : les 12 mois de l'année (Janvier -> Décembre) */
    private function periodeMois(string $premier): array
    {
        $annee = (int) substr($premier, 0, 4);
        $dates = $labels = $longs = [];
        for ($m = 1; $m <= 12; $m++) {
            $dates[]  = sprintf('%04d-%02d-01', $annee, $m);
            $labels[] = self::MOIS_C[$m - 1];
            $longs[]  = self::MOIS_L[$m - 1] . ' ' . $annee;
        }
        return ['dates' => $dates, 'labels' => $labels, 'longs' => $longs, 'titre' => (string) $annee];
    }

    private function dCourt(int $ts): string
    {
        return date('j', $ts) . ' ' . self::MOIS_C[(int) date('n', $ts) - 1];
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