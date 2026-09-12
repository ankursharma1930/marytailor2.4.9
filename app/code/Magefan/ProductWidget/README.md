# [Magento 2 Product Widget](https://magefan.com/magento-2-product-widget) by Magefan

This Magento 2 module allows to insert Product Blog inside WYSIWYG editor and other containers on Magento 2 Store.

## Storefront Demo
https://pw.demo.magefan.com/gear/bags.html
## Admin Panel Demo
https://pw.demo.magefan.com/admin/


## Requirements
  * Magento Community Edition 2.1.x-2.2.x or Magento Enterprise Edition 2.1.x-2.2.x

## Installation Method 1 - Installing via Composer (prefer)
  * Open command line
  * Using command "cd" navigate to your magento2 root directory
  * Run commands: 
```
  composer config repositories.magefan composer https://magefan/repo/
  composer require magefan/module-productwidget
  #Authentication Data can be found in your [Magefan Account](https://magefan.com/downloadable/customer/products/)
  php bin/magento setup:upgrade
  php bin/magento setup:di:compile
  php bin/magento setup:static-content:deploy
```


## Installation Method 2
  * Unzip Magefan Product Widget Extension Archive
  * In your Magento 2 root directory create a folder app/code/Magefan/ProductWidget
  * Copy files and folders from archive to that folder
  * In command line, using "cd", navigate to your Magento 2 root directory
  * Run commands:
```
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
```

## Support
If you have any issues, please [contact us](mailto:support@magefan.com)

## Need More Features?
Please contact us to get a quote
https://magefan.com/contact

## License
The code is licensed under [EULA](https://magefan.com/end-user-license-agreement).
