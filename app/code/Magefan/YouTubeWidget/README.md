# [Magento 2 YouTube Widget](https://magefan.com/) by Magefan

This Magento 2 module allows you to insert YouTube video into WYSIWYG editor and also to any part of the page.

## Features
  * Flexible settings
  * Video lazy load

## Storefront Demo
https://ytw.demo.magefan.com/
## Admin Panel Demo
https://ytw.demo.magefan.com/admin/


## Requirements
  * Magento Community Edition 2.0.0-2.3.x or Magento Enterprise Edition 2.0.0-2.3.x

## Installation Method 1 - Installing via Composer (prefer)
  * Open command line
  * Using command "cd" navigate to your magento2 root directory
  * Run commands: 
```
  composer config repositories.magefan composer https://magefan.com/repo/
  composer require magefan/module-youtube-widget
  #Authentication Data can be found in your [Magefan Account](https://magefan.com/downloadable/customer/products/)
  php bin/magento setup:upgrade
  php bin/magento setup:di:compile
  php bin/magento setup:static-content:deploy
```


## Installation Method 2 (Long One)
  * Install Magefan Community Extension (https://github.com/magefan/module-community)
  * Unzip Extension Archive
  * In your Magento 2 root directory create a folder app/code/Magefan/YouTubeWidget
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
