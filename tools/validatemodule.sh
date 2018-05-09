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


### Cleanup.
#
# Triggered by a trap to clean on unexpected exit as well.

function cleanup {
  if [ -n ${REPORT} ]; then
    rm -f ${REPORT}
  fi
}
trap cleanup 0


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


### Preparations.

# We write into a report file to allow us to a) collect multiple findings and
# b) evaluate the collection before exiting.
REPORT=$(mktemp)
export REPORT


### Auxilliary functions.

# Report an error.
function e {
  echo "Error: ${1}" >> ${REPORT}
}

# Report a warning.
function w {
  echo "Warning: ${1}" >> ${REPORT}
}


### .gitignore

# .gitignore should contain a minimum set of entries.
grep -q '^/translations/\*$' .gitignore || \
  e "line with '/translations/*' missing in .gitignore."
grep -q '^!/translations/index\.php$' .gitignore || \
  e "line with '!/translations/index.php' missing in .gitignore."
grep -q "^$(basename $(pwd))-\\*\\.zip$" .gitignore || \
  e "line with '$(basename $(pwd))-*.zip' missing in .gitignore."


### Translations stuff.
#
# Even modules not adding to the user interface have translations, e.g.
# name and description in the list of modules in backoffice.

# Note: 'grep -q .' is needed because 'git ls-files' always returns success.
git ls-files translations/index.php | grep -q '.' || \
  e "file translations/index.php doesn't exist."
git ls-files translations/\* | grep -vq '^translations/index\.php$' && \
  e "files other than index.php in translations/."


### Evaluation of findings.

cat ${REPORT}

if grep -q '^Error:' ${REPORT}; then
  exit 1
fi

exit 0
