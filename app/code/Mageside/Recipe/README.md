Magento 2 Recipe by Mageside
============================

####Support
    v1.3.6 - Magento 2.3.* - 2.4.*

####Change list
    v1.3.6 - Fixed recipe visibility after disabling.
    v1.3.4 - Added sorting to recipe list.
    v1.3.3 - Fixed operations MassAction in Recipe Review. Update filter option model.
    v1.3.2 - Fixed display of the button 'See more recipe' and duplicate recipes.
    v1.3.1 - Add image option filter for multistore.
    v1.3.0 - Added visibility for filters by store view.
    v1.2.19 - Сheck the activity of the module when creating SiteMap.
    v1.2.18 - Fixed sorting and filtering some fields in forms grid.    
    v1.2.17 - Added recipes metaData for Facebook and Twitter.
    v1.2.16 - Fixed sorting and filtering some fields in recipe and review grids.
    v1.2.15 - Fixed bugs.
    v1.2.14 - Magento 2.4 support checking (updated composer.json)
    v1.2.13 - Fix for installation scripts
    v1.2.12 - Added compatibility with Magento 2.4
    v1.2.11 - Added redirect without slash of the URL end
    v1.2.10 - Show Assigned Products from all Stores in Edit Recipe.
    v1.2.9 - Fixed bugs.
    v1.2.8 - Added fix module sequence / optimization images / added collection methods for recipe graphql 
    v1.2.7 - Added fix error to search any recipe titles in backend.
    v1.2.6 - Add recipes metaData.
    v1.2.5 - Add recipes to SiteMap.
    v1.2.4 - Fix options label, Added cache tags.
    v1.2.0 - Added widget, 
             added review ingredients before adding to cart, 
             added support of configurable products, 
             refactoring code
    v1.1.10 - Added fix add and view reviews
    v1.1.9 - Fix print preview recipe page
    v1.1.8 - Added update for saving url_rewrite and url_key. Needed for compatibility with PWA theme. 
    v1.1.7 - Added fix store view
    v1.1.6 - Added fix grid collection
    v1.1.5 - Added fix for saving recipe
    v1.1.4 - Added fix for saving recipe thumbnails  
    v1.1.3 - Added improvements in upgrade script
    v1.1.1 - Added Pinterest share button
    v1.1.0 - Ability to change writter for recipe. Print recipe functionality. Full support of translation from admin panel. 
    v1.0.20 - Fix for saving recipes for "allStores"
    v1.0.19 - Fixed issue deleting filter options
    v1.0.18 - Fix issue with unic "writer_url_key", added compatibility with Magento 2.3
    v1.0.17 - Review logic improvements
    v1.0.16 - Added translation file en_US
    v1.0.15 - Search logic improvements
    v1.0.14 - Added "desc" sorting for recipes list, added fix for description field
    v1.0.13 - Added update for urlKeys and required fields
    v1.0.12 - Fixed for multistore
    v1.0.11 - Images processing fixes
    v1.0.10 - Rich snippets improvements
    v1.0.8 - Fixed review availability and cooking time for Magento 2.2.5
    v1.0.6 - Fixed bug filtering recipies
    v1.0.4 - Fixed installer (added getTableName method for tables)
    v1.0.2 - Corrected product page link
    v1.0.0 - Start project

####Installation
    1. Download the archive.
    2. Make sure to create the directory structure in your Magento - 'Magento_Root/app/code/Mageside/Recipe'.
    3. Unzip the content of archive (use command 'unzip ArchiveName.zip') 
       to directory 'Magento_Root/app/code/Mageside/Recipe'.
    4. Run the command 'php bin/magento module:enable Mageside_Recipe' in Magento root.
       If you need to clear static content use 'php bin/magento module:enable --clear-static-content Mageside_Recipe'.
    5. Run the command 'php bin/magento setup:upgrade' in Magento root.
    6. Run the command 'php bin/magento setup:di:compile' if you have a single website and store, 
       or 'php bin/magento setup:di:compile-multi-tenant' if you have multiple ones.
    7. Clear cache: 'php bin/magento cache:clean', 'php bin/magento cache:flush'
