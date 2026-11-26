<?php
// app/Controllers/User/PlanAction.php

namespace App\Controllers\User;

use App\Controllers\BaseController;

class PlanAction extends BaseController
{
    private const BACK = 'utilisateur/plan-action';

    // =====================================================================
    //  AFFICHAGE : les plans d'action des KPI attribués à l'utilisateur
    // =====================================================================
    public function index()
    {
        $userId = (int) session()->get('id');
        $db     = db_connect();

        // KPI attribués à CET utilisateur (liste du formulaire)
        $kpis = $this->mesKpi($userId);

        $plans = $db->table('plan_action p')
            ->select('p.id, p.id_configuration, p.observation, p.action, p.responsable, p.echeance, n.nom AS nom_kpi, n.departement')
            ->join('configuration c', 'c.id = p.id_configuration')
            ->join('nom_kpi n', 'n.id = c.id_nom')
            ->where('c.id_user', $userId)
            ->orderBy('p.echeance', 'ASC')
            ->orderBy('p.id', 'DESC')
            ->get()->getResultArray();

        return view('user/plan_action', [
            'title'      => "Plan d'action",
            'notifCount' => 0,
            'kpis'       => $kpis,
            'plans'      => $plans,
        ]);
    }

    // =====================================================================
    //  AJOUT / MODIFICATION (id = 0 -> ajout)
    // =====================================================================
    public function enregistrer()
    {
        $userId = (int) session()->get('id');
        $db     = db_connect();

        $id          = (int) $this->request->getPost('id');
        $idConfig    = (int) $this->request->getPost('id_configuration');
        $observation = trim((string) $this->request->getPost('observation'));
        $action      = trim((string) $this->request->getPost('action'));
        $responsable = trim((string) $this->request->getPost('responsable'));
        $echeance    = trim((string) $this->request->getPost('echeance'));

        // ---- Validation ----
        if ($action === '' || mb_strlen($action) > 1000) {
            return $this->retour('error', "L'action est obligatoire (1000 caractères maximum).");
        }
        if ($responsable === '' || mb_strlen($responsable) > 150) {
            return $this->retour('error', 'Le responsable est obligatoire (150 caractères maximum).');
        }
        if (mb_strlen($observation) > 2000) {
            return $this->retour('error', "L'observation est trop longue (2000 caractères maximum).");
        }
        if (! $this->dateValide($echeance)) {
            return $this->retour('error', "L'échéance est invalide.");
        }

        // ---- Sécurité : le KPI doit être attribué à l'utilisateur en session ----
        $mesConfigs = array_map('intval', array_column($this->mesKpi($userId), 'id_configuration'));
        if (! in_array($idConfig, $mesConfigs, true)) {
            return $this->retour('error', "Ce KPI ne vous est pas attribué.");
        }

        $data = [
            'id_configuration' => $idConfig,
            'observation'      => $observation !== '' ? $observation : null,
            'action'           => $action,
            'responsable'      => $responsable,
            'echeance'         => $echeance,
        ];

        if ($id > 0) {
            // Modification : la ligne doit appartenir à un KPI de l'utilisateur
            $existe = $db->table('plan_action')
                ->where('id', $id)
                ->whereIn('id_configuration', $mesConfigs)
                ->countAllResults();
            if (! $existe) {
                return $this->retour('error', "Plan d'action introuvable.");
            }
            $db->table('plan_action')->where('id', $id)->update($data);
            return $this->retour('success', "Plan d'action modifié.");
        }

        $db->table('plan_action')->insert($data);
        return $this->retour('success', "Plan d'action ajouté.");
    }

    // =====================================================================
    //  SUPPRESSION
    // =====================================================================
    public function supprimer()
    {
        $userId = (int) session()->get('id');
        $id     = (int) $this->request->getPost('id');

        $mesConfigs = array_map('intval', array_column($this->mesKpi($userId), 'id_configuration'));
        if ($id <= 0 || ! $mesConfigs) {
            return $this->retour('error', 'Requête invalide.');
        }

        db_connect()->table('plan_action')
            ->where('id', $id)
            ->whereIn('id_configuration', $mesConfigs)
            ->delete();

        return $this->retour('success', "Plan d'action supprimé.");
    }

    // =====================================================================
    //  OUTILS
    // =====================================================================

    /** KPI attribués à l'utilisateur : id_configuration, nom, departement */
    private function mesKpi(int $userId): array
    {
        return db_connect()->table('configuration c')
            ->select('c.id AS id_configuration, n.nom, n.departement')
            ->join('nom_kpi n', 'n.id = c.id_nom')
            ->where('c.id_user', $userId)
            ->orderBy('n.departement')->orderBy('n.nom')
            ->get()->getResultArray();
    }

    private function retour(string $type, string $message)
    {
        return redirect()->to(site_url(self::BACK))->with($type, $message);
    }

    private function dateValide(string $d): bool
    {
        if (! preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $d, $m)) return false;
        return checkdate((int) $m[2], (int) $m[3], (int) $m[1]);
    }
}