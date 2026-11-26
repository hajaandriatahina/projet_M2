<?php
// app/Views/user/plan_action.php
// Layout : app/Views/layouts/user_layout.php
// Variables : $kpis (id_configuration, nom, departement) — $plans (id, id_configuration, nom_kpi, departement, observation, action, responsable, echeance)

$flags = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
?>
<?= $this->extend('layouts/user_layout') ?>

<?= $this->section('content') ?>

<!-- ============ Liste des plans d'action ============ -->
<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 sm:p-5">

    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <h1 class="flex items-center gap-2 text-xl font-bold text-slate-800">
            <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 24 24"><path d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/></svg>
            Liste des plans d'action
        </h1>
        <button type="button" id="btnAjouter" <?= empty($kpis) ? 'disabled' : '' ?>
                class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 disabled:bg-slate-300 disabled:cursor-not-allowed text-white text-sm font-semibold rounded-lg px-4 py-2.5 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
            Ajouter un plan d'action
        </button>
    </div>

    <?php if (empty($kpis)): ?>
        <p class="mb-4 text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
            Aucun KPI ne vous est attribué : vous ne pouvez pas encore créer de plan d'action.
        </p>
    <?php endif; ?>

    <!-- Filtres -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
        <div class="flex items-center gap-2 border border-slate-300 rounded-lg px-3 py-1.5">
            <label for="fDep" class="text-sm font-semibold text-slate-600 whitespace-nowrap">Département :</label>
            <select id="fDep" class="flex-1 min-w-0 bg-transparent text-sm outline-none"></select>
        </div>
        <div class="flex items-center gap-2 border border-slate-300 rounded-lg px-3 py-1.5">
            <label for="fKpi" class="text-sm font-semibold text-slate-600 whitespace-nowrap">KPI :</label>
            <select id="fKpi" class="flex-1 min-w-0 bg-transparent text-sm outline-none"></select>
        </div>
        <div class="sm:col-span-2 flex items-center border border-slate-300 rounded-lg overflow-hidden">
            <svg class="w-4 h-4 text-slate-400 ml-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="M20 20l-3.5-3.5"/></svg>
            <input id="fSearch" type="search" placeholder="Rechercher une action…"
                   class="flex-1 min-w-0 px-3 py-2 text-sm outline-none">
        </div>
    </div>

    <!-- Tableau -->
    <div class="overflow-x-auto border border-slate-200 rounded-lg">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr class="text-left">
                    <th class="p-3 w-12">#</th>
                    <th class="p-3 min-w-[240px]">Action</th>
                    <th class="p-3 min-w-[180px]">Origine (KPI)</th>
                    <th class="p-3">Département</th>
                    <th class="p-3 min-w-[220px]">Observation</th>
                    <th class="p-3">Responsable</th>
                    <th class="p-3">Échéance</th>
                    <th class="p-3 text-center">Actions</th>
                </tr>
            </thead>
            <tbody id="tbody" class="divide-y divide-slate-100"></tbody>
        </table>
    </div>

    <!-- Pied : info + pagination -->
    <div class="flex flex-wrap items-center justify-between gap-3 mt-3">
        <p id="info" class="text-sm text-slate-600"></p>
        <div id="pager" class="flex items-center gap-1"></div>
    </div>
</div>

<!-- ============ Fenêtre : ajouter / modifier / voir ============ -->
<div id="modal" class="hidden fixed inset-0 z-50 bg-black/50 overflow-y-auto p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-3xl w-full mx-auto my-8 overflow-hidden">

        <div class="flex items-center justify-between bg-[#0b3a6e] text-white px-5 py-3.5">
            <h2 class="flex items-center gap-2 text-lg font-bold">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 8v8M8 12h8"/></svg>
                <span id="mTitre">Ajouter un plan d'action</span>
            </h2>
            <button type="button" id="mFermer" class="p-1 rounded hover:bg-white/15" aria-label="Fermer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </div>

        <form id="form" method="post" action="<?= site_url('utilisateur/plan-action/enregistrer') ?>" autocomplete="off">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="mId" value="0">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 p-5">

                <!-- Colonne gauche -->
                <div class="space-y-4">
                    <div>
                        <label for="mAction" class="block text-sm font-semibold text-slate-700 mb-1">Action à réaliser <span class="text-red-500">*</span></label>
                        <textarea id="mAction" name="action" rows="4" required maxlength="1000"
                                  placeholder="Décrivez l'action à mettre en œuvre…"
                                  class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-300 outline-none"></textarea>
                    </div>
                    <div>
                        <label for="mKpi" class="block text-sm font-semibold text-slate-700 mb-1">Origine (Nom du KPI) <span class="text-red-500">*</span></label>
                        <select id="mKpi" name="id_configuration" required
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-300 outline-none"></select>
                    </div>
                    <div>
                        <label for="mDep" class="block text-sm font-semibold text-slate-700 mb-1">Département</label>
                        <input id="mDep" type="text" readonly tabindex="-1" placeholder="Rempli automatiquement selon le KPI"
                               class="w-full rounded-lg border border-slate-200 bg-slate-50 text-slate-600 px-3 py-2 text-sm">
                    </div>
                </div>

                <!-- Colonne droite -->
                <div class="space-y-4">
                    <div>
                        <label for="mObs" class="block text-sm font-semibold text-slate-700 mb-1">Observation</label>
                        <textarea id="mObs" name="observation" rows="4" maxlength="2000"
                                  placeholder="État, constat, informations complémentaires…"
                                  class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-300 outline-none"></textarea>
                    </div>
                    <div>
                        <label for="mResp" class="block text-sm font-semibold text-slate-700 mb-1">Responsable <span class="text-red-500">*</span></label>
                        <input id="mResp" name="responsable" type="text" required maxlength="150" placeholder="Nom du responsable"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-300 outline-none">
                    </div>
                    <div>
                        <label for="mEch" class="block text-sm font-semibold text-slate-700 mb-1">Échéance <span class="text-red-500">*</span></label>
                        <input id="mEch" name="echeance" type="date" required
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-300 outline-none">
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 bg-slate-50 px-5 py-3">
                <button type="button" id="mReset" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-slate-300 bg-white text-sm font-medium text-slate-700 hover:bg-slate-100">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v6h6M20 20v-6h-6M5 14a8 8 0 0014 3M19 10A8 8 0 005 7"/></svg>
                    Réinitialiser
                </button>
                <div class="flex items-center gap-3">
                    <button type="button" id="mAnnuler" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-slate-300 bg-white text-sm font-medium text-slate-700 hover:bg-slate-100">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
                        Annuler
                    </button>
                    <button type="submit" id="mSave" class="inline-flex items-center gap-2 px-5 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 4h11l3 3v13H5zM8 4v5h7V4M8 20v-6h8v6"/></svg>
                        Enregistrer
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Formulaire caché de suppression -->
<form id="formSuppr" method="post" action="<?= site_url('utilisateur/plan-action/supprimer') ?>" class="hidden">
    <?= csrf_field() ?>
    <input type="hidden" name="id" id="sId" value="0">
</form>

<script>
(function () {
    const KPIS  = <?= json_encode($kpis,  $flags) ?>;
    const PLANS = <?= json_encode($plans, $flags) ?>;
    const PAR_PAGE = 8;

    const $   = id => document.getElementById(id);
    const esc = s  => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const fmtDate = d => d ? d.split('-').reverse().join('/') : '—';
    const t = new Date();
    const today = `${t.getFullYear()}-${String(t.getMonth() + 1).padStart(2, '0')}-${String(t.getDate()).padStart(2, '0')}`;

    const st = {dep: '', kpi: '', q: '', page: 1};

    // ---------------- Filtres ----------------
    function remplirFiltres() {
        const deps = [...new Set(KPIS.map(k => k.departement))].sort();
        if (st.dep && !deps.includes(st.dep)) st.dep = '';
        $('fDep').innerHTML = '<option value="">Tous</option>' + deps.map(d => `<option value="${esc(d)}">${esc(d)}</option>`).join('');
        $('fDep').value = st.dep;

        const liste = KPIS.filter(k => !st.dep || k.departement === st.dep);
        if (st.kpi && !liste.some(k => String(k.id_configuration) === st.kpi)) st.kpi = '';
        $('fKpi').innerHTML = '<option value="">Tous</option>' + liste.map(k => `<option value="${k.id_configuration}">${esc(k.nom)}</option>`).join('');
        $('fKpi').value = st.kpi;
    }

    function filtres() {
        const q = st.q.trim().toLowerCase();
        return PLANS.filter(p =>
            (!st.dep || p.departement === st.dep) &&
            (!st.kpi || String(p.id_configuration) === st.kpi) &&
            (!q || [p.action, p.observation, p.responsable, p.nom_kpi].join(' ').toLowerCase().includes(q)));
    }

    // ---------------- Tableau + pagination ----------------
    function rendre() {
        const rows  = filtres();
        const pages = Math.max(1, Math.ceil(rows.length / PAR_PAGE));
        if (st.page > pages) st.page = pages;
        const debut = (st.page - 1) * PAR_PAGE;
        const vue   = rows.slice(debut, debut + PAR_PAGE);

        $('tbody').innerHTML = vue.map((p, i) => {
            const depasse = p.echeance < today;
            return `<tr class="hover:bg-slate-50 align-top">
                <td class="p-3 text-slate-500">${debut + i + 1}</td>
                <td class="p-3 font-medium text-slate-800">${esc(p.action)}</td>
                <td class="p-3 text-slate-700">${esc(p.nom_kpi)}</td>
                <td class="p-3 text-slate-600">${esc(p.departement)}</td>
                <td class="p-3 text-slate-600">${esc(p.observation) || '<span class="text-slate-300">—</span>'}</td>
                <td class="p-3 text-slate-700">${esc(p.responsable)}</td>
                <td class="p-3 whitespace-nowrap ${depasse ? 'text-rose-600 font-semibold' : 'text-slate-700'}" ${depasse ? 'title="Échéance dépassée"' : ''}>
                    ${fmtDate(p.echeance)}</td>
                <td class="p-3">
                    <div class="flex items-center justify-center gap-3">
                        <button type="button" data-a="voir" data-id="${p.id}" class="text-blue-600 hover:text-blue-800" title="Voir">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                        <button type="button" data-a="modifier" data-id="${p.id}" class="text-blue-600 hover:text-blue-800" title="Modifier">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M17.5 3.5a2.1 2.1 0 013 3L12 15l-4 1 1-4 8.5-8.5z"/></svg>
                        </button>
                        <button type="button" data-a="supprimer" data-id="${p.id}" class="text-red-600 hover:text-red-800" title="Supprimer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M10 11v6M14 11v6M6 7l1 12a2 2 0 002 2h6a2 2 0 002-2l1-12M9 7V4h6v3"/></svg>
                        </button>
                    </div>
                </td>
            </tr>`;
        }).join('') || `<tr><td colspan="8" class="p-10 text-center text-slate-400">Aucun plan d'action</td></tr>`;

        $('info').textContent = rows.length
            ? `Affichage de ${debut + 1} à ${debut + vue.length} sur ${rows.length} entrée${rows.length > 1 ? 's' : ''}`
            : 'Aucune entrée';

        // pagination
        const btn = (label, p, actif, off) =>
            `<button type="button" data-p="${p}" ${off ? 'disabled' : ''}
               class="min-w-[32px] h-8 px-2 rounded border text-sm ${actif ? 'bg-blue-600 border-blue-600 text-white' : 'border-slate-300 text-slate-600 hover:bg-slate-50'} ${off ? 'opacity-40 cursor-not-allowed' : ''}">${label}</button>`;
        let h = btn('‹', st.page - 1, false, st.page === 1);
        const a = Math.max(1, st.page - 2), b = Math.min(pages, a + 4);
        for (let p = Math.max(1, b - 4); p <= b; p++) h += btn(p, p, p === st.page, false);
        h += btn('›', st.page + 1, false, st.page === pages);
        $('pager').innerHTML = pages > 1 ? h : '';
    }

    // ---------------- Fenêtre ----------------
    const modal = $('modal');
    let courant = null;   // plan en cours d'édition (pour « Réinitialiser »)

    function majDep() {
        const k = KPIS.find(x => String(x.id_configuration) === $('mKpi').value);
        $('mDep').value = k ? k.departement : '';
    }

    function remplir(p) {
        $('mId').value     = p ? p.id : 0;
        $('mKpi').value    = p ? p.id_configuration : '';
        $('mAction').value = p ? p.action : '';
        $('mObs').value    = p ? (p.observation || '') : '';
        $('mResp').value   = p ? p.responsable : '';
        $('mEch').value    = p ? p.echeance : '';
        majDep();
    }

    function ouvrir(mode, p) {
        courant = p || null;
        $('mKpi').innerHTML = '<option value="">Sélectionner…</option>' +
            KPIS.map(k => `<option value="${k.id_configuration}">${esc(k.nom)} — ${esc(k.departement)}</option>`).join('');
        remplir(p);

        const lecture = mode === 'voir';
        ['mKpi', 'mAction', 'mObs', 'mResp', 'mEch'].forEach(id => $(id).disabled = lecture);
        $('mSave').classList.toggle('hidden', lecture);
        $('mReset').classList.toggle('hidden', lecture);
        $('mAnnuler').lastChild.textContent = lecture ? ' Fermer' : ' Annuler';
        $('mTitre').textContent = {ajouter: "Ajouter un plan d'action", modifier: "Modifier le plan d'action", voir: "Détail du plan d'action"}[mode];

        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function fermer() {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    // ---------------- Évènements ----------------
    $('fDep').onchange    = e => { st.dep = e.target.value; st.page = 1; remplirFiltres(); rendre(); };
    $('fKpi').onchange    = e => { st.kpi = e.target.value; st.page = 1; rendre(); };
    $('fSearch').oninput  = e => { st.q = e.target.value;   st.page = 1; rendre(); };

    $('pager').addEventListener('click', e => {
        const b = e.target.closest('button[data-p]');
        if (b && !b.disabled) { st.page = +b.dataset.p; rendre(); }
    });

    $('tbody').addEventListener('click', e => {
        const b = e.target.closest('button[data-a]');
        if (!b) return;
        const p = PLANS.find(x => String(x.id) === b.dataset.id);
        if (!p) return;
        if (b.dataset.a === 'supprimer') {
            if (confirm('Supprimer ce plan d\'action ?')) { $('sId').value = p.id; $('formSuppr').submit(); }
        } else {
            ouvrir(b.dataset.a, p);
        }
    });

    $('btnAjouter').onclick = () => ouvrir('ajouter', null);
    $('mKpi').onchange      = majDep;
    $('mReset').onclick     = () => remplir(courant);
    $('mFermer').onclick = $('mAnnuler').onclick = fermer;
    modal.addEventListener('mousedown', e => { if (e.target === modal) fermer(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') fermer(); });

    remplirFiltres();
    rendre();
})();
</script>

<?= $this->endSection() ?>