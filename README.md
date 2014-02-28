# MudSnips - Code Snippet Software

## About
MudSnips is a simple way to manage snippets of source code.

## Installation
You'll need Composer to install the dependancies.
Run `composer install` in the root dir to install everything.
Create your database using data/db.sql as a guide.
Create the dirs data/smarty/cache, data/smarty/templates_c, data/smarty/configs
Visit the site in your browser and fill in the blanks. (will auto redirect to the installer page)
You can delete the install.php after installation, but it is coded to
redirect to the main page if it has already been ran and a config produced.
Profit!?!

## Configuration
All config is in config.php.
Options that aren't listed in the installer are:
* ALLOW_REGISTRATIONS - set to false after you've created your account if you don't want random people registering.
* You can also set up custom configurations for Slim or phpActiveRecord or GeSHi if you want, refer to their docs for help with that.

## License
GPL-3, see LICENSE.txt for details.

## Powered by
* Mootools
* PureCSS
* Slim
* phpactiverecord
* GeSHi
* Smarty
