<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * app/Models/ConfigurationModel.php
 * Table PostgreSQL : configuration (id_user, id_nom)
 * Associe à chaque utilisateur les KPI (nom_kpi) qu'il a le droit de renseigner.
 */
class ConfigurationModel extends Model
{
    protected $table         = 'configuration';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = ['id_user', 'id_nom'];

    /** Liste des id_nom (KPI) déjà attribués à un utilisateur. */
    public function idsForUser(int $userId): array
    {
        return array_map(
            static fn ($row) => (int) $row['id_nom'],
            $this->select('id_nom')->where('id_user', $userId)->findAll()
        );
    }

    /**
     * Remplace entièrement l'attribution d'un utilisateur par la nouvelle liste de KPI.
     * $kpiIds peut être vide (= on retire tout).
     */
    public function replaceForUser(int $userId, array $kpiIds): void
    {
        $this->where('id_user', $userId)->delete();

        $kpiIds = array_values(array_unique(array_map('intval', $kpiIds)));
        if ($kpiIds === []) {
            return;
        }

        $rows = array_map(
            static fn ($id) => ['id_user' => $userId, 'id_nom' => $id],
            $kpiIds
        );
        $this->insertBatch($rows);
    }

    /** Nombre de KPI attribués, groupé par utilisateur : [id_user => count]. */
    public function countsByUser(): array
    {
        $rows = $this->select('id_user, COUNT(*) AS nb')->groupBy('id_user')->findAll();

        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r['id_user']] = (int) $r['nb'];
        }

        return $out;
    }
}