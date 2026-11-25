<?php
/**
 * app/Views/layouts/app_layout.php
 * Layout UNIQUE (sidebar + header) pour Utilisateur et Directeur.
 * Même design, même structure, même fonctionnement : seul le menu change selon le rôle.
 *
 * Variables attendues (envoyées par le contrôleur) :
 *   $title       : titre de la page
 *   $notifCount  : nombre de notifications non lues (défaut 0)
 */

$session     = session();
$role        = (string) ($session->get('role') ?? '');
$nom         = (string) ($session->get('nom') ?? '');
$prenom      = (string) ($session->get('prenom') ?? '');
$fullName    = trim($prenom . ' ' . $nom) ?: 'Utilisateur';
$sousTitre   = $session->get('departement') ?: ($role ?: 'Non défini'); // département, sinon le rôle
$photo       = $session->get('photo');
$initiales   = strtoupper(mb_substr($prenom ?: $nom ?: 'U', 0, 1) . mb_substr($nom, 0, 1));
$notifCount  = $notifCount ?? 0;

// Icônes (Heroicons outline)
$ICONS = [
    'home'     => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
    'kpi'      => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
    'bell'     => 'M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
    'user'     => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
    'report'   => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    'calendar' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
    'logout'   => 'M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1',
];

// Menu selon le rôle (la déconnexion est toujours ajoutée en bas)
$menus = [
    'Utilisateur' => [
        ['Tableau de bord',   'home', 'utilisateur/dashboard'],
        ['Mes KPI, Objectif', 'kpi',  'utilisateur/mes-kpi'],
        ['Notifications',     'bell', 'utilisateur/notifications'],
        ['Mon profil',        'user', 'utilisateur/profil'],
    ],
    'Directeur' => [
        ['Tableau de bord', 'home',     'directeur/dashboard'],
        ['Rapports',        'report',   'directeur/rapports'],
        ['Planning',        'calendar', 'directeur/planning'],
    ],
];
$menu = $menus[$role] ?? [['Tableau de bord', 'home', \App\Models\UserModel::homeFor($role) ?? 'login']];

$current  = trim(uri_string(), '/');
$homePath = $menu[0][2];
$notifUrl = $role === 'Utilisateur' ? site_url('utilisateur/notifications') : '#';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Socolait KPI') ?> | Socolait KPI</title>
    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
</head>
<body class="bg-slate-100 text-slate-800 min-h-screen">

<!-- Overlay mobile -->
<div id="overlay" class="fixed inset-0 bg-black/40 z-30 hidden lg:hidden"></div>

<!-- ===================== SIDEBAR ===================== -->
<aside id="sidebar"
       class="fixed inset-y-0 left-0 z-40 w-64 bg-gradient-to-b from-violet-700 to-violet-900 text-white
              flex flex-col transform -translate-x-full lg:translate-x-0 transition-transform duration-200">

    <div class="h-16 flex items-center gap-3 px-6 border-b border-white/10 lg:hidden">
        <span class="text-lg font-bold">Socolait KPI</span>
    </div>

    <nav class="flex-1 px-3 py-6 space-y-1 overflow-y-auto">
        <?php foreach ($menu as [$label, $icon, $path]):
            $active = $current === $path || str_starts_with($current, $path . '/'); ?>
            <a href="<?= site_url($path) ?>"
               class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition
                      <?= $active ? 'bg-white text-violet-700 shadow' : 'text-violet-100 hover:bg-white/10' ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="<?= $ICONS[$icon] ?>"/>
                </svg>
                <span class="flex-1"><?= esc($label) ?></span>
                <?php if ($icon === 'bell' && $notifCount > 0): ?>
                    <span class="text-xs font-semibold bg-rose-500 text-white rounded-full px-2 py-0.5"><?= (int) $notifCount ?></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="px-3 pb-6 border-t border-white/10 pt-4">
        <a href="<?= site_url('logout') ?>"
           class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-violet-100 hover:bg-rose-500/90 hover:text-white transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="<?= $ICONS['logout'] ?>"/>
            </svg>
            Déconnexion
        </a>
    </div>
</aside>

<!-- ===================== CONTENU ===================== -->
<div class="lg:ml-64 min-h-screen flex flex-col">

    <!-- ===================== HEADER ===================== -->
    <header class="sticky top-0 z-20 h-16 bg-white border-b border-slate-200 flex items-center justify-between px-4 sm:px-6">

        <div class="flex items-center gap-3">
            <button id="btnMenu" class="lg:hidden p-2 rounded-lg hover:bg-slate-100" aria-label="Ouvrir le menu">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <a href="<?= site_url($homePath) ?>" class="flex items-center gap-2">
                <!-- Remplacer par le vrai logo : <img src="<?= base_url('assets/img/logo-socolait.png') ?>" class="h-9" alt="Socolait"> -->
                <span class="w-9 h-9 rounded-lg bg-violet-600 text-white font-bold flex items-center justify-center">S</span>
                <span class="text-lg font-bold text-violet-700">Socolait <span class="text-slate-800">KPI</span></span>
            </a>
        </div>

        <div class="flex items-center gap-2 sm:gap-4">

            <!-- Notifications -->
            <a href="<?= $notifUrl ?>"
               class="relative p-2 rounded-full text-slate-500 hover:bg-slate-100 hover:text-violet-700 transition"
               aria-label="Notifications">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="<?= $ICONS['bell'] ?>"/>
                </svg>
                <?php if ($notifCount > 0): ?>
                    <span class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 text-[10px] font-bold bg-rose-500 text-white rounded-full flex items-center justify-center">
                        <?= $notifCount > 99 ? '99+' : (int) $notifCount ?>
                    </span>
                <?php endif; ?>
            </a>

            <!-- Menu du compte -->
            <div class="relative">
                <button id="btnAccount" class="flex items-center gap-3 pl-2 pr-1 py-1 rounded-full hover:bg-slate-100 transition">
                    <div class="hidden sm:block text-right leading-tight">
                        <p class="text-sm font-semibold text-slate-800"><?= esc($fullName) ?></p>
                        <p class="text-xs text-slate-500"><?= esc($sousTitre) ?></p>
                    </div>
                    <?php if ($photo): ?>
                        <img src="<?= base_url('uploads/profils/' . esc($photo, 'url')) ?>" alt="Photo de profil"
                             class="w-10 h-10 rounded-full object-cover ring-2 ring-violet-200">
                    <?php else: ?>
                        <span class="w-10 h-10 rounded-full bg-violet-600 text-white text-sm font-semibold flex items-center justify-center ring-2 ring-violet-200">
                            <?= esc($initiales) ?>
                        </span>
                    <?php endif; ?>
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div id="accountMenu" class="hidden absolute right-0 mt-2 w-60 bg-white rounded-xl shadow-lg border border-slate-200 py-2">
                    <div class="px-4 py-3 border-b border-slate-100 sm:hidden">
                        <p class="text-sm font-semibold"><?= esc($fullName) ?></p>
                        <p class="text-xs text-slate-500"><?= esc($sousTitre) ?></p>
                    </div>
                    <?php foreach ($menu as [$label, $icon, $path]): ?>
                        <a href="<?= site_url($path) ?>" class="block px-4 py-2 text-sm hover:bg-violet-50 hover:text-violet-700"><?= esc($label) ?></a>
                    <?php endforeach; ?>
                    <div class="border-t border-slate-100 my-1"></div>
                    <a href="<?= site_url('logout') ?>" class="block px-4 py-2 text-sm text-rose-600 hover:bg-rose-50">Déconnexion</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Messages flash (succès / erreur) -->
    <?php if (session()->getFlashdata('success')): ?>
        <div class="mx-4 sm:mx-6 mt-4 p-3 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm">
            <?= esc(session()->getFlashdata('success')) ?>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="mx-4 sm:mx-6 mt-4 p-3 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-sm">
            <?= esc(session()->getFlashdata('error')) ?>
        </div>
    <?php endif; ?>

    <main class="flex-1 p-4 sm:p-6">
        <?= $this->renderSection('content') ?>
    </main>
</div>

<script>
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    document.getElementById('btnMenu').addEventListener('click', () => {
        sidebar.classList.toggle('-translate-x-full');
        overlay.classList.toggle('hidden');
    });
    overlay.addEventListener('click', () => {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
    });

    const btnAccount  = document.getElementById('btnAccount');
    const accountMenu = document.getElementById('accountMenu');
    btnAccount.addEventListener('click', (e) => { e.stopPropagation(); accountMenu.classList.toggle('hidden'); });
    document.addEventListener('click', () => accountMenu.classList.add('hidden'));
</script>
</body>
</html>