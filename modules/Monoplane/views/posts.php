<?php
$width  = $app->retrieve('monoplane/lexy/headerimage/width', 800)  . 'px';
$height = $app->retrieve('monoplane/lexy/headerimage/height', 200) . 'px';
?>

        <main id="main">
            <article>

                @if(!empty($page['image']))
                <img class="featured_image" src="@headerimage($page['image']['_id'])" alt="{{ $page['image']['title'] ?? 'image' }}" width="{{ $width }}" height="{{ $height }}" />
                @elseif(!empty($page['featured_image']))
                <img class="featured_image" src="@headerimage($page['featured_image']['_id'])" alt="{{ $page['featured_image']['title'] ?? 'image' }}" width="{{ $width }}" height="{{ $height }}" />
                @endif

                <h2>{{ $page['title'] }}</h2>

                {{ $page['content'] }}

                @if(!empty($page['gallery']))
                    @render('views:partials/gallery.php', compact('page'))
                @endif

                @if(!empty($page['video']))
                    @render('views:partials/video.php', ['video' => $page['video']])
                @endif

            </article>
        </main>
