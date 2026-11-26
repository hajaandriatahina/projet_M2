<?php
// app/Views/user/rapport.php
// Layout : app/Views/layouts/user_layout.php
// Variables : $kpisJs, $plans, $filtres (freq, kpi, mois), $moisLabel

$flags = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
$iconCal = '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path stroke-linecap="round" d="M3 10h18M8 3v4M16 3v4"/></svg>';
?>
<?= $this->extend('layouts/user_layout') ?>

<?= $this->section('content') ?>

<style>
    /* Export PDF : on imprime seulement le rapport (Enregistrer au format PDF dans la fenêtre d'impression) */
    @page { size: A4 landscape; margin: 10mm; }
    @media print {
        aside#sidebar, header, #overlay, .no-print { display: none !important; }
        .lg\:ml-64 { margin-left: 0 !important; }
        body { background: #fff !important; }
        .scroll-print { max-height: none !important; overflow: visible !important; }
        #chartInner { min-width: 0 !important; }
        .carte-rapport { break-inside: avoid; }
    }
</style>

<div class="mb-4">
    <h1 class="text-2xl font-bold text-slate-800">Rapport</h1>
    <p class="text-sm text-slate-500 no-print">Évolution du KPI, observations et plans d'action du mois — réunion mensuelle avec le directeur.</p>
    <p class="hidden print:block text-sm text-slate-600">Rapport — <?= esc($moisLabel) ?></p>
</div>

<form id="formRapport" method="get" action="<?= site_url('utilisateur/rapport') ?>">
    <input type="hidden" name="freq" id="fFreq" value="<?= esc($filtres['freq']) ?>">

    <!-- ============ Onglets fréquence ============ -->
    <div id="tabs" class="no-print flex flex-wrap items-center gap-2 mb-4"></div>

    <!-- ============ Filtres : Indicateur + Mois + Export ============ -->
    <div class="no-print bg-white rounded-xl shadow-sm border border-slate-200 p-3 sm:p-4 mb-4
                flex flex-wrap items-center gap-x-6 gap-y-3">
        <div class="flex items-center gap-3 min-w-0">
            <label for="fKpi" class="text-sm font-bold text-slate-700">Indicateur :</label>
            <select id="fKpi" name="kpi" class="min-w-[220px] max-w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-300 outline-none"></select>
        </div>
        <div class="flex items-center gap-3">
            <label for="fMois" class="text-sm font-bold text-slate-700">Mois :</label>
            <input type="month" id="fMois" name="mois" value="<?= esc($filtres['mois']) ?>"
                   class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-300 outline-none">
        </div>
        <button type="button" id="btnPdf"
                class="ml-auto inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg px-4 py-2.5 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v11m0 0l-4-4m4 4l4-4M5 20h14"/></svg>
            Exporter en PDF
        </button>
    </div>
</form>

<!-- ============ Graphique (celui du dashboard) ============ -->
<div class="carte-rapport bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden mb-5">
    <div class="flex flex-wrap items-center justify-between gap-2 bg-[#0b3a6e] text-white px-5 py-3">
        <h2 class="flex items-center gap-2 text-lg font-bold">
            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M4 20V10h4v10H4zm6 0V4h4v16h-4zm6 0v-7h4v7h-4z"/></svg>
            <span id="cTitre">—</span>
        </h2>
        <span id="cSous" class="text-xs opacity-80"></span>
    </div>

    <p id="cVide" class="hidden py-16 text-center text-slate-400">Aucun KPI pour cette fréquence.</p>

    <div id="cZone" class="p-4">
        <!-- Défilement horizontal quand il y a beaucoup de points (ex. 31 jours) -->
        <div class="overflow-x-auto scroll-print">
            <div id="chartInner" class="relative" style="height:340px;">
                <canvas id="chart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- ============ Observations + Plan d'action ============ -->
<div class="grid grid-cols-1 lg:grid-cols-5 gap-5">

    <!-- Observations -->
    <div class="carte-rapport lg:col-span-2 bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="flex items-center gap-2 bg-[#0b3a6e] text-white px-5 py-3">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 18h6M10 21h4M12 3a6 6 0 00-3.5 10.9c.6.5 1 1.2 1 2V16h5v-.1c0-.8.4-1.5 1-2A6 6 0 0012 3z"/></svg>
            <h2 class="text-lg font-bold uppercase">Observations</h2>
        </div>
        <div class="p-5">
            <p class="text-lg font-bold text-slate-800">État actuel :</p>
            <div class="flex items-center gap-4 mt-1 pb-4 border-b border-slate-200">
                <div class="flex-1 min-w-0">
                    <p id="oVal" class="text-3xl font-extrabold text-[#0b3a6e] leading-tight">—</p>
                    <p id="oPer" class="text-sm text-slate-600"></p>
                </div>
                <div class="border-l border-slate-200 pl-4 text-center">
                    <p id="oVar" class="text-2xl font-extrabold text-slate-400 flex items-center justify-center gap-1">—</p>
                    <p class="text-xs text-slate-600">vs période précédente</p>
                </div>
            </div>

            <p class="text-lg font-bold text-slate-800 mt-4 mb-2">Commentaires :</p>
            <ul id="oListe" class="space-y-2 text-sm text-slate-700 max-h-48 overflow-y-auto scroll-print pr-1"></ul>
        </div>
    </div>

    <!-- Plan d'action -->
    <div class="carte-rapport lg:col-span-3 bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-2 bg-[#0b3a6e] text-white px-5 py-3">
            <h2 class="flex items-center gap-2 text-lg font-bold uppercase">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                Plan d'action
            </h2>
            <div class="flex items-center gap-3">
                <span class="text-xs opacity-80"><?= esc($moisLabel) ?></span>
                <div class="no-print inline-flex rounded-lg bg-white/15 p-0.5 text-xs font-semibold">
                    <button type="button" data-portee="kpi"  class="portee px-3 py-1 rounded-md">Ce KPI</button>
                    <button type="button" data-portee="tous" class="portee px-3 py-1 rounded-md">Tous les KPI</button>
                </div>
            </div>
        </div>

        <!-- Défilement vertical quand il y a plusieurs plans -->
        <div class="max-h-80 overflow-y-auto overflow-x-auto scroll-print">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-700 sticky top-0">
                    <tr>
                        <th class="p-3 w-12 text-center">ID</th>
                        <th id="pThKpi" class="p-3 text-left hidden">KPI</th>
                        <th class="p-3 text-left">ACTIONS</th>
                        <th class="p-3 text-left">Responsable</th>
                        <th class="p-3 text-left">Échéance</th>
                    </tr>
                </thead>
                <tbody id="pBody" class="divide-y divide-slate-100"></tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    const KPIS  = <?= json_encode($kpisJs, $flags) ?>;
    const PLANS = <?= json_encode($plans,  $flags) ?>;
    const INIT  = <?= json_encode($filtres, $flags) ?>;
    const ICON_CAL = <?= json_encode($iconCal) ?>;

    const FREQS  = [['hebdomadaire', 'Hebdomadaire'], ['journalier', 'Journalier'], ['mensuel', 'Mensuel']];
    const POINT_W = {journalier: 44, hebdomadaire: 58, mensuel: 70};   // largeur mini par point -> défilement horizontal

    const $   = id => document.getElementById(id);
    const esc = s  => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const nf  = v  => new Intl.NumberFormat('fr-FR', {maximumFractionDigits: 2}).format(v);
    const nf1 = v  => new Intl.NumberFormat('fr-FR', {maximumFractionDigits: 1}).format(v);
    const fmtDate = d => d ? d.split('-').reverse().join('/') : '—';
    const t = new Date();
    const today = `${t.getFullYear()}-${String(t.getMonth() + 1).padStart(2, '0')}-${String(t.getDate()).padStart(2, '0')}`;

    const st = {freq: INIT.freq, kpi: INIT.kpi, portee: 'kpi'};

    Chart.defaults.font.family = 'ui-sans-serif, system-ui, sans-serif';
    Chart.defaults.maintainAspectRatio = false;

    // ---------- Graphique : même graphique que le dashboard (Valeur vs Objectif) ----------
    const chart = new Chart($('chart'), {
        type: 'line',
        data: { labels: [], datasets: [
            { label: 'Valeur',   data: [], borderColor: '#7c3aed', backgroundColor: 'rgba(124,58,237,.12)',
              fill: true, tension: .3, pointRadius: 3, spanGaps: true },
            { label: 'Objectif', data: [], borderColor: '#f59e0b', borderDash: [6, 4],
              fill: false, tension: 0, pointRadius: 0, spanGaps: true }
        ] },
        options: {
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { position: 'top', align: 'end' } },
            scales: { y: { beginAtZero: true }, x: { grid: { display: false } } }
        }
    });
    window.addEventListener('beforeprint', () => chart.resize());
    window.addEventListener('afterprint',  () => chart.resize());

    // ---------- Onglets ----------
    function rendreOnglets() {
        $('tabs').innerHTML = FREQS.map(([key, label]) => {
            const on = st.freq === key;
            return `<button type="button" data-f="${key}"
                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold transition
                       ${on ? 'bg-blue-600 text-white shadow' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'}">
                ${ICON_CAL}${label}</button>`;
        }).join('');
    }

    // ---------- Liste des KPI de la fréquence choisie ----------
    function listeKpi() { return KPIS.filter(k => k.freq === st.freq); }

    function remplirKpi() {
        const liste = listeKpi();
        if (!liste.some(k => String(k.id) === String(st.kpi))) st.kpi = liste.length ? String(liste[0].id) : '';
        $('fKpi').innerHTML = liste.map(k => `<option value="${k.id}">${esc(k.nom)} (${esc(k.departement)})</option>`).join('')
                              || '<option value="">Aucun KPI</option>';
        $('fKpi').value = st.kpi;
        $('fFreq').value = st.freq;
    }

    function kpiCourant() { return KPIS.find(k => String(k.id) === String(st.kpi)); }

    // ---------- Graphique ----------
    function rendreGraphique() {
        const k = kpiCourant();
        $('cVide').classList.toggle('hidden', !!k);
        $('cZone').classList.toggle('hidden', !k);
        if (!k) { $('cTitre').textContent = '—'; $('cSous').textContent = ''; return; }

        $('cTitre').textContent = k.nom + (k.unite ? ' (' + k.unite + ')' : '');
        $('cSous').textContent  = k.departement + ' · ' + k.titrePeriode;

        $('chartInner').style.minWidth = (k.labels.length * POINT_W[k.freq]) + 'px';
        chart.data.labels = k.labels;
        chart.data.datasets[0].data = k.valeurs;
        chart.data.datasets[1].data = k.objectifs;
        chart.update();
        chart.resize();
    }

    // ---------- Plans du mois (selon la portée : ce KPI / tous) ----------
    function plansVisibles() {
        return st.portee === 'tous' ? PLANS : PLANS.filter(p => String(p.id_configuration) === String(st.kpi));
    }

    function rendrePlans() {
        document.querySelectorAll('.portee').forEach(b => {
            const on = b.dataset.portee === st.portee;
            b.classList.toggle('bg-white', on);
            b.classList.toggle('text-[#0b3a6e]', on);
            b.classList.toggle('text-white', !on);
        });
        const tous = st.portee === 'tous';
        $('pThKpi').classList.toggle('hidden', !tous);

        const rows = plansVisibles();
        $('pBody').innerHTML = rows.map((p, i) => `<tr class="hover:bg-slate-50 align-top">
            <td class="p-3 text-center text-slate-600">${i + 1}</td>
            ${tous ? `<td class="p-3 text-slate-700">${esc(p.nom_kpi)}</td>` : ''}
            <td class="p-3 font-medium text-slate-800">${esc(p.action)}</td>
            <td class="p-3 text-slate-700">${esc(p.responsable)}</td>
            <td class="p-3 whitespace-nowrap ${p.echeance < today ? 'text-rose-600 font-semibold' : 'text-slate-700'}">${fmtDate(p.echeance)}</td>
        </tr>`).join('') || `<tr><td colspan="${tous ? 5 : 4}" class="p-8 text-center text-slate-400">Aucun plan d'action pour ce mois</td></tr>`;
    }

    // ---------- Observations : état actuel, variation, commentaires ----------
    function rendreObservations() {
        const k = kpiCourant();
        const unite = k && k.unite ? ' ' + k.unite : '';
        let idx = -1, prev = -1;
        if (k) {
            for (let i = k.valeurs.length - 1; i >= 0; i--) {
                if (k.valeurs[i] !== null) { if (idx < 0) idx = i; else { prev = i; break; } }
            }
        }

        const items = [];   // HTML déjà échappé

        if (idx < 0) {
            $('oVal').textContent = '—';
            $('oPer').textContent = k ? 'Aucune saisie sur la période affichée' : '';
            $('oVar').className = 'text-2xl font-extrabold text-slate-400 flex items-center justify-center gap-1';
            $('oVar').textContent = '—';
        } else {
            const cur = k.valeurs[idx];
            $('oVal').textContent = nf(cur) + unite;
            $('oPer').textContent = '(' + k.longs[idx] + ')';

            if (prev >= 0 && k.valeurs[prev] !== 0) {
                const pct = (cur - k.valeurs[prev]) / Math.abs(k.valeurs[prev]) * 100;
                const haut = pct > 0, bas = pct < 0;
                const coul = haut ? 'text-emerald-600' : (bas ? 'text-rose-600' : 'text-slate-500');
                const fleche = haut ? 'M7 17L17 7M9 7h8v8' : (bas ? 'M7 7l10 10M17 9v8H9' : 'M5 12h14');
                $('oVar').className = 'text-2xl font-extrabold flex items-center justify-center gap-1 ' + coul;
                $('oVar').innerHTML = `<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="${fleche}"/></svg>${haut ? '+' : ''}${nf1(pct)} %`;
                items.push(esc(haut ? 'La valeur est en hausse par rapport à la période précédente.'
                                : bas ? 'La valeur est en baisse par rapport à la période précédente.'
                                      : 'La valeur est stable par rapport à la période précédente.'));
            } else {
                $('oVar').className = 'text-2xl font-extrabold text-slate-400 flex items-center justify-center gap-1';
                $('oVar').textContent = '—';
            }

            const obj = k.objectifs[idx];
            if (obj !== null && obj !== 0) items.push(esc(`L'objectif est atteint à ${nf1(cur / obj * 100)} %.`));
        }

        // Observations saisies dans les plans d'action du mois
        const tous = st.portee === 'tous';
        plansVisibles().filter(p => p.observation && p.observation.trim()).forEach(p =>
            items.push((tous ? `<strong>${esc(p.nom_kpi)} :</strong> ` : '') + esc(p.observation).replace(/\n/g, '<br>')));

        $('oListe').innerHTML = items.map(h => `<li class="flex gap-2"><span class="mt-2 w-2 h-2 rounded-full bg-blue-600 shrink-0"></span><span>${h}</span></li>`).join('')
            || '<li class="text-slate-400">Aucune observation pour ce mois.</li>';
    }

    function rendre() {
        rendreOnglets();
        remplirKpi();
        rendreGraphique();
        rendreObservations();
        rendrePlans();
    }

    // ---------- Évènements ----------
    $('tabs').addEventListener('click', e => {
        const b = e.target.closest('button[data-f]');
        if (b) { st.freq = b.dataset.f; rendre(); }
    });
    $('fKpi').onchange = e => { st.kpi = e.target.value; rendreGraphique(); rendreObservations(); rendrePlans(); };
    document.querySelectorAll('.portee').forEach(b => b.onclick = () => { st.portee = b.dataset.portee; rendreObservations(); rendrePlans(); });

    // Le mois change les périodes du graphique et les plans : rechargement (fréquence et KPI conservés)
    $('fMois').addEventListener('change', () => { if ($('fMois').value) $('formRapport').submit(); });

    // Exporter en PDF : fenêtre d'impression -> « Enregistrer au format PDF »
    $('btnPdf').onclick = () => window.print();

    rendre();
})();
</script>

<?= $this->endSection() ?>