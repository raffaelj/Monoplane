
        <main id="main">

            <h2>{{ $page['title'] }}</h2>

            {{ $page['content'] }}

            @if(!empty($page['gallery']))
                @render('views:partials/gallery.php', compact('page'))
            @endif

            @if(!empty($page['video']))
                @render('views:partials/video.php', ['video' => $page['video']])
            @endif

            @if (!empty($page['add_blog_module']) && $page['add_blog_module'])
                @render('views:partials/blog.php', ['posts' => $page['blog_module']['posts'], 'pagination' => $page['blog_module']['pagination']])
            @endif

        </main>

@if (!empty($page['contactform']) && $page['contactform'])
        @render('views:partials/contactform.php', compact('page'))
@endif
