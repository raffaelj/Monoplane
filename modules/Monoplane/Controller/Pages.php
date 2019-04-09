<?php

namespace Monoplane\Controller;

class Pages extends \LimeExtra\Controller {

    public function before() {

        $this->mp = array_replace_recursive([
            'id' => '_id',
            'pages' => 'pages',
            'site' => [],
        ],$this->retrieve('monoplane', []));

        $this->mp['nav'] = $this->nav();

        $this->trigger('monoplane.pages.before', [&$this->mp]);

    }

    // follow the naming convention of Cockpit and name it 'index'
    public function index($slug = '') {

        $options = [
            'filter' => [
                $this->mp['id'] => $slug,
                'published' => true,
            ],
        ];

        if (empty($slug)) { // start page
            $options = [
                'filter' => [
                    'is_startpage' => true,
                    'published' => true
                ],
            ];
        }

        if (!$page = $this->app->module('collections')->findOne($this->mp['pages'], $options['filter'], null, false, ['lang' => $this('i18n')->locale])) {

            if (!$this->app->module('collections')->exists($this->mp['pages'])) {
                include(MP_DOCS_ROOT.'/modules/Monoplane/demo.php');
                return $this->render('views:demo.php', ['mp' => $this->mp]);
            }

            return false;

        }

        // replace relative links in wysiwyg field if site is in subdirectory
        if (!empty($page['content']) && MP_BASE_URL !== '' && strpos($page['content'], 'href="/') !== false) {
            $page['content'] = $this->replaceRelativeLinksInHTML($page['content']);
        }

        $this->mp['_id'] = $slug;

        return $this->render('views:index.php', ['mp' => $this->mp, 'page' => $page]);

    }

    public function error($status = '') {

        // To do: 401, 500

        switch ($status) {
            case '404':
                return $this->render('views:errors/404.php', ['mp' => $this->mp]);
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
                $ext = pathinfo($options['src'], PATHINFO_EXTENSION);
            }

            if (( $ext == 'png' || $ext == 'gif' ) && in_array($options['mode'], ['thumbnail', 'bestFit', 'resize', 'crop'])) {
                $options['mode'] = 'fitToWidth';
                $options['filters'] = null;
            }

        }

        // return $this->module('cockpit')->thumbnail($options); // browser caching didn't work anymore

        $thumbpath = $this->module('cockpit')->thumbnail($options);

        $ext = pathinfo($thumbpath, PATHINFO_EXTENSION);

        $store = $ext == 'svg' ? 'uploads://' : 'thumbs://';
        $thumbpath = $store . '/' . str_replace($this->app->filestorage->getUrl($store), '', $thumbpath);

        $timestamp = $this->app->filestorage->getTimestamp($thumbpath);
        $gmt_timestamp = gmdate(DATE_RFC1123, $timestamp);

        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == strtotime($gmt_timestamp)) {
            header('HTTP/1.1 304 Not Modified');
            $this->app->stop();
        }

        header("Content-Type: image/" . ($ext == 'svg' ? 'svg+xml' : $ext));
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

            // language filter doesn't work with fields projection
            // quick fix to make it work
            $lang = $this('i18n')->locale !== 'en' ? '_' . $this('i18n')->locale : '';
            if (!empty($lang)) {
                $options['lang'] = $this('i18n')->locale;
                $options['fields']['title'.$lang] = true;
            }

        }

        return $this->app->module('collections')->find($collection, $options);

    }

    protected function replaceRelativeLinksInHTML($html) {

            $dom = new \DomDocument();

            // inspired by https://stackoverflow.com/a/45680712 and
            // https://stackoverflow.com/questions/4879946/how-to-savehtml-of-domdocument-without-html-wrapper#comment86181089_45680712

            $dom->loadHTML('<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body>' . $html . '</body></html>');

            $anchors = $dom->getElementsByTagName('a');

            foreach ($anchors as $a) {

                $href = $a->getAttribute('href');

                if (strpos($href, '/') === 0)
                    $a->setAttribute('href', MP_BASE_URL.$href);

            }

            return substr(trim($dom->saveHTML()),199,-14);

    }

}
