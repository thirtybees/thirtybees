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

if [ -f .git ]; then
  IS_GIT='true'
  echo "Git repository detected. Looking at branch 'master'."

  # Abstract 'cat' and 'ls' to allow validating non-repositories as well.
  function git-cat { for F in "${@}"; do git show master:"${F}"; done }
  CAT='git-cat'
  LS='git ls-files master'
else
  IS_GIT='false'
  echo "Not a Git repository. Validating bare file trees not tested. Aborting."

  CAT='cat'
  # Note that 'git ls-files' lists paths recursively, similar to 'find', and
  # that we take advantage of this.
  LS='ls'

  exit 1
fi


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

if [ ${IS_GIT} = 'true' ]; then
  # .gitignore should contain a minimum set of entries.
  ${CAT} .gitignore | grep -q '^/translations/\*$' || \
    e "line with '/translations/*' missing in .gitignore."
  ${CAT} .gitignore | grep -q '^!/translations/index\.php$' || \
    e "line with '!/translations/index.php' missing in .gitignore."
  ${CAT} .gitignore | grep -q '^/config\*\.xml$' || \
    e "line with 'config*.xml' missing in .gitignore."
  ${CAT} .gitignore | grep -q "^$(basename $(pwd))-\\*\\.zip$" || \
    e "line with '$(basename $(pwd))-*.zip' missing in .gitignore."
fi


### Translations stuff.
#
# Even modules not adding to the user interface have translations, e.g.
# name and description in the list of modules in backoffice.

# Note: 'grep -q .' is needed because 'git ls-files' always returns success.
${LS} translations/index.php | grep -q '.' || \
  e "file translations/index.php doesn't exist."
${LS} translations/\* | grep -vq '^translations/index\.php$' && \
  e "files other than index.php in translations/."


### Mail templates stuff.

if ${LS} mails | grep -q '.'; then
  ${LS} mails/index.php | grep -q '.' || \
    e "mails folder, but no file mails/index.php."
  ${LS} mails/en/index.php | grep -q '.' || \
    e "mails folder, but no file mails/en/index.php."
  ${LS} mails/\* | grep -v '^mails/index\.php$' | grep -vq '^mails/en' && \
    e "mail templates other than english exist."
fi


### config.xml
#
# We insist on no such file to exist. These files get auto-generated. Trusting
# the auto-generated one means there can't be a content mismatch against the
# module's main class definitions.

${LS} config\.xml | grep -q '.' && \
  e "file config.xml exists."
${LS} config_\*\.xml | grep -q '.' && \
  e "at least one file config_<lang>.xml exists."


### 'thirty bees' is lowercase.

${LS} . | while read F; do
  ${CAT} "${F}" | grep -q 'Thirty Bees' && \
    e "file ${F} contains 'Thirty Bees'; should be 'thirty bees'."
  ${CAT} "${F}" | grep -q 'ThirtyBees' && \
    e "file ${F} contains 'ThirtyBees'; should be 'thirtybees'."
done


### Evaluation of findings.

cat ${REPORT}

if grep -q '^Error:' ${REPORT}; then
  if grep -q 'Thirty Bees' ${REPORT} || grep -q 'ThirtyBees' ${REPORT}; then
    echo
    echo "For the 'Thirty Bees' vs. 'thirty bees' issue, these commands"
    echo "should cover most of the cases (and only these cases):"
    echo
    echo "find . -type f -exec grep -q 'Thirty Bees' {} \; -exec sed -i 's/@author    Thirty Bees/@author    thirty bees/' {} \;"
    echo "find . -type f -exec grep -q 'Thirty Bees' {} \; -exec sed -i 's/Thirty Bees is an extension/thirty bees is an extension/' {} \;"
    echo "find . -type f -exec grep -q 'Thirty Bees' {} \; -exec sed -i 's/Copyright (C) 2017 Thirty Bees/Copyright (C) 2017 thirty bees/' {} \;"
    echo "find . -type f -exec grep -q 'Thirty Bees' {} \; -exec sed -i 's/copyright 2017 Thirty Bees/copyright 2017 thirty bees/' {} \;"
    echo "find . -type f -exec grep -q 'ThirtyBees' {} \; -exec sed -i 's/username\/ThirtyBees\.git/username\/thirtybees.git/' {} \;"
    echo "find . -type f -exec grep -q 'ThirtyBees' {} \; -exec sed -i 's/github.com\/thirtybees\/ThirtyBees/github.com\/thirtybees\/thirtybees/' {} \;"
  fi

  exit 1
else
  echo "Validation succeeded."
  exit 0
fi
