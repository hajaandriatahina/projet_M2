<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;

/**
 * app/Controllers/Admin/Users.php
 * Reçoit les données du formulaire (modal) du dashboard et les enregistre dans PostgreSQL.
 * Chaque échec affiche maintenant un message clair (session, validation ou base de données).
 */
class Users extends BaseController
{
    private const ROLES = 'Utilisateur,Validateur,Directeur,Administrateur';

    private UserModel $users;

    public function __construct()
    {
        $this->users = new UserModel();
    }

    /** Seul l'administrateur a le droit d'utiliser ces actions. */
    private function isAdmin(): bool
    {
        return (bool) session()->get('isLoggedIn') && session()->get('role') === 'Administrateur';
    }

    /** Page d'accueil de l'administrateur (même route que dans UserModel::homeFor). */
    private function home(): string
    {
        return site_url(UserModel::homeFor('Administrateur'));
    }

    private function forbidden()
    {
        $role = session()->get('role') ?? 'aucun';
        log_message('error', 'admin/users refusé : isLoggedIn=' . var_export(session()->get('isLoggedIn'), true) . ' role=' . var_export($role, true));

        return redirect()->to('/login')->with(
            'error',
            "Accès refusé : la session doit contenir isLoggedIn = true et role = Administrateur (rôle lu : {$role})."
        );
    }

    /** Exécute l'opération SQL et affiche le vrai message d'erreur si elle échoue. */
    private function run(callable $operation, string $okMessage)
    {
        try {
            $result = $operation();
        } catch (\Throwable $e) {
            log_message('error', 'admin/users : ' . $e->getMessage());

            return redirect()->to($this->home())->with('error', 'Erreur base de données : ' . $e->getMessage());
        }

        if ($result === false) {
            $dbError = db_connect()->error();
            $detail  = $dbError['message'] ?? implode(' ', $this->users->errors());

            return redirect()->to($this->home())->with('error', 'Échec de l\'opération. ' . $detail);
        }

        return redirect()->to($this->home())->with('success', $okMessage);
    }

    /* ============ POST admin/users/store  ->  INSERT ============ */
    public function store()
    {
        if (! $this->isAdmin()) {
            return $this->forbidden();
        }

        $rules = [
            'nom'           => 'required|max_length[100]',
            'prenom'        => 'required|max_length[100]',
            'numero'        => 'permit_empty|max_length[30]',
            'poste_travail' => 'permit_empty|max_length[150]',
            'role'          => 'required|in_list[' . self::ROLES . ']',
            'email'         => 'required|valid_email|is_unique[users.email]',
            'password'      => 'required|min_length[6]',
        ];
        $messages = [
            'email' => ['is_unique' => 'Cette adresse e-mail est déjà utilisée.'],
        ];

        if (! $this->validate($rules, $messages)) {
            return redirect()->back()->withInput()
                ->with('form_errors', $this->validator->getErrors())
                ->with('form_mode', 'create');
        }

        $data = $this->payload(true);

        return $this->run(fn () => $this->users->insert($data), 'Utilisateur ajouté avec succès.');
    }

    /* ============ POST admin/users/update/{id}  ->  UPDATE ============ */
    public function update(int $id)
    {
        if (! $this->isAdmin()) {
            return $this->forbidden();
        }

        if (! $this->users->find($id)) {
            return redirect()->to($this->home())->with('error', 'Utilisateur introuvable.');
        }

        $rules = [
            'nom'           => 'required|max_length[100]',
            'prenom'        => 'required|max_length[100]',
            'numero'        => 'permit_empty|max_length[30]',
            'poste_travail' => 'permit_empty|max_length[150]',
            'role'          => 'required|in_list[' . self::ROLES . ']',
            'email'         => "required|valid_email|is_unique[users.email,id,{$id}]",
            'password'      => 'permit_empty|min_length[6]',
        ];
        $messages = [
            'email' => ['is_unique' => 'Cette adresse e-mail est déjà utilisée.'],
        ];

        if (! $this->validate($rules, $messages)) {
            return redirect()->back()->withInput()
                ->with('form_errors', $this->validator->getErrors())
                ->with('form_mode', 'edit')
                ->with('form_id', $id);
        }

        $data = $this->payload(false);

        return $this->run(fn () => $this->users->update($id, $data), 'Utilisateur mis à jour.');
    }

    /* ============ POST admin/users/delete/{id}  ->  DELETE ============ */
    public function delete(int $id)
    {
        if (! $this->isAdmin()) {
            return $this->forbidden();
        }

        if ($id === (int) session()->get('user_id')) {
            return redirect()->to($this->home())->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        return $this->run(fn () => $this->users->delete($id), 'Utilisateur supprimé.');
    }

    /** Prépare les données reçues du formulaire pour la base. */
    private function payload(bool $withPassword): array
    {
        $post = $this->request->getPost();

        $data = [
            'nom'           => trim($post['nom'] ?? ''),
            'prenom'        => trim($post['prenom'] ?? ''),
            'numero'        => trim($post['numero'] ?? ''),
            'poste_travail' => trim($post['poste_travail'] ?? ''),
            'role'          => $post['role'] ?? 'Utilisateur',
            'email'         => strtolower(trim($post['email'] ?? '')),
            'statut'        => ($post['statut'] ?? '0') === '1',   // booléen PostgreSQL
            'password'      => $post['password'] ?? '',            // haché dans UserModel
        ];

        if (! $withPassword && $data['password'] === '') {
            unset($data['password']);
        }

        return $data;
    }
}