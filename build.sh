#!/usr/bin/env bash
composer install --no-dev
find -type d -name tests -exec rm -rf {} \;
composer dump-autoload -o
mv admin-dev admin
mv install-dev install
rm -rf .git
rm architecture.md
rm codeception.yml
rm .coveralls.yml
rm .gitignore
rm .gitmodules
rm .scrutinizer.yml
rm .travis.yml
rm build.sh
node ./generatemd5list.js

exit 0
