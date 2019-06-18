# Monoplane

**I decided, that the code base is ugly and I rewrote the whole thing.**

> CpMultiplane is the refactored version of Monoplane. The code base was ugly, it was designed for very simple portfolio websites with a few pages and it didn't really support multilingual setups.

If you used or use Monoplane and find bugs, I might fix them, but I will concentrate on [CpMultiplane][13]. The code base is cleaner, it supports multilingual pages by default and it has much more features...

Most options from Monoplane will also work in CpMultiplane, but a lot of the config variables changed.

--------------------------------------------------------------------------------

Monoplane is a small PHP front end for the fast and headless [Cockpit CMS][1]. It is no Cockpit addon. Don't copy it in the cockpit addon folder. It is designed to use Cockpit as a library to keep the idea of having a headless CMS. When calling Cockpit directly (UI or API), it has no clue about Monoplane in the background.

Monoplane is in alpha state. Some options may change, but I try my best to keep it backwards compatible. Feel free to file issues or to send pull requests.

[Demo][12]

## Requirements

* PHP >= 7.0
* PDO + SQLite (or MongoDB)
* GD extension
* mod_rewrite, mod_versions enabled (on apache)

make also sure that `$_SERVER['DOCUMENT_ROOT']` exists and is set correctly.

## Installation

1. Download Monoplane and put the monoplane folder in the root of your web project - `MP_DOCS_ROOT`.
2. Download Cockpit and put the cockpit folder in a subdirectory of the root of your web project - `MP_DOCS_ROOT/cockpit`
3. Follow the [installation instructions of Cockpit][2].
4. Install optional cockpit addons, e. g. [UniqueSlugs][3], [FormValidation][4], [EditorFormats][5], [SelectRequestOptions][6]
4. Create a collection "pages" with these fields:
  * "title" (type: text)
  * "content" (type: wysiwyg)
  * "published" (type: boolean)
  * "is_startpage" (type: boolean)
  * "navigation" (type: multipleselect, options: `{"options":["main","footer"]}`)
5. Adjust your settings
6. Add your content
7. Change everything, if you don't like the defaults

Now login with admin/admin, change your password and start your work.

If you don't like clicking, use the CLI commands. Scroll down or [click here](#fast-cli-installation) ;-).

## Settings

The fastest way to change some defaults, is to add some values to `MP_DOCS_ROOT/cockpit/config/config.yaml`:

```yaml
monoplane:
    id: slug                    # the field name for slugs, default: _id
    i18n: de                    # default: cockpit i18n or en
    pages: collection_name      # collection name for pages, default: pages
    site:
        site_name: My Site      # site name
        description: for SEO    # fallback if no page description exists
        logo:
            _id: asset_id       # asset id or asset path
            path: /img/logo.png # or a path relative to your base
    lexy:                       # Monoplane extends the Lexy renderer
        logo:                   # @logo('assets_id or path')
            width: 200
            height: 200
            quality: 80
        thumbnail:              # @thumbnail('assets_id or path')
            width: 100
            height: 100
        image:                  # @image('assets_id or path')
            width: 800
```

If you change some settings and your page doesn't update, clear your cache in *Settings --> System --> Cache --> click trash icon* or just call `/cockpit/call/cockpit/clearCache?acl=qwe` while you are logged in and have cockpit manage rights. If you didn't rename the cockpit dir, the full url looks a bit weird with a doubled `/cockpit`, e. g. `https://example.com/cockpit/cockpit/call/cockpit/clearCache?acl=qwe`.

## Reserved routes

* `/login` - Calling `example.com/login` reroutes to the admin folder, e. g. `example.com/cockpit`
* `/getImage` - Calling `/getImage?src=assets_id?w=100&h=100&m=thumbnail` returns images/thumbnails with predefined settings, that can be adjusted with params

## How to change everything

Just add a custom bootstrap to `MP_DOCS_ROOT/config/bootstrap.php`

```php
<?php

$this->path('views', MP_DOCS_ROOT . '/views');  // reset Monoplane views dir and add your own
$this->layout = 'views:fancy.php';              // pass a default layout file to Lexy renderer

// bind routes to https://example.com/products/products_id_or_slug
$this->bind('/products/:slug', function($params) {
    return $this->invoke('Monoplane\\Controller\\Products', 'product', ['slug' => $params['slug']]);
});

// add custom color css
$css = [];
foreach ($this->module('collections')->find('colors') as $color) {
    $css[] = '.mp-color-' . $color['name'] . ' {background-color:' . $color['color'] . ';}';
}
$this('mp')->add('monoplane/user.css', $css);

// add assets
$this->set('monoplane.assets.top', []);                       // clear default styles
$this('mp')->add('monoplane.assets.top', [
    MP_BASE_URL.'/assets/css/style.min.css',                  // main style file
    MP_BASE_URL.'/assets/lib/wa-mediabox/wa-mediabox.min.css' // gallery lightbox
]);
$this('mp')->add('monoplane.assets.bottom', [
    MP_BASE_URL.'/assets/lib/wa-mediabox/wa-mediabox.min.js'  // gallery lightbox
]);

// change site settings via Singleton
$this->on('monoplane.pages.before', function(&$mp) {
    $mp['site'] = $this->module('singletons')->getData('config') ?? [];
});

// load i18n from FormValidation addon
$locale = $this('i18n')->locale;
if ($translationspath = $this->path("#config:formvalidation/i18n/{$locale}.php")) {
    $this('i18n')->load($translationspath, $locale);
}
```

## i18n

Change `MP_DOCS_ROOT/cockpit/config/config.yaml` to

```yaml
monoplane:
    i18n: de
```

or build your own logic to change the current language.

Adding the query parameter `?lang=de` to the called url changes the language, but it doesn't save it when clicking on another link without the query parameter. I'm not sure about the best logic for multilingual websites (usability, SEO etc.).

For i18n strings of your base application add a language file to `MP_DOCS_ROOT/config/i18n/de.php`, e. g.:

```php
<?php return array (

    // 404
    'Page not found' => 'Seite nicht auffindbar',
    'Something went wrong. This site doesn\'t exist.' => 'Etwas ist schiefgegangen. Diese Seite existiert nicht.',
    'back to start page' => 'Zurück zur Startseite',

    // copyright notice
    'built with' => 'erstellt mit',
    'since' => 'seit',

);
```

## Change cockpit folder name and custom constants

If you want to rename the cockpit folder, e. g. to `admin`, add a file `MP_DOCS_ROOT/defines.php` with

```
<?php

define('MP_ADMINFOLDER', 'admin');
```

If you have edge cases and need custom constants, change them here too.

## How to add custom controllers

Just add a class in `MP_DOCS_ROOT/Controller/Fancy.php` with this content:

```php
<?php

namespace Monoplane\Controller;

class Fancy extends \LimeExtra\Controller { 
    // do some fancy stuff
}
```

Or if you want to use the Pages functions, too:

```php
<?php

namespace Monoplane\Controller;

class Fancy extends Pages { 
    // do some fancy stuff
}
```

Or add it as an addon in `MP_DOCS_ROOT/addons/MyFancyAddon/Controller/Fancy.php`.

```php
<?php

namespace MyFancyAddon\Controller;

class Fancy extends \LimeExtra\Controller { 
    // do some fancy stuff
}
```

If you follow the naming schema above, your classes are in the autoload registry and you don't have to include them manually.

## Fast CLI installation

**Don't copy and paste everything!** Read it, understand and modify it to your needs.

```bash
cd html

# base
git clone https://github.com/raffaelj/Monoplane.git .
git clone https://github.com/agentejo/cockpit.git cockpit

# install addons
git clone https://github.com/raffaelj/cockpit_UniqueSlugs.git cockpit/addons/UniqueSlugs

# check for dependencies
./mp check

# Use other cli commands to import collections or singletons
# All cockpit cli commands should work with `./mp`, but you can call `cockpit/cp` instead, too.

# create cockpit config dir
mkdir -p cockpit/config

cat > cockpit/config/config.yaml <<EOF
app.name: Monoplane
languages:
    de: Deutsch
unique_slugs:
    collections:
        pages: title
EOF

# i18n
mkdir -p cockpit/config/cockpit/i18n
wget -O cockpit/config/cockpit/i18n/de.php https://raw.githubusercontent.com/agentejo/cockpit-i18n/master/de.php

# Monoplane i18n
mkdir -p config/i18n
cat > config/i18n/de.php <<EOF
<?php return [
    'Page not found' => 'Seite nicht auffindbar',
    'Something went wrong. This site doesn\'t exist.' => 'Etwas ist schiefgegangen. Diese Seite existiert nicht.',
    'back to start page' => 'Zurück zur Startseite',
    'built with' => 'erstellt mit',
    'since' => 'seit',
];
EOF

# This is the last step, because it requires a user input. 

# create admin user, type a email, a password and press Enter
./mp account/create --user raffael --name Raffael
```

## Copyright and License

Copyright 2019 Raffael Jesche under the MIT license.

See [LICENSE][11] for more information.

## Credits and third party resources

Without Cockpit, Monoplane couldn't exist. Thanks to [Artur Heinze][7] and to all [contributors][8].

I used [highlight.js][9] for code highlighting on the demo page on startup, which is released under the [BSD License][10].


[1]: https://github.com/agentejo/cockpit/
[2]: https://github.com/agentejo/cockpit/#installation
[3]: https://github.com/raffaelj/cockpit_UniqueSlugs
[4]: https://github.com/raffaelj/cockpit_FormValidation
[5]: https://github.com/pauloamgomes/CockpitCms-EditorFormats
[6]: https://github.com/raffaelj/cockpit_SelectRequestOptions
[7]: https://github.com/aheinze
[8]: https://github.com/agentejo/cockpit/graphs/contributors
[9]: https://highlightjs.org
[10]: https://github.com/highlightjs/highlight.js/blob/master/LICENSE
[11]: https://github.com/raffaelj/Monoplane/blob/master/LICENSE
[12]: https://monoplane.rlj.me
[13]: https://github.com/raffaelj/CpMultiplane
