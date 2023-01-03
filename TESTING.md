# Automated testing

## Environment:

Tests expect thirty bees installation with:
 - language: `en`
 - country: `FR`
 - admin user with email `test@thirty.bees` and password `thirtybees`
 - front office user with email `pub@thirtybees.com` and password `123456789`

Easiest way to create such environment is by using following command
(adjust database user, password, and domain):

```
php install-dev/index_cli.php \
--newsletter=0 \
--language=en \
--country=fr \
--domain=localhost \
--db_name=thirtybees \
--db_user=thirtybees \
--db_password=thirtybees \
--db_clear=1 \
--db_create=1 \
--name=thirtybees \
--email=test@thirty.bees \
--firstname=thirty \
--lastname=bees \
--password=thirtybees
```

## Create custom codeception config file

Copy file `codeception.yml` to `codeception.local.yml` and update it
to match your environment. Adjust domain name, database user and password.

#### Example configuration file

```yaml
namespace: Tests
support_namespace: Support
paths:
    tests: tests
    output: tests/_output
    data: tests/Support/Data
    support: tests/Support
    envs: tests/_envs
actor_suffix: Tester
settings:
    colors: true
    error_level: E_ALL | E_STRICT
    memory_limit: 1280M
bootstrap: _bootstrap.php
extensions:
    enabled:
        - Codeception\Extension\RunFailed
modules:
    config:
        Db:
            dsn: 'mysql:host=localhost;dbname=thirtybees'
            user: 'thirtybees'
            password: 'thirtybees'
            populate: false
            cleanup: false
            reconnect: true
        WebDriver:
            url: 'http://localhost'
            browser: phantomjs
            window_size: 1920x1080
        PhpBrowser:
            url: 'http://localhost'


```

## Running tests

Test are written using codeception framework.

You can run tests suites using following commands:

### Unit tests
```
./vendor/bin/codecept run Unit -c codeception.local.yml
```

### Integration tests
```
./vendor/bin/codecept run Integration -c codeception.local.yml
```

### Functional tests
```
./vendor/bin/codecept run Functional -c codeception.local.yml
```