<?php

namespace Monoplane\Helper;

class MP extends \Lime\Helper {

    // add a value to a key in Lime registry
    public function add($key, $value) {

        $this->app->set($key, array_merge_recursive($this->app->retrieve($key, []), $value));

    }

    public function userStyles() {

        $styles = $this->app->retrieve('monoplane/user.css');

        if (empty($styles)) return;

        echo "\r\n<style>\r\n";

        foreach ($styles as $selector => $style) {
            if (is_numeric($selector) && is_string($style)) {
                echo $style . "\r\n";
                continue;
            }
            elseif (is_string($style)) {
                echo "$selector $style" . "\r\n";
            }
        }

        echo "</style>\r\n";

    }

    public function userScripts() {

        $scripts = $this->app->retrieve('monoplane/user.js');

        if (empty($scripts)) return;

        echo "\r\n<script>\r\n";

        foreach ($scripts as $script) {
            echo $script . "\r\n";
        }

        echo "</script>\r\n";

    }

}
