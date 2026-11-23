<?php
/**
 * app/Views/indicateurs/index.php
 * CRUD des indicateurs KPI (table nom_kpi) — même layout (sidebar + header) que le dashboard.
 *
 * Variables : $role, $title, $heading, $description, $ctaLabel, $cards,
 *             $indicateurs, $departements, $sqcdp, $frequences, $types
 *
 * Routes (POST) : admin/indicateurs/store | update/{id} | delete/{id}
 * Flash facultatifs : success, error, form_errors, form_mode ('create'|'edit'), form_id
 */
$formErrors = session()->getFlashdata('form_errors') ?? [];
$formMode   = session()->getFlashdata('form_mode');
$formId     = session()->getFlashdata('form_id');

$sqcdpBadge = [
    'S' => 'bg-emerald-100 text-emerald-800',
    'Q' => 'bg-sky-100 text-sky-800',
    'C' => 'bg-amber-100 text-amber-800',
    'D' => 'bg-violet-100 text-violet-800',
    'P' => 'bg-rose-100 text-rose-800',
];

$inputBase = 'h-11 w-full rounded-md border bg-white px-3.5 text-sm text-slate-800 placeholder-slate-400 outline-none transition '
           . 'focus:border-primary-500 focus:ring-4 focus:ring-primary-500/15';
$selectBase = 'h-11 rounded-md border border-slate-300 bg-white px-3 text-sm text-slate-700 outline-none transition '
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

        <button type="button" id="btnAdd"
                class="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-md bg-primary-700 px-5 text-sm font-semibold text-white shadow-lg shadow-primary-700/25 transition hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300/60 active:scale-[.98]">
            <?= ui_icon('plus', 'w-4 h-4') ?> <?= esc($ctaLabel) ?>
        </button>
    </div>

    <!-- ============ Cartes de statistiques ============ -->
 

    <!-- ============ Filtres ============ -->
    <section class="mt-8">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
            <div class="relative w-full lg:max-w-sm">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                    <?= ui_icon('search', 'w-5 h-5') ?>
                </span>
                <input id="tableSearch" type="search" placeholder="Rechercher un indicateur..."
                       class="h-11 w-full rounded-md border border-slate-300 bg-white pl-11 pr-4 text-sm text-slate-800 placeholder-slate-400 outline-none transition focus:border-primary-500 focus:ring-4 focus:ring-primary-500/15">
            </div>

            <select id="fDept" aria-label="Filtrer par département" class="<?= $selectBase ?>">
                <option value="">Tous les départements</option>
                <?php foreach ($departements as $d): ?>
                    <option value="<?= esc($d, 'attr') ?>"><?= esc($d) ?></option>
                <?php endforeach; ?>
            </select>

            <select id="fSqcdp" aria-label="Filtrer par SQCDP" class="<?= $selectBase ?>">
                <option value="">Tous SQCDP</option>
                <?php foreach ($sqcdp as $code => $label): ?>
                    <option value="<?= $code ?>"><?= $code ?> — <?= esc($label) ?></option>
                <?php endforeach; ?>
            </select>

            <select id="fFreq" aria-label="Filtrer par fréquence" class="<?= $selectBase ?>">
                <option value="">Toutes fréquences</option>
                <?php foreach ($frequences as $code => $label): ?>
                    <option value="<?= $code ?>"><?= esc($label) ?></option>
                <?php endforeach; ?>
            </select>

            <button type="button" id="btnReset"
                    class="h-11 rounded-md border border-slate-300 bg-white px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                Réinitialiser
            </button>
        </div>

        <!-- ============ Tableau ============ -->
        <div class="mt-5 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 text-xs font-semibold text-slate-700">
                        <tr>
                            <?php foreach ([['Indicateur', 0], ['Département', 1], ['SQCDP', 2], ['Unité', 3], ['Fréquence', 4], ['Type', 5]] as [$label, $i]): ?>
                                <th class="px-4 py-3">
                                    <button type="button" data-sort="<?= $i ?>" class="inline-flex items-center gap-1.5 hover:text-primary-700">
                                        <?= $label ?> <?= ui_icon('sort', 'w-3.5 h-3.5 text-slate-400') ?>
                                    </button>
                                </th>
                            <?php endforeach; ?>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="kpiBody" class="divide-y divide-slate-100">
                        <?php foreach ($indicateurs as $k):
                            $code    = (string) ($k['sqcdp'] ?? '');
                            $freq    = (string) ($k['frequence'] ?? '');
                            $payload = [
                                'id'          => $k['id'],
                                'nom'         => $k['nom'],
                                'description' => $k['description'] ?? '',
                                'departement' => $k['departement'],
                                'sqcdp'       => $code,
                                'unite'       => $k['unite'] ?? '',
                                'frequence'   => $freq,
                                'type'        => $k['type'] ?? '',
                            ];
                        ?>
                            <tr class="transition hover:bg-primary-50/40"
                                data-dept="<?= esc($k['departement'], 'attr') ?>"
                                data-sqcdp="<?= esc($code, 'attr') ?>"
                                data-freq="<?= esc($freq, 'attr') ?>">
                                <td class="px-4 py-3" data-key="<?= esc(mb_strtolower($k['nom']), 'attr') ?>">
                                    <p class="font-medium text-primary-700"><?= esc($k['nom']) ?></p>
                                    <?php if (! empty($k['description'])): ?>
                                        <p class="mt-0.5 max-w-xs truncate text-xs text-slate-400" title="<?= esc($k['description'], 'attr') ?>"><?= esc($k['description']) ?></p>
                                    <?php endif; ?>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600"><?= esc($k['departement']) ?></td>
                                <td class="px-4 py-3">
                                    <?php if ($code !== ''): ?>
                                        <span title="<?= esc($sqcdp[$code] ?? '', 'attr') ?>"
                                              class="inline-flex h-6 w-6 items-center justify-center rounded text-xs font-bold <?= $sqcdpBadge[$code] ?? 'bg-slate-100 text-slate-700' ?>"><?= esc($code) ?></span>
                                    <?php else: ?>
                                        <span class="text-slate-300">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-slate-600"><?= ! empty($k['unite']) ? esc($k['unite']) : '<span class="text-slate-300">—</span>' ?></td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600"><?= $freq !== '' ? esc($frequences[$freq] ?? $freq) : '<span class="text-slate-300">—</span>' ?></td>
                                <td class="px-4 py-3">
                                    <?php if (! empty($k['type'])): ?>
                                        <span class="inline-block rounded bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-700">Type <?= (int) $k['type'] ?></span>
                                    <?php else: ?>
                                        <span class="text-slate-300">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button type="button" data-edit='<?= esc(json_encode($payload, JSON_UNESCAPED_UNICODE), 'attr') ?>'
                                                title="Modifier" aria-label="Modifier <?= esc($k['nom'], 'attr') ?>"
                                                class="flex h-8 w-8 items-center justify-center rounded-md border border-slate-200 text-slate-500 transition hover:border-primary-300 hover:bg-primary-50 hover:text-primary-700">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/></svg>
                                        </button>
                                        <button type="button" data-delete-id="<?= (int) $k['id'] ?>" data-delete-name="<?= esc($k['nom'], 'attr') ?>"
                                                title="Supprimer" aria-label="Supprimer <?= esc($k['nom'], 'attr') ?>"
                                                class="flex h-8 w-8 items-center justify-center rounded-md border border-slate-200 text-slate-500 transition hover:border-red-300 hover:bg-red-50 hover:text-red-600">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p id="noResult" class="hidden px-4 py-8 text-center text-sm text-slate-500">Aucun indicateur trouvé.</p>
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
    </section>

    <!-- ============ BOÎTE DE MESSAGE : Ajouter / Modifier ============ -->
    <div id="kpiModal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true" aria-labelledby="kpiModalTitle">
        <div data-close class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"></div>

        <div class="pointer-events-none relative flex min-h-full items-end justify-center p-4 sm:items-center">
            <div id="kpiModalBox"
                 class="pointer-events-auto w-full max-w-2xl scale-95 overflow-hidden rounded-2xl bg-white opacity-0 shadow-2xl ring-1 ring-black/5 transition duration-200">

                <div class="flex items-start justify-between gap-4 bg-gradient-to-r from-primary-700 to-primary-500 px-6 py-5 text-white">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3v18h18M7 14l4-4 4 4 5-6"/></svg>
                        </span>
                        <div>
                            <h2 id="kpiModalTitle" class="text-lg font-semibold leading-tight">Ajouter un indicateur</h2>
                            <p id="kpiModalSub" class="text-xs text-primary-100">Renseignez les informations de l'indicateur.</p>
                        </div>
                    </div>
                    <button type="button" data-close aria-label="Fermer"
                            class="flex h-8 w-8 items-center justify-center rounded-md text-white/80 transition hover:bg-white/15 hover:text-white">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form id="kpiForm" method="post" action="<?= site_url('admin/indicateurs/store') ?>" class="max-h-[70vh] overflow-y-auto px-6 py-6" novalidate>
                    <?= csrf_field() ?>

                    <div class="grid grid-cols-1 gap-x-5 gap-y-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="k_nom" class="mb-1.5 block text-sm font-medium text-slate-700">Nom de l'indicateur <span class="text-red-500">*</span></label>
                            <input id="k_nom" name="nom" type="text" required maxlength="255" placeholder="Taux de réclamations clients"
                                   value="<?= esc(old('nom'), 'attr') ?>"
                                   class="<?= $inputBase ?> <?= isset($formErrors['nom']) ? 'border-red-400' : 'border-slate-300' ?>">
                            <?php if (isset($formErrors['nom'])): ?><p class="mt-1 text-xs text-red-600"><?= esc($formErrors['nom']) ?></p><?php endif; ?>
                        </div>

                        <div class="sm:col-span-2">
                            <label for="k_desc" class="mb-1.5 block text-sm font-medium text-slate-700">Description</label>
                            <textarea id="k_desc" name="description" rows="3" placeholder="Mode de calcul, périmètre, source des données..."
                                      class="w-full rounded-md border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-400 outline-none transition focus:border-primary-500 focus:ring-4 focus:ring-primary-500/15"><?= esc(old('description')) ?></textarea>
                        </div>

                        <div>
                            <label for="k_dept" class="mb-1.5 block text-sm font-medium text-slate-700">Département <span class="text-red-500">*</span></label>
                            <select id="k_dept" name="departement" required
                                    class="<?= $inputBase ?> <?= isset($formErrors['departement']) ? 'border-red-400' : 'border-slate-300' ?>">
                                <option value="">— Choisir —</option>
                                <?php foreach ($departements as $d): ?>
                                    <option value="<?= esc($d, 'attr') ?>" <?= old('departement') === $d ? 'selected' : '' ?>><?= esc($d) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($formErrors['departement'])): ?><p class="mt-1 text-xs text-red-600"><?= esc($formErrors['departement']) ?></p><?php endif; ?>
                        </div>

                        <div>
                            <label for="k_sqcdp" class="mb-1.5 block text-sm font-medium text-slate-700">SQCDP</label>
                            <select id="k_sqcdp" name="sqcdp"
                                    class="<?= $inputBase ?> <?= isset($formErrors['sqcdp']) ? 'border-red-400' : 'border-slate-300' ?>">
                                <option value="">— Aucun —</option>
                                <?php foreach ($sqcdp as $code => $label): ?>
                                    <option value="<?= $code ?>" <?= old('sqcdp') === $code ? 'selected' : '' ?>><?= $code ?> — <?= esc($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($formErrors['sqcdp'])): ?><p class="mt-1 text-xs text-red-600"><?= esc($formErrors['sqcdp']) ?></p><?php endif; ?>
                        </div>

                        <div>
                            <label for="k_unite" class="mb-1.5 block text-sm font-medium text-slate-700">Unité</label>
                            <input id="k_unite" name="unite" type="text" maxlength="50" placeholder="%, L, kg, Ar..."
                                   value="<?= esc(old('unite'), 'attr') ?>"
                                   class="<?= $inputBase ?> <?= isset($formErrors['unite']) ? 'border-red-400' : 'border-slate-300' ?>">
                            <?php if (isset($formErrors['unite'])): ?><p class="mt-1 text-xs text-red-600"><?= esc($formErrors['unite']) ?></p><?php endif; ?>
                        </div>

                        <div>
                            <label for="k_freq" class="mb-1.5 block text-sm font-medium text-slate-700">Fréquence</label>
                            <select id="k_freq" name="frequence"
                                    class="<?= $inputBase ?> <?= isset($formErrors['frequence']) ? 'border-red-400' : 'border-slate-300' ?>">
                                <option value="">— Aucune —</option>
                                <?php foreach ($frequences as $code => $label): ?>
                                    <option value="<?= $code ?>" <?= old('frequence') === $code ? 'selected' : '' ?>><?= esc($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($formErrors['frequence'])): ?><p class="mt-1 text-xs text-red-600"><?= esc($formErrors['frequence']) ?></p><?php endif; ?>
                        </div>

                        <div>
                            <label for="k_type" class="mb-1.5 block text-sm font-medium text-slate-700">Type</label>
                            <select id="k_type" name="type"
                                    class="<?= $inputBase ?> <?= isset($formErrors['type']) ? 'border-red-400' : 'border-slate-300' ?>">
                                <option value="">— Aucun —</option>
                                <?php foreach ($types as $t): ?>
                                    <option value="<?= $t ?>" <?= (string) old('type') === (string) $t ? 'selected' : '' ?>>Type <?= $t ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($formErrors['type'])): ?><p class="mt-1 text-xs text-red-600"><?= esc($formErrors['type']) ?></p><?php endif; ?>
                        </div>
                    </div>

                    <div class="mt-6 flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
                        <button type="button" data-close
                                class="h-11 rounded-md border border-slate-300 bg-white px-5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                            Annuler
                        </button>
                        <button type="submit" id="kpiSubmit"
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

        <div class="pointer-events-none relative flex min-h-full items-center justify-center p-4">
            <div id="deleteModalBox"
                 class="pointer-events-auto w-full max-w-md scale-95 rounded-2xl bg-white p-6 text-center opacity-0 shadow-2xl ring-1 ring-black/5 transition duration-200">
                <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-red-100 text-red-600">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                </span>
                <h2 id="delTitle" class="mt-4 text-lg font-semibold text-slate-900">Supprimer cet indicateur ?</h2>
                <p class="mt-2 text-sm text-slate-500">
                    Vous allez supprimer <strong id="delName" class="text-slate-800"></strong>.
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

</div>

<script>
    (function () {
        /* ---------- Tableau : recherche + filtres + tri + pagination (10 / page) ---------- */
        const PER_PAGE = 10;
        const tbody   = document.getElementById('kpiBody');
        const search  = document.getElementById('tableSearch');
        const fDept   = document.getElementById('fDept');
        const fSqcdp  = document.getElementById('fSqcdp');
        const fFreq   = document.getElementById('fFreq');
        const empty   = document.getElementById('noResult');
        const info    = document.getElementById('pageInfo');
        const pageNum = document.getElementById('pageNum');
        const prev    = document.getElementById('btnPrev');
        const next    = document.getElementById('btnNext');

        let rows = [...tbody.querySelectorAll('tr')];
        let page = 1;

        function matches(tr, q) {
            if (q && !tr.innerText.toLowerCase().includes(q)) return false;
            if (fDept.value  && tr.dataset.dept  !== fDept.value)  return false;
            if (fSqcdp.value && tr.dataset.sqcdp !== fSqcdp.value) return false;
            if (fFreq.value  && tr.dataset.freq  !== fFreq.value)  return false;
            return true;
        }

        function render() {
            const q = search.value.trim().toLowerCase();
            const filtered = rows.filter(tr => matches(tr, q));
            const pages = Math.max(1, Math.ceil(filtered.length / PER_PAGE));
            if (page > pages) page = pages;

            const start = (page - 1) * PER_PAGE;
            const visible = new Set(filtered.slice(start, start + PER_PAGE));
            rows.forEach(tr => tr.classList.toggle('hidden', !visible.has(tr)));

            empty.classList.toggle('hidden', filtered.length !== 0);
            info.textContent = filtered.length
                ? `${start + 1}–${start + visible.size} sur ${filtered.length} indicateur(s)`
                : '0 indicateur';
            pageNum.textContent = `${page} / ${pages}`;
            prev.disabled = page <= 1;
            next.disabled = page >= pages;
        }

        [search, fDept, fSqcdp, fFreq].forEach(el => el.addEventListener('input', () => { page = 1; render(); }));
        document.getElementById('btnReset').addEventListener('click', () => {
            search.value = ''; fDept.value = ''; fSqcdp.value = ''; fFreq.value = '';
            page = 1; render();
        });
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
        const kpiModal = document.getElementById('kpiModal');
        const kpiBox   = document.getElementById('kpiModalBox');
        const delModal = document.getElementById('deleteModal');
        const delBox   = document.getElementById('deleteModalBox');
        const form     = document.getElementById('kpiForm');

        const STORE_URL  = <?= json_encode(site_url('admin/indicateurs/store')) ?>;
        const UPDATE_URL = <?= json_encode(site_url('admin/indicateurs/update')) ?> + '/';
        const DELETE_URL = <?= json_encode(site_url('admin/indicateurs/delete')) ?> + '/';

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

        [[kpiModal, kpiBox], [delModal, delBox]].forEach(([m, b]) => {
            m.querySelectorAll('[data-close]').forEach(el => el.addEventListener('click', () => close(m, b)));
        });
        document.addEventListener('keydown', e => {
            if (e.key !== 'Escape') return;
            if (!kpiModal.classList.contains('hidden')) close(kpiModal, kpiBox);
            if (!delModal.classList.contains('hidden')) close(delModal, delBox);
        });

        function setMode(mode, data) {
            const edit = mode === 'edit';
            document.getElementById('kpiModalTitle').textContent = edit ? 'Modifier l\'indicateur' : 'Ajouter un indicateur';
            document.getElementById('kpiModalSub').textContent   = edit ? 'Mettez à jour les informations de l\'indicateur.' : 'Renseignez les informations de l\'indicateur.';
            document.getElementById('kpiSubmit').textContent     = edit ? 'Mettre à jour' : 'Enregistrer';
            form.action = edit ? UPDATE_URL + data.id : STORE_URL;

            if (data) {
                form.nom.value         = data.nom ?? '';
                form.description.value = data.description ?? '';
                form.departement.value = data.departement ?? '';
                form.sqcdp.value       = data.sqcdp ?? '';
                form.unite.value       = data.unite ?? '';
                form.frequence.value   = data.frequence ?? '';
                form.type.value        = data.type ?? '';
            } else {
                form.reset();
                ['departement', 'sqcdp', 'frequence', 'type'].forEach(n => form[n].value = '');
            }
        }

        document.getElementById('btnAdd').addEventListener('click', () => {
            setMode('create', null);
            open(kpiModal, kpiBox);
            form.nom.focus();
        });

        tbody.addEventListener('click', e => {
            const editBtn = e.target.closest('[data-edit]');
            if (editBtn) {
                setMode('edit', JSON.parse(editBtn.dataset.edit));
                open(kpiModal, kpiBox);
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

        // Réouvrir le formulaire s'il y a des erreurs de validation après envoi
        <?php if (! empty($formErrors)): ?>
        (function () {
            const mode = <?= json_encode($formMode ?: 'create') ?>;
            const id   = <?= json_encode($formId) ?>;
            const keep = {
                nom: form.nom.value, description: form.description.value, departement: form.departement.value,
                sqcdp: form.sqcdp.value, unite: form.unite.value, frequence: form.frequence.value, type: form.type.value
            };
            setMode(mode, { id: id, ...keep });
            open(kpiModal, kpiBox);
        })();
        <?php endif; ?>
    })();
</script>

<?= $this->endSection() ?>