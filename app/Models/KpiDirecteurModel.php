<?php
// app/Models/KpiDirecteurModel.php

namespace App\Models;

use CodeIgniter\Model;
use DateTime;

/**
 * Tables utilisées (PostgreSQL) :
 *   nom_kpi      (id, nom, description, departement, sqcdp, unite, frequence, type, sens)
 *   configuration(id, id_user, id_nom)
 *   saisie_kpi   (id, id_configuration, id_user, date_saisie, valeur, type, verrouille, date_creation)
 *     type = 'valeur' ou 'objectif' (adaptez les deux requêtes si vos valeurs sont différentes)
 *
 * Si la colonne sens n'existe pas encore :
 *   ALTER TABLE nom_kpi ADD COLUMN IF NOT EXISTS sens VARCHAR(3) DEFAULT 'max';  -- 'max' ou 'min'
 */
class KpiDirecteurModel extends Model
{
    protected $table      = 'nom_kpi';
    protected $primaryKey = 'id';

    private const FREQ = ['Journ' => 'Journalier', 'Hebdo' => 'Hebdo', 'Mensuel' => 'Mensuel'];

    /** Tous les KPI (le directeur voit tout) avec leurs valeurs de l'année. */
    public function getKpisAvecValeurs(int $annee): array
    {
        // 1) Liste des KPI + dernier objectif
        $kpis = $this->db->query("
            SELECT k.id, k.departement, k.nom, k.sqcdp, k.frequence,
                   COALESCE(k.sens, 'max') AS sens,
                   COALESCE(o.valeur, 0)   AS objectif
            FROM nom_kpi k
            LEFT JOIN (
                SELECT DISTINCT ON (c.id_nom) c.id_nom, s.valeur
                FROM saisie_kpi s
                JOIN configuration c ON c.id = s.id_configuration
                WHERE s.type = 'objectif'
                ORDER BY c.id_nom, s.date_saisie DESC, s.id DESC
            ) o ON o.id_nom = k.id
            ORDER BY k.departement, k.nom
        ")->getResultArray();

        $result = [];
        foreach ($kpis as $k) {
            $result[$k['id']] = [
                'id'          => (int) $k['id'],
                'departement' => $k['departement'],
                'nom'         => $k['nom'],
                'sqcdp'       => $k['sqcdp'],
                'frequence'   => self::FREQ[$k['frequence']] ?? $k['frequence'],
                'objectif'    => (float) $k['objectif'],
                'sens'        => $k['sens'],
                'values'      => [],
            ];
        }

        // 2) Valeurs saisies de l'année
        $rows = $this->db->query("
            SELECT c.id_nom, s.date_saisie, s.valeur
            FROM saisie_kpi s
            JOIN configuration c ON c.id = s.id_configuration
            WHERE s.type = 'valeur' AND s.valeur IS NOT NULL
              AND s.date_saisie >= ? AND s.date_saisie < ?
        ", [$annee . '-01-01', ($annee + 1) . '-01-01'])->getResultArray();

        // Clés lues par la vue : m1..m12 (mois), w36 (semaine ISO), d<mois0>_<jour> (jour)
        $sums = [];
        foreach ($rows as $r) {
            if (! isset($result[$r['id_nom']])) {
                continue;
            }
            $d   = new DateTime($r['date_saisie']);
            $val = (float) $r['valeur'];
            $id  = $r['id_nom'];

            $result[$id]['values']['d' . ((int) $d->format('n') - 1) . '_' . (int) $d->format('j')] = $val;

            foreach (['m' . (int) $d->format('n'), 'w' . (int) $d->format('W')] as $key) {
                $sums[$id][$key]['s'] = ($sums[$id][$key]['s'] ?? 0) + $val;
                $sums[$id][$key]['n'] = ($sums[$id][$key]['n'] ?? 0) + 1;
            }
        }

        // Moyenne par mois et par semaine
        foreach ($sums as $id => $keys) {
            foreach ($keys as $key => $a) {
                $result[$id]['values'][$key] = round($a['s'] / $a['n'], 2);
            }
        }

        return array_values($result);
    }
}