
# Upgrade Scripts

This directory contains scripts for doing database and other upgrades on
shop updates.

## Structure

- Two directories:
  - `sql`
  - `php`

- All these files go into the Extras ZIP file, which gets served on
  *api.thirtybees.com* for consumption by module *tbupdater*. Content of such a
  ZIP file is like this:
```
    php/
    php/index.php
    php/alter_blocklink.php
    php/remove_module_from_hook.php
    sql/
    sql/index.php
    sql/1.0.1.sql
    sql/1.0.2.sql
    sql/1.0.4.sql
```

- PHP scripts don't get executed by the updater module on their own, but only
  on demand of the SQL script.


## PHP Scripts

PHP scripts go into directory `php`.

  - File suffix is `.php`.
  - Each file contains at least a function PHP function matching the file name.
  - This PHP function can accept zero or more strings as parameters.
  - Name of the file matches the name of the function, all lowercase, plus the
    suffix.
  - There are no version numbers. Each Extras file contains all necessary
    scripts.


## SQL scripts

SQL scripts go into directory `sql`.

  - File suffix is `.sql`.
  - Name of the file matches the shop software version the SQL script upgrades
    to, plus the suffix.
  - The updater module looks at the shop software version running before the
    update and drops all SQL scripts for earlier versions. SQL scripts for
    versions later than the version after the update are not expected.
  - SQL commands can be multi-line, a command ends with a `;` at the end of the
    line.
  - An exact string `PREFIX_` anywhere in the command gets replaced by the
    database table prefix (typically `tb_` or `ps_`.
  - An exact string `ENGINE_TYPE` anywhere in the command get replaced by the DB
    engine name defined by *`_MYSQL_ENGINE_`*, or *MyISAM*, if there's nothing
    defined.
  - The updater module executes these scripts command by command, using the
    same database connection.
  - If a command contains exactly the string `CREATE TABLE` (casing and spacing
    matters), it injects another SQL command to drop this table before executing
    the actual command.
  - Note: in SQL, `/*` and `*/` are comment delimiters, so one can also add
    comments in these SQL scripts. It's good style to add a `;` after a comment,
    so comments get reported in their own line.
  - If a command contains exactly the string `/* PHP:` (casing and spacing
    matters), the updater module parses the line up to a `*/` and executes this
    PHP function, using the like-named file and converting parameters to
    strings. (Note: there's also some code to support static methods, but this
    appears to be unused and broken)

    Example: a line
```sql
/* PHP:remove_module_from_hook(blockcategories, afterCreateHtaccess); */;
```
executes in file `remove_module_from_hook.php` this code:
```php
remove_module_from_hook('blockcategories', 'afterCreateHtaccess');
```


## Tweaks for the Migration Module

There's an *Extras* ZIP package for module *psonesixmigrator* just like the
ones for the updater module. With one distinction: as the module executes only
scripts with a higher version number, they got renamed to `2.0.0.1.sql`,
`2.1.0.2.sql` and `2.1.0.4.sql`, and so on.

Changes in detail:

- `install-dev/upgrade/sql/1.0.2.sql` matches `2.1.0.2.sql` in
  `thirtybees-extra-v1.0.7.zip` in `migration/packs` on *api.thirtybees.com*.

- `install-dev/upgrade/sql/1.0.4.sql` matches `2.1.0.4.sql` in
  `thirtybees-extra-v1.0.7.zip` in `migration/packs` in *api.thirtybees.com*.

- `2.1.0.2.sql` in `thirtybees-extra-v1.0.7.zip` in `migration/packs` in
  *api.thirtybees.com* is a merge of `1.0.0.sql`, `1.0.1.sql` and `1.0.2.sql`,
  all in `install-dev/upgrade/sql/`. Exception is table `PREFIX_url_rewrite`,
  which got newly created in `1.0.0.sql` and dropped yet again in `1.0.1.sql`.
