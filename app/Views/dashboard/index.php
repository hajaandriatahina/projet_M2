<?php
/**
 * app/Views/dashboard/index.php
 * Dashboard commun aux 4 rôles (titre, boutons, cartes et tableau changent selon le rôle).
 *
 * Variables : $role, $heading, $description, $ctaLabel, $cards, $users (ou null), $title
 */
$avatarColors = ['bg-amber-200 text-amber-900', 'bg-sky-200 text-sky-900', 'bg-rose-200 text-rose-900',
                 'bg-emerald-200 text-emerald-900', 'bg-primary-200 text-primary-900'];

$roleBadge = [
    'Administrateur' => 'bg-primary-700 text-white',
    'Directeur'      => 'bg-indigo-100 text-indigo-800',
    'Validateur'     => 'bg-amber-100 text-amber-800',
    'Utilisateur'    => 'bg-slate-100 text-slate-700',
];
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<!-- ============ En-tête de page ============ -->
<div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
    <div>
        <h1 class="text-3xl font-bold tracking-tight text-slate-900"><?= esc($heading) ?></h1>
        <p class="mt-2 max-w-2xl text-sm text-slate-600"><?= esc($description) ?></p>
    </div>

    <a href="#"
       class="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-md bg-primary-700 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300/60">
        <?= ui_icon('plus', 'w-4 h-4') ?> <?= esc($ctaLabel) ?>
    </a>
</div>

<!-- ============ Cartes de statistiques ============ -->
<section class="mt-7 grid grid-cols-1 gap-4 md:grid-cols-3" aria-label="Statistiques">
    <?php foreach ($cards as $card): ?>
        <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-2">
                <p class="text-sm text-slate-500"><?= esc($card['label']) ?></p>
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
    <?php if (is_array($users)): ?>

        <!-- Recherche -->
        <div class="relative max-w-md">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                <?= ui_icon('search', 'w-5 h-5') ?>
            </span>
            <input id="tableSearch" type="search" placeholder="Rechercher un utilisateur..."
                   class="h-11 w-full rounded-md border border-slate-300 bg-white pl-11 pr-4 text-sm text-slate-800 placeholder-slate-400 outline-none transition focus:border-primary-500 focus:ring-4 focus:ring-primary-500/15">
        </div>

        <div class="mt-5 overflow-x-auto rounded-lg border border-slate-200 bg-white">
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
                    </tr>
                </thead>
                <tbody id="usersBody" class="divide-y divide-slate-100">
                    <?php foreach ($users as $u):
                        $actif    = in_array($u['statut'], [true, 't', 'true', '1', 1], true);
                        $initiales = ui_initials((string) $u['prenom'], (string) $u['nom']);
                        $color    = $avatarColors[((int) $u['id']) % count($avatarColors)];
                        $badge    = $roleBadge[$u['role']] ?? 'bg-slate-100 text-slate-700';
                    ?>
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-4 py-3" data-key="<?= esc(mb_strtolower($u['nom'] . ' ' . $u['prenom']), 'attr') ?>">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-bold <?= $color ?>">
                                        <?= esc($initiales) ?>
                                    </span>
                                    <span class="font-medium text-primary-700 underline-offset-2 hover:underline">
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
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p id="noResult" class="hidden px-4 py-8 text-center text-sm text-slate-500">Aucun résultat.</p>
        </div>

        <script>
            (function () {
                const tbody  = document.getElementById('usersBody');
                const search = document.getElementById('tableSearch');
                const empty  = document.getElementById('noResult');

                // Recherche en direct
                search.addEventListener('input', () => {
                    const q = search.value.trim().toLowerCase();
                    let visible = 0;
                    tbody.querySelectorAll('tr').forEach(tr => {
                        const show = tr.innerText.toLowerCase().includes(q);
                        tr.classList.toggle('hidden', !show);
                        if (show) visible++;
                    });
                    empty.classList.toggle('hidden', visible !== 0);
                });

                // Tri par colonne
                const keyOf = (tr, i) => (tr.children[i].dataset.key ?? tr.children[i].innerText).trim().toLowerCase();
                document.querySelectorAll('[data-sort]').forEach(btn => {
                    let asc = true;
                    btn.addEventListener('click', () => {
                        const i = Number(btn.dataset.sort);
                        [...tbody.querySelectorAll('tr')]
                            .sort((a, b) => keyOf(a, i).localeCompare(keyOf(b, i), 'fr', { numeric: true }) * (asc ? 1 : -1))
                            .forEach(tr => tbody.appendChild(tr));
                        asc = !asc;
                    });
                });
            })();
        </script>

    <?php else: ?>

        <!-- Zone de contenu (rôles sans tableau pour l'instant) -->
        <div class="flex flex-col items-center justify-center rounded-lg border border-dashed border-slate-300 bg-slate-50/60 px-6 py-14 text-center">
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

<?= $this->endSection() ?>