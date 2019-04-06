<?php

$this->mp['content'] = (new \Parsedown())->text($this->app->filestorage->read('site://README.md'));
$this('mp')->add('monoplane.assets.top', [
    MP_BASE_URL.'/modules/Monoplane/assets/highlight/styles/default.css',
    MP_BASE_URL.'/modules/Monoplane/assets/highlight/styles/github.css',
]);
$this('mp')->add('monoplane.assets.bottom', [
    MP_BASE_URL.'/modules/Monoplane/assets/highlight/highlight.pack.js',
]);
$this('mp')->add('monoplane/user.js', [
    'hljs.initHighlightingOnLoad();',
]);
$this('mp')->add('monoplane/user.css', [
    'body > header h1 {height:0;width:0;padding:0;margin:0;font-size:0;text-indent:-9999px;}',
]);

$this->mp['id'] = 'url';
$this->mp['_id'] = '';
$this->mp['site'] = [
    'id' => 'url',
    '_id' => '',
    'site_name' => 'Monoplane',
    'description' => 'Monoplane is a small front-end for the fast and headless Cockpit CMS.',
    'logo' => [
        'path' => '/modules/Monoplane/assets/media/monoplane-logo.png',
    ],
];

$this->mp['nav'] = [
    [
        'title' => 'Source',
        'navigation' => 'main',
        'url' => 'https://github.com/raffaelj/Monoplane',
    ],
    [
        'title' => 'Cockpit',
        'navigation' => 'main',
        'url' => 'https://getcockpit.com/',
    ],
    [
        'title' => 'Cockpit Source',
        'navigation' => 'main',
        'url' => 'https://github.com/agentejo/cockpit',
    ],
    // [
        // 'title' => 'Imprint',
        // 'navigation' => 'footer',
        // 'url' => 'https://monoplane.rlj.me/imprint',
    // ],
    // [
        // 'title' => 'Privacy',
        // 'navigation' => 'footer',
        // 'url' => 'https://monoplane.rlj.me/privacy',
    // ],
    [
        'title' => 'Login',
        'navigation' => 'footer',
        'url' => '/login',
    ],
];
