# v2.0.4
## 09/21/2018

1. [](#new)
    * Added new `tntsearch: index: true|false` page header option to skip specific pages
1. [](#bugfix)
    * Skip indexing of pages with `redirect` set in page header [#21](https://github.com/trilbymedia/grav-plugin-tntsearch/issues/21)

# v2.0.3
## 08/16/2018

1. [](#new)
    * New option to allow disabling of page events, manual updates will be required to pick up changes
1. [](#bugfix)
    * Don't remove the X button if `built_in_css` is `false`

# v2.0.2
## 07/20/2018

1. [](#bugfix)
    * Ensure that credentials are passed in when searching via `fetch`
    * Compressed JS for better performance

# v2.0.1
## 05/21/2018

1. [](#bugfix)
    * Potential fix for history conflicts.

# v2.0.0
## 05/11/2018

1. [](#new)
    * Refactored TNTSearch to allow core classes to be extensible by other plugins
    * Added `phrases` search support [#32](https://github.com/trilbymedia/grav-plugin-tntsearch/pull/32)
1. [](#improved)
    * Defaulted TNTSearch to search **all pages** out of the box. This should be tweaked though
    * Added auto-focus to search input [#28](https://github.com/trilbymedia/grav-plugin-tntsearch/pull/28)
    * Added option to control `powered by` [#34](https://github.com/trilbymedia/grav-plugin-tntsearch/pull/34)
    * Added a timer on CLI index command
    * Exposing `GravTNTSearch` to the browser for JS manipulation
    * Dispatching `tntsearch:start` and `tntsearch:done` events when starting/rendering results
    * README.md typo fixes
1. [](#bugfix)
    * Implemented options as default values that were being ignored
    * Fixed missing `break` in foreach [#33](https://github.com/trilbymedia/grav-plugin-tntsearch/pull/33)
    * Add missing `use` statement [#41](https://github.com/trilbymedia/grav-plugin-tntsearch/pull/41)   

# v1.2.5
## 03/07/2018

1. [](#improved)
    * Only update the a page on save if it exists in the current filter and is therefore eligible to be indexed\
    * Removed Admin dependency, it works fine without admin too, just need to use CLI

# v1.2.4
## 02/14/2018

1. [](#bugfix)
    * Fix issue with admin saving 'string' for filter [#25](https://github.com/trilbymedia/grav-plugin-tntsearch/issues/25)

# v1.2.3
## 02/14/2018

1. [](#bugfix)
    * Missing comma in Admin JS breaking quick-tray reindexing

# v1.2.2
## 02/09/2018

1. [](#improved)
    * Updated TNTSearch to use version `1.3.1` of TNTSearch library for PHP 7.2 compatibility [#24](https://github.com/trilbymedia/grav-plugin-tntsearch/issues/24)
1. [](#bugfix)
    * Fixed URI `hash` getting unintentionally removed by TNTSearch [#15](https://github.com/trilbymedia/grav-plugin-tntsearch/pull/15)
    * Fixed issue with param separator needed for Windows [#16](https://github.com/trilbymedia/grav-plugin-tntsearch/pull/16)
    * Fixed placeholder format in blueprint [#18](https://github.com/trilbymedia/grav-plugin-tntsearch/pull/18)

# v1.2.1
## 01/16/2018

1. [](#new)
    * Added `onTNTSearchReIndex()` that you can fire from any plugin to reindex everything
1. [](#bugfix)
    * Fixed an XSS exploit in query    

# v1.2.0
## 10/29/2017

1. [](#new)
    * Reworked JS to VanillaJS [#12](https://github.com/trilbymedia/grav-plugin-tntsearch/pull/12)
    * Implemented live URI / history refresh when typing in the field
    * Added new 'auto' setting for search_type that automatically detects 'basic' or 'boolean'.
    * It is now possible to force a search_type mode whether it's `basic` or `boolean`
    * Updated to TNTSearch Library to v1.1.0             
1. [](#improved)
    * Allow the ability to pass a `placeholder` to the  `partials/tntsearch.html.twig` template
    * Moved 'fuzzy' option as independent option
1. [](#bugfix)
    * Fixed JS issue when at login page
    * Fixed results showing on load for drop-downs, instead of in_page only view [#10](https://github.com/trilbymedia/grav-plugin-tntsearch/issues/10)

# v1.1.0
## 08/22/2017

1. [](#new)
    * Extensible output JSON support via new `onTTNTSearchQuery()` event.
    * Added a 'powered-by' link that can be disabled via configuration
    * Improved docs by including instructions on how to use CLI to index. 
    
# v1.0.1
## 08/22/2017

1. [](#new)
    * Changed cartoon bomb icon with more friendly version (binoculars) [#4](https://github.com/trilbymedia/grav-plugin-tntsearch/issues/4)
    * Added the ability to disable CSS and JS independently [#3](https://github.com/trilbymedia/grav-plugin-tntsearch/issues/3)

# v1.0.0
## 08/16/2017

1. [](#new)
    * Initial release...
