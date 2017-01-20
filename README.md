# thirty bees 
thirty bees is an opensource fork of PrestaShop. Our aim with this fork is to provide a feature set that merchants. We are rewriting a lot of the core modules and cleaning up the legacy code. We aim to provide a stable, feature rich ecommerce platform to grow businesses.

![thirty bees screenshot](https://cloud.githubusercontent.com/assets/6775736/22063185/c5ef8e3c-dd7d-11e6-923c-4b62ac404c86.png)


## Roadmap for thirty bees to version 1.0.0

**Overview of the general goal:**

To launch version 1.0.0 of Thirty Bees around February 1st 2017. In this version we are striving to fix as many bugs as possible, while maintaining compatibility with existing themes and modules. We want to provide the stablest platform for current users to migrate into. 

**New Features being added into v1.0.0 of thirty bees:**

* Ability to add CSS from the back office
* Ability to add JavaScript snippets from the back office
* Enhanced Favicon / device icon support
* Full page caching in the core, with the following caching mechanisms:
  * redis
  * memcached(d)
  * APCu
  * file system

**Core Modules:**

Core modules will be refactored to remove legacy code. Code that supports previous PrestaShop versions will be removed and refactored to support PHP 5.5 - PHP 7.1. 

 **Rewritten modules:**

* PayPal will totally be rewritten
* Authorize.net will be refactored

**New modules included in the core:**

* MailChimp sync
* Stripe (Credit cards, Apple Pay, AliPay, Bitcoins)
* Thirty Bees blog module

## Requirements
**General server requirements:**

- PHP 5.5 - PHP 7.1
- Apache, nginx or IIS
- Windows, Linux or OS X
- MySQL/MariaDB
- PHP extensions:
  - Required:
    - GD
    - intl
    - imap
    - SimpleXML
    - json
    - zip
    - MySQL (PDO only)
  - Recommended:
    - cURL
    - mbstring
    - opcache

## Installation
There is currently no release package available, but you can install from git. These are the instructions:
- Recursively clone the repository:
```shell
$ git clone https://github.com/thirtybees/ThirtyBees.git --recursive
```
- Then cd into the `ThirtyBees` folder
- Run composer to install the dependencies:
```shell
$ composer install
```
- Then install the software as usual, using either the web interface (https://example.com/install-dev) or cli (/install-dev/index_cli.php) 

## Contributing
See [CONTRIBUTING.md](CONTRIBUTING.md)
