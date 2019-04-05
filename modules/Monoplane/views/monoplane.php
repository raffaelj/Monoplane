<?php extract($mp); ?>
<!DOCTYPE html>
<html lang="{{ $app('i18n')->locale }}">

    <head>

        <meta charset="utf-8" />
        <meta content='text/html; charset=utf-8' http-equiv='Content-Type'>
        <meta http-equiv='X-UA-Compatible' content='IE=edge'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0'>

        <title>{{ (!empty($page['title']) ? $page['title'] . ' - ' : '') . ($site['site_name'] ?? $app['app.name']) }}</title>
        <meta name="description" content="{{ $app->escape(!empty($page['description']) ? $page['description'] : ($site['description'] ?? '')) }}" />

        @render('views:partials/open-graph.php', compact('page', 'site'))

        <link rel="shortcut icon" href="{{ MP_BASE_URL }}/favicon.png?ver={{ $version }}">

        {{ $app->assets($app['monoplane.assets.top'], $version) }}
        {{ $app('mp')->userStyles() }}

    </head>

    <body id="top">

        <header>

            <a href="{{ MP_BASE_URL }}/">
@if(!empty($site['logo']))
                <img class="logo" alt="{{ $site['logo']['title'] ?? 'logo' }}" src="@logo($site['logo']['_id'] ?? $site['logo']['path'])" title="@lang('back to start page')" />
@endif
                <h1>{{ $site['site_name'] ?? $app['app.name'] }}</h1>
            </a>

        </header>

        @render('views:partials/nav.php', ['mp' => $mp, 'type' => 'main'])

        <main id="main">

            {{ $content_for_layout }}

        </main>

        <footer>
            @render('views:partials/nav.php', ['mp' => $mp, 'type' => 'footer'])
            @render('views:partials/copyright.php')
        </footer>

        {{ $app->assets($app['monoplane.assets.bottom'], $version) }}
        {{ $app('mp')->userScripts() }}

    </body>

</html>
