<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * app/Models/IndicateurModel.php
 * Table PostgreSQL : nom_kpi
 */
class IndicateurModel extends Model
{
    protected $table         = 'nom_kpi';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'nom', 'description', 'departement', 'sqcdp', 'unite', 'frequence', 'type',
    ];
}