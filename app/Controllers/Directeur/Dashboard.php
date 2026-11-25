<?php
// app/Controllers/Directeur.php

namespace App\Controllers;

class Directeur extends BaseController
{
    // =====================================================================
    //  TABLEAU DE BORD
    // =====================================================================
    public function dashboard()
    {
        return view('directeur/dashboard', [
            'title'      => 'Tableau de bord',
            'notifCount' => 0,
            'kpis'       => $this->chargerKpis(),
        ]);
    }

    // =====================================================================
    //  RAPPORTS : dernière valeur de chaque KPI comparée à son objectif
    // =====================================================================
    public function rapports()
    {
        $kpis = $this->chargerKpis();
        $deps = [];
        $totaux = ['total' => 0, 'atteint' => 0, 'non' => 0, 'na' => 0];

        foreach ($kpis as &$k) {
            $k['statut'] = $this->evaluer($k);

            $d = $k['departement'] ?: 'Non défini';
            $deps[$d] ??= ['total' => 0, 'atteint' => 0, 'non' => 0, 'na' => 0];
            $deps[$d]['total']++;
            $deps[$d][$k['statut']]++;

            $totaux['total']++;
            $totaux[$k['statut']]++;
        }
        unset($k);
        ksort($deps);

        return view('directeur/rapports', [
            'title'      => 'Rapports',
            'notifCount' => 0,
            'kpis'       => $kpis,
            'deps'       => $deps,
            'totaux'     => $totaux,
        ]);
    }

    // =====================================================================
    //  PLANNING : KPI par fréquence de saisie + état de la saisie
    // =====================================================================
    public function planning()
    {
        $groupes = ['Journalier' => [], 'Hebdo' => [], 'Mensuel' => []];

        foreach ($this->chargerKpis() as $k) {
            $k['saisie'] = $this->statutSaisie($k);
            $groupes[$k['frequence']][] = $k;
        }

        return view('directeur/planning', [
            'title'      => 'Planning',
            'notifCount' => 0,
            'groupes'    => $groupes,
        ]);
    }

    // =====================================================================
    //  DONNÉES (le directeur voit TOUS les KPI)
    // =====================================================================
    /**
     * Retourne la liste des KPI avec :
     *   - frequence normalisée : Journalier | Hebdo | Mensuel
     *   - objectif  : dernier objectif saisi (type 'objectif'), sinon null
     *   - values    : [ 'YYYY-MM-DD' => valeur ]  (date de début de période)
     *   - derniere / derniere_valeur : dernière saisie connue
     *
     * Si plusieurs utilisateurs ont le même KPI (table configuration), la saisie la plus récente l'emporte.
     * Si votre table nom_kpi contient une colonne de sens ('max'/'min'), ajoutez-la au select
     * et remplacez 'sens' => 'max' ci-dessous par $r['sens'].
     */
    private function chargerKpis(): array
    {
        $db    = db_connect();
        $today = date('Y-m-d');

        $rows = $db->table('nom_kpi n')
            ->select('n.id, n.nom, n.departement, n.sqcdp, n.unite, n.frequence')
            ->orderBy('n.departement')->orderBy('n.nom')
            ->get()->getResultArray();

        $kpis = [];
        foreach ($rows as $r) {
            $f = $this->normFreq((string) $r['frequence']);
            if ($f === null) continue; // trimestriel / annuel : pas affiché ici

            $kpis[(int) $r['id']] = [
                'id'              => (int) $r['id'],
                'nom'             => $r['nom'],
                'departement'     => $r['departement'],
                'sqcdp'           => $r['sqcdp'],
                'unite'           => $r['unite'],
                'frequence'       => $f,
                'sens'            => 'max',
                'objectif'        => null,
                'values'          => [],
                'derniere'        => null,
                'derniere_valeur' => null,
            ];
        }
        if (! $kpis) return [];

        // ---- Valeurs : depuis le 1er octobre de l'année précédente (hebdo = 3 derniers mois, mensuel/jour = année en cours)
        $from = (new \DateTime('first day of january this year'))->modify('-3 months')->format('Y-m-d');
        $vals = $db->table('saisie_kpi s')
            ->select('c.id_nom, s.date_saisie, s.valeur')
            ->join('configuration c', 'c.id = s.id_configuration')
            ->where('s.type', 'valeur')
            ->where('s.date_saisie >=', $from)
            ->orderBy('s.date_creation', 'ASC')
            ->get()->getResultArray();

        foreach ($vals as $v) {
            $id = (int) $v['id_nom'];
            if (isset($kpis[$id])) {
                $kpis[$id]['values'][$v['date_saisie']] = (float) $v['valeur'];
            }
        }

        // ---- Objectifs : le plus récent dont la période est passée, sinon le plus proche à venir
        $objs = $db->table('saisie_kpi s')
            ->select('c.id_nom, s.date_saisie, s.valeur')
            ->join('configuration c', 'c.id = s.id_configuration')
            ->where('s.type', 'objectif')
            ->orderBy('s.date_saisie', 'DESC')
            ->orderBy('s.date_creation', 'DESC')
            ->get()->getResultArray();

        $passe = [];
        $futur = [];
        foreach ($objs as $o) {
            $id = (int) $o['id_nom'];
            if (! isset($kpis[$id])) continue;
            if ($o['date_saisie'] <= $today) {
                $passe[$id] ??= (float) $o['valeur'];   // premier rencontré = le plus récent
            } else {
                $futur[$id] = (float) $o['valeur'];      // dernier rencontré = le plus proche
            }
        }
        foreach ($kpis as $id => &$k) {
            $k['objectif'] = $passe[$id] ?? $futur[$id] ?? null;
            if ($k['values']) {
                ksort($k['values']);
                $k['derniere']        = array_key_last($k['values']);
                $k['derniere_valeur'] = $k['values'][$k['derniere']];
            }
        }
        unset($k);

        return array_values($kpis);
    }

    private function normFreq(string $f): ?string
    {
        $f = mb_strtolower(trim($f));
        if (str_starts_with($f, 'jour') || str_starts_with($f, 'quot')) return 'Journalier';
        if (str_starts_with($f, 'hebdo'))                              return 'Hebdo';
        if (str_starts_with($f, 'mens'))                               return 'Mensuel';
        return null;
    }

    /** atteint | non | na (pas d'objectif ou pas de donnée) */
    private function evaluer(array $k): string
    {
        if ($k['objectif'] === null || $k['derniere_valeur'] === null) return 'na';
        $ok = $k['sens'] === 'min'
            ? $k['derniere_valeur'] <= $k['objectif']
            : $k['derniere_valeur'] >= $k['objectif'];
        return $ok ? 'atteint' : 'non';
    }

    /** a_jour : la période précédente est saisie ; retard sinon */
    private function statutSaisie(array $k): string
    {
        $seuil = match ($k['frequence']) {
            'Journalier' => date('Y-m-d', strtotime('-1 day')),
            'Hebdo'      => date('Y-m-d', strtotime('monday this week -7 days')),
            default      => date('Y-m-01', strtotime('first day of last month')),
        };
        return ($k['derniere'] !== null && $k['derniere'] >= $seuil) ? 'a_jour' : 'retard';
    }
}