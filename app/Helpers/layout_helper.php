<?php

/**
 * app/Helpers/layout_helper.php
 * Charger avec :  helper('layout');
 */

use App\Models\UserModel;

if (! function_exists('ui_icon')) {
    /**
     * Icône SVG (style Heroicons outline).
     */
    function ui_icon(string $name, string $class = 'w-6 h-6'): string
    {
        $paths = [
            'logo'     => 'M3 3v18h18M7 14l4-4 4 4 5-6',
            'home'     => 'M2.25 12l8.954-8.955a1.126 1.126 0 011.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25',
            'chart'    => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z',
            'users'    => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z',
            'check'    => 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
            'report'   => 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z',
            'edit'     => 'M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10',
            'settings' => 'M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75',
            'search'   => 'M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z',
            'bell'     => 'M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0',
            'logout'   => 'M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75',
            'menu'     => 'M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5',
            'close'    => 'M6 18L18 6M6 6l12 12',
            'chevron'  => 'M19.5 8.25l-7.5 7.5-7.5-7.5',
            'plus'     => 'M12 4.5v15m7.5-7.5h-15',
            'sort'     => 'M3 7.5L7.5 3m0 0L12 7.5M7.5 3v13.5m13.5 0L16.5 21m0 0L12 16.5m4.5 4.5V7.5',
            'inbox'    => 'M2.25 13.5h3.86a2.25 2.25 0 012.012 1.244l.256.512a2.25 2.25 0 002.013 1.244h3.218a2.25 2.25 0 002.013-1.244l.256-.512a2.25 2.25 0 012.013-1.244h3.859M2.25 13.5V6a2.25 2.25 0 012.25-2.25h15A2.25 2.25 0 0121.75 6v7.5M2.25 13.5v4.5A2.25 2.25 0 004.5 20.25h15a2.25 2.25 0 002.25-2.25v-4.5',
        ];

        $d = $paths[$name] ?? $paths['logo'];

        return '<svg class="' . esc($class, 'attr') . '" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">'
            . '<path stroke-linecap="round" stroke-linejoin="round" d="' . $d . '"/></svg>';
    }
}

if (! function_exists('ui_menu')) {
    /**
     * Menu de navigation selon le rôle.
     * 'path' = route CodeIgniter (null = page pas encore créée -> lien "#").
     * Quand tu crées une page, remplace simplement null par sa route.
     */
   function ui_menu(string $role): array
{
    $home   = UserModel::homeFor($role) ?? 'login';
    $prefix = match ($role) {
        'Administrateur' => 'admin',
        'Directeur'      => 'directeur',
        'Validateur'     => 'validateur',
        'Utilisateur'    => 'utilisateur',
        default          => 'login',
    };

    $dashboard   = ['label' => 'Tableau de bord', 'icon' => 'home',     'path' => $home];
    $indicateurs = ['label' => 'Indicateurs KPI', 'icon' => 'chart',    'path' => $prefix . '/indicateurs'];
    $parametres  = ['label' => 'Paramètres',      'icon' => 'settings', 'path' => 'admin/configuration'];

    return match ($role) {
        'Administrateur' => [$dashboard, $indicateurs, $parametres],
        'Directeur', 'Validateur' => [$dashboard, $indicateurs],
        'Utilisateur'    => [
            $dashboard,
            ['label' => 'Mes KPI, Objectif', 'icon' => 'edit', 'path' => 'utilisateur/mes-kpi'],
            ['label' => 'Notifications',     'icon' => 'bell', 'path' => 'utilisateur/notifications'],
            ['label' => 'Mon profil',        'icon' => 'users', 'path' => 'utilisateur/profil'],
        ],
        default => [$dashboard],
    };
}
}

if (! function_exists('ui_url')) {
    function ui_url(array $item): string
    {
        return $item['path'] !== null ? site_url($item['path']) : '#';
    }
}

if (! function_exists('ui_is_active')) {
    function ui_is_active(array $item): bool
    {
        if ($item['path'] === null) {
            return false;
        }

        $current = trim(uri_string(), '/');
        $path    = trim($item['path'], '/');

        return $current === $path || str_starts_with($current, $path . '/');
    }
}

if (! function_exists('ui_initials')) {
    function ui_initials(string $prenom, string $nom): string
    {
        $a = mb_strtoupper(mb_substr(trim($prenom), 0, 1));
        $b = mb_strtoupper(mb_substr(trim($nom), 0, 1));

        return ($a . $b) !== '' ? $a . $b : '?';
    }
}

if (! function_exists('ui_date_fr')) {
    /**
     * Date en français sans dépendre de l'extension intl.
     */
    function ui_date_fr(?int $timestamp = null): string
    {
        $mois = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin',
                 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
        $t = $timestamp ?? time();

        return date('j', $t) . ' ' . $mois[(int) date('n', $t) - 1] . ' ' . date('Y', $t);
    }
}