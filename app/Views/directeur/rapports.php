<?php
// app/Views/directeur/rapports.php
// Variables : $kpis (avec 'statut'), $deps, $totaux

$fmt = static fn ($v) => $v === null ? '—' : rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');

$statutUi = [
    'atteint' => ['Atteint',      'bg-emerald-100 text-emerald-700'],
    'non'     => ['Non atteint',  'bg-rose-100 text-rose-700'],
    'na'      => ['Sans données', 'bg-slate-100 text-slate-500'],
];
$badge = [
    'S' => 'bg-red-100 text-red-700', 'Q' => 'bg-blue-100 text-blue-700', 'C' => 'bg-amber-100 text-amber-700',
    'D' => 'bg-emerald-100 text-emerald-700', 'P' => 'bg-violet-100 text-violet-700',
];
?>
<?= $this->extend('layouts/app_layout') ?>

<?= $this->section('content') ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-800">Rapports</h1>
    <p class="text-sm text-slate-500">Dernière valeur saisie de chaque KPI comparée à son objectif.</p>
</div>

<!-- Synthèse globale -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <?php foreach ([
        ['Total KPI',     $totaux['total'],   'text-slate-800'],
        ['Atteints',      $totaux['atteint'], 'text-emerald-600'],
        ['Non atteints',  $totaux['non'],     'text-rose-600'],
        ['Sans données',  $totaux['na'],      'text-slate-500'],
    ] as [$label, $n, $couleur]): ?>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500"><?= $label ?></p>
            <p class="mt-2 text-3xl font-bold <?= $couleur ?>"><?= (int) $n ?></p>
        </div>
    <?php endforeach; ?>
</div>

<!-- Par département -->
<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden mb-6">
    <div class="px-5 py-4 border-b border-slate-100"><h3 class="font-semibold text-slate-700">Synthèse par département</h3></div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                <tr>
                    <th class="text-left px-5 py-3">Département</th>
                    <th class="text-center px-5 py-3">KPI</th>
                    <th class="text-center px-5 py-3">Atteints</th>
                    <th class="text-center px-5 py-3">Non atteints</th>
                    <th class="text-center px-5 py-3">Sans données</th>
                    <th class="text-left px-5 py-3 min-w-[180px]">Taux d'atteinte</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($deps)): ?>
                    <tr><td colspan="6" class="px-5 py-8 text-center text-slate-400">Aucun KPI enregistré.</td></tr>
                <?php endif; ?>
                <?php foreach ($deps as $nom => $d):
                    $mesures = $d['atteint'] + $d['non'];
                    $taux    = $mesures > 0 ? (int) round($d['atteint'] / $mesures * 100) : null; ?>
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3 font-medium text-slate-800"><?= esc($nom) ?></td>
                        <td class="px-5 py-3 text-center"><?= $d['total'] ?></td>
                        <td class="px-5 py-3 text-center text-emerald-600 font-semibold"><?= $d['atteint'] ?></td>
                        <td class="px-5 py-3 text-center text-rose-600 font-semibold"><?= $d['non'] ?></td>
                        <td class="px-5 py-3 text-center text-slate-500"><?= $d['na'] ?></td>
                        <td class="px-5 py-3">
                            <?php if ($taux === null): ?>
                                <span class="text-slate-400">—</span>
                            <?php else: ?>
                                <div class="flex items-center gap-3">
                                    <div class="flex-1 h-2 rounded-full bg-slate-200 overflow-hidden">
                                        <div class="h-full rounded-full <?= $taux >= 80 ? 'bg-emerald-500' : ($taux >= 50 ? 'bg-amber-500' : 'bg-rose-500') ?>"
                                             style="width: <?= $taux ?>%"></div>
                                    </div>
                                    <span class="w-10 text-right font-semibold"><?= $taux ?>%</span>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Détail par KPI -->
<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
        <h3 class="font-semibold text-slate-700">Détail par KPI</h3>
        <span class="text-xs text-slate-500"><?= count($kpis) ?> KPI</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                <tr>
                    <th class="text-left px-5 py-3">Département</th>
                    <th class="text-left px-5 py-3">KPI</th>
                    <th class="text-center px-5 py-3">SQCDP</th>
                    <th class="text-left px-5 py-3">Fréquence</th>
                    <th class="text-right px-5 py-3">Objectif</th>
                    <th class="text-right px-5 py-3">Dernière valeur</th>
                    <th class="text-left px-5 py-3 pl-8">Date</th>
                    <th class="text-center px-5 py-3">Statut</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($kpis as $k): [$libelle, $classe] = $statutUi[$k['statut']]; ?>
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3 text-slate-600"><?= esc($k['departement']) ?></td>
                        <td class="px-5 py-3 font-medium text-slate-800"><?= esc($k['nom']) ?></td>
                        <td class="px-5 py-3 text-center">
                            <span class="inline-block w-7 h-7 leading-7 rounded-full text-xs font-bold <?= $badge[$k['sqcdp']] ?? 'bg-slate-100 text-slate-600' ?>"><?= esc($k['sqcdp']) ?></span>
                        </td>
                        <td class="px-5 py-3 text-slate-600"><?= esc($k['frequence']) ?></td>
                        <td class="px-5 py-3 text-right"><?= $fmt($k['objectif']) ?></td>
                        <td class="px-5 py-3 text-right font-semibold"><?= $fmt($k['derniere_valeur']) ?> <span class="text-xs font-normal text-slate-400"><?= esc($k['unite'] ?? '') ?></span></td>
                        <td class="px-5 py-3 pl-8 text-slate-500"><?= $k['derniere'] ? esc(date('d/m/Y', strtotime($k['derniere']))) : '—' ?></td>
                        <td class="px-5 py-3 text-center"><span class="px-2.5 py-1 rounded-full text-xs font-medium <?= $classe ?>"><?= $libelle ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>