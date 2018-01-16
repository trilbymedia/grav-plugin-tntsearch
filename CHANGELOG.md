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
1. [](#bugfix)
    * Fixed JS issue when at login page
    * Fixed results showing on load for drop-downs, instead of in_page only view [#10](https://github.com/trilbymedia/grav-plugin-tntsearch/issues/10)
1. [](#improved)
    * Allow the ability to pass a `placeholder` to the  `partials/tntsearch.html.twig` template
    * Moved 'fuzzy' option as independent option

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
