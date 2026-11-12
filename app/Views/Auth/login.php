<?php
/**
 * app/Views/login.php
 * Page de connexion moderne — CodeIgniter 4 + Tailwind CSS
 * - Tailwind via CDN (aucune recompilation nécessaire en développement)
 * - Tient sur un seul écran : aucun scroll sur la page
 */
$validation = $validation ?? \Config\Services::validation();
?>
<!DOCTYPE html>
<html lang="fr" class="h-full overflow-hidden">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion | KPI Socolait</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!--
        DÉVELOPPEMENT : Tailwind CDN (génère les classes à la volée).
        PRODUCTION   : remplacer par <link href="<?= base_url('css/tailwind.css') ?>"> après recompilation.
    -->
    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
                    colors: {
                        brand: {
                            50: '#eff6ff', 100: '#dbeafe', 200: '#bfdbfe', 300: '#93c5fd',
                            400: '#60a5fa', 500: '#2563eb', 600: '#1d4ed8', 700: '#1e40af',
                            800: '#1e3a8a', 900: '#172554'
                        }
                    },
                    keyframes: {
                        float: { '0%,100%': { transform: 'translateY(0)' }, '50%': { transform: 'translateY(-10px)' } }
                    },
                    animation: { float: 'float 6s ease-in-out infinite' }
                }
            }
        }
    </script>

    <!-- Animation des barres (balayage lumineux) -->
    <style>
        .lumiere-bar { position: relative; overflow: hidden; animation: lumiere-pulse 2.4s ease-in-out infinite; animation-delay: var(--d); }
        .lumiere-bar::after {
            content: ''; position: absolute; inset: 0;
            background: linear-gradient(180deg, transparent 0%, rgba(255,255,255,.65) 50%, transparent 100%);
            transform: translateY(-100%);
            animation: lumiere-sweep 2.4s ease-in-out infinite; animation-delay: var(--d);
        }
        @keyframes lumiere-sweep { 0% { transform: translateY(-100%); } 60%, 100% { transform: translateY(100%); } }
        @keyframes lumiere-pulse { 0%, 100% { filter: brightness(1); } 30% { filter: brightness(1.35); } }
        .dots { background-image: radial-gradient(rgba(255,255,255,.18) 1px, transparent 1px); background-size: 22px 22px; }
    </style>
</head>

<body class="relative h-screen overflow-hidden bg-slate-100 font-sans flex items-center justify-center p-4 sm:p-6 lg:p-8">

    <!-- Fond décoratif -->
    <div class="pointer-events-none absolute -top-32 -left-32 w-[28rem] h-[28rem] rounded-full bg-brand-400/30 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-40 -right-32 w-[30rem] h-[30rem] rounded-full bg-indigo-400/25 blur-3xl"></div>

    <!-- ============ CARTE ============ -->
    <main class="relative w-full max-w-5xl h-full max-h-[640px] bg-white/90 backdrop-blur rounded-3xl shadow-[0_30px_80px_-20px_rgba(30,64,175,.35)] ring-1 ring-slate-200/70 overflow-hidden grid grid-cols-1 md:grid-cols-[1.05fr_1fr] md:gap-2 p-3">

        <!-- ============ PANNEAU GAUCHE ============ -->
        <section class="relative hidden md:flex min-h-0 flex-col justify-between rounded-2xl bg-gradient-to-br from-brand-500 via-brand-600 to-brand-900 text-white p-9 lg:p-10 overflow-hidden">

            <div class="dots absolute inset-0"></div>
            <div class="absolute -top-24 -left-24 w-72 h-72 rounded-full bg-white/10"></div>
            <div class="absolute top-1/2 -right-24 w-64 h-64 rounded-full bg-white/10"></div>
            <div class="absolute -bottom-28 left-10 w-80 h-80 rounded-full bg-white/5"></div>

            <!-- Logo -->
            <div class="relative flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-white/20 ring-1 ring-white/30 backdrop-blur flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v18h18M7 14l4-4 4 4 5-6"/>
                    </svg>
                </div>
                <span class="text-xl font-bold tracking-wide">KPI Socolait</span>
            </div>

            <!-- Texte principal -->
            <div class="relative">
                <span class="inline-flex items-center gap-2 rounded-full bg-white/15 ring-1 ring-white/25 px-3 py-1 text-xs font-medium text-blue-50">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-300"></span> Espace de pilotage
                </span>

                <h1 class="mt-4 text-4xl lg:text-5xl font-extrabold leading-[1.1] tracking-tight">
                    Welcome<br>Back !
                </h1>

                <p class="mt-4 text-blue-100 text-base leading-relaxed max-w-sm">
                    Suivez, saisissez et validez vos indicateurs de performance en un seul endroit.
                </p>

                <!-- Pastilles -->
                <div class="mt-5 flex flex-wrap gap-2">
                    <span class="rounded-lg bg-white/15 ring-1 ring-white/20 px-3 py-1 text-xs font-semibold">Sécurité</span>
                    <span class="rounded-lg bg-white/15 ring-1 ring-white/20 px-3 py-1 text-xs font-semibold">Qualité</span>
                    <span class="rounded-lg bg-white/15 ring-1 ring-white/20 px-3 py-1 text-xs font-semibold">Coûts</span>
                    <span class="rounded-lg bg-white/15 ring-1 ring-white/20 px-3 py-1 text-xs font-semibold">Délais</span>
                    <span class="rounded-lg bg-white/15 ring-1 ring-white/20 px-3 py-1 text-xs font-semibold">Personnel</span>
                </div>

                <!-- Barres animées -->
                <div class="mt-6 flex items-end gap-3 h-20">
                    <div class="lumiere-bar w-8 rounded-t-lg bg-white/30 h-8"  style="--d:0s"></div>
                    <div class="lumiere-bar w-8 rounded-t-lg bg-white/40 h-12" style="--d:.15s"></div>
                    <div class="lumiere-bar w-8 rounded-t-lg bg-white/50 h-10" style="--d:.3s"></div>
                    <div class="lumiere-bar w-8 rounded-t-lg bg-white/70 h-20" style="--d:.45s"></div>
                    <div class="lumiere-bar w-8 rounded-t-lg bg-white/60 h-16" style="--d:.6s"></div>
                </div>
            </div>

            <p class="relative text-xs text-blue-200">&copy; <?= date('Y') ?> Socolait. Tous droits réservés.</p>
        </section>

        <!-- ============ PANNEAU DROIT ============ -->
        <section class="min-h-0 flex flex-col justify-center overflow-y-auto px-6 sm:px-10 lg:px-12 py-6">
            <div class="w-full max-w-sm mx-auto">

                <!-- Logo mobile -->
                <div class="md:hidden mb-6 flex items-center gap-2 text-brand-600 font-bold text-lg">
                    <div class="w-9 h-9 rounded-xl bg-brand-500 text-white flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v18h18M7 14l4-4 4 4 5-6"/>
                        </svg>
                    </div>
                    KPI Socolait
                </div>

                <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight">Connexion</h2>
                <p class="mt-2 text-sm text-slate-500 leading-6">Entrez vos identifiants pour accéder à votre espace.</p>

                <!-- Messages flash -->
                <?php if (session()->getFlashdata('error')): ?>
                    <div class="mt-5 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        <svg class="w-5 h-5 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10A8 8 0 11 2 10a8 8 0 0116 0zm-8-4a1 1 0 00-1 1v3a1 1 0 002 0V7a1 1 0 00-1-1zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                        </svg>
                        <span><?= esc(session()->getFlashdata('error')) ?></span>
                    </div>
                <?php endif; ?>

                <?php if (session()->getFlashdata('success')): ?>
                    <div class="mt-5 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                        <?= esc(session()->getFlashdata('success')) ?>
                    </div>
                <?php endif; ?>

                <form action="<?= site_url('login') ?>" method="post" class="mt-7 space-y-5" novalidate>
                    <?= csrf_field() ?>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-semibold text-slate-700 mb-2">Adresse e-mail</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400 pointer-events-none">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l9 6 9-6M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </span>
                            <input type="email" id="email" name="email" value="<?= esc(old('email')) ?>"
                                   placeholder="nom@basan.com" autocomplete="email" required autofocus
                                   class="block w-full h-12 rounded-xl border bg-slate-50 pl-12 pr-4 text-sm text-slate-800 placeholder-slate-400 outline-none transition
                                          hover:border-slate-300 focus:bg-white focus:ring-4 focus:ring-brand-500/15 focus:border-brand-500
                                          <?= $validation->hasError('email') ? 'border-red-400' : 'border-slate-200' ?>">
                        </div>
                        <?php if ($validation->hasError('email')): ?>
                            <p class="mt-1.5 text-xs text-red-600"><?= esc($validation->getError('email')) ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Mot de passe -->
                    <div>
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <label for="password" class="block text-sm font-semibold text-slate-700">Mot de passe</label>
                            <a href="#" class="text-xs font-semibold text-brand-600 hover:text-brand-700 hover:underline whitespace-nowrap">Mot de passe oublié ?</a>
                        </div>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400 pointer-events-none">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4"/>
                                </svg>
                            </span>
                            <input type="password" id="password" name="password" placeholder="••••••••"
                                   autocomplete="current-password" required
                                   class="block w-full h-12 rounded-xl border bg-slate-50 pl-12 pr-12 text-sm text-slate-800 placeholder-slate-400 outline-none transition
                                          hover:border-slate-300 focus:bg-white focus:ring-4 focus:ring-brand-500/15 focus:border-brand-500
                                          <?= $validation->hasError('password') ? 'border-red-400' : 'border-slate-200' ?>">
                            <button type="button" id="togglePwd" aria-label="Afficher le mot de passe" aria-pressed="false"
                                    class="absolute inset-y-0 right-0 flex items-center px-4 text-slate-400 hover:text-slate-600">
                                <svg id="eyeOn" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg id="eyeOff" class="w-5 h-5 hidden" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>
                                </svg>
                            </button>
                        </div>
                        <?php if ($validation->hasError('password')): ?>
                            <p class="mt-1.5 text-xs text-red-600"><?= esc($validation->getError('password')) ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Se souvenir -->
                    <label class="flex items-center gap-2.5 cursor-pointer select-none">
                        <input type="checkbox" name="remember" value="1"
                               class="w-4 h-4 rounded border-slate-300 text-brand-500 focus:ring-brand-500">
                        <span class="text-sm text-slate-600">Se souvenir de moi</span>
                    </label>

                    <!-- Bouton -->
                    <button type="submit"
                            class="group w-full h-12 rounded-xl bg-gradient-to-r from-brand-500 to-brand-600 font-semibold text-white
                                   shadow-lg shadow-brand-500/30 transition hover:shadow-xl hover:shadow-brand-500/40 hover:from-brand-600 hover:to-brand-700
                                   focus:outline-none focus:ring-4 focus:ring-brand-500/30 active:scale-[.99] flex items-center justify-center gap-2">
                        Se connecter
                        <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6"/>
                        </svg>
                    </button>
                </form>

                <p class="mt-6 text-center text-sm text-slate-500">
                    Pas de compte ? <span class="font-medium text-slate-700">Contactez l'administrateur.</span>
                </p>
            </div>
        </section>
    </main>

    <script>
        const pwd = document.getElementById('password');
        const togglePwd = document.getElementById('togglePwd');
        const eyeOn = document.getElementById('eyeOn');
        const eyeOff = document.getElementById('eyeOff');

        togglePwd.addEventListener('click', () => {
            const show = pwd.type === 'password';
            pwd.type = show ? 'text' : 'password';
            eyeOn.classList.toggle('hidden', show);
            eyeOff.classList.toggle('hidden', !show);
            togglePwd.setAttribute('aria-pressed', show ? 'true' : 'false');
            togglePwd.setAttribute('aria-label', show ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
        });
    </script>
</body>
</html>