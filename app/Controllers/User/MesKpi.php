<?php
// app/Controllers/User/MesKpi.php

namespace App\Controllers\User;

use App\Controllers\BaseController;

class MesKpi extends BaseController
{
    private const FREQS = ['journalier', 'hebdomadaire', 'mensuel'];
    private const TYPES = ['valeur', 'objectif'];

    private const MOIS_C = ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];
    private const MOIS_L = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];

    // =====================================================================
    //  AFFICHAGE
    // =====================================================================
    public function index()
    {
        $userId = (int) session()->get('id');

        $freq = (string) $this->request->getGet('freq');
        $type = (string) $this->request->getGet('type');
        $ref  = (string) $this->request->getGet('ref');

        if (! in_array($freq, self::FREQS, true)) $freq = 'journalier';
        if (! in_array($type, self::TYPES, true)) $type = 'valeur';
        $ref = $this->dateValide($ref) ? $ref : date('Y-m-d');

        // KPI attribués à CET utilisateur, classés par fréquence
        $parFreq = $this->kpisParFrequence($userId);
        $kpis    = $parFreq[$freq];
        $tabs    = array_map('count', $parFreq);

        // Colonnes (dates) selon la fréquence
        $periode = $this->colonnes($freq, $ref);
        $dates   = array_column($periode['cols'], 'date');

        $db = db_connect();

        // Données déjà enregistrées pour les colonnes affichées
        $grid = [];
        if ($kpis && $dates) {
            $rows = $db->table('saisie_kpi')
                ->select('id, id_configuration, date_saisie, valeur, verrouille')
                ->where('type', $type)
                ->whereIn('id_configuration', array_keys($kpis))
                ->whereIn('date_saisie', $dates)
                ->get()->getResultArray();
            foreach ($rows as $r) {
                $grid[$r['id_configuration']][$r['date_saisie']] = $r;
            }
        }

        // Historique (même fréquence, même type) — pas de filtre
        $hist = [];
        if ($kpis) {
            $hist = $db->table('saisie_kpi s')
                ->select('s.date_saisie, s.valeur, s.verrouille, s.date_creation, n.nom, n.unite')
                ->join('configuration c', 'c.id = s.id_configuration')
                ->join('nom_kpi n', 'n.id = c.id_nom')
                ->where('s.type', $type)
                ->whereIn('s.id_configuration', array_keys($kpis))
                ->orderBy('s.date_saisie', 'DESC')
                ->orderBy('n.nom', 'ASC')
                ->limit(60)
                ->get()->getResultArray();
            foreach ($hist as &$h) {
                $h['periode'] = $this->libellePeriode($freq, $h['date_saisie']);
                $h['cree']    = date('d/m/Y H:i', strtotime($h['date_creation']));
            }
            unset($h);
        }

        return view('user/mes_kpi', [
            'title'      => 'Mes KPI, Objectif',
            'notifCount' => 0,
            'freq'       => $freq,
            'type'       => $type,
            'ref'        => $ref,
            'tabs'       => $tabs,
            'kpis'       => $kpis,
            'periode'    => $periode,
            'grid'       => $grid,
            'hist'       => $hist,
        ]);
    }

    // =====================================================================
    //  ENREGISTREMENT (insertion + verrouillage)
    // =====================================================================
    public function enregistrer()
    {
        $userId = (int) session()->get('id');

        $freq = (string) $this->request->getPost('freq');
        $type = (string) $this->request->getPost('type');
        $ref  = (string) $this->request->getPost('ref');

        if (! in_array($freq, self::FREQS, true) || ! in_array($type, self::TYPES, true)) {
            return redirect()->to(site_url('utilisateur/mes-kpi'))->with('error', 'Requête invalide.');
        }
        $ref  = $this->dateValide($ref) ? $ref : date('Y-m-d');
        $back = site_url('utilisateur/mes-kpi') . '?' . http_build_query(['freq' => $freq, 'type' => $type, 'ref' => $ref]);

        $cells = $this->request->getPost('cells');
        if (! is_array($cells)) {
            return redirect()->to($back)->with('error', 'Aucune valeur à enregistrer.');
        }

        // Seuls les KPI attribués à l'utilisateur ET de cette fréquence sont autorisés
        $kpis  = $this->kpisParFrequence($userId)[$freq];
        $today = date('Y-m-d');
        $aEnregistrer = [];

        foreach ($cells as $idConfig => $parDate) {
            if (! is_array($parDate)) continue;

            if (! isset($kpis[(int) $idConfig])) {
                return redirect()->to($back)->with('error', "Accès refusé : un KPI envoyé ne vous est pas attribué. Rien n'a été enregistré.");
            }

            foreach ($parDate as $date => $val) {
                $val = trim(str_replace(',', '.', (string) $val));
                if ($val === '') continue;

                if (! is_numeric($val) || abs((float) $val) >= 1e12) {
                    return redirect()->to($back)->with('error', 'Valeur invalide : « ' . esc($val) . ' ». Rien n\'a été enregistré.');
                }
                if (! $this->dateValide((string) $date)) {
                    return redirect()->to($back)->with('error', 'Date invalide. Rien n\'a été enregistré.');
                }

                $ts = strtotime($date);
                if ($freq === 'hebdomadaire' && (int) date('N', $ts) !== 1) {
                    return redirect()->to($back)->with('error', 'Période hebdomadaire invalide.');
                }
                if ($freq === 'mensuel' && (int) date('j', $ts) !== 1) {
                    return redirect()->to($back)->with('error', 'Période mensuelle invalide.');
                }
                if ($type === 'valeur' && $this->estFutur($freq, $date, $today)) {
                    return redirect()->to($back)->with('error', "Impossible de saisir une valeur pour une période à venir.");
                }

                $aEnregistrer[] = [(int) $idConfig, $date, (float) $val];
            }
        }

        if (! $aEnregistrer) {
            return redirect()->to($back)->with('error', 'Aucune valeur à enregistrer.');
        }

        $db = db_connect();
        $db->transBegin();

        try {
            foreach ($aEnregistrer as [$idConfig, $date, $val]) {
                $existant = $db->table('saisie_kpi')
                    ->where('id_configuration', $idConfig)
                    ->where('type', $type)
                    ->where('date_saisie', $date)
                    ->get()->getRowArray();

                if ($existant) {
                    // Déjà verrouillée : seul l'administrateur peut débloquer
                    if ($this->estVrai($existant['verrouille'])) {
                        $db->transRollback();
                        return redirect()->to($back)->with('error', 'Certaines données sont déjà verrouillées et ne peuvent plus être modifiées. Rien n\'a été enregistré.');
                    }
                    $db->table('saisie_kpi')->where('id', $existant['id'])->update([
                        'valeur'     => $val,
                        'id_user'    => $userId,
                        'verrouille' => true,
                    ]);
                } else {
                    $db->table('saisie_kpi')->insert([
                        'id_configuration' => $idConfig,
                        'id_user'          => $userId,
                        'type'             => $type,
                        'date_saisie'      => $date,
                        'valeur'           => $val,
                        'verrouille'       => true,
                    ]);
                }
            }
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'MesKpi::enregistrer - ' . $e->getMessage());
            return redirect()->to($back)->with('error', "Erreur lors de l'enregistrement. Rien n'a été enregistré.");
        }

        $n = count($aEnregistrer);
        return redirect()->to($back)->with('success', $n . ' donnée' . ($n > 1 ? 's' : '') . ' enregistrée' . ($n > 1 ? 's' : '') . ' et verrouillée' . ($n > 1 ? 's' : '') . '.');
    }

    // =====================================================================
    //  OUTILS
    // =====================================================================

    /** KPI attribués à l'utilisateur, classés par fréquence : [freq][id_configuration] => ligne */
    private function kpisParFrequence(int $userId): array
    {
        $rows = db_connect()->table('configuration c')
            ->select('c.id AS id_configuration, n.nom, n.unite, n.sqcdp, n.frequence')
            ->join('nom_kpi n', 'n.id = c.id_nom')
            ->where('c.id_user', $userId)
            ->orderBy('n.sqcdp')->orderBy('n.nom')
            ->get()->getResultArray();

        $out = ['journalier' => [], 'hebdomadaire' => [], 'mensuel' => []];
        foreach ($rows as $r) {
            $f = $this->normFreq((string) $r['frequence']);
            if ($f !== null) {
                $out[$f][(int) $r['id_configuration']] = $r;
            }
        }
        return $out;
    }

    private function normFreq(string $f): ?string
    {
        $f = mb_strtolower(trim($f));
        if (str_starts_with($f, 'jour') || str_starts_with($f, 'quot')) return 'journalier';
        if (str_starts_with($f, 'hebdo'))                              return 'hebdomadaire';
        if (str_starts_with($f, 'mens'))                               return 'mensuel';
        return null;
    }

    /** Colonnes de la grille + titre + liens précédent/suivant */
    private function colonnes(string $freq, string $ref): array
    {
        $today = date('Y-m-d');
        $hier  = date('Y-m-d', strtotime('-1 day'));
        $cols  = [];

        if ($freq === 'journalier') {
            // Les 7 jours de la semaine (lundi -> dimanche)
            $lundi = strtotime('monday this week', strtotime($ref));
            for ($i = 0; $i < 7; $i++) {
                $ts = strtotime("+$i day", $lundi);
                $d  = date('Y-m-d', $ts);
                $cols[] = [
                    'date'  => $d,
                    'label' => $d === $today ? "Aujourd'hui" : ($d === $hier ? 'Hier' : $this->dFr($ts)),
                    'sub'   => ($d === $today || $d === $hier) ? $this->dFr($ts) : '',
                    'today' => $d === $today,
                    'futur' => $d > $today,
                ];
            }
            $dimanche = strtotime('+6 day', $lundi);
            return [
                'cols'  => $cols,
                'titre' => 'Semaine du ' . $this->dCourt($lundi) . ' au ' . $this->dFr($dimanche),
                'prev'  => date('Y-m-d', strtotime('-7 day', $lundi)),
                'next'  => date('Y-m-d', strtotime('+7 day', $lundi)),
            ];
        }

        if ($freq === 'hebdomadaire') {
            // Les semaines du mois
            $premier = strtotime(substr($ref, 0, 7) . '-01');
            $dernier = strtotime(date('Y-m-t', $premier));
            $debut   = strtotime('monday this week', $premier);
            $n = 1;
            while ($debut <= $dernier) {
                $fin = strtotime('+6 day', $debut);
                $d   = date('Y-m-d', $debut);
                $cols[] = [
                    'date'  => $d,
                    'label' => 'S' . $n,
                    'sub'   => $this->dCourt($debut) . ' – ' . $this->dCourt($fin),
                    'today' => $today >= $d && $today <= date('Y-m-d', $fin),
                    'futur' => $d > $today,
                ];
                $debut = strtotime('+7 day', $debut);
                $n++;
            }
            return [
                'cols'  => $cols,
                'titre' => self::MOIS_L[(int) date('n', $premier) - 1] . ' ' . date('Y', $premier),
                'prev'  => date('Y-m-d', strtotime('first day of previous month', $premier)),
                'next'  => date('Y-m-d', strtotime('first day of next month', $premier)),
            ];
        }

        // mensuel : les 12 mois de l'année
        $annee = (int) substr($ref, 0, 4);
        $moisCourant = date('Y-m-01');
        for ($m = 1; $m <= 12; $m++) {
            $d = sprintf('%04d-%02d-01', $annee, $m);
            $cols[] = [
                'date'  => $d,
                'label' => self::MOIS_L[$m - 1],
                'sub'   => (string) $annee,
                'today' => $d === $moisCourant,
                'futur' => $d > $moisCourant,
            ];
        }
        return [
            'cols'  => $cols,
            'titre' => (string) $annee,
            'prev'  => ($annee - 1) . '-01-01',
            'next'  => ($annee + 1) . '-01-01',
        ];
    }

    private function estFutur(string $freq, string $date, string $today): bool
    {
        if ($freq === 'mensuel') return $date > date('Y-m-01');
        return $date > $today; // journalier : le jour ; hebdomadaire : le lundi de la semaine
    }

    private function libellePeriode(string $freq, string $date): string
    {
        $ts = strtotime($date);
        return match ($freq) {
            'hebdomadaire' => 'Semaine du ' . $this->dFr($ts),
            'mensuel'      => self::MOIS_L[(int) date('n', $ts) - 1] . ' ' . date('Y', $ts),
            default        => $this->dFr($ts),
        };
    }

    private function dFr(int $ts): string
    {
        return date('j', $ts) . ' ' . self::MOIS_C[(int) date('n', $ts) - 1] . ' ' . date('Y', $ts);
    }

    private function dCourt(int $ts): string
    {
        return date('j', $ts) . ' ' . self::MOIS_C[(int) date('n', $ts) - 1];
    }

    private function dateValide(string $d): bool
    {
        if (! preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $d, $m)) return false;
        return checkdate((int) $m[2], (int) $m[3], (int) $m[1]);
    }

    private function estVrai($v): bool
    {
        return in_array($v, [true, 't', 'true', '1', 1], true);
    }
}