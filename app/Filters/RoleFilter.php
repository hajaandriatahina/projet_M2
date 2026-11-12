<?php

namespace App\Filters;

use App\Models\UserModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Protège les pages : il faut être connecté ET avoir le bon rôle.
 * Usage dans Routes : ['filter' => 'role:Administrateur']
 *                  ou ['filter' => 'role:Administrateur,Directeur']
 */
class RoleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        if (! $session->get('isLoggedIn')) {
            return redirect()->to(site_url('login'))->with('error', 'Veuillez vous connecter.');
        }

        $role = (string) $session->get('role');

        if (! empty($arguments) && ! in_array($role, $arguments, true)) {
            // Mauvais rôle : retour vers sa propre page
            $home = UserModel::homeFor($role) ?? 'login';

            return redirect()->to(site_url($home))->with('error', 'Accès non autorisé.');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Empêche le retour arrière vers une page protégée après déconnexion
        return $response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate');
    }
}