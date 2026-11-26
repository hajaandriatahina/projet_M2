<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// ---------- Authentification ----------
$routes->get('/', 'Auth::index');
$routes->get('login', 'Auth::index');
$routes->post('login', 'Auth::attempt');
$routes->get('logout', 'Auth::logout');

// ---------- Une page par rôle (protégée par RoleFilter) ----------
$routes->group('admin', ['filter' => 'role:Administrateur'], static function (RouteCollection $routes) {
    $routes->get('dashboard', 'Dashboard::administrateur');
    
});

$routes->group('directeur', ['filter' => 'role:Directeur'], static function (RouteCollection $routes) {
    $routes->get('dashboard', 'Directeur::dashboard');
    $routes->get('rapports', 'Directeur::rapports');
    $routes->get('planning', 'Directeur::planning');
});

$routes->group('validateur', ['filter' => 'role:Validateur'], static function (RouteCollection $routes) {
    $routes->get('dashboard', 'Dashboard::validateur');
});

$routes->group('utilisateur', ['filter' => 'role:Utilisateur', 'namespace' => 'App\Controllers\User'], static function (RouteCollection $routes) {
    $routes->get('dashboard', 'Dashboard::index');
    $routes->get('mes-kpi', 'MesKpi::index');
    $routes->post('mes-kpi/enregistrer', 'MesKpi::enregistrer');
    $routes->get('mes-kpi/historique', 'MesKpi::historique');
    $routes->get('notifications', 'Notifications::index');
    $routes->get('profil', 'Profil::index');
    $routes->post('profil/modifier', 'Profil::modifier');
    $routes->get('plan-action', 'PlanAction::index');
    $routes->get('rapport', 'Rapport::index');
$routes->post('plan-action/enregistrer', 'PlanAction::enregistrer');
$routes->post('plan-action/supprimer',   'PlanAction::supprimer');
});

$routes->group('admin', ['namespace' => 'App\Controllers\Admin' ], function ($routes) {
    $routes->post('users/store', 'Users::store');
    $routes->post('users/update/(:num)', 'Users::update/$1');
    $routes->post('users/delete/(:num)', 'Users::delete/$1');
    $routes->get('indicateurs', 'Indicateurs::index');
    $routes->post('indicateurs/store', 'Indicateurs::store');
    $routes->post('indicateurs/update/(:num)', 'Indicateurs::update/$1');
    $routes->post('indicateurs/delete/(:num)', 'Indicateurs::delete/$1');
    $routes->get('configuration', 'Configuration::index');
    $routes->post('configuration/save/(:num)', 'Configuration::save/$1');

});

