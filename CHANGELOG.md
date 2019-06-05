# Changelog

## 0.3.0

* removed language change via get parameter (`?lang=de`)
* removed demo page if collection doesn't exist
* added support for multilingual pages
  * pattern: `example.com/en/page-name`
* added support for sub pages in different collections
* added blog module
* added contact form controller (requires FormValidation Addon)
* added my personal base scss files
* added views, js and css for
  * contact form (php only, ajax is on the to-do-list)
  * video link to iframe conversion (js, css)
  * privacy notice (js, css)
  * gallery with lightbox (js, css)
  * blog module with pagination
  * and improved some views and css

## 0.2.4

* fixed wrong mime type detection for multiple image files
* added field type renderer
* added experimental static output
* added Lexy extension `@headerimage()`

## 0.2.3

* added svg mime type to `/getImage` route

## 0.2.2

* added cli commands

## 0.2.1

* initial commit - I did the versioning in private before publishing the whole thing
