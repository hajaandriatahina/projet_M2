<?php
// app/Views/directeur/dashboard.php
// Variable envoyée par Directeur::dashboard() : $kpis (voir le contrôleur)
$kpis = $kpis ?? [];
?>
<?= $this->extend('layouts/app_layout') ?>

<?= $this->section('content') ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-800">Tableau de bord</h1>
    <p class="text-sm text-slate-500">Vue d'ensemble de tous les indicateurs — filtres liés entre eux.</p>
</div>

<!-- ============ Cartes S Q C D P ============ -->
<div id="cards" class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6"></div>

<!-- ============ Fréquence (Journalier / Hebdo / Mensuel) ============ -->
<div id="tabs" class="inline-flex flex-wrap items-center gap-1 bg-slate-200/70 rounded-xl p-1 mb-4"></div>

<!-- ============ Filtres en cascade ============ -->
<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 mb-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
    <div>
        <label class="block text-xs font-medium text-slate-500 mb-1">Département</label>
        <select id="fDep" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-violet-400 outline-none"></select>
    </div>
    <div>
        <label class="block text-xs font-medium text-slate-500 mb-1">SQCDP</label>
        <select id="fLetter" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-violet-400 outline-none"></select>
    </div>
    <div>
        <label class="block text-xs font-medium text-slate-500 mb-1">Nom du KPI</label>
        <select id="fKpi" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-violet-400 outline-none"></select>
    </div>
    <div id="wrapMonth" class="hidden">
        <label class="block text-xs font-medium text-slate-500 mb-1">Mois</label>
        <select id="fMonth" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-violet-400 outline-none"></select>
    </div>
    <button id="fReset" type="button" class="px-4 py-2 text-sm rounded-lg border border-slate-300 hover:bg-slate-50 text-slate-600">Réinitialiser</button>
</div>

<!-- ============ Graphiques (un par KPI, avec son nom) ============ -->
<div id="charts" class="grid gap-4 mb-2"></div>
<p id="chartsNote" class="hidden text-sm text-slate-500 mb-4"></p>

<!-- ============ Tableau ============ -->
<div class="mt-4 bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
        <h3 class="font-semibold text-slate-700">Données</h3>
        <span id="info" class="text-xs text-slate-500"></span>
    </div>
    <div class="overflow-x-auto"><table class="min-w-full text-sm" id="tbl"></table></div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
const KPIS = <?= json_encode($kpis, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

const MOIS    = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
const FREQS   = ['Journalier','Hebdo','Mensuel'];
const FREQ_LB = {Journalier:'Journalier', Hebdo:'Hebdomadaire', Mensuel:'Mensuel'};
const LETTRES = {
  S:{nom:'Sécurité',  c:'#ef4444'}, Q:{nom:'Qualité',   c:'#3b82f6'}, C:{nom:'Coût', c:'#f59e0b'},
  D:{nom:'Délai',     c:'#10b981'}, P:{nom:'Personnel', c:'#8b5cf6'}
};
const MAX_GRAPHES = 12;

const today = new Date();
const $    = id => document.getElementById(id);
const pad  = n  => String(n).padStart(2, '0');
const ymd  = d  => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
const uniq = a  => [...new Set(a)];
const esc  = s  => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

/* ---------- État ---------- */
const premiereFreq = FREQS.includes('Mensuel') && KPIS.some(k => k.frequence === 'Mensuel')
  ? 'Mensuel' : (FREQS.find(f => KPIS.some(k => k.frequence === f)) || 'Mensuel');
const st = {freq: premiereFreq, dep: '', letter: '', kpi: '', month: today.getMonth()};
let charts = [];

/* ---------- Semaine ISO ---------- */
function isoWeek(d){
  const t = new Date(Date.UTC(d.getFullYear(), d.getMonth(), d.getDate()));
  t.setUTCDate(t.getUTCDate() + 4 - (t.getUTCDay() || 7));
  const y0 = new Date(Date.UTC(t.getUTCFullYear(), 0, 1));
  return Math.ceil(((t - y0) / 86400000 + 1) / 7);
}

/* ---------- Colonnes selon la fréquence ---------- */
function columns(){
  const y = today.getFullYear();
  if (st.freq === 'Mensuel') {                                   // Janvier -> Décembre
    return MOIS.map((m, i) => ({label: m, short: m.slice(0, 3), key: `${y}-${pad(i + 1)}-01`}));
  }
  if (st.freq === 'Hebdo') {                                     // semaines des 3 derniers mois
    const d = new Date(today.getFullYear(), today.getMonth() - 2, 1);
    d.setDate(d.getDate() - ((d.getDay() + 6) % 7));             // lundi de la semaine du 1er
    const out = [];
    for (; d <= today; d.setDate(d.getDate() + 7)) {
      out.push({label: 'S' + isoWeek(d), short: 'S' + isoWeek(d), key: ymd(d)});
    }
    return out;
  }
  const n = new Date(y, st.month + 1, 0).getDate();              // jours du mois choisi
  return Array.from({length: n}, (_, i) =>
    ({label: String(i + 1), short: String(i + 1), key: `${y}-${pad(st.month + 1)}-${pad(i + 1)}`}));
}

function etat(k, v){
  if (v == null || k.objectif == null) return '';
  const bon = k.sens === 'min' ? v <= k.objectif : v >= k.objectif;
  return bon ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
}

/* =====================================================================
   FILTRES EN CASCADE : Fréquence -> Département -> SQCDP -> Nom du KPI
   Chaque filtre ne propose que ce qui existe avec les choix précédents,
   et une sélection devenue impossible est remise à « Tous ».
   ===================================================================== */
function fillSelect(sel, placeholder, options, value){
  sel.innerHTML = `<option value="">${esc(placeholder)}</option>` +
    options.map(([v, l]) => `<option value="${esc(v)}">${esc(l)}</option>`).join('');
  sel.value = value;
}

function sync(){
  const parFreq = KPIS.filter(k => k.frequence === st.freq);

  const deps = uniq(parFreq.map(k => k.departement)).sort();
  if (st.dep && !deps.includes(st.dep)) st.dep = '';

  const parDep  = parFreq.filter(k => !st.dep || k.departement === st.dep);
  const lettres = Object.keys(LETTRES).filter(l => parDep.some(k => k.sqcdp === l));
  if (st.letter && !lettres.includes(st.letter)) st.letter = '';

  const liste = parDep.filter(k => !st.letter || k.sqcdp === st.letter);
  if (st.kpi && !liste.some(k => String(k.id) === st.kpi)) st.kpi = '';

  fillSelect($('fDep'),    'Tous les départements', deps.map(d => [d, d]), st.dep);
  fillSelect($('fLetter'), 'Toutes les catégories', lettres.map(l => [l, `${l} — ${LETTRES[l].nom}`]), st.letter);
  fillSelect($('fKpi'),    'Tous les KPI', liste.map(k => [String(k.id), k.nom]), st.kpi);

  return {parDep, liste};
}

/* ---------- Rendus ---------- */
function renderTabs(){
  $('tabs').innerHTML = FREQS.map(f => {
    const n = KPIS.filter(k => k.frequence === f).length, on = st.freq === f;
    return `<button type="button" data-f="${f}" class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition
              ${on ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700'}">
              ${FREQ_LB[f]}
              <span class="text-xs rounded-full px-2 py-0.5 ${on ? 'bg-indigo-500 text-white' : 'bg-slate-300/70 text-slate-600'}">${pad(n)}</span>
            </button>`;
  }).join('');
  $('tabs').querySelectorAll('button').forEach(b => b.onclick = () => { st.freq = b.dataset.f; render(); });
}

function renderCards(parDep){
  $('cards').innerHTML = Object.entries(LETTRES).map(([l, s]) => {
    const n = parDep.filter(k => k.sqcdp === l).length, on = st.letter === l;
    return `<button type="button" data-l="${l}" ${n === 0 ? 'disabled' : ''}
        class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 text-center transition
               ${on ? 'ring-2 ring-violet-400' : ''} ${n === 0 ? 'opacity-50 cursor-not-allowed' : 'hover:shadow-md'}">
      <p class="text-sm font-semibold tracking-wide uppercase text-slate-500">${s.nom}</p>
      <div class="mx-auto my-4 w-28 h-28 rounded-full flex items-center justify-center"
           style="background:${s.c}; padding:7px">
        <span class="w-full h-full rounded-full bg-white flex items-center justify-center text-6xl font-extrabold" style="color:${s.c}">${l}</span>
      </div>
      <p class="text-2xl font-bold text-slate-800">${n}</p>
      <p class="text-xs text-slate-500">Total KPI « ${l} »</p>
    </button>`;
  }).join('');
  $('cards').querySelectorAll('button').forEach(b => b.onclick = () => {
    st.letter = st.letter === b.dataset.l ? '' : b.dataset.l;
    render();
  });
}

function renderCharts(rows, cols){
  charts.forEach(c => c.destroy());
  charts = [];
  const box = $('charts'), note = $('chartsNote');

  if (!KPIS.length) {
    box.className = 'mb-2';
    box.innerHTML = '<div class="bg-white rounded-xl border border-slate-200 p-10 text-center text-slate-400">Aucun KPI enregistré dans la base.</div>';
    note.classList.add('hidden');
    return;
  }
  if (!rows.length) {
    box.className = 'mb-2';
    box.innerHTML = '<div class="bg-white rounded-xl border border-slate-200 p-10 text-center text-slate-400">Aucun KPI pour ces critères.</div>';
    note.classList.add('hidden');
    return;
  }

  // Un KPI choisi -> un seul graphique ; sinon un graphique PAR KPI (jamais plusieurs KPI sur le même graphique)
  const shown = st.kpi ? rows : rows.slice(0, MAX_GRAPHES);
  box.className = 'grid gap-4 mb-2 ' + (shown.length === 1 ? 'grid-cols-1' : 'grid-cols-1 lg:grid-cols-2 2xl:grid-cols-3');
  box.innerHTML = shown.map((k, i) => {
    const c = (LETTRES[k.sqcdp] || {c: '#2563eb'}).c;
    return `<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
      <div class="flex items-start justify-between gap-2 mb-3">
        <div>
          <h3 class="font-semibold text-slate-800 leading-tight">${esc(k.nom)}</h3>
          <p class="text-xs text-slate-500 mt-0.5">${esc(k.departement)} · ${FREQ_LB[k.frequence]}${k.unite ? ' · ' + esc(k.unite) : ''}${k.objectif != null ? ' · Objectif : ' + k.objectif : ''}</p>
        </div>
        <span class="shrink-0 w-7 h-7 rounded-full text-xs font-bold flex items-center justify-center" style="background:${c}22;color:${c}">${esc(k.sqcdp)}</span>
      </div>
      <div class="${shown.length === 1 ? 'h-80' : 'h-56'}"><canvas id="ch${i}"></canvas></div>
    </div>`;
  }).join('');

  shown.forEach((k, i) => {
    const c = (LETTRES[k.sqcdp] || {c: '#2563eb'}).c;
    const datasets = [{
      label: k.nom, data: cols.map(col => k.values[col.key] ?? null),
      borderColor: c, backgroundColor: c + '22', fill: true, tension: .3, spanGaps: true, pointRadius: 3
    }];
    if (k.objectif != null) {
      datasets.push({label: 'Objectif', data: cols.map(() => k.objectif),
        borderColor: '#64748b', borderDash: [6, 4], pointRadius: 0, fill: false});
    }
    charts.push(new Chart($('ch' + i), {
      type: 'line',
      data: {labels: cols.map(col => col.short), datasets},
      options: {maintainAspectRatio: false, plugins: {legend: {display: k.objectif != null, position: 'bottom'}}}
    }));
  });

  if (!st.kpi && rows.length > MAX_GRAPHES) {
    note.textContent = `${MAX_GRAPHES} graphiques affichés sur ${rows.length} KPI : choisissez un département, une catégorie ou un KPI pour affiner.`;
    note.classList.remove('hidden');
  } else {
    note.classList.add('hidden');
  }
}

function renderTable(rows, cols){
  $('tbl').innerHTML = `<thead class="bg-slate-50 text-slate-500 text-xs uppercase"><tr>
      <th class="p-3 text-left">Département</th><th class="p-3 text-left">Nom du KPI</th><th class="p-3 text-center">Objectif</th>
      ${cols.map(c => `<th class="p-3 text-center">${esc(c.label)}</th>`).join('')}</tr></thead>
    <tbody class="divide-y divide-slate-100">${rows.map(k => `<tr class="hover:bg-slate-50">
      <td class="p-3 text-slate-600">${esc(k.departement)}</td>
      <td class="p-3 font-medium text-slate-800">${esc(k.nom)}</td>
      <td class="p-3 text-center font-semibold">${k.objectif ?? '—'}</td>
      ${cols.map(c => { const v = k.values[c.key]; return `<td class="p-3 text-center ${etat(k, v)}">${v ?? '-'}</td>`; }).join('')}
    </tr>`).join('') || '<tr><td class="p-8 text-center text-slate-400" colspan="99">Aucun KPI pour ces critères</td></tr>'}</tbody>`;
}

function render(){
  const {parDep, liste} = sync();
  const rows = st.kpi ? liste.filter(k => String(k.id) === st.kpi) : liste;
  const cols = columns();

  renderTabs();
  renderCards(parDep);
  renderCharts(rows, cols);
  renderTable(rows, cols);

  $('wrapMonth').classList.toggle('hidden', st.freq !== 'Journalier');
  $('info').textContent = `${rows.length} KPI — ${FREQ_LB[st.freq]}`;
}

/* ---------- Évènements ---------- */
MOIS.forEach((m, i) => $('fMonth').add(new Option(m, i)));
$('fMonth').value = st.month;
$('fDep').onchange    = e => { st.dep    = e.target.value; render(); };
$('fLetter').onchange = e => { st.letter = e.target.value; render(); };
$('fKpi').onchange    = e => { st.kpi    = e.target.value; render(); };
$('fMonth').onchange  = e => { st.month  = +e.target.value; render(); };
$('fReset').onclick   = () => { Object.assign(st, {dep: '', letter: '', kpi: '', month: today.getMonth()}); $('fMonth').value = st.month; render(); };

render();
</script>

<?= $this->endSection() ?>