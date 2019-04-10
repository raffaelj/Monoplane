<?php

namespace Monoplane\Helper;

class Fields extends \Lime\Helper {

    public function index($content = null, $options = []) {

        if (is_string($content)) {
            return $content;
        }

        if (is_array($content)) {
            return json_encode($content);
        }

        return '';

    }

    public function __call($name, $arguments) {

        if (is_callable([$this, $name]) && method_exists($this, $name)) {

            return call_user_func_array([$this, $name], $arguments);
        }

        return call_user_func_array([$this, 'index'], $arguments);

    }

    public function text($content = '', $options = []) {

        return '<p>' . $content . '</p>';

    }

    public function textarea($content = '', $options = []) {

        return '<p>' . nl2br($content) . '</p>';

    }

    public function wysiwyg($content = '', $options = []) {

        return $this->replaceRelativeLinksInHTML($content);

    }

    public function markdown($content = '', $options = [], $extra = false) {

        return $this->replaceRelativeLinksInHTML($this->app->module('cockpit')->markdown($content, $extra));

    }
/* 
    public function set($content = null, $options = []) {

        // to do...
        return $this->index($content);

    }

    public function repeater($content = null, $options = []) {

        // to do...
        return $this->index($content);

    }
 */
    public function replaceRelativeLinksInHTML($html) {

        if(empty(MP_BASE_URL)) return $html;
        
        $dom = new \DomDocument();

        // inspired by https://stackoverflow.com/a/45680712 and
        // https://stackoverflow.com/questions/4879946/how-to-savehtml-of-domdocument-without-html-wrapper#comment86181089_45680712

        // disable errors - workaround for HTML5 tags like "nav" or "header", https://stackoverflow.com/a/6090728
        libxml_use_internal_errors(true);

        $dom->loadHTML('<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body>' . $html . '</body></html>');

        libxml_clear_errors();

        $anchors = $dom->getElementsByTagName('a');

        foreach ($anchors as $a) {

            $href = $a->getAttribute('href');

            if (strpos($href, '/') === 0 && strpos($href, '//') === false)
                $a->setAttribute('href', MP_BASE_URL.$href);

        }

        return substr(trim($dom->saveHTML()), 199, -14);

    }

}
