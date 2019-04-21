# thirty bees
[![Build Status](https://travis-ci.org/thirtybees/thirtybees.svg?branch=master)](https://travis-ci.org/thirtybees/thirtybees)
[![Crowdin](https://d322cqt584bo4o.cloudfront.net/thirty-bees/localized.svg)](https://crowdin.com/project/thirty-bees)
[![Gitter](https://img.shields.io/gitter/room/thirtybees/General.svg)](https://gitter.im/thirtybees/General)

thirty bees is an open-source fork of PrestaShop 1.6. Our aim with this fork is to provide a feature set that merchants need. We are rewriting a lot of the core modules and cleaning up the legacy code. We aim to provide a stable, feature-rich e-commerce platform to grow businesses.
## Supporters

thirty bees is commited to being free and open source. We are also committed to making all software that thirty bees develops free and open source. For that reason we have setup a [Patreon](https://www.patreon.com/thirtybees) page so our community can help support us. You can [view our current list of supporters here](https://github.com/thirtybees/thirtybees/blob/1.0.x/SUPPORTERS.md) which will be shipped will all thirty bees versions moving forward.

![thirty bees screenshot](https://cloud.githubusercontent.com/assets/6775736/22063185/c5ef8e3c-dd7d-11e6-923c-4b62ac404c86.png)


## Roadmap for thirty bees version 1.0.x

**Overview of the general goal:**

With version 1.0.x we are striving to fix as many bugs as possible, while maintaining compatibility with existing themes and modules. We want to provide the stablest platform for current users to migrate into.

**New Features being added into v1.0.x of thirty bees:**

* Ability to add CSS from the back office
* Ability to add JavaScript snippets from the back office
* Enhanced Favicon / device icon support
* Full page caching in the core, with the following caching mechanisms:
  * redis
  * memcache(d)
  * APCu
  * file system

**Native Modules:**

Native modules will be refactored to remove legacy code. Code that supports previous PrestaShop versions is currently being removed and refactored to support PHP 5.5 - PHP 7.2.

**Rewritten modules:**

* PayPal has been rewritten
* Authorize.net has been refactored

**New native modules:**

* MailChimp (including e-commerce features)
* Stripe (Credit cards, Apple Pay, AliPay, Bitcoins)
* thirty bees blog module
* tawkto

### Updated roadmap
You can find the latest version of the roadmap here: https://thirtybees.com/roadmap

## Requirements
Support for these general requirements (except recommendations) gets tested during installation, so one can simply try to proceed. A proceeding installation means all requirements are met.

- PHP 5.6 - PHP 7.2
- Apache or nginx
- Linux or MacOS
- MySQL 5.5.3+ or MariaDB 5.5+
- PHP extensions:
  - Required:
    - bcmath
    - gd
    - json
    - mbstring
    - openssl
    - mysql (PDO only)
    - xml (SimpleXML, DOMDocument)
    - zip
  - Recommended:
    - imap (for allowing to use an IMAP server rather than PHP's built-in mail function)
    - curl (for better handling of background HTTPS requests)
    - opcache (not mandatory because some hosters turn this off in favor of other caching mechanisms)
    - apcu/redis/memcache(d) (for the (currently incomplete) full page cache)

## Browser support

| [<img src="https://raw.githubusercontent.com/godban/browsers-support-badges/master/src/images/edge.png" alt="IE / Edge" width="16px" height="16px" />](http://godban.github.io/browsers-support-badges/)</br>IE / Edge | [<img src="https://raw.githubusercontent.com/godban/browsers-support-badges/master/src/images/firefox.png" alt="Firefox" width="16px" height="16px" />](http://godban.github.io/browsers-support-badges/)</br>Firefox | [<img src="https://raw.githubusercontent.com/godban/browsers-support-badges/master/src/images/chrome.png" alt="Chrome" width="16px" height="16px" />](http://godban.github.io/browsers-support-badges/)</br>Chrome | [<img src="https://raw.githubusercontent.com/godban/browsers-support-badges/master/src/images/safari.png" alt="Safari" width="16px" height="16px" />](http://godban.github.io/browsers-support-badges/)</br>Safari | [<img src="https://raw.githubusercontent.com/godban/browsers-support-badges/master/src/images/opera.png" alt="Opera" width="16px" height="16px" />](http://godban.github.io/browsers-support-badges/)</br>Opera | [<img src="https://raw.githubusercontent.com/godban/browsers-support-badges/master/src/images/safari-ios.png" alt="iOS Safari" width="16px" height="16px" />](http://godban.github.io/browsers-support-badges/)</br>iOS Safari | [<img src="https://raw.githubusercontent.com/godban/browsers-support-badges/master/src/images/chrome-android.png" alt="Chrome for Android" width="16px" height="16px" />](http://godban.github.io/browsers-support-badges/)</br>Chrome for Android |
| --------- | --------- | --------- | --------- | --------- | --------- | --------- |
| IE9, IE10, IE11, Edge| 30+ | 30+ | 9+ | 36+ | 9+ | 30+ |

Browserlist string: <code>[defaults, ie >= 9, ie_mob >= 10, edge >= 12, chrome >= 30, chromeandroid >= 30, android >= 4.4, ff >= 30, safari >= 9, ios >= 9, opera >= 36](http://browserl.ist/?q=defaults%2C+ie+%3E%3D+9%2C+ie_mob+%3E%3D+10%2C+edge+%3E%3D+12%2C+chrome+%3E%3D+30%2C+chromeandroid+%3E%3D+30%2C+android+%3E%3D+4.4%2C+ff+%3E%3D+30%2C+safari+%3E%3D+9%2C+ios+%3E%3D+9%2C+opera+%3E%3D+36)</code>

## Installation
You can install the master or follow a [release package](https://github.com/thirtybees/thirtybees/releases)
- Recursively clone the repository and choose tag release version number from the -b parameter:
```shell
$ git clone https://github.com/thirtybees/thirtybees.git --recursive -b #.##
```
- Then cd into the `thirtybees` folder
- Run composer to install the dependencies:
```shell
$ composer install
```
- Then install the software as usual, using either a web browser (https://example.com/install-dev)
- Or install via command line
```shell
$  php install-dev/index_cli.php --newsletter=1 --language=en --country=us --domain=thirty.bees:8888 --db_name=thirtybees --db_create=1 --name=thirtybees --email=test@thirty.bees --firstname=thirty --lastname=bees --password=thirtybees
```
- Arguments available:
```
--step          all / database,fixtures,theme,modules,addons_modules    (Default: all)
--language      Language iso code                                       (Default: en)
--all_languages Install all available languages                         (Default: 0)
--timezone                                                              (Default: Europe/Paris)
--base_uri                                                              (Default: /)
--domain                                                                (Default: localhost)
--db_server                                                             (Default: localhost)
--db_user                                                               (Default: root)
--db_password                                                           (Default: )
--db_name                                                               (Default: thirtybees)
--db_clear      Drop existing tables                                    (Default: 1)
--db_create     Create the database if not exist                        (Default: 0)
--prefix                                                                (Default: tb_)
--engine        InnoDB                                                  (Default: InnoDB)
--name                                                                  (Default: thirty bees)
--activity                                                              (Default: 0)
--country                                                               (Default: fr)
--firstname                                                             (Default: John)
--lastname                                                              (Default: Doe)
--password                                                              (Default: 0123456789)
--email                                                                 (Default: pub@thirtybees.com)
--license       Show thirty bees license                                (Default: 0)
--newsletter    Get news from thirty bees                               (Default: 1)
--send_email    Send an email to the administrator after installation   (Default: 1)
```

## Contributing
See [CONTRIBUTING.md](CONTRIBUTING.md)
