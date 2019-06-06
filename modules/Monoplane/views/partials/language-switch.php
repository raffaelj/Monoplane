<?php
if (!isset($app['languages']) || !is_array($app['languages'])) return;

$locale      = $app('i18n')->locale;
$defaultLang = $app->retrieve('monoplane/i18n') ?? $app->retrieve('i18n', 'en');
$languages   = [];

foreach($app['languages'] as $languageCode => $name) {

    $slug = '';

    if ($languageCode == 'default') {
        $lang = $defaultLang;
    } else {
        $lang = $languageCode;
    }

    $active = $locale == $lang;

    $filter = [
        'published' => true,
        '_id' => $page['_id'] ?? '',
    ];

    $projection = [
        $mp['id'] => true,
        $mp['id'] . '_' . $lang => true
    ];

    $fieldsFilter = [
        'lang' => $lang
    ];

    $entry = $app->module('collections')->findOne($mp['pages'], $filter, $projection, false, $fieldsFilter);
    if (isset($entry[$mp['id']])) $slug = $entry[$mp['id']];

    $subpage = null;
    if (isset($mp['subpage'])) {
        $subpage = $app->retrieve('monoplane/blog_module/routes/route'.($lang == $defaultLang ? '' : '_'.$lang), null);
    }

    $languages[] = [
        'code' => $lang,
        'name' => $name,
        'active' => $active,
        'url' => MP_BASE_URL . '/' . $lang . '/' . ($subpage ? $subpage . '/' : '') . $slug,
    ];

}

?>

            <nav class="language-switch">
                <ul>
@foreach($languages as $lang)
                    <li>
@if($lang['active'])
                        <span title="{{ $lang['name'] }}">{{ $lang['code'] }}</span>
@else
                        <a class="{{ $lang['active'] ? 'active' : '' }}" href="{{ $lang['url'] }}" title="{{ $lang['name'] }}">{{ $lang['code'] }}</a>
@endif
                    </li>
@endforeach
                </ul>
            </nav>
