<?php
/**
 * app/Views/layouts/main.php
 * Layout principal : sidebar violette + header (style helpdesk).
 *
 * Dans une page :
 *   <?= $this->extend('layouts/main') ?>
 *   <?= $this->section('content') ?> ... <?= $this->endSection() ?>
 * Variable optionnelle : $title
 */
helper('layout');

$session   = session();
$role      = (string) $session->get('role');
$prenom    = (string) $session->get('prenom');
$nom       = (string) $session->get('nom');
$email     = (string) $session->get('email');
$poste     = (string) $session->get('poste_travail');
$menu      = ui_menu($role);
$pageTitle = $title ?? 'Tableau de bord';
$initials  = ui_initials($prenom, $nom);
?>
<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($pageTitle) ?> | KPI Socolait</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- DÉVELOPPEMENT : Tailwind CDN. PRODUCTION : css/tailwind.css recompilé. -->
    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
                    colors: {
                        primary: {
                            50: '#f5f1fc', 100: '#ebe3f8', 200: '#d8c9f1', 300: '#bda4e6',
                            400: '#a284da', 500: '#8a66cc', 600: '#6f4cb0', 700: '#5e3e98',
                            800: '#4b3178', 900: '#382459'
                        }
                    }
                }
            }
        }
    </script>
</head>

<body class="min-h-full bg-white font-sans text-slate-800 antialiased">

    <!-- Voile (mobile) -->
    <div id="overlay" class="fixed inset-0 z-30 hidden bg-slate-900/50 md:hidden"></div>

    <!-- ================= SIDEBAR ================= -->
    <aside id="sidebar"
           class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-primary-700 text-white transition-transform duration-200 md:w-[76px] md:translate-x-0">

        <!-- Logo -->
        <div class="flex h-16 items-center justify-between px-5 md:justify-center md:px-0">
            <a href="<?= site_url(\App\Models\UserModel::homeFor($role) ?? 'login') ?>" class="flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/25">
                    <?= ui_icon('logo', 'w-6 h-6') ?>
                </span>
                <span class="text-lg font-bold tracking-wide md:hidden">KPI Socolait</span>
            </a>
            <button type="button" id="sidebarClose" class="rounded-lg p-1.5 text-primary-100 hover:bg-white/10 md:hidden" aria-label="Fermer le menu">
                <?= ui_icon('close', 'w-6 h-6') ?>
            </button>
        </div>

        <!-- Navigation -->
        <nav class="mt-2 flex flex-col" aria-label="Navigation principale">
            <?php foreach ($menu as $item): $active = ui_is_active($item); ?>
                <a href="<?= ui_url($item) ?>"
                   <?= $active ? 'aria-current="page"' : '' ?>
                   class="group relative flex h-14 items-center gap-4 px-5 transition md:justify-center md:px-0
                          <?= $active ? 'bg-primary-400 text-white' : 'text-primary-100 hover:bg-white/10 hover:text-white' ?>">
                    <?= ui_icon($item['icon'], 'w-6 h-6 shrink-0') ?>
                    <span class="text-sm font-medium md:hidden"><?= esc($item['label']) ?></span>

                    <!-- Infobulle (desktop) -->
                    <span class="pointer-events-none absolute left-full top-1/2 z-50 ml-3 hidden -translate-y-1/2 whitespace-nowrap rounded-md bg-slate-900 px-2.5 py-1 text-xs font-medium text-white opacity-0 shadow-lg transition group-hover:opacity-100 md:block">
                        <?= esc($item['label']) ?><?= $item['path'] === null ? ' (bientôt)' : '' ?>
                    </span>
                </a>
            <?php endforeach; ?>
        </nav>

        <!-- Déconnexion -->
        <div class="mt-auto pb-3">
            <a href="<?= site_url('logout') ?>"
               class="group relative flex h-14 items-center gap-4 px-5 text-primary-100 transition hover:bg-white/10 hover:text-white md:justify-center md:px-0">
                <?= ui_icon('logout', 'w-6 h-6 shrink-0') ?>
                <span class="text-sm font-medium md:hidden">Se déconnecter</span>
                <span class="pointer-events-none absolute left-full top-1/2 z-50 ml-3 hidden -translate-y-1/2 whitespace-nowrap rounded-md bg-slate-900 px-2.5 py-1 text-xs font-medium text-white opacity-0 shadow-lg transition group-hover:opacity-100 md:block">
                    Se déconnecter
                </span>
            </a>
        </div>
    </aside>

    <!-- ================= CONTENU ================= -->
    <div class="flex min-h-screen flex-col md:pl-[76px]">

        <!-- ---------- HEADER ---------- -->
        <header class="sticky top-0 z-20 flex h-16 items-center justify-between gap-4 border-b border-slate-200 bg-slate-50/90 px-4 backdrop-blur sm:px-6 lg:px-8">

            <div class="flex items-center gap-3">
                <!-- Burger (mobile) -->
                <button type="button" id="sidebarOpen" class="rounded-lg p-2 text-slate-600 hover:bg-slate-200/70 md:hidden" aria-label="Ouvrir le menu">
                    <?= ui_icon('menu', 'w-6 h-6') ?>
                </button>

                <!-- Liens du header -->
                <nav class="hidden items-center gap-1 lg:flex" aria-label="Navigation secondaire">
                    <?php foreach (array_slice($menu, 0, 4) as $item): $active = ui_is_active($item); ?>
                        <a href="<?= ui_url($item) ?>"
                           class="rounded-md px-3 py-2 text-sm transition
                                  <?= $active ? 'font-semibold text-primary-700' : 'text-slate-600 hover:bg-slate-200/60 hover:text-slate-900' ?>">
                            <?= esc($item['label']) ?>
                        </a>
                    <?php endforeach; ?>
                </nav>

                <!-- Titre (mobile) -->
                <span class="text-base font-semibold text-slate-800 lg:hidden"><?= esc($pageTitle) ?></span>
            </div>

            <div class="flex items-center gap-2 sm:gap-3">
                <span class="hidden text-sm font-semibold text-slate-700 xl:block"><?= esc(ui_date_fr()) ?></span>

                <button type="button" id="headerSearch" class="rounded-lg p-2 text-slate-500 hover:bg-slate-200/70 hover:text-slate-800" aria-label="Rechercher">
                    <?= ui_icon('search', 'w-5 h-5') ?>
                </button>

                <button type="button" class="rounded-lg border border-slate-300 bg-white p-2 text-slate-500 hover:text-slate-800" aria-label="Notifications">
                    <?= ui_icon('bell', 'w-5 h-5') ?>
                </button>

                <!-- Menu utilisateur -->
                <div class="relative">
                    <button type="button" id="userBtn" aria-haspopup="true" aria-expanded="false"
                            class="flex items-center gap-1.5 rounded-full p-0.5 pr-1.5 hover:bg-slate-200/70">
                        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-primary-400 text-sm font-bold text-white">
                            <?= esc($initials) ?>
                        </span>
                        <?= ui_icon('chevron', 'w-4 h-4 text-slate-500') ?>
                    </button>

                    <div id="userMenu" class="absolute right-0 mt-2 hidden w-72 rounded-xl border border-slate-200 bg-white p-2 shadow-xl">
                        <div class="flex items-center gap-3 rounded-lg bg-slate-50 p-3">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-primary-400 text-base font-bold text-white">
                                <?= esc($initials) ?>
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-900"><?= esc(trim($prenom . ' ' . $nom)) ?></p>
                                <p class="truncate text-xs text-slate-500"><?= esc($email) ?></p>
                                <span class="mt-1 inline-block rounded-full bg-primary-100 px-2 py-0.5 text-[11px] font-semibold text-primary-800">
                                    <?= esc($role) ?>
                                </span>
                            </div>
                        </div>
                        <?php if ($poste !== ''): ?>
                            <p class="px-3 pb-1 pt-3 text-xs text-slate-500">Poste : <span class="font-medium text-slate-700"><?= esc($poste) ?></span></p>
                        <?php endif; ?>
                        <a href="<?= site_url('logout') ?>"
                           class="mt-2 flex items-center gap-2 rounded-lg px-3 py-2.5 text-sm font-medium text-red-600 hover:bg-red-50">
                            <?= ui_icon('logout', 'w-5 h-5') ?> Se déconnecter
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- ---------- PAGE ---------- -->
        <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
            <div class="mx-auto w-full max-w-7xl">

                <?php if (session()->getFlashdata('error')): ?>
                    <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
                        <?= esc(session()->getFlashdata('error')) ?>
                    </div>
                <?php endif; ?>
                <?php if (session()->getFlashdata('success')): ?>
                    <div class="mb-5 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700" role="status">
                        <?= esc(session()->getFlashdata('success')) ?>
                    </div>
                <?php endif; ?>

                <?= $this->renderSection('content') ?>
            </div>
        </main>

        <footer class="border-t border-slate-200 px-4 py-4 text-center text-xs text-slate-400 sm:px-6 lg:px-8">
            &copy; <?= date('Y') ?> Socolait. Tous droits réservés.
        </footer>
    </div>

    <script>
        // --- Sidebar mobile ---
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        const openSidebar  = () => { sidebar.classList.remove('-translate-x-full'); overlay.classList.remove('hidden'); };
        const closeSidebar = () => { sidebar.classList.add('-translate-x-full');    overlay.classList.add('hidden'); };
        document.getElementById('sidebarOpen').addEventListener('click', openSidebar);
        document.getElementById('sidebarClose').addEventListener('click', closeSidebar);
        overlay.addEventListener('click', closeSidebar);

        // --- Menu utilisateur ---
        const userBtn  = document.getElementById('userBtn');
        const userMenu = document.getElementById('userMenu');
        userBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            const open = userMenu.classList.toggle('hidden') === false;
            userBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
        document.addEventListener('click', (e) => {
            if (!userMenu.contains(e.target)) {
                userMenu.classList.add('hidden');
                userBtn.setAttribute('aria-expanded', 'false');
            }
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') { userMenu.classList.add('hidden'); closeSidebar(); }
        });

        // --- Loupe du header : met le focus sur la recherche de la page ---
        document.getElementById('headerSearch').addEventListener('click', () => {
            const field = document.getElementById('tableSearch');
            if (field) { field.focus(); field.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
        });
    </script>
</body>
</html>