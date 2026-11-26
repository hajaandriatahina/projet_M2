<?php
// app/Views/user/mes_kpi.php
// Layout : app/Views/layouts/user_layout.php

$base = site_url('utilisateur/mes-kpi');
$url  = static fn (array $p) => $base . '?' . http_build_query(array_merge(['freq' => $freq, 'type' => $type, 'ref' => $ref], $p));

$tabsDef = ['journalier' => 'Quotidien', 'hebdomadaire' => 'Hebdomadaire', 'mensuel' => 'Mensuel'];
$fmt     = static fn ($v) => strpos((string) $v, '.') !== false ? rtrim(rtrim((string) $v, '0'), '.') : (string) $v;
$estVrai = static fn ($v) => in_array($v, [true, 't', 'true', '1', 1], true);

// Couleur d'une cellule du tableau de suivi (comme l'interface directeur) : vert = objectif atteint, rouge = non atteint
// Ajoutez n.sens dans le select de kpisParFrequence() si votre table nom_kpi a une colonne « sens » (min / max)
$etat = static function (array $k, $v, $obj): string {
    if ($v === null || $obj === null) return '';
    $obj = (float) $obj;
    $bon = (($k['sens'] ?? 'max') === 'min') ? $v <= $obj : $v >= $obj;
    return $bon ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
};

// Largeur des colonnes selon la fréquence
$largeurCol = ['journalier' => 76, 'hebdomadaire' => 112, 'mensuel' => 120][$freq];

$nbEditable = 0;
$cols = $periode['cols'];
?>
<?= $this->extend('layouts/user_layout') ?>

<?= $this->section('content') ?>

<div class="mb-4">
    <h1 class="text-2xl font-bold text-slate-800">Mes KPI, Objectif</h1>
    <p class="text-sm text-slate-500">Saisissez vos données puis cliquez sur « Enregistrer ». Une fois enregistrées, elles sont verrouillées.</p>
</div>

<!-- ============ Onglets fréquence + type (valeur / objectif) ============ -->
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <div class="flex items-center gap-1 bg-slate-200/70 rounded-xl p-1">
        <?php foreach ($tabsDef as $key => $label): $actif = $freq === $key; ?>
            <a href="<?= $url(['freq' => $key, 'ref' => date('Y-m-d')]) ?>"
               class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition
                      <?= $actif ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700' ?>">
                <?= $label ?>
                <span class="text-xs rounded-full px-2 py-0.5 <?= $actif ? 'bg-indigo-500 text-white' : 'bg-slate-300/70 text-slate-600' ?>">
                    <?= str_pad((string) $tabs[$key], 2, '0', STR_PAD_LEFT) ?>
                </span>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="flex items-center gap-1 bg-slate-200/70 rounded-xl p-1">
        <a href="<?= $url(['type' => 'valeur']) ?>"
           class="px-4 py-2 rounded-lg text-sm font-medium <?= $type === 'valeur' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700' ?>">KPI</a>
        <a href="<?= $url(['type' => 'objectif']) ?>"
           class="px-4 py-2 rounded-lg text-sm font-medium <?= $type === 'objectif' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700' ?>">Objectif</a>
    </div>
</div>

<!-- ============ Navigation de période ============ -->
<div class="flex items-center gap-3 mb-3">
    <a href="<?= $url(['ref' => $periode['prev']]) ?>" class="w-9 h-9 flex items-center justify-center rounded-lg bg-white border border-slate-200 hover:bg-slate-50" aria-label="Période précédente">
        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
    </a>
    <span class="text-sm font-semibold text-slate-700 min-w-[200px] text-center"><?= esc($periode['titre']) ?></span>
    <a href="<?= $url(['ref' => $periode['next']]) ?>" class="w-9 h-9 flex items-center justify-center rounded-lg bg-white border border-slate-200 hover:bg-slate-50" aria-label="Période suivante">
        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
    </a>
    <a href="<?= $url(['ref' => date('Y-m-d')]) ?>" class="ml-2 text-sm text-indigo-600 hover:underline">Aujourd'hui</a>
    <span class="ml-auto text-xs font-medium px-3 py-1 rounded-full <?= $type === 'valeur' ? 'bg-violet-100 text-violet-700' : 'bg-amber-100 text-amber-700' ?>">
        <?= $type === 'valeur' ? 'Saisie des valeurs' : 'Saisie des objectifs' ?>
    </span>
</div>

<!-- ============ Grille de saisie (input) ============ -->
<form id="formSaisie" method="post" action="<?= site_url('utilisateur/mes-kpi/enregistrer') ?>" autocomplete="off">
    <?= csrf_field() ?>
    <input type="hidden" name="freq" value="<?= esc($freq) ?>">
    <input type="hidden" name="type" value="<?= esc($type) ?>">
    <input type="hidden" name="ref"  value="<?= esc($ref) ?>">

    <?php if (empty($kpis)): ?>
        <div class="bg-white rounded-xl border border-slate-200 p-10 text-center text-slate-400">
            Aucun KPI <?= esc(strtolower($tabsDef[$freq])) ?> ne vous est attribué.
        </div>
    <?php else: ?>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-x-auto">
            <table class="text-sm" style="border-collapse: separate; border-spacing: 0; min-width: 100%;">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 text-xs uppercase">
                        <th class="text-left px-4 py-3 border-b border-slate-200"
                            style="position:sticky; left:0; z-index:3; background:#f8fafc; min-width:140px; width:140px;">Département</th>
                        <th class="text-left px-4 py-3 border-b border-r border-slate-200"
                            style="position:sticky; left:140px; z-index:3; background:#f8fafc; min-width:230px; width:230px;">Nom du KPI</th>
                        <th class="text-center px-3 py-3 border-b border-slate-200" style="min-width:90px;">Objectif</th>

                        <?php foreach ($cols as $c):
                            $weekend = $c['weekend'] ?? false;
                            $cls = $c['today'] ? 'bg-slate-600 text-white' : ($weekend ? 'bg-slate-200 text-slate-600' : 'bg-slate-50 text-slate-600');
                        ?>
                            <th class="px-2 py-2 text-center font-semibold border-b border-l border-slate-200 <?= $cls ?>"
                                style="min-width:<?= $largeurCol ?>px;">
                                <?php if ($freq === 'hebdomadaire'): ?>
                                    <!-- Icône sur chaque colonne de semaine -->
                                    <svg class="w-4 h-4 mx-auto mb-1 <?= $c['today'] ? 'text-white' : 'text-indigo-500' ?>" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                        <rect x="3" y="5" width="18" height="16" rx="2"/><path stroke-linecap="round" d="M3 10h18M8 3v4M16 3v4"/>
                                    </svg>
                                <?php endif; ?>
                                <div class="text-sm"><?= esc($c['label']) ?></div>
                                <?php if ($c['sub'] !== ''): ?>
                                    <div class="text-[11px] font-normal normal-case opacity-80"><?= esc($c['sub']) ?></div>
                                <?php endif; ?>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($kpis as $idCfg => $k): ?>
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-4 py-3 text-slate-600 border-b border-slate-100"
                                style="position:sticky; left:0; z-index:2; background:#fff;">
                                <?= esc($k['departement'] ?? '—') ?>
                            </td>
                            <td class="px-4 py-3 border-b border-r border-slate-100"
                                style="position:sticky; left:140px; z-index:2; background:#fff;">
                                <p class="font-semibold text-slate-800 leading-tight"><?= esc($k['nom']) ?></p>
                                <?php if (! empty($k['unite'])): ?>
                                    <p class="text-xs text-slate-400"><?= esc($k['unite']) ?></p>
                                <?php endif; ?>
                            </td>
                            <td class="px-3 py-3 text-center font-semibold text-amber-700 border-b border-slate-100">
                                <?= isset($objectifs[$idCfg]) ? esc($fmt($objectifs[$idCfg])) : '—' ?>
                            </td>

                            <?php foreach ($cols as $c):
                                $saved  = $grid[$idCfg][$c['date']] ?? null;
                                $locked = $saved && $estVrai($saved['verrouille']);
                                $bloqueFutur = ($type === 'valeur' && $c['futur']);
                            ?>
                                <td class="px-1.5 py-2 border-b border-l border-slate-100 <?= $c['today'] ? 'bg-indigo-50/60' : '' ?>">
                                    <?php if ($locked): ?>
                                        <input type="text" value="<?= esc($fmt($saved['valeur'])) ?>" disabled
                                               title="Donnée verrouillée"
                                               class="w-full h-10 rounded-lg border border-slate-200 bg-slate-100 text-slate-500 text-center font-medium cursor-not-allowed">
                                    <?php elseif ($bloqueFutur): ?>
                                        <input type="text" disabled placeholder="—" title="Période à venir"
                                               class="w-full h-10 rounded-lg border border-slate-100 bg-slate-50 text-center cursor-not-allowed">
                                    <?php else: $nbEditable++; ?>
                                        <input type="text" inputmode="decimal" data-saisie
                                               name="cells[<?= (int) $idCfg ?>][<?= esc($c['date']) ?>]"
                                               value="<?= $saved ? esc($fmt($saved['valeur'])) : '' ?>"
                                               class="w-full h-10 rounded-lg border border-cyan-400 bg-white text-center font-medium
                                                      focus:outline-none focus:ring-2 focus:ring-cyan-300">
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="flex flex-col items-end gap-2 mt-3">
            <p id="msgSaisie" class="hidden text-sm text-rose-600"></p>
            <?php if ($nbEditable === 0): ?>
                <p class="text-sm text-slate-500">Toutes les données de cette période sont verrouillées.</p>
            <?php endif; ?>
            <button type="button" id="btnSave" <?= $nbEditable === 0 ? 'disabled' : '' ?>
                    class="bg-indigo-500 hover:bg-indigo-600 disabled:bg-slate-300 disabled:cursor-not-allowed
                           text-white font-semibold rounded-xl px-8 py-3 transition">
                Enregistrer
            </button>
        </div>
    <?php endif; ?>
</form>

<!-- ============ Tableau « Données » sous le bouton Enregistrer (même forme que l'interface directeur) ============ -->
<div class="mt-8 bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
        <h3 class="font-semibold text-slate-700">Données</h3>
        <span class="text-xs text-slate-500"><?= count($kpis) ?> KPI — <?= esc($tabsDef[$freq]) ?></span>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                <tr>
                    <th class="p-3 text-left">Département</th>
                    <th class="p-3 text-left">Nom du KPI</th>
                    <th class="p-3 text-center">Objectif</th>
                    <?php foreach ($cols as $c): ?>
                        <th class="p-3 text-center">
                            <?php if ($freq === 'hebdomadaire'): ?>
                                <svg class="w-4 h-4 mx-auto mb-1 text-indigo-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <rect x="3" y="5" width="18" height="16" rx="2"/><path stroke-linecap="round" d="M3 10h18M8 3v4M16 3v4"/>
                                </svg>
                            <?php endif; ?>
                            <?= esc($c['label']) ?>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($kpis)): ?>
                    <tr><td class="p-8 text-center text-slate-400" colspan="<?= count($cols) + 3 ?>">Aucun KPI ne vous est attribué pour cette fréquence</td></tr>
                <?php endif; ?>
                <?php foreach ($kpis as $idCfg => $k):
                    $obj = $objectifs[$idCfg] ?? null; ?>
                    <tr class="hover:bg-slate-50">
                        <td class="p-3 text-slate-600"><?= esc($k['departement'] ?? '') ?></td>
                        <td class="p-3 font-medium text-slate-800"><?= esc($k['nom']) ?></td>
                        <td class="p-3 text-center font-semibold"><?= $obj !== null ? esc($fmt($obj)) : '—' ?></td>
                        <?php foreach ($cols as $c):
                            $v = $valeurs[$idCfg][$c['date']] ?? null; ?>
                            <td class="p-3 text-center <?= $etat($k, $v, $obj) ?>"><?= $v !== null ? esc($fmt($v)) : '-' ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ============ Fenêtre de confirmation ============ -->
<div id="modalConfirm" class="hidden fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6">
        <h4 class="text-lg font-bold text-slate-800 mb-2">Confirmer l'enregistrement</h4>
        <p class="text-sm text-slate-600 mb-5">
            Vous allez enregistrer <strong id="nbValeurs">0</strong> donnée(s).
            Une fois enregistrées, elles seront <strong>verrouillées</strong> : vous ne pourrez plus les modifier ni les supprimer
            (seul l'administrateur peut les débloquer).
        </p>
        <div class="flex justify-end gap-3">
            <button type="button" id="btnAnnuler" class="px-5 py-2 rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-50">Annuler</button>
            <button type="button" id="btnConfirmer" class="px-5 py-2 rounded-lg bg-indigo-500 hover:bg-indigo-600 text-white font-semibold">Confirmer</button>
        </div>
    </div>
</div>

<script>
(function () {
    const form   = document.getElementById('formSaisie');
    const btn    = document.getElementById('btnSave');
    if (!form || !btn) return;

    const msg    = document.getElementById('msgSaisie');
    const modal  = document.getElementById('modalConfirm');
    const inputs = form.querySelectorAll('input[data-saisie]');
    let confirme = false;

    // Entrée dans un champ ne soumet pas le formulaire : il faut passer par la confirmation
    inputs.forEach(i => i.addEventListener('keydown', e => { if (e.key === 'Enter') e.preventDefault(); }));
    form.addEventListener('submit', e => { if (!confirme) e.preventDefault(); });

    btn.addEventListener('click', () => {
        let rempli = 0, erreur = false;
        msg.classList.add('hidden');

        inputs.forEach(i => {
            i.classList.remove('border-rose-500'); i.classList.add('border-cyan-400');
            const v = i.value.trim();
            if (v === '') return;
            if (!/^-?\d+([.,]\d+)?$/.test(v)) {
                erreur = true;
                i.classList.remove('border-cyan-400'); i.classList.add('border-rose-500');
                return;
            }
            rempli++;
        });

        if (erreur) { msg.textContent = 'Certaines valeurs sont invalides : saisissez uniquement des nombres.'; msg.classList.remove('hidden'); return; }
        if (rempli === 0) { msg.textContent = 'Saisissez au moins une valeur avant d\'enregistrer.'; msg.classList.remove('hidden'); return; }

        document.getElementById('nbValeurs').textContent = rempli;
        modal.classList.remove('hidden');
    });

    document.getElementById('btnAnnuler').addEventListener('click', () => modal.classList.add('hidden'));
    document.getElementById('btnConfirmer').addEventListener('click', () => {
        confirme = true;
        document.getElementById('btnConfirmer').disabled = true;
        btn.disabled = true;
        form.submit();
    });
})();
</script>

<?= $this->endSection() ?>