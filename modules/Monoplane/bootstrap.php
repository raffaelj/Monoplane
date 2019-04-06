<?php

// adjust some auto-detected directory routes to current dir, otherwise inbuilt
// functions from Lime\App, like pathToUrl() would return wrong paths
$this->set('docs_root', MP_DOCS_ROOT);
$this->set('base_url', MP_BASE_URL);
$this->set('base_route', MP_BASE_URL); // for reroute()
$this->set('site_url', $this->getSiteUrl(true)); // for pathToUrl(), which is used in thumbnail function

// rewrite filestorage paths to get correct image urls
$this->on('cockpit.filestorages.init', function(&$storages) {
    $storages['uploads']['url'] = $this->pathToUrl('#uploads:', true);
    $storages['thumbs']['url'] = $this->pathToUrl('#thumbs:', true);
});

// set paths
$this->path('views', __DIR__.'/views');
$this->path('mp_config', MP_DOCS_ROOT . '/config');

// register autoload classes in namespace Monoplane\Controller from
// `MP_DOCS_ROOT/Controller`, e. g.: `/Controller/Products.php`
spl_autoload_register(function($class){
    $class_path = MP_DOCS_ROOT.'/Controller'.str_replace(['Monoplane\Controller', '\\'], ['', '/'], $class).'.php';
    if(file_exists($class_path)) include_once($class_path);
});

// add Monoplane Helper, functions are available via $app('mp')->myFunction();
include_once(__DIR__.'/Helper/MP.php'); // because auto-load not ready yet
$this->helpers['mp'] = 'Monoplane\\Helper\\MP';

// pass custom layout file to LimeExtra
$this->layout = 'views:monoplane.php';

// add assets
$this->set('monoplane.assets.top', [
    MP_BASE_URL.'/modules/Monoplane/assets/css/style.min.css', // main style file
]);

// init + load i18n
$this('i18n')->locale = $this->retrieve('monoplane/i18n') ?? $this->retrieve('i18n', 'en');
$locale = $this->param('lang') ?? $_SESSION['lang'] ?? $this('i18n')->locale;
$this('i18n')->locale = $locale;
$this->set('monoplane/i18n', $locale);

if ($translationspath = $this->path("mp_config:i18n/{$locale}.php")) {
    $this('i18n')->load($translationspath, $locale);
}

// bind routes
$this->bind('/login', function() {
    $this->reroute(MP_ADMINFOLDER);
});

$this->bind('/getImage', function() {
    return $this->invoke('Monoplane\\Controller\\Pages', 'getImage');
});

$this->bind('/:slug', function($params) {
    return $this->invoke('Monoplane\\Controller\\Pages', 'index', ['slug' => $params['slug']]);
});

// extend lexy parser for custom image resizing
$this->renderer->extend(function($content){ // returns relative url of scaled logo
    return preg_replace('/(\s*)@logo\((.+?)\)/', '$1<?php echo MP_BASE_URL."/getImage?src=".urlencode($2)."&w=".$app->retrieve("monoplane/lexy/logo/width", 200)."&h=".$app->retrieve("monoplane/lexy/logo/height", 200)."&q=".$app->retrieve("monoplane/lexy/logo/quality", 80); ?>', $content);
});

$this->renderer->extend(function($content) { // returns relative url of image
    return preg_replace('/(\s*)@uploads\((.+?)\)/', '$1<?php echo MP_BASE_URL; $app->base("#uploads:" . $2); ?>', $content);
});

$this->renderer->extend(function($content){ // returns relative url of scaled image (thumbnail)
    return preg_replace('/(\s*)@thumbnail\((.+?)\)/', '$1<?php echo MP_BASE_URL."/getImage?src=".urlencode($2)."&w=".$app->retrieve("monoplane/lexy/thumbnail/width", 100)."&h=".$app->retrieve("monoplane/lexy/thumbnail/height", 100)."&q=".$app->retrieve("monoplane/lexy/thumbnail/quality", 70)."&m=".$app->retrieve("monoplane/lexy/thumbnail/mode", "thumbnail"); ?>', $content);
});

$this->renderer->extend(function($content){ // returns relative url of scaled image (image)
    return preg_replace('/(\s*)@image\((.+?)\)/', '$1<?php echo MP_BASE_URL."/getImage?src=".urlencode($2)."&w=".$app->retrieve("monoplane/lexy/image/width", 800)."&h=".$app->retrieve("monoplane/lexy/image/height", 800)."&q=".$app->retrieve("monoplane/lexy/image/quality", 80)."&m=".$app->retrieve("monoplane/lexy/image/mode", "bestFit"); ?>', $content);
});

// error handling
$this->on('after', function() {

    if (!$this->response->body || $this->response->body === 404)
        $this->response->status = 404;

    switch($this->response->status){
        case '404':
            $this->response->body = $this->invoke('Monoplane\\Controller\\Pages', 'error', ['status' => $this->response->status]);
            break;
    }

});

// load custom bootstrap file
if (file_exists(MP_CONFIG_DIR.'/bootstrap.php')) {
    include(MP_CONFIG_DIR.'/bootstrap.php');
}

// CLI
if (COCKPIT_CLI) {
    $this->path('#cli', __DIR__.'/cli');
}
