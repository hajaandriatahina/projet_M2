<?php
// app/Views/user/dashboard.php
// Utilise le layout : app/Views/layouts/user_layout.php

$sections = [
    'S' => ['nom' => 'Sécurité',  'couleur' => '#ef4444'],
    'Q' => ['nom' => 'Qualité',   'couleur' => '#3b82f6'],
    'C' => ['nom' => 'Coût',      'couleur' => '#f59e0b'],
    'D' => ['nom' => 'Délai',     'couleur' => '#10b981'],
    'P' => ['nom' => 'Personnel', 'couleur' => '#8b5cf6'],
];
$badge = [
    'S' => 'bg-red-100 text-red-700',
    'Q' => 'bg-blue-100 text-blue-700',
    'C' => 'bg-amber-100 text-amber-700',
    'D' => 'bg-emerald-100 text-emerald-700',
    'P' => 'bg-violet-100 text-violet-700',
];
$moisLabel = strftime_fr($filtres['mois']);

// petite aide : "2026-11" -> "Novembre 2026"
function strftime_fr(string $ym): string
{
    $mois = ['01'=>'Janvier','02'=>'Février','03'=>'Mars','04'=>'Avril','05'=>'Mai','06'=>'Juin',
             '07'=>'Juillet','08'=>'Août','09'=>'Septembre','10'=>'Octobre','11'=>'Novembre','12'=>'Décembre'];
    [$y, $m] = explode('-', $ym);
    return ($mois[$m] ?? $m) . ' ' . $y;
}

$totalKpi   = array_sum(array_column($cartes, 'total'));
$totalSaisi = array_sum(array_column($cartes, 'saisis'));
?>
<?= $this->extend('layouts/user_layout') ?>

<?= $this->section('content') ?>

<!-- ============ Titre + filtres ============ -->
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-800">Tableau de bord</h1>
    <p class="text-sm text-slate-500">Indicateurs de vos KPI attribués — <?= esc($moisLabel) ?></p>
</div>

<form method="get" action="<?= site_url('user/dashboard') ?>"
      class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 mb-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">

    <div>
        <label class="block text-xs font-medium text-slate-500 mb-1">Département</label>
        <select name="departement" class="w-full rounded-lg border-slate-300 border px-3 py-2 text-sm focus:ring-2 focus:ring-violet-400 outline-none">
            <option value="">Tous</option>
            <?php foreach ($departements as $d): ?>
                <option value="<?= esc($d) ?>" <?= $filtres['departement'] === $d ? 'selected' : '' ?>><?= esc($d) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div>
        <label class="block text-xs font-medium text-slate-500 mb-1">SQCDP</label>
        <select name="sqcdp" class="w-full rounded-lg border-slate-300 border px-3 py-2 text-sm focus:ring-2 focus:ring-violet-400 outline-none">
            <option value="">Tous</option>
            <?php foreach ($sections as $l => $s): ?>
                <option value="<?= $l ?>" <?= $filtres['sqcdp'] === $l ? 'selected' : '' ?>><?= $l ?> — <?= $s['nom'] ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div>
        <label class="block text-xs font-medium text-slate-500 mb-1">Fréquence</label>
        <select name="frequence" class="w-full rounded-lg border-slate-300 border px-3 py-2 text-sm focus:ring-2 focus:ring-violet-400 outline-none">
            <option value="">Toutes</option>
            <?php foreach ($frequences as $f): ?>
                <option value="<?= $f ?>" <?= $filtres['frequence'] === $f ? 'selected' : '' ?>><?= $f ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div>
        <label class="block text-xs font-medium text-slate-500 mb-1">Mois</label>
        <input type="month" name="mois" value="<?= esc($filtres['mois']) ?>"
               class="w-full rounded-lg border-slate-300 border px-3 py-2 text-sm focus:ring-2 focus:ring-violet-400 outline-none">
    </div>

    <div class="flex gap-2">
        <button type="submit" class="flex-1 bg-violet-600 hover:bg-violet-700 text-white text-sm font-medium rounded-lg px-4 py-2 transition">Filtrer</button>
        <a href="<?= site_url('user/dashboard') ?>" class="px-4 py-2 text-sm rounded-lg border border-slate-300 hover:bg-slate-50 text-slate-600">Réinitialiser</a>
    </div>
</form>

<!-- ============ Cartes S Q C D P ============ -->
<div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
    <?php foreach ($sections as $l => $s):
        $c   = $cartes[$l];
        $pct = $c['total'] > 0 ? round($c['saisis'] / $c['total'] * 100) : 0;
    ?>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 text-center
                    <?= $filtres['sqcdp'] === $l ? 'ring-2 ring-violet-400' : '' ?>">
            <p class="text-sm font-semibold tracking-wide uppercase text-slate-500"><?= $s['nom'] ?></p>

            <!-- Lettre à la place du graphique circulaire -->
            <a href="<?= site_url('user/dashboard') . '?' . http_build_query(array_merge($filtres, ['sqcdp' => $l])) ?>"
               class="mx-auto my-4 w-28 h-28 rounded-full flex items-center justify-center text-6xl font-extrabold hover:scale-105 transition"
               style="color: <?= $s['couleur'] ?>; border: 8px solid <?= $s['couleur'] ?>22; background: conic-gradient(<?= $s['couleur'] ?> <?= $pct ?>%, #e5e7eb 0);
                      -webkit-mask: none;">
                <span class="w-[84px] h-[84px] rounded-full bg-white flex items-center justify-center"><?= $l ?></span>
            </a>

            <!-- Total de KPI de cette lettre -->
            <p class="text-2xl font-bold text-slate-800"><?= $c['total'] ?></p>
            <p class="text-xs text-slate-500">Total KPI « <?= $l ?> »</p>
            <p class="mt-2 text-xs text-slate-400"><?= $c['saisis'] ?>/<?= $c['total'] ?> saisis (<?= $pct ?>%)</p>
        </div>
    <?php endforeach; ?>
</div>

<!-- ============ Graphiques ============ -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
        <h3 class="text-sm font-semibold text-slate-700 text-center mb-3">KPI par section SQCDP</h3>
        <div class="h-64"><canvas id="chartSqcdp"></canvas></div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
        <h3 class="text-sm font-semibold text-slate-700 text-center mb-3">Saisies par jour — <?= esc($moisLabel) ?></h3>
        <div class="h-64"><canvas id="chartJour"></canvas></div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
        <h3 class="text-sm font-semibold text-slate-700 text-center mb-3">Avancement de la saisie</h3>
        <div class="h-64"><canvas id="chartAvancement"></canvas></div>
    </div>
</div>

<!-- ============ Tableau des données ============ -->
<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
        <h3 class="font-semibold text-slate-700">Mes indicateurs</h3>
        <span class="text-xs text-slate-500"><?= count($kpis) ?> KPI</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                <tr>
                    <th class="text-left px-5 py-3">KPI</th>
                    <th class="text-left px-5 py-3">Département</th>
                    <th class="text-center px-5 py-3">SQCDP</th>
                    <th class="text-left px-5 py-3">Fréquence</th>
                    <th class="text-left px-5 py-3">Unité</th>
                    <th class="text-right px-5 py-3">Dernière valeur</th>
                    <th class="text-left px-5 py-3">Dernière saisie</th>
                    <th class="text-center px-5 py-3">Statut</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($kpis)): ?>
                    <tr><td colspan="8" class="px-5 py-10 text-center text-slate-400">Aucun KPI ne vous est attribué pour ces filtres.</td></tr>
                <?php endif; ?>
                <?php foreach ($kpis as $k): ?>
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3 font-medium text-slate-800"><?= esc($k['nom']) ?></td>
                        <td class="px-5 py-3 text-slate-600"><?= esc($k['departement']) ?></td>
                        <td class="px-5 py-3 text-center">
                            <span class="inline-block w-7 h-7 leading-7 rounded-full text-xs font-bold <?= $badge[$k['sqcdp']] ?? 'bg-slate-100 text-slate-600' ?>"><?= esc($k['sqcdp']) ?></span>
                        </td>
                        <td class="px-5 py-3 text-slate-600"><?= esc($k['frequence']) ?></td>
                        <td class="px-5 py-3 text-slate-600"><?= esc($k['unite']) ?></td>
                        <td class="px-5 py-3 text-right font-semibold"><?= $k['derniere_valeur'] !== null ? esc($k['derniere_valeur']) : '—' ?></td>
                        <td class="px-5 py-3 text-slate-600"><?= $k['derniere_date'] ? esc(date('d/m/Y', strtotime($k['derniere_date']))) : '—' ?></td>
                        <td class="px-5 py-3 text-center">
                            <?php if ($k['nb_saisies'] > 0): ?>
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">Saisi</span>
                            <?php else: ?>
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-rose-100 text-rose-700">À saisir</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    const couleurs = <?= json_encode(array_column($sections, 'couleur')) ?>;
    const lettres  = <?= json_encode(array_keys($sections)) ?>;
    const totaux   = <?= json_encode(array_values(array_column($cartes, 'total'))) ?>;
    const saisis   = <?= json_encode(array_values(array_column($cartes, 'saisis'))) ?>;
    const serieJour = <?= json_encode($serieJour) ?>;
    const jours = Array.from({length: <?= (int) $nbJours ?>}, (_, i) => i + 1);

    Chart.defaults.font.family = 'ui-sans-serif, system-ui, sans-serif';
    Chart.defaults.maintainAspectRatio = false;

    // 1. KPI par section
    new Chart(document.getElementById('chartSqcdp'), {
        type: 'bar',
        data: { labels: lettres, datasets: [{ label: 'KPI', data: totaux, backgroundColor: couleurs, borderRadius: 6 }] },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });

    // 2. Saisies par jour
    new Chart(document.getElementById('chartJour'), {
        type: 'line',
        data: { labels: jours, datasets: [{ label: 'Saisies', data: serieJour, borderColor: '#7c3aed',
                backgroundColor: 'rgba(124,58,237,.12)', fill: true, tension: .3, pointRadius: 2 }] },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });

    // 3. Avancement : saisis vs total par section
    new Chart(document.getElementById('chartAvancement'), {
        type: 'bar',
        data: { labels: lettres, datasets: [
            { label: 'Saisis',  data: saisis, backgroundColor: '#22c55e', borderRadius: 4 },
            { label: 'Total',   data: totaux, backgroundColor: '#e5e7eb', borderRadius: 4 }
        ] },
        options: { scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });
</script>

<?= $this->endSection() ?>