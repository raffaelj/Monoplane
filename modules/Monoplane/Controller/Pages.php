<?php

namespace Monoplane\Controller;

class Pages extends \LimeExtra\Controller {

    public function before() {

        $this->mp = array_replace_recursive([
            'id' => '_id',
            '_id' => '',
            'pages' => 'pages',
            'posts' => 'posts',
            'site' => [],
        ],$this->retrieve('monoplane', []));

        $this->mp['nav'] = $this->nav();

        $this->trigger('monoplane.pages.before', [&$this->mp]);

    }

    public function index($slug = '') {
        return $this->page($slug);
    }

    public function page($slug = '') {

        if (strpos($slug, '/')) { // sub pages - call different collections

            $parts = explode('/', $slug);

            $subPages = $this->mp['public_routes'] ?? false;
            if ($subPages
                && ($subPages == 'all' || array_key_exists($parts[0], $subPages))
                && $this->module('collections')->exists($subPages[$parts[0]])
                ) {

                // pagination for blog module
                if ((int)$parts[1]) {
                    $slug = $parts[0];
                    $_REQUEST['page'] = $parts[1];
                }

                // sub page in different collection
                else {
                    $this->mp['pages'] = $subPages[$parts[0]];
                    $this->mp['subpage'] = $subPages[$parts[0]];
                    $slug = $parts[1];
                }

            } else return false;

        }

        if (empty($slug)) { // start page
            $filter = [
                'is_startpage' => true,
                'published' => true
            ];
        } else {

            $filter = [
                'published' => true,
            ];

            if ($this->retrieve('monoplane/multilingual')) {

                $lang = $this('i18n')->locale;
                $defaultLang = $this->retrieve('monoplane/i18n') ?? $this->retrieve('i18n', 'en');

                if ($this->mp['id'] != '_id' && $this->isLocalized() && $lang != $defaultLang) {
                    $filter[$this->mp['id'].'_'.$lang] = $slug;
                } else {
                    $filter[$this->mp['id']] = $slug;
                }

            } else {
                $filter[$this->mp['id']] = $slug;
            }

        }

        if (!$page = $this->app->module('collections')->findOne($this->mp['pages'], $filter, null, false, ['lang' => $this('i18n')->locale])) {

            return false;

        }

        $collection = $this->module('collections')->collection($this->mp['pages']);

        $fields = [];
        foreach ($collection['fields'] as $field) {
            $fields[$field['name']] = $field;
        }
        
        $type = $fields['content']['type'];
        if (in_array($type, ['__construct', '__call', '__invoke', '__get', 'initialize']))
            $type = 'index';

        $options = $fields['content']['options'] ?? [];

        // render content
        $page['content'] = (new \Monoplane\Helper\Fields($this->app))->$type($page['content'], $options);
        
        if (isset($page['add_blog_module']) && $page['add_blog_module'] == true) {
            
            $page['blog_module'] = $this->posts();
            
        }

        $this->mp['_id'] = $slug;

        $view = 'views:index.php';
        if ($path = $this->app->path('views:'.$this->mp['pages'].'.php')) {
            $view = $path;
        }

        // experimental: return rendered html (static) instead of rendered php (Lexy)
        if (isset($this->mp['static']) && $this->mp['static'] == true) {

            $hash = $slug . '_' . md5(json_encode($this->mp)) . '.html';

            if ($this->app->path('#tmp:'.$hash)) {
                return $this->app->filestorage->read('tmp://'.$hash);
            }

            $output = $this->render($view, ['mp' => $this->mp, 'page' => $page]);

            $this->app->filestorage->write('tmp://'.$hash, $output);

            return $output;

        }

        return $this->render($view, ['mp' => $this->mp, 'page' => $page]);

    }

    public function posts() {

        $_collection = $this->module('collections')->collection($this->mp['posts']);

        if (!$_collection) return false;
        
        $page = $this->param('page', 1);

        $limit = $this->retrieve('monoplane/blog_module/pagination', 10);
        $skip = ($page - 1) * $limit;

        $options = [
            'filter' => [
                'published' => true,
            ],
            'lang'  => $this('i18n')->locale,
            'limit' => $limit,
            'skip'  => $skip,
        ];

        $posts = $this->app->module('collections')->find($this->mp['posts'], $options);

        if (!$posts) {
            $this->app->response->status = 404;
            return;
        }

        $fields = [];
        foreach ($_collection['fields'] as $field) {
            $fields[$field['name']] = $field;
        }

        $type = $fields['content']['type'];
        if (in_array($type, ['__construct', '__call', '__invoke', '__get', 'initialize']))
            $type = 'index';

        $posts_options = $fields['content']['options'] ?? [];

        if (!empty($posts)) {
            foreach($posts as &$entry) {

                if (!empty($entry['excerpt'])) {
                    $entry['excerpt'] = (new \Monoplane\Helper\Fields($this->app))->$type($entry['excerpt'], $posts_options);
                }

                // skip this step if excerpt is present
                elseif (!empty($entry['content'])) {
                    $entry['content'] = (new \Monoplane\Helper\Fields($this->app))->$type($entry['content'], $posts_options);
                }

            }

        }

        // pagination
        $count = $this->app->module('collections')->count($this->mp['posts'], $options['filter']);

        return [
            'posts' => $posts,
            'pagination' => [
                'count' => $count,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($count / $limit),
            ]
        ];

    }

    public function error($status = '') {

        // To do: 401, 500

        switch ($status) {
            case '404':
                return $this->render('views:errors/404.php', ['mp' => $this->mp, 'page' => []]);
                break;
        }

    }

    public function getImage($options = []) {

        $src = $this->param('src', null);

        if (!$src) return false;

        // lazy uploads prefix if src is an assets id (has no dot in filename) or is mp asset
        if (strpos($src, '/modules/Monoplane') !== 0 && strpos($src, '.') !== false) {
            $src = '#uploads:'.$src;
        }

        $options = [
            'src' => $src,
            'mode' => $this->escape($this->param('m', 'bestFit')),
            'width' => intval($this->param('w', 800)),
            'height' => intval($this->param('h', null)),
            'quality' => intval($this->param('q', 80)),
            // 'output' => true, // browser caching didn't work anymore
        ];

        if ($this->param('blur')) {
            $options['filters']['blur'] = ['type' => 'gaussian', 'passes' => intval($this->param('blur', 5))];
        }

        // quick fix to prevent black backgrounds on servers with none-bundled gd
        // as soon as SimpleImage calls two methods, e. g. crop+resize, on a 
        // transparent image, the alpha channel gets destroyed
        // see https://github.com/claviska/SimpleImage/issues/236 for more info
        if (GD_BUNDLED === 0) {

            $ext = '';
            $src = $options['src'];
            if (strpos($src, '.') === false) {
                $asset = $this->app->storage->findOne('cockpit/assets', ['_id' => $src], ['mime' => true]);
                if ($asset) $ext = str_replace('image/', '', $asset['mime']);
            }
            else {
                $ext = strtolower(pathinfo($options['src'], PATHINFO_EXTENSION));
            }

            if (( $ext == 'png' || $ext == 'gif' ) && in_array($options['mode'], ['thumbnail', 'bestFit', 'resize', 'crop'])) {
                $options['mode'] = 'fitToWidth';
                $options['filters'] = null;
            }

        }

        // return $this->module('cockpit')->thumbnail($options); // browser caching didn't work anymore

        $thumbpath = $this->module('cockpit')->thumbnail($options);

        $ext = strtolower(pathinfo($thumbpath, PATHINFO_EXTENSION));

        $store = $ext == 'svg' ? 'uploads://' : 'thumbs://';
        $thumbpath = $store . '/' . str_replace($this->app->filestorage->getUrl($store), '', $thumbpath);

        $timestamp = $this->app->filestorage->getTimestamp($thumbpath);
        $gmt_timestamp = gmdate(DATE_RFC1123, $timestamp);

        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == strtotime($gmt_timestamp)) {
            header('HTTP/1.1 304 Not Modified');
            $this->app->stop();
        }

        $mime = \Lime\App::$mimeTypes[$ext];

        header("Content-Type: " . $mime);
        header('Content-Length: '.$this->app->filestorage->getSize($thumbpath));
        header('Last-Modified: ' . $gmt_timestamp);
        header('Expires: ' . gmdate(DATE_RFC1123, time() + 31556926));
        header('Cache-Control: max-age=31556926');
        header('Pragma: max-age=31556926');

        echo $this->app->filestorage->read($thumbpath);

        $this->app->stop();

    }

    protected function nav($collection = null, $options = null) {

        if (!$collection || !is_string($collection))
            $collection = $this->mp['pages'];

        if (!$options) {

            $options = [
                'filter' => [
                    'published' => true,
                ],
                'fields' => [
                    $this->mp['id'] => true,
                    'title' => true,
                    'navigation' => true,
                ],
            ];

            if ($this->retrieve('monoplane/multilingual')) {

                $lang = $this('i18n')->locale;
                $defaultLang = $this->retrieve('monoplane/i18n') ?? $this->retrieve('i18n', 'en');

                $options['lang'] = $lang;

                if ($lang != $defaultLang) {
                    $options['fields']['title_'.$lang] = true;
                    if ($this->mp['id'] != '_id') {
                        $options['fields'][$this->mp['id'].'_'.$lang] = true;
                    }
                }

            }

        }

        return $this->app->module('collections')->find($collection, $options);

    }

    protected function isLocalized() {

        if (isset($this->app['modules']['uniqueslugs'])) {

            return $this->retrieve('unique_slugs/localize/'.$this->mp['pages'], false);

        }

        // to do: other possible methods to localize slugs...

        return false;

    }

}
