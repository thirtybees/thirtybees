#!/bin/bash
##
# Copyright (C) 2018 thirty bees
#
# NOTICE OF LICENSE
#
# This source file is subject to the Academic Free License (AFL 3.0)
# that is bundled with this package in the file LICENSE.md
# It is also available through the world-wide-web at this URL:
# http://opensource.org/licenses/afl-3.0.php
# If you did not receive a copy of the license and are unable to
# obtain it through the world-wide-web, please send an email
# to license@thirtybees.com so we can send you a copy immediately.
#
# @author    thirty bees <modules@thirtybees.com>
# @copyright 2018 thirty bees
# @license   Academic Free License (AFL 3.0)

function usage {
  echo "Usage: validatemodule.sh [-h|--help]"
  echo
  echo "This script runs a couple of plausibility and conformance tests on"
  echo "thirty bees modules contained in a Git repository. Note that files"
  echo "checked into the repository get validated, no the ones on disk."
  echo
  echo "    -h, --help            Show this help and exit."
  echo
  echo "Example to test a single module:"
  echo
  echo "  cd modules/bankwire"
  echo "  ../../tools/validatemodule.sh"
  echo
  echo "Example to test all submodules of the core repository:"
  echo
  echo "  git submodule foreach ../../tools/validatemodule.sh"
  echo
}


### Options parsing.

for OPTION in "$@"; do
  case "${OPTION}" in
    '-h'|'--help')
      usage
      exit 0
      ;;
    *)
      echo "Unknown option '${OPTION}'. Try ${0} --help."
      exit 1
      ;;
  esac
done


### .gitignore

# .gitignore should contain a minimum set of entries.
grep -q '^/translations/\*$' .gitignore || (
  echo ".gitignore is missing a line with '/translations/*'."
  exit 1 )
grep -q '^!/translations/index\.php$' .gitignore || (
  echo ".gitignore is missing a line with '!/translations/index.php'."
  exit 1 )
grep -q "^$(basename $(pwd))-\\*\\.zip$" .gitignore || (
  echo ".gitignore is missing a line with '$(basename $(pwd))-*.zip'."
  exit 1 )
