# Contributing to thirty bees
thirty bees is an open-source e-commerce solution, forked from [PrestaShop 1.6.x](https://github.com/PrestaShop/PrestaShop/tree/1.6.1.x) . We'd like to encourage everyone to participate in the project, including contributing your own improvements to the source code.
 
## Procedures
 In order to contribute to this project, you need to be familiar with Git, an open source version control system used extensively by thirty bees developers, as well as GitHub:
 - A nice tutorial about Git can be found here: https://try.github.io/
 - GitHub help: https://help.github.com/
 
 Contributing to this project via Git can be done as follows:
 - Fork this project to your own GitHub account
 - Recursively clone this project to your local machine (use e.g. `git clone git@github.com:username/ThirtyBees.git --recursive`)
 - Optionally install the dependencies of this project to test it on your local machine (`composer install` in the root directory)
 - Create an issue in the remote repository you plan on coding.
 - Add the base repository as a `git remote`. Read [**Github - Configuring a remote for a fork**](https://help.github.com/articles/configuring-a-remote-for-a-fork/)
 - Now read [**Github Syncing Your Fork**](https://help.github.com/articles/syncing-a-fork/) 
 - Create your own local branch and give it a name of an issue. Such as **issue01**. (`git checkout -b issue01`)
 - Make your changes
 - Commit (`git commit -m "Commit message"`). We do not have a commit message norm, but do make sure you use the [present tense](https://en.wikipedia.org/wiki/Present_tense)!
 - Then push the commit to your own fork (`git push -u origin issue01`)
 - Visit either the fork or the thirty bees repository and GitHub should ask you to make a pull request. Follow this procedure and wait for one of our developers to include your changes into the codebase or tell you about possible improvements your pull request might need. 
 
 That's it. Thank you for your contribution to [**thirty bees**](https://thirtybees.com)!
 
## Coding standards
 We like to aim for a very high quality open source e-commerce platform. This means that we need to implement high quality standards, guidelines and coding styles that should be used by everyone participating in the project, at all times. Not abiding by the project's coding standards may be a reason to decline your contribution, so be sure to read this section in order to maximize the chance of your changes to land in thirty bees' codebase. 
 The majority of thirty bees is written in PHP, but in our codebase you will also find JavaScript, HTML, CSS, Smarty templates, SQL, XML and JSON. For these languages we use the following code standards:
 - PHP: [Symfony Standards](http://symfony.com/doc/current/contributing/code/standards.html) PLUS [shorthand aligned arrays](https://github.com/thirtybees/ThirtyBees/blob/de63e54d405c6e3c4660a846684937868838732f/classes/Address.php#L122-L149) MINUS [yoda conditions](https://en.wikipedia.org/wiki/Yoda_conditions)
 - JavaScript: [Airbnb JavaScript style](https://github.com/airbnb/javascript)
 - HTML, XML, CSS and Smarty templates: [Mark Otto's coding standards](http://codeguide.co/)
 - SQL: See [SQL Guidelines](#sql-guidelines) below
 - JSON: [Google JSON Style Guide](https://google.github.io/styleguide/jsoncstyleguide.xml)
 
### SQL Guidelines
#### Table names
1. Table names must begin with thirty bees' `_DB_PREFIX_` prefix.
2. Table names must have the same name as the object they reflect: `ps_cart` (replace `ps_` with your own prefix).
3. Table names are singular: `ps_order`
4. Language data has to be stored in a table named exactly like the object's table, but also has the `_lang` suffix: `ps_product_lang`.
5. The same goes for specific shop data. These tables require the `_shop` suffix.

#### SQL Query
1. For simple queries the `DbQuery` class MUST be used:
    ```php
    $sql = new DbQuery();
    $sql->select('p.`name`');
    $sql->from('product', 'p');
    $sql->innerJoin('product_lang', 'pl', 'p.`id_lang` = pl.`id_lang`');
    $sql->where('pl.`id_lang` = 1');
    ```

2. When referring to an `ObjectModel`s primary key or table name, use the escaped `$definition` property:
    ```php
    $sql = new DbQuery();
    $sql->select('p.`'.bqSQL(self::$definition['primary']).'`');
    $sql->from(bqSQL(self::$definition['table']), 'p');
    $sql->where('p.`'.bqSQL(self::$definition['primary']).'` = 1');
    ``` 

3. Keywords in raw db queries must be written in uppercase.
    ```sql
    SELECT `firstname`
    FROM `'._DB_PREFIX_.'customer`
    ``` 

4. Table aliases have to be named by taking the first letter of each word and must be lowercase:
    ```php
    $sql = new DbQuery();
    $sql->select('p.`'.bqSQL(self::$definition['primary']).'`');
    $sql->from(bqSQL(self::$definition['table']), 'p');
    $sql->where('p.`'.bqSQL(self::$definition['primary']).'` = 1');
    ``` 

5. When conflicts between table aliases occur, the second character also has to be used in the name:
    ```php
    $sql = new DbQuery();
    $sql->select('ca.`'.bqSQL(Product::$definition['primary']).'`, cu.`firstname`');
    $sql->from(bqSQL(Cart::$definition['table']), 'ca');
    $sql->innerJoin(bqSQL(Customer::$definition['table']), 'cu', 'ca.`'.bqSQL(Customer::$definition['primary']).'` = cu.`'.Customer::$definition['primary']).'`');
    ``` 

6. A new line has to be created for each clause in raw db queries:
    ```php
    $sql = 'SELECT pl.`name`
    FROM `'._DB_PREFIX_'.product_lang` pl
    WHERE pl.`id_product` = 17';
    ``` 

7. It is forbidden to make a `JOIN` in a `WHERE` clause

## Licenses
Do not change the license headers of a file, except updating the copyright year. 
Files have either an [Open Software License 3.0 (OSL)](https://tldrlegal.com/license/open-software-licence-3.0) license (Core files) or an [Academic Free License 3.0 (AFL)](https://tldrlegal.com/license/academic-free-license-3.0-(afl)) (module files). If your contribution includes files with a different license your contribution cannot be accepted. If you do need to include a library for your improvement, add it to `composer.json`. 
By contributing to this project, you grant thirty bees a perpetual license on the content you submit to the project. This license implies granting use, modification, improvement, distribution and deletion of your contributions to the administrator of the project. It does not grant you the ability to request the removal of your contributions from the project. Contributing to this project implies that you are the author of the content or that you are authorized by the content author to submit these contributions to thirty bees. If your contributation adds an extra author field, changes the copyright or anything else that changes the software's license, the contribution will be rejected. Instead add your name to the `CONTRIBUTORS.md` file in the changeset. We'd love to add your name to it, after a successful merge!
