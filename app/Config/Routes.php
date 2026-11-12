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
    $routes->get('dashboard', 'Dashboard::directeur');
});

$routes->group('validateur', ['filter' => 'role:Validateur'], static function (RouteCollection $routes) {
    $routes->get('dashboard', 'Dashboard::validateur');
});

$routes->group('utilisateur', ['filter' => 'role:Utilisateur'], static function (RouteCollection $routes) {
    $routes->get('dashboard', 'Dashboard::utilisateur');
});