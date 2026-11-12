<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;

class Auth extends BaseController
{
    /**
     * GET /login — affiche le formulaire.
     */
    public function index()
    {
        $session = session();

        // Déjà connecté : on l'envoie directement sur sa page
        if ($session->get('isLoggedIn')) {
            $home = UserModel::homeFor((string) $session->get('role'));
            if ($home !== null) {
                return redirect()->to(site_url($home));
            }
        }

        return view('Auth/login');
    }

    /**
     * POST /login — vérifie les identifiants et redirige selon le rôle.
     */
    public function attempt(): RedirectResponse
    {
        $rules = [
            'email' => [
                'label'  => 'Adresse e-mail',
                'rules'  => 'required|valid_email|max_length[150]',
                'errors' => [
                    'required'    => "L'adresse e-mail est obligatoire.",
                    'valid_email' => "Entrez une adresse e-mail valide.",
                ],
            ],
            'password' => [
                'label'  => 'Mot de passe',
                'rules'  => 'required|max_length[255]',
                'errors' => [
                    'required' => 'Le mot de passe est obligatoire.',
                ],
            ],
        ];

        if (! $this->validate($rules)) {
            unset($_POST['password']); // ne pas remettre le mot de passe en session flash
            return redirect()->back()->withInput();
        }

        $email    = strtolower(trim((string) $this->request->getPost('email')));
        $password = (string) $this->request->getPost('password');

        // Anti brute-force : 5 tentatives par minute (IP + e-mail)
        $throttleKey = 'login_' . md5($this->request->getIPAddress() . $email);
        if (service('throttler')->check($throttleKey, 5, MINUTE) === false) {
            return $this->backWithError('Trop de tentatives. Réessayez dans une minute.');
        }

        $users = new UserModel();
        $user  = $users->findByEmail($email);

        // Même message pour e-mail inconnu ou mauvais mot de passe
        if ($user === null || ! password_verify($password, $user['password'])) {
            return $this->backWithError('Adresse e-mail ou mot de passe incorrect.');
        }


        if (! $users->isActive($user)) {
            return $this->backWithError("Votre compte est désactivé. Contactez l'administrateur.");
        }

        $home = UserModel::homeFor($user['role']);
        if ($home === null) {
            return $this->backWithError("Rôle inconnu. Contactez l'administrateur.");
        }

        // Met à jour le hash si l'algorithme par défaut a changé
        if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
            $users->update($user['id'], ['password' => $password]);
        }

        // Ouverture de session
        $session = session();
        $session->regenerate(true); // protège contre la fixation de session
        $session->set([
            'isLoggedIn'    => true,
            'id'            => (int) $user['id'],
            'nom'           => $user['nom'],
            'prenom'        => $user['prenom'],
            'email'         => $user['email'],
            'poste_travail' => $user['poste_travail'],
            'role'          => $user['role'],
        ]);

        return redirect()->to(site_url($home));
    }

    /**
     * GET /logout
     */
    public function logout(): RedirectResponse
    {
        $session = session();
        $session->remove(['isLoggedIn', 'id', 'nom', 'prenom', 'email', 'poste_travail', 'role']);
        $session->regenerate(true);

        return redirect()->to(site_url('login'))->with('success', 'Vous avez été déconnecté.');
    }

    private function backWithError(string $message): RedirectResponse
    {
        unset($_POST['password']);

        return redirect()->back()->withInput()->with('error', $message);
    }
}