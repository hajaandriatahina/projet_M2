<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\IndicateurModel;

/**
 * app/Controllers/Admin/Indicateurs.php
 * CRUD des indicateurs KPI (table nom_kpi).
 * La protection par rôle est faite dans Routes.php avec le filtre : 'role:Administrateur'.
 */
class Indicateurs extends BaseController
{
    public const DEPARTEMENTS = [
        'ACHAT TECHNIQUE', 'AMC', 'SUPPLY CHAIN', 'COLLECTE', 'LOGISTIQUE MPF', 'PRODUCTION',
        'QUALITÉ HYGIÈNE', 'TECHNIQUE', 'R&D', 'CDG', 'LOGISTIQUE MPE', 'RH', 'SSE', 'RSE',
        'FINANCES', 'ACHAT', 'SSI', 'BOOST', 'CODIR', 'MKT',
    ];

    public const SQCDP = [
        'S' => 'Sécurité',
        'Q' => 'Qualité',
        'C' => 'Coût',
        'D' => 'Délai',
        'P' => 'Personnel',
    ];

    public const FREQUENCES = [
        'Journ'     => 'Journalier',
        'Hebdo'     => 'Hebdomadaire',
        'Mensuel'   => 'Mensuel',
        'Trimestre' => 'Trimestriel',
        'Annuel'    => 'Annuel',
    ];

    public const TYPES = [1, 2, 3, 4];

    private IndicateurModel $model;

    public function __construct()
    {
        $this->model = new IndicateurModel();
    }

    /** Route de la page (utilisée pour toutes les redirections). */
    private function page(): string
    {
        return site_url('admin/indicateurs');
    }

    /* ============ GET admin/indicateurs  ->  SELECT ============ */
    public function index()
    {
        $liste = $this->model->orderBy('departement', 'ASC')->orderBy('nom', 'ASC')->findAll();

        $total     = count($liste);
        $depts     = count(array_unique(array_column($liste, 'departement')));
        $mensuels  = count(array_filter($liste, static fn ($k) => ($k['frequence'] ?? '') === 'Mensuel'));

        $cards = [
            ['label' => 'Total indicateurs',    'value' => $total,    'chip' => null, 'hint' => 'Enregistrés dans la base'],
            ['label' => 'Départements couverts', 'value' => $depts,    'chip' => $depts . ' / ' . count(self::DEPARTEMENTS), 'hint' => 'Au moins un indicateur'],
            ['label' => 'Indicateurs mensuels', 'value' => $mensuels, 'chip' => $total > 0 ? round($mensuels / $total * 100) . ' % du total' : null, 'hint' => 'Fréquence : Mensuel'],
        ];

        // Mêmes variables que Dashboard::render() pour que le layout (header + sidebar) fonctionne.
        return view('indicateurs/index', [
            'role'         => 'Administrateur',
            'title'        => 'Indicateurs KPI',
            'heading'      => 'Indicateurs KPI',
            'description'  => 'Créez, recherchez et gérez les indicateurs suivis par chaque département.',
            'ctaLabel'     => 'Ajouter un indicateur',
            'cards'        => $cards,
            'indicateurs'  => $liste,
            'departements' => self::DEPARTEMENTS,
            'sqcdp'        => self::SQCDP,
            'frequences'   => self::FREQUENCES,
            'types'        => self::TYPES,
        ]);
    }

    /* ============ POST admin/indicateurs/store  ->  INSERT ============ */
    public function store()
    {
        if (! $this->validate($this->rules(), $this->messages())) {
            return redirect()->back()->withInput()
                ->with('form_errors', $this->validator->getErrors())
                ->with('form_mode', 'create');
        }

        $data = $this->payload();

        return $this->run(fn () => $this->model->insert($data), 'Indicateur ajouté avec succès.');
    }

    /* ============ POST admin/indicateurs/update/{id}  ->  UPDATE ============ */
    public function update(int $id)
    {
        if (! $this->model->find($id)) {
            return redirect()->to($this->page())->with('error', 'Indicateur introuvable.');
        }

        if (! $this->validate($this->rules(), $this->messages())) {
            return redirect()->back()->withInput()
                ->with('form_errors', $this->validator->getErrors())
                ->with('form_mode', 'edit')
                ->with('form_id', $id);
        }

        $data = $this->payload();

        return $this->run(fn () => $this->model->update($id, $data), 'Indicateur mis à jour.');
    }

    /* ============ POST admin/indicateurs/delete/{id}  ->  DELETE ============ */
    public function delete(int $id)
    {
        if (! $this->model->find($id)) {
            return redirect()->to($this->page())->with('error', 'Indicateur introuvable.');
        }

        return $this->run(fn () => $this->model->delete($id), 'Indicateur supprimé.');
    }

    /* ------------------------------------------------------------------ */

    private function rules(): array
    {
        return [
            'nom'         => 'required|max_length[255]',
            'description' => 'permit_empty',
            'departement' => 'required|in_list[' . implode(',', self::DEPARTEMENTS) . ']',
            'sqcdp'       => 'permit_empty|in_list[' . implode(',', array_keys(self::SQCDP)) . ']',
            'unite'       => 'permit_empty|max_length[50]',
            'frequence'   => 'permit_empty|in_list[' . implode(',', array_keys(self::FREQUENCES)) . ']',
            'type'        => 'permit_empty|in_list[' . implode(',', self::TYPES) . ']',
        ];
    }

    private function messages(): array
    {
        return [
            'nom'         => ['required' => 'Le nom de l\'indicateur est obligatoire.'],
            'departement' => ['required' => 'Choisissez un département.', 'in_list' => 'Département invalide.'],
        ];
    }

    /** Données du formulaire -> base. Les champs vides deviennent NULL (sinon les CHECK PostgreSQL refusent ''). */
    private function payload(): array
    {
        $post = $this->request->getPost();
        $nullable = static fn ($v) => ($v === null || trim((string) $v) === '') ? null : trim((string) $v);

        return [
            'nom'         => trim($post['nom'] ?? ''),
            'description' => $nullable($post['description'] ?? null),
            'departement' => $post['departement'] ?? '',
            'sqcdp'       => $nullable($post['sqcdp'] ?? null),
            'unite'       => $nullable($post['unite'] ?? null),
            'frequence'   => $nullable($post['frequence'] ?? null),
            'type'        => ($t = $nullable($post['type'] ?? null)) === null ? null : (int) $t,
        ];
    }

    /** Exécute l'opération SQL et affiche le vrai message d'erreur si elle échoue. */
    private function run(callable $operation, string $okMessage)
    {
        try {
            $result = $operation();
        } catch (\Throwable $e) {
            log_message('error', 'admin/indicateurs : ' . $e->getMessage());

            return redirect()->to($this->page())->with('error', 'Erreur base de données : ' . $e->getMessage());
        }

        if ($result === false) {
            $dbError = db_connect()->error();
            $detail  = $dbError['message'] ?? implode(' ', $this->model->errors());

            return redirect()->to($this->page())->with('error', 'Échec de l\'opération. ' . $detail);
        }

        return redirect()->to($this->page())->with('success', $okMessage);
    }
}