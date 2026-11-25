<?php
// app/Views/user/dashboard.php
// Layout : app/Views/layouts/user_layout.php

$sections = [
    'S' => ['nom' => 'Sécurité',  'couleur' => '#ef4444'],
    'Q' => ['nom' => 'Qualité',   'couleur' => '#3b82f6'],
    'C' => ['nom' => 'Coût',      'couleur' => '#f59e0b'],
    'D' => ['nom' => 'Délai',     'couleur' => '#10b981'],
    'P' => ['nom' => 'Personnel', 'couleur' => '#8b5cf6'],
];
$freqLabels = ['journalier' => 'Journalier', 'hebdomadaire' => 'Hebdo', 'mensuel' => 'Mensuel'];
?>
<?= $this->extend('layouts/user_layout') ?>

<?= $this->section('content') ?>

<!-- ============ Titre ============ -->
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-800">Tableau de bord</h1>
    <p class="text-sm text-slate-500">Évolution de vos KPI attribués — <?= esc($moisLabel) ?></p>
</div>

<!-- ============ Filtres en cascade ============ -->
<form id="formFiltres" method="get" action="<?= site_url('user/dashboard') ?>"
      class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 mb-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">

    <div>
        <label class="block text-xs font-medium text-slate-500 mb-1">Département</label>
        <select id="fDep" name="departement" class="w-full rounded-lg border-slate-300 border px-3 py-2 text-sm focus:ring-2 focus:ring-violet-400 outline-none">
            <option value="">Tous</option>
            <?php foreach ($departements as $d): ?>
                <option value="<?= esc($d) ?>" <?= $filtres['departement'] === $d ? 'selected' : '' ?>><?= esc($d) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div>
        <label class="block text-xs font-medium text-slate-500 mb-1">SQCDP</label>
        <select id="fSq" name="sqcdp" class="w-full rounded-lg border-slate-300 border px-3 py-2 text-sm focus:ring-2 focus:ring-violet-400 outline-none">
            <option value="">Tous</option>
            <?php foreach ($sections as $l => $s): ?>
                <option value="<?= $l ?>" <?= $filtres['sqcdp'] === $l ? 'selected' : '' ?>><?= $l ?> — <?= $s['nom'] ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div>
        <label class="block text-xs font-medium text-slate-500 mb-1">Fréquence</label>
        <select id="fFreq" name="frequence" class="w-full rounded-lg border-slate-300 border px-3 py-2 text-sm focus:ring-2 focus:ring-violet-400 outline-none">
            <option value="">Toutes</option>
            <?php foreach ($freqLabels as $key => $label): ?>
                <option value="<?= $key ?>" <?= $filtres['frequence'] === $key ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div>
        <label class="block text-xs font-medium text-slate-500 mb-1">Nom du KPI</label>
        <select id="fKpi" name="kpi" class="w-full rounded-lg border-slate-300 border px-3 py-2 text-sm focus:ring-2 focus:ring-violet-400 outline-none"></select>
    </div>

    <div>
        <label class="block text-xs font-medium text-slate-500 mb-1">Mois (jours / 3 mois de semaines)</label>
        <input type="month" id="fMois" name="mois" value="<?= esc($filtres['mois']) ?>"
               class="w-full rounded-lg border-slate-300 border px-3 py-2 text-sm focus:ring-2 focus:ring-violet-400 outline-none">
    </div>
</form>

<!-- ============ Cartes S Q C D P ============ -->
<div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
    <?php foreach ($sections as $l => $s):
        $c   = $cartes[$l];
        $pct = $c['total'] > 0 ? round($c['saisis'] / $c['total'] * 100) : 0;
    ?>
        <button type="button" data-carte="<?= $l ?>"
                class="carte bg-white rounded-xl shadow-sm border border-slate-200 p-5 text-center transition hover:shadow-md">
            <p class="text-sm font-semibold tracking-wide uppercase text-slate-500"><?= $s['nom'] ?></p>

            <div class="mx-auto my-4 w-28 h-28 rounded-full flex items-center justify-center text-6xl font-extrabold"
                 style="color: <?= $s['couleur'] ?>; background: conic-gradient(<?= $s['couleur'] ?> <?= $pct ?>%, #e5e7eb 0);">
                <span class="w-[84px] h-[84px] rounded-full bg-white flex items-center justify-center"><?= $l ?></span>
            </div>

            <p class="text-2xl font-bold text-slate-800"><?= $c['total'] ?></p>
            <p class="text-xs text-slate-500">Total KPI « <?= $l ?> »</p>
            <p class="mt-2 text-xs text-slate-400"><?= $c['saisis'] ?>/<?= $c['total'] ?> saisis (<?= $pct ?>%)</p>
        </button>
    <?php endforeach; ?>
</div>

<!-- ============ Graphiques : évolution du KPI sélectionné ============ -->
<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
    <div id="enteteKpi" class="flex flex-wrap items-center justify-between gap-2 mb-4">
        <div>
            <h3 id="gTitre" class="text-lg font-bold text-slate-800">—</h3>
            <p id="gSous" class="text-xs text-slate-500"></p>
        </div>
        <span id="gBadge" class="text-xs font-medium px-3 py-1 rounded-full bg-violet-100 text-violet-700"></span>
    </div>

    <p id="gVide" class="hidden py-16 text-center text-slate-400">Aucun KPI ne correspond à ces filtres.</p>

    <div id="gZone" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div>
            <h4 class="text-sm font-semibold text-slate-700 text-center mb-3">Évolution — Valeur vs Objectif</h4>
            <div class="h-72"><canvas id="chartEvolution"></canvas></div>
        </div>
        <div>
            <h4 class="text-sm font-semibold text-slate-700 text-center mb-3">Évolution de l'écart (Valeur − Objectif)</h4>
            <div class="h-72"><canvas id="chartEcart"></canvas></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    const KPIS    = <?= json_encode($kpisJs, JSON_UNESCAPED_UNICODE) ?>;
    const COULEURS = <?= json_encode(array_map(static fn ($s) => $s['couleur'], $sections)) ?>;
    const FREQ_LBL = <?= json_encode($freqLabels, JSON_UNESCAPED_UNICODE) ?>;
    const KPI_INIT = <?= json_encode($filtres['kpi']) ?>;

    const fDep = document.getElementById('fDep'), fSq = document.getElementById('fSq'),
          fFreq = document.getElementById('fFreq'), fKpi = document.getElementById('fKpi'),
          fMois = document.getElementById('fMois'), form = document.getElementById('formFiltres');

    Chart.defaults.font.family = 'ui-sans-serif, system-ui, sans-serif';
    Chart.defaults.maintainAspectRatio = false;

    // ---------- Graphiques ----------
    const chartEvo = new Chart(document.getElementById('chartEvolution'), {
        type: 'line',
        data: { labels: [], datasets: [
            { label: 'Valeur',   data: [], borderColor: '#7c3aed', backgroundColor: 'rgba(124,58,237,.12)',
              fill: true, tension: .3, pointRadius: 3, spanGaps: true },
            { label: 'Objectif', data: [], borderColor: '#f59e0b', borderDash: [6, 4],
              fill: false, tension: 0, pointRadius: 0, spanGaps: true }
        ] },
        options: { interaction: { mode: 'index', intersect: false },
                   scales: { y: { beginAtZero: true }, x: { grid: { display: false } } } }
    });

    const chartEcart = new Chart(document.getElementById('chartEcart'), {
        type: 'bar',
        data: { labels: [], datasets: [{ label: 'Écart', data: [], borderRadius: 4, backgroundColor: [] }] },
        options: { plugins: { legend: { display: false } },
                   scales: { x: { grid: { display: false } } } }
    });

    // ---------- Filtres en cascade ----------
    function filtres() {
        return KPIS.filter(k =>
            (!fDep.value  || k.departement === fDep.value) &&
            (!fSq.value   || k.sqcdp === fSq.value) &&
            (!fFreq.value || k.freq === fFreq.value));
    }

    function remplirKpi(garder) {
        const liste = filtres();
        fKpi.innerHTML = '';
        liste.forEach(k => {
            const o = document.createElement('option');
            o.value = k.id; o.textContent = k.nom;
            fKpi.appendChild(o);
        });
        if (garder && liste.some(k => String(k.id) === String(garder))) fKpi.value = garder;
        majCartes();
        dessiner();
    }

    function majCartes() {
        document.querySelectorAll('.carte').forEach(c => {
            const actif = fSq.value === c.dataset.carte;
            c.classList.toggle('ring-2', actif);
            c.classList.toggle('ring-violet-400', actif);
        });
    }

    function dessiner() {
        const k = KPIS.find(x => String(x.id) === String(fKpi.value));
        const vide = !k;
        document.getElementById('gVide').classList.toggle('hidden', !vide);
        document.getElementById('gZone').classList.toggle('hidden', vide);
        document.getElementById('enteteKpi').classList.toggle('hidden', vide);
        if (vide) return;

        const unite = k.unite ? ' (' + k.unite + ')' : '';
        document.getElementById('gTitre').textContent = k.nom + unite;
        document.getElementById('gSous').textContent  = k.departement + ' · ' + k.sqcdp + ' · ' + k.titrePeriode;
        document.getElementById('gBadge').textContent = FREQ_LBL[k.freq] + ' — ' + k.nb + ' saisie(s)';

        chartEvo.data.labels = k.labels;
        chartEvo.data.datasets[0].data = k.valeurs;
        chartEvo.data.datasets[1].data = k.objectifs;
        chartEvo.update();

        const ecart = k.valeurs.map((v, i) => (v !== null && k.objectifs[i] !== null) ? +(v - k.objectifs[i]).toFixed(2) : null);
        chartEcart.data.labels = k.labels;
        chartEcart.data.datasets[0].data = ecart;
        chartEcart.data.datasets[0].backgroundColor = ecart.map(e => e !== null && e < 0 ? '#fb7185' : '#6366f1');
        chartEcart.update();
    }

    // Cascade : chaque filtre met à jour la liste des KPI puis les graphiques, sans recharger la page
    [fDep, fSq, fFreq].forEach(f => f.addEventListener('change', () => remplirKpi(fKpi.value)));
    fKpi.addEventListener('change', dessiner);

    // Cartes S/Q/C/D/P : clic = filtre SQCDP (re-clic = tous)
    document.querySelectorAll('.carte').forEach(c => c.addEventListener('click', () => {
        fSq.value = fSq.value === c.dataset.carte ? '' : c.dataset.carte;
        remplirKpi(fKpi.value);
    }));

    // Le mois change les périodes affichées : rechargement (les filtres sont conservés dans l'URL)
    fMois.addEventListener('change', () => { if (fMois.value) form.submit(); });

    remplirKpi(KPI_INIT);
})();
</script>

<?= $this->endSection() ?>