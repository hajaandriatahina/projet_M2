<?php
/**
 * app/Views/dashboard/index.php
 * Dashboard commun aux 4 rôles (titre, boutons, cartes et tableau changent selon le rôle).
 * Pour l'administrateur : ajout / modification / suppression des comptes via boîtes de message (modals).
 *
 * Variables : $role, $heading, $description, $ctaLabel, $cards, $users (ou null), $title
 *
 * Routes attendues (POST) :
 *   admin/users/store            -> créer un compte
 *   admin/users/update/{id}      -> modifier un compte (mot de passe facultatif)
 *   admin/users/delete/{id}      -> supprimer un compte
 *
 * Flash facultatifs : success, error, form_errors (tableau champ => message), form_mode ('create'|'edit'), form_id
 */
$avatarColors = ['bg-amber-200 text-amber-900', 'bg-sky-200 text-sky-900', 'bg-rose-200 text-rose-900',
                 'bg-emerald-200 text-emerald-900', 'bg-primary-200 text-primary-900'];

$roleBadge = [
    'Administrateur' => 'bg-primary-700 text-white',
    'Directeur'      => 'bg-indigo-100 text-indigo-800',
    'Validateur'     => 'bg-amber-100 text-amber-800',
    'Utilisateur'    => 'bg-slate-100 text-slate-700',
];

$isAdminTable = is_array($users ?? null);
$formErrors   = session()->getFlashdata('form_errors') ?? [];
$formMode     = session()->getFlashdata('form_mode');   // create | edit | null
$formId       = session()->getFlashdata('form_id');

$inputBase = 'h-11 w-full rounded-md border bg-white px-3.5 text-sm text-slate-800 placeholder-slate-400 outline-none transition '
           . 'focus:border-primary-500 focus:ring-4 focus:ring-primary-500/15';
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="relative isolate">

    <!-- ============ Fond décoratif ============ -->
    <div aria-hidden="true" class="pointer-events-none absolute inset-x-0 -top-8 -z-10 h-80 overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-b from-primary-50 via-white/60 to-transparent"></div>
        <div class="absolute -left-20 -top-24 h-72 w-72 rounded-full bg-primary-200/40 blur-3xl"></div>
        <div class="absolute right-0 -top-16 h-64 w-64 rounded-full bg-sky-200/40 blur-3xl"></div>
        <div class="absolute inset-0 opacity-[.35]"
             style="background-image: radial-gradient(rgb(148 163 184 / .35) 1px, transparent 1px); background-size: 22px 22px;
                    -webkit-mask-image: linear-gradient(to bottom, black, transparent); mask-image: linear-gradient(to bottom, black, transparent);"></div>
    </div>

    <!-- ============ Messages flash ============ -->
    <?php if (session()->getFlashdata('success')): ?>
        <div id="flashOk" role="status"
             class="mb-5 flex items-center gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 shadow-sm">
            <svg class="h-5 w-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z" clip-rule="evenodd"/></svg>
            <span class="flex-1"><?= esc(session()->getFlashdata('success')) ?></span>
            <button type="button" onclick="this.closest('#flashOk').remove()" class="text-emerald-700 hover:text-emerald-900" aria-label="Fermer">&times;</button>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div role="alert" class="mb-5 flex items-center gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 shadow-sm">
            <svg class="h-5 w-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10A8 8 0 112 10a8 8 0 0116 0zm-8-4a1 1 0 00-1 1v3a1 1 0 002 0V7a1 1 0 00-1-1zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
            <span><?= esc(session()->getFlashdata('error')) ?></span>
        </div>
    <?php endif; ?>

    <!-- ============ En-tête de page ============ -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900"><?= esc($heading) ?></h1>
            <p class="mt-2 max-w-2xl text-sm text-slate-600"><?= esc($description) ?></p>
        </div>

        <?php if ($isAdminTable): ?>
            <button type="button" id="btnAdd"
                    class="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-md bg-primary-700 px-5 text-sm font-semibold text-white shadow-lg shadow-primary-700/25 transition hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300/60 active:scale-[.98]">
                <?= ui_icon('plus', 'w-4 h-4') ?> <?= esc($ctaLabel) ?>
            </button>
        <?php else: ?>
            <a href="#"
               class="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-md bg-primary-700 px-5 text-sm font-semibold text-white shadow-lg shadow-primary-700/25 transition hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300/60">
                <?= ui_icon('plus', 'w-4 h-4') ?> <?= esc($ctaLabel) ?>
            </a>
        <?php endif; ?>
    </div>

    <!-- ============ Cartes de statistiques ============ -->
    <section class="mt-7 grid grid-cols-1 gap-4 md:grid-cols-3" aria-label="Statistiques">
        <?php foreach ($cards as $card): ?>
            <article class="group relative overflow-hidden rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <span class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-primary-500 to-primary-300"></span>
                <div class="flex items-start justify-between gap-2">
                    <p class="text-sm font-medium text-slate-500"><?= esc($card['label']) ?></p>
                </div>
                <div class="mt-4 flex items-end justify-between gap-3">
                    <p class="text-4xl font-semibold tracking-tight text-slate-900"><?= esc((string) $card['value']) ?></p>
                    <?php if (! empty($card['chip'])): ?>
                        <span class="mb-1 inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                            <?= esc($card['chip']) ?>
                        </span>
                    <?php endif; ?>
                </div>
                <?php if (! empty($card['hint'])): ?>
                    <p class="mt-3 text-xs text-slate-400"><?= esc($card['hint']) ?></p>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </section>

    <!-- ============ Tableau / contenu ============ -->
    <section class="mt-8">
        <?php if ($isAdminTable): ?>

            <!-- Recherche -->
            <div class="relative max-w-md">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                    <?= ui_icon('search', 'w-5 h-5') ?>
                </span>
                <input id="tableSearch" type="search" placeholder="Rechercher un utilisateur..."
                       class="h-11 w-full rounded-md border border-slate-300 bg-white pl-11 pr-4 text-sm text-slate-800 placeholder-slate-400 outline-none transition focus:border-primary-500 focus:ring-4 focus:ring-primary-500/15">
            </div>

            <div class="mt-5 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="border-b border-slate-200 bg-slate-50 text-xs font-semibold text-slate-700">
                            <tr>
                                <?php foreach ([['Nom', 0], ['E-mail', 1], ['Poste', 2], ['Rôle', 3], ['Statut', 4]] as [$label, $i]): ?>
                                    <th class="px-4 py-3">
                                        <button type="button" data-sort="<?= $i ?>" class="inline-flex items-center gap-1.5 hover:text-primary-700">
                                            <?= $label ?> <?= ui_icon('sort', 'w-3.5 h-3.5 text-slate-400') ?>
                                        </button>
                                    </th>
                                <?php endforeach; ?>
                                <th class="px-4 py-3">Créé le</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="usersBody" class="divide-y divide-slate-100">
                            <?php foreach ($users as $u):
                                $actif     = in_array($u['statut'], [true, 't', 'true', '1', 1], true);
                                $initiales = ui_initials((string) $u['prenom'], (string) $u['nom']);
                                $color     = $avatarColors[((int) $u['id']) % count($avatarColors)];
                                $badge     = $roleBadge[$u['role']] ?? 'bg-slate-100 text-slate-700';
                                $payload   = [
                                    'id'            => $u['id'],
                                    'nom'           => $u['nom'],
                                    'prenom'        => $u['prenom'],
                                    'numero'        => $u['numero'] ?? '',
                                    'poste_travail' => $u['poste_travail'] ?? '',
                                    'role'          => $u['role'],
                                    'email'         => $u['email'],
                                    'statut'        => $actif,
                                ];
                            ?>
                                <tr class="transition hover:bg-primary-50/40">
                                    <td class="px-4 py-3" data-key="<?= esc(mb_strtolower($u['nom'] . ' ' . $u['prenom']), 'attr') ?>">
                                        <div class="flex items-center gap-3">
                                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-bold <?= $color ?>">
                                                <?= esc($initiales) ?>
                                            </span>
                                            <span class="font-medium text-primary-700">
                                                <?= esc($u['prenom'] . ' ' . $u['nom']) ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-slate-600"><?= esc($u['email']) ?></td>
                                    <td class="px-4 py-3 text-slate-600"><?= esc($u['poste_travail']) ?></td>
                                    <td class="px-4 py-3">
                                        <span class="inline-block rounded px-2 py-0.5 text-[11px] font-bold <?= $badge ?>"><?= esc($u['role']) ?></span>
                                    </td>
                                    <td class="px-4 py-3" data-key="<?= $actif ? 'actif' : 'désactivé' ?>">
                                        <span class="inline-block rounded px-2 py-0.5 text-[11px] font-bold text-white <?= $actif ? 'bg-emerald-600' : 'bg-red-600' ?>">
                                            <?= $actif ? 'Actif' : 'Désactivé' ?>
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-slate-500">
                                        <?= $u['date_creation'] ? esc(date('d/m/Y H:i', strtotime($u['date_creation']))) : '—' ?>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button type="button" data-edit='<?= esc(json_encode($payload, JSON_UNESCAPED_UNICODE), 'attr') ?>'
                                                    title="Modifier" aria-label="Modifier <?= esc($u['prenom'] . ' ' . $u['nom'], 'attr') ?>"
                                                    class="flex h-8 w-8 items-center justify-center rounded-md border border-slate-200 text-slate-500 transition hover:border-primary-300 hover:bg-primary-50 hover:text-primary-700">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/></svg>
                                            </button>
                                            <button type="button" data-delete-id="<?= (int) $u['id'] ?>" data-delete-name="<?= esc($u['prenom'] . ' ' . $u['nom'], 'attr') ?>"
                                                    title="Supprimer" aria-label="Supprimer <?= esc($u['prenom'] . ' ' . $u['nom'], 'attr') ?>"
                                                    class="flex h-8 w-8 items-center justify-center rounded-md border border-slate-200 text-slate-500 transition hover:border-red-300 hover:bg-red-50 hover:text-red-600">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p id="noResult" class="hidden px-4 py-8 text-center text-sm text-slate-500">Aucun résultat.</p>
                </div>

                <!-- Pagination -->
                <div class="flex flex-col items-center justify-between gap-3 border-t border-slate-200 bg-slate-50/60 px-4 py-3 sm:flex-row">
                    <p id="pageInfo" class="text-xs text-slate-500"></p>
                    <div class="flex items-center gap-2">
                        <button type="button" id="btnPrev"
                                class="h-9 rounded-md border border-slate-300 bg-white px-4 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
                            Précédent
                        </button>
                        <span id="pageNum" class="min-w-[3.5rem] text-center text-sm font-semibold text-slate-700"></span>
                        <button type="button" id="btnNext"
                                class="h-9 rounded-md bg-primary-700 px-4 text-sm font-semibold text-white transition hover:bg-primary-800 disabled:cursor-not-allowed disabled:opacity-40">
                            Suivant
                        </button>
                    </div>
                </div>
            </div>

            <!-- ============ BOÎTE DE MESSAGE : Ajouter / Modifier ============ -->
            <div id="userModal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true" aria-labelledby="userModalTitle">
                <div data-close class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"></div>

                <div class="relative flex min-h-full items-end justify-center p-4 sm:items-center">
                    <div id="userModalBox"
                         class="w-full max-w-2xl scale-95 overflow-hidden rounded-2xl bg-white opacity-0 shadow-2xl ring-1 ring-black/5 transition duration-200">

                        <!-- En-tête -->
                        <div class="flex items-start justify-between gap-4 bg-gradient-to-r from-primary-700 to-primary-500 px-6 py-5 text-white">
                            <div class="flex items-center gap-3">
                                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM3 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 019.374 21c-2.331 0-4.512-.645-6.374-1.766z"/></svg>
                                </span>
                                <div>
                                    <h2 id="userModalTitle" class="text-lg font-semibold leading-tight">Ajouter un utilisateur</h2>
                                    <p id="userModalSub" class="text-xs text-primary-100">Renseignez les informations du compte.</p>
                                </div>
                            </div>
                            <button type="button" data-close aria-label="Fermer"
                                    class="flex h-8 w-8 items-center justify-center rounded-md text-white/80 transition hover:bg-white/15 hover:text-white">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <!-- Formulaire -->
                        <form id="userForm" method="post" action="<?= site_url('admin/users/store') ?>" class="max-h-[70vh] overflow-y-auto px-6 py-6" novalidate>
                            <?= csrf_field() ?>

                            <div class="grid grid-cols-1 gap-x-5 gap-y-4 sm:grid-cols-2">
                                <div>
                                    <label for="f_nom" class="mb-1.5 block text-sm font-medium text-slate-700">Nom <span class="text-red-500">*</span></label>
                                    <input id="f_nom" name="nom" type="text" required placeholder="Rakoto"
                                           value="<?= esc(old('nom'), 'attr') ?>"
                                           class="<?= $inputBase ?> <?= isset($formErrors['nom']) ? 'border-red-400' : 'border-slate-300' ?>">
                                    <?php if (isset($formErrors['nom'])): ?><p class="mt-1 text-xs text-red-600"><?= esc($formErrors['nom']) ?></p><?php endif; ?>
                                </div>

                                <div>
                                    <label for="f_prenom" class="mb-1.5 block text-sm font-medium text-slate-700">Prénom <span class="text-red-500">*</span></label>
                                    <input id="f_prenom" name="prenom" type="text" required placeholder="Haja"
                                           value="<?= esc(old('prenom'), 'attr') ?>"
                                           class="<?= $inputBase ?> <?= isset($formErrors['prenom']) ? 'border-red-400' : 'border-slate-300' ?>">
                                    <?php if (isset($formErrors['prenom'])): ?><p class="mt-1 text-xs text-red-600"><?= esc($formErrors['prenom']) ?></p><?php endif; ?>
                                </div>

                                <div>
                                    <label for="f_numero" class="mb-1.5 block text-sm font-medium text-slate-700">Numéro</label>
                                    <input id="f_numero" name="numero" type="text" placeholder="034 00 000 00"
                                           value="<?= esc(old('numero'), 'attr') ?>"
                                           class="<?= $inputBase ?> <?= isset($formErrors['numero']) ? 'border-red-400' : 'border-slate-300' ?>">
                                    <?php if (isset($formErrors['numero'])): ?><p class="mt-1 text-xs text-red-600"><?= esc($formErrors['numero']) ?></p><?php endif; ?>
                                </div>

                                <div>
                                    <label for="f_poste" class="mb-1.5 block text-sm font-medium text-slate-700">Poste de travail</label>
                                    <input id="f_poste" name="poste_travail" type="text" placeholder="Responsable qualité"
                                           value="<?= esc(old('poste_travail'), 'attr') ?>"
                                           class="<?= $inputBase ?> <?= isset($formErrors['poste_travail']) ? 'border-red-400' : 'border-slate-300' ?>">
                                    <?php if (isset($formErrors['poste_travail'])): ?><p class="mt-1 text-xs text-red-600"><?= esc($formErrors['poste_travail']) ?></p><?php endif; ?>
                                </div>

                                <div>
                                    <label for="f_role" class="mb-1.5 block text-sm font-medium text-slate-700">Rôle <span class="text-red-500">*</span></label>
                                    <select id="f_role" name="role" required
                                            class="<?= $inputBase ?> <?= isset($formErrors['role']) ? 'border-red-400' : 'border-slate-300' ?>">
                                        <?php foreach (['Utilisateur', 'Validateur', 'Directeur', 'Administrateur'] as $r): ?>
                                            <option value="<?= $r ?>" <?= old('role') === $r ? 'selected' : '' ?>><?= $r ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (isset($formErrors['role'])): ?><p class="mt-1 text-xs text-red-600"><?= esc($formErrors['role']) ?></p><?php endif; ?>
                                </div>

                                <div>
                                    <label for="f_email" class="mb-1.5 block text-sm font-medium text-slate-700">Adresse e-mail <span class="text-red-500">*</span></label>
                                    <input id="f_email" name="email" type="email" required placeholder="nom@socolait.com" autocomplete="off"
                                           value="<?= esc(old('email'), 'attr') ?>"
                                           class="<?= $inputBase ?> <?= isset($formErrors['email']) ? 'border-red-400' : 'border-slate-300' ?>">
                                    <?php if (isset($formErrors['email'])): ?><p class="mt-1 text-xs text-red-600"><?= esc($formErrors['email']) ?></p><?php endif; ?>
                                </div>

                                <div class="sm:col-span-2">
                                    <label for="f_password" class="mb-1.5 block text-sm font-medium text-slate-700">
                                        Mot de passe <span id="pwdStar" class="text-red-500">*</span>
                                        <span id="pwdHint" class="hidden font-normal text-slate-400">(laisser vide pour ne pas changer)</span>
                                    </label>
                                    <div class="relative">
                                        <input id="f_password" name="password" type="password" placeholder="••••••••" autocomplete="new-password"
                                               class="<?= $inputBase ?> pr-11 <?= isset($formErrors['password']) ? 'border-red-400' : 'border-slate-300' ?>">
                                        <button type="button" id="f_pwdToggle" aria-label="Afficher le mot de passe"
                                                class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 hover:text-slate-600">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </button>
                                    </div>
                                    <?php if (isset($formErrors['password'])): ?><p class="mt-1 text-xs text-red-600"><?= esc($formErrors['password']) ?></p><?php endif; ?>
                                </div>

                                <!-- Statut -->
                                <div class="sm:col-span-2">
                                    <label class="flex cursor-pointer items-center justify-between gap-4 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                                        <span>
                                            <span class="block text-sm font-medium text-slate-700">Compte actif</span>
                                            <span class="block text-xs text-slate-500">Un compte désactivé ne peut plus se connecter.</span>
                                        </span>
                                        <span class="relative inline-flex shrink-0">
                                            <input type="hidden" name="statut" value="0">
                                            <input id="f_statut" name="statut" type="checkbox" value="1" checked class="peer sr-only">
                                            <span class="h-6 w-11 rounded-full bg-slate-300 transition peer-checked:bg-emerald-500 peer-focus-visible:ring-4 peer-focus-visible:ring-emerald-500/25"></span>
                                            <span class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
                                        </span>
                                    </label>
                                </div>
                            </div>

                            <!-- Pied -->
                            <div class="mt-6 flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
                                <button type="button" data-close
                                        class="h-11 rounded-md border border-slate-300 bg-white px-5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                                    Annuler
                                </button>
                                <button type="submit" id="userSubmit"
                                        class="h-11 rounded-md bg-primary-700 px-6 text-sm font-semibold text-white shadow-lg shadow-primary-700/25 transition hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300/60 active:scale-[.98]">
                                    Enregistrer
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- ============ BOÎTE DE MESSAGE : Confirmation de suppression ============ -->
            <div id="deleteModal" class="fixed inset-0 z-50 hidden" role="alertdialog" aria-modal="true" aria-labelledby="delTitle">
                <div data-close class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"></div>

                <div class="relative flex min-h-full items-center justify-center p-4">
                    <div id="deleteModalBox"
                         class="w-full max-w-md scale-95 rounded-2xl bg-white p-6 text-center opacity-0 shadow-2xl ring-1 ring-black/5 transition duration-200">
                        <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-red-100 text-red-600">
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                        </span>
                        <h2 id="delTitle" class="mt-4 text-lg font-semibold text-slate-900">Supprimer ce compte ?</h2>
                        <p class="mt-2 text-sm text-slate-500">
                            Vous allez supprimer le compte de <strong id="delName" class="text-slate-800"></strong>.
                            Cette action est définitive.
                        </p>

                        <form id="deleteForm" method="post" action="" class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-center">
                            <?= csrf_field() ?>
                            <button type="button" data-close
                                    class="h-11 rounded-md border border-slate-300 bg-white px-6 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                                Annuler
                            </button>
                            <button type="submit"
                                    class="h-11 rounded-md bg-red-600 px-6 text-sm font-semibold text-white shadow-lg shadow-red-600/25 transition hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-300/60">
                                Oui, supprimer
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <script>
                (function () {
                    /* ---------- Tableau : recherche + tri + pagination (10 / page) ---------- */
                    const PER_PAGE = 10;
                    const tbody  = document.getElementById('usersBody');
                    const search = document.getElementById('tableSearch');
                    const empty  = document.getElementById('noResult');
                    const info   = document.getElementById('pageInfo');
                    const pageNum = document.getElementById('pageNum');
                    const prev   = document.getElementById('btnPrev');
                    const next   = document.getElementById('btnNext');

                    let rows = [...tbody.querySelectorAll('tr')];
                    let page = 1;

                    function render() {
                        const q = search.value.trim().toLowerCase();
                        const filtered = rows.filter(tr => tr.innerText.toLowerCase().includes(q));
                        const pages = Math.max(1, Math.ceil(filtered.length / PER_PAGE));
                        if (page > pages) page = pages;

                        const start = (page - 1) * PER_PAGE;
                        const visible = new Set(filtered.slice(start, start + PER_PAGE));
                        rows.forEach(tr => tr.classList.toggle('hidden', !visible.has(tr)));

                        empty.classList.toggle('hidden', filtered.length !== 0);
                        info.textContent = filtered.length
                            ? `${start + 1}–${start + visible.size} sur ${filtered.length} utilisateur(s)`
                            : '0 utilisateur';
                        pageNum.textContent = `${page} / ${pages}`;
                        prev.disabled = page <= 1;
                        next.disabled = page >= pages;
                    }

                    search.addEventListener('input', () => { page = 1; render(); });
                    prev.addEventListener('click', () => { page--; render(); });
                    next.addEventListener('click', () => { page++; render(); });

                    const keyOf = (tr, i) => (tr.children[i].dataset.key ?? tr.children[i].innerText).trim().toLowerCase();
                    document.querySelectorAll('[data-sort]').forEach(btn => {
                        let asc = true;
                        btn.addEventListener('click', () => {
                            const i = Number(btn.dataset.sort);
                            rows.sort((a, b) => keyOf(a, i).localeCompare(keyOf(b, i), 'fr', { numeric: true }) * (asc ? 1 : -1));
                            rows.forEach(tr => tbody.appendChild(tr));
                            asc = !asc;
                            page = 1;
                            render();
                        });
                    });
                    render();

                    /* ---------- Boîtes de message ---------- */
                    const userModal = document.getElementById('userModal');
                    const userBox   = document.getElementById('userModalBox');
                    const delModal  = document.getElementById('deleteModal');
                    const delBox    = document.getElementById('deleteModalBox');
                    const form      = document.getElementById('userForm');

                    const STORE_URL  = <?= json_encode(site_url('admin/users/store')) ?>;
                    const UPDATE_URL = <?= json_encode(site_url('admin/users/update')) ?> + '/';
                    const DELETE_URL = <?= json_encode(site_url('admin/users/delete')) ?> + '/';

                    function open(modal, box) {
                        modal.classList.remove('hidden');
                        document.body.classList.add('overflow-hidden');
                        requestAnimationFrame(() => box.classList.remove('scale-95', 'opacity-0'));
                    }
                    function close(modal, box) {
                        box.classList.add('scale-95', 'opacity-0');
                        setTimeout(() => {
                            modal.classList.add('hidden');
                            document.body.classList.remove('overflow-hidden');
                        }, 180);
                    }

                    [[userModal, userBox], [delModal, delBox]].forEach(([m, b]) => {
                        m.querySelectorAll('[data-close]').forEach(el => el.addEventListener('click', () => close(m, b)));
                    });
                    document.addEventListener('keydown', e => {
                        if (e.key !== 'Escape') return;
                        if (!userModal.classList.contains('hidden')) close(userModal, userBox);
                        if (!delModal.classList.contains('hidden'))  close(delModal, delBox);
                    });

                    function setMode(mode, data) {
                        const edit = mode === 'edit';
                        document.getElementById('userModalTitle').textContent = edit ? 'Modifier l\'utilisateur' : 'Ajouter un utilisateur';
                        document.getElementById('userModalSub').textContent   = edit ? 'Mettez à jour les informations du compte.' : 'Renseignez les informations du compte.';
                        document.getElementById('userSubmit').textContent     = edit ? 'Mettre à jour' : 'Enregistrer';
                        document.getElementById('pwdStar').classList.toggle('hidden', edit);
                        document.getElementById('pwdHint').classList.toggle('hidden', !edit);
                        document.getElementById('f_password').required = !edit;
                        form.action = edit ? UPDATE_URL + data.id : STORE_URL;

                        if (data) {
                            form.nom.value           = data.nom ?? '';
                            form.prenom.value        = data.prenom ?? '';
                            form.numero.value        = data.numero ?? '';
                            form.poste_travail.value = data.poste_travail ?? '';
                            form.role.value          = data.role ?? 'Utilisateur';
                            form.email.value         = data.email ?? '';
                            form.password.value      = '';
                            document.getElementById('f_statut').checked = !!data.statut;
                        } else {
                            form.reset();
                            document.getElementById('f_statut').checked = true;
                        }
                    }

                    document.getElementById('btnAdd').addEventListener('click', () => {
                        setMode('create', null);
                        open(userModal, userBox);
                        form.nom.focus();
                    });

                    tbody.addEventListener('click', e => {
                        const editBtn = e.target.closest('[data-edit]');
                        if (editBtn) {
                            setMode('edit', JSON.parse(editBtn.dataset.edit));
                            open(userModal, userBox);
                            form.nom.focus();
                            return;
                        }
                        const delBtn = e.target.closest('[data-delete-id]');
                        if (delBtn) {
                            document.getElementById('delName').textContent = delBtn.dataset.deleteName;
                            document.getElementById('deleteForm').action = DELETE_URL + delBtn.dataset.deleteId;
                            open(delModal, delBox);
                        }
                    });

                    // Afficher / masquer le mot de passe
                    const pwd = document.getElementById('f_password');
                    document.getElementById('f_pwdToggle').addEventListener('click', () => {
                        pwd.type = pwd.type === 'password' ? 'text' : 'password';
                    });

                    // Réouvrir le formulaire s'il y a des erreurs de validation après envoi
                    <?php if (! empty($formErrors)): ?>
                        (function () {
                            const mode = <?= json_encode($formMode ?: 'create') ?>;
                            const id   = <?= json_encode($formId) ?>;
                            const keepValues = {
                                nom: form.nom.value, prenom: form.prenom.value, numero: form.numero.value,
                                poste_travail: form.poste_travail.value, role: form.role.value, email: form.email.value
                            };
                            setMode(mode, mode === 'edit' ? { id: id, ...keepValues, statut: true } : null);
                            if (mode !== 'edit') Object.entries(keepValues).forEach(([k, v]) => form[k].value = v);
                            open(userModal, userBox);
                        })();
                    <?php endif; ?>
                })();
            </script>

        <?php else: ?>

            <!-- Zone de contenu (rôles sans tableau pour l'instant) -->
            <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 bg-white/70 px-6 py-14 text-center backdrop-blur">
                <span class="flex h-14 w-14 items-center justify-center rounded-full bg-primary-100 text-primary-700">
                    <?= ui_icon('inbox', 'w-7 h-7') ?>
                </span>
                <h2 class="mt-4 text-lg font-semibold text-slate-800">Aucun indicateur à afficher pour le moment</h2>
                <p class="mt-1 max-w-md text-sm text-slate-500">
                    Les indicateurs de votre espace apparaîtront ici dès que les tables KPI seront connectées.
                </p>
            </div>

        <?php endif; ?>
    </section>

</div>

<?= $this->endSection() ?>