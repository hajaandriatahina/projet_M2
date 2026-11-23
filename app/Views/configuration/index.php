<?php
/**
 * app/Views/configuration/index.php
 * Attribution des KPI par utilisateur (table configuration) — même layout que les autres pages admin.
 *
 * Variables : $role, $title, $heading, $description, $cards,
 *             $users, $kpis, $assignedByUser (id_user => [id_nom,...]), $counts (id_user => nb), $departements
 *
 * Route (POST) : admin/configuration/save/{id_user}
 */
$roleBadge = [
    'Directeur'   => 'bg-indigo-100 text-indigo-800',
    'Validateur'  => 'bg-amber-100 text-amber-800',
    'Utilisateur' => 'bg-slate-100 text-slate-700',
];
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
    <div>
        <h1 class="text-3xl font-bold tracking-tight text-slate-900"><?= esc($heading) ?></h1>
        <p class="mt-2 max-w-2xl text-sm text-slate-600"><?= esc($description) ?></p>
    </div>

    <!-- ============ Cartes de statistiques ============ -->
    <section class="mt-7 grid grid-cols-1 gap-4 md:grid-cols-3" aria-label="Statistiques">
        <?php foreach ($cards as $card): ?>
            <article class="group relative overflow-hidden rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <span class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-primary-500 to-primary-300"></span>
                <p class="text-sm font-medium text-slate-500"><?= esc($card['label']) ?></p>
                <div class="mt-4 flex items-end justify-between gap-3">
                    <p class="text-4xl font-semibold tracking-tight text-slate-900"><?= esc((string) $card['value']) ?></p>
                    <?php if (! empty($card['chip'])): ?>
                        <span class="mb-1 inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">
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

    <!-- ============ Recherche ============ -->
    <section class="mt-8">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
            <div class="relative w-full lg:max-w-sm">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                    <?= ui_icon('search', 'w-5 h-5') ?>
                </span>
                <input id="tableSearch" type="search" placeholder="Rechercher un utilisateur..."
                       class="h-11 w-full rounded-md border border-slate-300 bg-white pl-11 pr-4 text-sm text-slate-800 placeholder-slate-400 outline-none transition focus:border-primary-500 focus:ring-4 focus:ring-primary-500/15">
            </div>

            <select id="fRole" aria-label="Filtrer par rôle"
                    class="h-11 rounded-md border border-slate-300 bg-white px-3 text-sm text-slate-700 outline-none transition focus:border-primary-500 focus:ring-4 focus:ring-primary-500/15">
                <option value="">Tous les rôles</option>
                <option value="Utilisateur">Utilisateur</option>
                <option value="Validateur">Validateur</option>
                <option value="Directeur">Directeur</option>
            </select>
        </div>

        <!-- ============ Tableau ============ -->
        <div class="mt-5 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 text-xs font-semibold text-slate-700">
                        <tr>
                            <th class="px-4 py-3">Utilisateur</th>
                            <th class="px-4 py-3">E-mail</th>
                            <th class="px-4 py-3">Rôle</th>
                            <th class="px-4 py-3">KPI attribués</th>
                            <th class="px-4 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody id="usersBody" class="divide-y divide-slate-100">
                        <?php foreach ($users as $u):
                            $uid   = (int) $u['id'];
                            $nb    = $counts[$uid] ?? 0;
                            $badge = $roleBadge[$u['role']] ?? 'bg-slate-100 text-slate-700';
                        ?>
                            <tr class="transition hover:bg-primary-50/40" data-role="<?= esc($u['role'], 'attr') ?>">
                                <td class="px-4 py-3" data-key="<?= esc(mb_strtolower($u['nom'] . ' ' . $u['prenom']), 'attr') ?>">
                                    <span class="font-medium text-slate-800"><?= esc($u['prenom'] . ' ' . $u['nom']) ?></span>
                                </td>
                                <td class="px-4 py-3 text-slate-600"><?= esc($u['email']) ?></td>
                                <td class="px-4 py-3">
                                    <span class="inline-block rounded px-2 py-0.5 text-[11px] font-bold <?= $badge ?>"><?= esc($u['role']) ?></span>
                                </td>
                                <td class="px-4 py-3">
                                    <?php if ($nb > 0): ?>
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                            <?= $nb ?> KPI
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-500">
                                            Aucun KPI
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    <button type="button"
                                            data-configure-id="<?= $uid ?>"
                                            data-configure-name="<?= esc($u['prenom'] . ' ' . $u['nom'], 'attr') ?>"
                                            data-assigned='<?= esc(json_encode(array_map('intval', $assignedByUser[$uid] ?? []))) ?>'
                                            class="inline-flex h-9 items-center gap-1.5 rounded-md border border-primary-200 bg-primary-50 px-3 text-sm font-semibold text-primary-700 transition hover:border-primary-300 hover:bg-primary-100">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 011.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.559.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.893.149c-.425.07-.765.383-.93.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 01-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.397.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 01-.12-1.45l.527-.737c.25-.35.273-.806.108-1.204-.165-.397-.505-.71-.93-.78l-.894-.15c-.542-.09-.94-.559-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.108-1.204l-.526-.738a1.125 1.125 0 01.12-1.45l.773-.773a1.125 1.125 0 011.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        Configurer
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p id="noResult" class="hidden px-4 py-8 text-center text-sm text-slate-500">Aucun utilisateur trouvé.</p>
            </div>
        </div>
    </section>

    <!-- ============ BOÎTE DE MESSAGE : Configuration des KPI d'un utilisateur ============ -->
    <div id="cfgModal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true" aria-labelledby="cfgModalTitle">
        <div data-close class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"></div>

        <div class="pointer-events-none relative flex min-h-full items-end justify-center p-4 sm:items-center">
            <div id="cfgModalBox"
                 class="pointer-events-auto flex max-h-[85vh] w-full max-w-3xl scale-95 flex-col overflow-hidden rounded-2xl bg-white opacity-0 shadow-2xl ring-1 ring-black/5 transition duration-200">

                <!-- En-tête -->
                <div class="flex items-start justify-between gap-4 bg-gradient-to-r from-primary-700 to-primary-500 px-6 py-5 text-white">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                        <div>
                            <h2 id="cfgModalTitle" class="text-lg font-semibold leading-tight">Configurer les KPI</h2>
                            <p id="cfgModalSub" class="text-xs text-primary-100">Cochez les KPI que cet utilisateur peut renseigner.</p>
                        </div>
                    </div>
                    <button type="button" data-close aria-label="Fermer"
                            class="flex h-8 w-8 items-center justify-center rounded-md text-white/80 transition hover:bg-white/15 hover:text-white">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Barre d'outils -->
                <div class="flex flex-col gap-2.5 border-b border-slate-100 px-6 py-3 sm:flex-row sm:items-center">
                    <div class="relative flex-1">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <?= ui_icon('search', 'w-4 h-4') ?>
                        </span>
                        <input id="cfgSearch" type="search" placeholder="Rechercher un KPI..."
                               class="h-10 w-full rounded-md border border-slate-300 bg-white pl-9 pr-3 text-sm outline-none transition focus:border-primary-500 focus:ring-4 focus:ring-primary-500/15">
                    </div>
                    <select id="cfgDept" aria-label="Filtrer par département"
                            class="h-10 rounded-md border border-slate-300 bg-white px-2.5 text-sm text-slate-700 outline-none transition focus:border-primary-500 focus:ring-4 focus:ring-primary-500/15">
                        <option value="">Tous les départements</option>
                        <?php foreach ($departements as $d): ?>
                            <option value="<?= esc($d, 'attr') ?>"><?= esc($d) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="flex items-center gap-2 text-xs">
                        <button type="button" id="cfgCheckAll" class="rounded-md border border-slate-300 px-2.5 py-2 font-medium text-slate-600 hover:bg-slate-50">Tout cocher</button>
                        <button type="button" id="cfgUncheckAll" class="rounded-md border border-slate-300 px-2.5 py-2 font-medium text-slate-600 hover:bg-slate-50">Tout décocher</button>
                    </div>
                </div>

                <!-- Formulaire : liste des KPI groupés par département -->
                <form id="cfgForm" method="post" action="" class="flex-1 overflow-y-auto px-6 py-4">
                    <?= csrf_field() ?>
                    <div id="cfgList" class="space-y-5"></div>
                    <p id="cfgEmpty" class="hidden py-8 text-center text-sm text-slate-500">Aucun KPI ne correspond à la recherche.</p>
                </form>

                <!-- Pied -->
                <div class="flex flex-col-reverse gap-3 border-t border-slate-100 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <p id="cfgCount" class="text-xs font-medium text-slate-500">0 KPI sélectionné</p>
                    <div class="flex flex-col-reverse gap-3 sm:flex-row">
                        <button type="button" data-close
                                class="h-11 rounded-md border border-slate-300 bg-white px-5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                            Annuler
                        </button>
                        <button type="submit" form="cfgForm"
                                class="h-11 rounded-md bg-primary-700 px-6 text-sm font-semibold text-white shadow-lg shadow-primary-700/25 transition hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300/60 active:scale-[.98]">
                            Enregistrer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
    (function () {
        /* ---------- Catalogue complet des KPI (chargé une seule fois) ---------- */
        const KPIS = <?= json_encode(array_map(static fn ($k) => [
            'id'         => (int) $k['id'],
            'nom'        => $k['nom'],
            'departement' => $k['departement'],
            'sqcdp'      => $k['sqcdp'] ?? '',
        ], $kpis), JSON_UNESCAPED_UNICODE) ?>;

        const SAVE_URL = <?= json_encode(site_url('admin/configuration/save')) ?> + '/';

        /* ---------- Tableau des utilisateurs : recherche + filtre rôle ---------- */
        const tbody  = document.getElementById('usersBody');
        const search = document.getElementById('tableSearch');
        const fRole  = document.getElementById('fRole');
        const empty  = document.getElementById('noResult');
        const rows   = [...tbody.querySelectorAll('tr')];

        function renderUsers() {
            const q = search.value.trim().toLowerCase();
            let visible = 0;
            rows.forEach(tr => {
                const okSearch = !q || tr.innerText.toLowerCase().includes(q);
                const okRole   = !fRole.value || tr.dataset.role === fRole.value;
                const show = okSearch && okRole;
                tr.classList.toggle('hidden', !show);
                if (show) visible++;
            });
            empty.classList.toggle('hidden', visible !== 0);
        }
        search.addEventListener('input', renderUsers);
        fRole.addEventListener('change', renderUsers);

        /* ---------- Boîte de message : configuration des KPI ---------- */
        const modal   = document.getElementById('cfgModal');
        const box     = document.getElementById('cfgModalBox');
        const form    = document.getElementById('cfgForm');
        const list    = document.getElementById('cfgList');
        const cfgSearch = document.getElementById('cfgSearch');
        const cfgDept   = document.getElementById('cfgDept');
        const cfgEmpty  = document.getElementById('cfgEmpty');
        const cfgCount  = document.getElementById('cfgCount');

        function open() {
            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
            requestAnimationFrame(() => box.classList.remove('scale-95', 'opacity-0'));
        }
        function close() {
            box.classList.add('scale-95', 'opacity-0');
            setTimeout(() => {
                modal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }, 180);
        }
        modal.querySelectorAll('[data-close]').forEach(el => el.addEventListener('click', close));
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) close();
        });

        function updateCount() {
            const n = form.querySelectorAll('input[name="kpis[]"]:checked').length;
            cfgCount.textContent = n + (n > 1 ? ' KPI sélectionnés' : ' KPI sélectionné');
        }

        function buildList(assignedIds) {
            const groups = {};
            KPIS.forEach(k => { (groups[k.departement] ??= []).push(k); });

            list.innerHTML = '';
            Object.keys(groups).sort((a, b) => a.localeCompare(b, 'fr')).forEach(dept => {
                const section = document.createElement('div');
                section.dataset.dept = dept;

                const title = document.createElement('h3');
                title.className = 'mb-2 text-xs font-bold uppercase tracking-wide text-slate-400';
                title.textContent = dept;
                section.appendChild(title);

                const grid = document.createElement('div');
                grid.className = 'grid grid-cols-1 gap-2 sm:grid-cols-2';

                groups[dept].forEach(k => {
                    const label = document.createElement('label');
                    label.className = 'kpi-row flex cursor-pointer items-start gap-2.5 rounded-lg border border-slate-200 px-3 py-2.5 text-sm transition hover:border-primary-300 hover:bg-primary-50/50';
                    label.dataset.search = k.nom.toLowerCase();

                    label.innerHTML = `
                        <input type="checkbox" name="kpis[]" value="${k.id}" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500">
                        <span class="leading-snug text-slate-700">${k.nom}${k.sqcdp ? ` <span class="ml-1 rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold text-slate-500">${k.sqcdp}</span>` : ''}</span>
                    `;
                    if (assignedIds.includes(k.id)) label.querySelector('input').checked = true;
                    label.querySelector('input').addEventListener('change', updateCount);
                    grid.appendChild(label);
                });

                section.appendChild(grid);
                list.appendChild(section);
            });
            updateCount();
        }

        function applyFilters() {
            const q = cfgSearch.value.trim().toLowerCase();
            const dept = cfgDept.value;
            let visibleTotal = 0;

            list.querySelectorAll('[data-dept]').forEach(section => {
                const deptMatch = !dept || section.dataset.dept === dept;
                let visibleInSection = 0;
                section.querySelectorAll('.kpi-row').forEach(row => {
                    const show = deptMatch && (!q || row.dataset.search.includes(q));
                    row.classList.toggle('hidden', !show);
                    if (show) visibleInSection++;
                });
                section.classList.toggle('hidden', visibleInSection === 0);
                visibleTotal += visibleInSection;
            });
            cfgEmpty.classList.toggle('hidden', visibleTotal !== 0);
        }
        cfgSearch.addEventListener('input', applyFilters);
        cfgDept.addEventListener('change', applyFilters);

        document.getElementById('cfgCheckAll').addEventListener('click', () => {
            list.querySelectorAll('.kpi-row:not(.hidden) input').forEach(c => c.checked = true);
            updateCount();
        });
        document.getElementById('cfgUncheckAll').addEventListener('click', () => {
            list.querySelectorAll('.kpi-row:not(.hidden) input').forEach(c => c.checked = false);
            updateCount();
        });

        tbody.addEventListener('click', e => {
            const btn = e.target.closest('[data-configure-id]');
            if (! btn) return;

            const userId     = btn.dataset.configureId;
            const assignedIds = JSON.parse(btn.dataset.assigned || '[]');

            document.getElementById('cfgModalTitle').textContent = 'Configurer les KPI de ' + btn.dataset.configureName;
            form.action = SAVE_URL + userId;
            cfgSearch.value = '';
            cfgDept.value = '';
            buildList(assignedIds);
            applyFilters();
            open();
        });
    })();
</script>

<?= $this->endSection() ?>