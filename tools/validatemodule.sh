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

  # Don't continue if there is no branch 'master'. This currently applies to
  # the default theme, only.
  if ! git branch | grep -q 'master'; then
    echo "Error: there is no branch 'master', can't continue."
    # Exiting with 0 anyways to not stop 'git submodule foreach' runs.
    exit 0
  fi
else
  IS_GIT='false'
  echo "Not a Git repository. Validating bare file trees not tested. Aborting."

  CAT='cat'
  # Note that 'git ls-files' lists paths recursively, similar to 'find', and
  # that we take advantage of this.
  LS='ls'

  exit 1
fi

# Find directory with verification templates.
TEMPLATES_DIR="${0%/*}/templates"
if [ ! -r "${TEMPLATES_DIR}/README.md.module" ]; then
  echo "Verification templates directory should be ${TEMPLATES_DIR},"
  echo "but there is no file README.md.module inside it. Aborting."
  exit 1
fi


### Auxilliary functions.

# Report an error.
function e {
  echo "  Error: ${1}" >> ${REPORT}
}

# Report a warning.
function w {
  echo "Warning: ${1}" >> ${REPORT}
}

# Report a note.
function n {
  echo "   Note: ${1}" >> ${REPORT}
}

# Report unchanged.
function u {
  echo "${1}" >> ${REPORT}
}

# Extract a property of the module main class. More precisely, those properies
# which are set by '$this-><property>' in the constructor.
#
# Parameter 1: Property. E.g. 'bla' for getting what's set with '$this->bla'.
#
#      Return: Value of the requested property. Empty string if there is no
#              such entry.
function constructorentry {
  local MODULE_NAME

  MODULE_NAME=$(basename $(pwd))
  ${CAT} "${MODULE_NAME}".php | sed -n '/__construct/,/^    \}$/p' | \
    grep '$this->'"${1}" | \
    head -1 | \
    cut -d "'" -f 2
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


### Capitalization.

# 'thirty bees' should be lowercase everywhere.
${LS} . | while read F; do
  ${CAT} "${F}" | grep -q 'Thirty Bees' && \
    e "file ${F} contains 'Thirty Bees'; should be 'thirty bees'."
  ${CAT} "${F}" | grep -q 'ThirtyBees' && \
    e "file ${F} contains 'ThirtyBees'; should be 'thirtybees'."
done

# Module name should be all uppercase, except for small words and 'thirty bees'.
NAME=$(constructorentry 'displayName')
FAULT='false'

for W in ${NAME}; do
  if [ ${#W} -gt 3 ] \
     && [ ${W} != 'thirty' ] \
     && [ ${W} != 'bees' ] \
     && [ ${W} != ${W^} ]; then
    e "'${W}' in module name should be uppercase."
    FAULT='true'
  fi
done
[ ${FAULT} = 'true' ] && \
  n "see PHP main class constructor, '\$this->displayName'."
unset NAME FAULT


### thirty bees store files.

if [ ${IS_GIT} = 'true' ]; then
  FILES=('.tbstore.yml')
  FILES+=('.tbstore/configuration.yml')
  FILES+=('.tbstore/description.md')
  FILES+=('.tbstore/images/image-1.png')

  # Each mandatory file should exist in the repository.
  for F in "${FILES[@]}"; do
    ${LS} "${F}" | grep -q '.' || \
      e "mandatory file ${F} missing."
  done
  unset FILES

  # .tbstore.yml and .tbstore/configuration.yml should be identical.
  if ${LS} .tbstore.yml | grep -q '.' \
     && ${LS} .tbstore/configuration.yml | grep -q '.'; then
    TBSTORE_TEXT=$(${CAT} .tbstore.yml)
    CONFIG_TEXT=$(${CAT} .tbstore/configuration.yml)
    if [ "${TBSTORE_TEXT}" != "${CONFIG_TEXT}" ]; then
      e "files .tbstore.yml and .tbstore/configuration.yml not identical."
      n "diff between .tbstore.yml (+) and .tbstore/configuration.yml (-):"
      u "$(diff -u0 <(echo "${CONFIG_TEXT}") <(echo "${TBSTORE_TEXT}") | \
        tail -n+3)"
    fi
    unset TBSTORE_TEXT CONFIG_TEXT
  fi

  if ${LS} .tbstore.yml | grep -q '.'; then
    # Field 'author:' should match 'author' main class property.
    CODE_AUTHOR=$(constructorentry 'author')
    TBSTORE_AUTHOR=$(${CAT} .tbstore.yml | sed -n 's/^author:\s*// p')

    if [ "${CODE_AUTHOR}" != "${TBSTORE_AUTHOR}" ]; then
      e "'.tbstore.yml' and PHP main class module authors not identical."
      n "PHP main class property 'author': '${CODE_AUTHOR}'"
      n "'author' in .tbstore.yml: '${TBSTORE_AUTHOR}'"
    fi
    unset CODE_AUTHOR TBSTORE_AUTHOR

    # Field 'module_name:' should match 'displayName' main class property.
    CODE_NAME=$(constructorentry 'displayName')
    TBSTORE_NAME=$(${CAT} .tbstore.yml | sed -n 's/^module_name:\s*// p')

    if [ "${CODE_NAME}" != "${TBSTORE_NAME}" ]; then
      e "'.tbstore.yml' and PHP main class module names not identical."
      n "PHP main class property 'displayName': '${CODE_NAME}'"
      n "'module_name' in .tbstore.yml: '${TBSTORE_NAME}'"
    fi
    unset CODE_NAME TBSTORE_NAME
  fi
fi


### Documentation files.

# A README.md should exist.
README=$(${LS} . | grep -i '^readme.md$' | grep -v '^README.md$')
if [ -z ${README} ]; then
  ${LS} README.md | grep -q '.' || \
    e "file README.md missing."
else
  # Wrong capitalization.
  e "file ${README} exists, but should be named 'README.md' (capitalization)."
fi
unset README

# Former documentation files should be absent.
FILES=('readme')
FILES+=('readme.txt')
FILES+=('roadmap')
FILES+=('roadmap.md')
FILES+=('roadmap.txt')
FILES+=('contributing')
FILES+=('contributing.md')
FILES+=('contributing.txt')

FAULT='false'
for F in "${FILES[@]}"; do
  UNWANTED=$(${LS} . | grep -i '^'"${F}"'$')
  if [ -n "${UNWANTED}" ]; then
    e "file ${UNWANTED} shouldn't exist."
    FAULT='true'
  fi
done
[ ${FAULT} = 'true' ] && \
  n "content of such former documentation files goes into README.md now."
unset FILES FAULT UNWANTED

if ${LS} README.md | grep -q '.'; then
  # These are needed as delimiters, so check for their presence early.
  HEADINGS=('Description')
  HEADINGS+=('License')
  HEADINGS+=('Roadmap')

  HEADING_MISSING='false'
  for H in "${HEADINGS[@]}"; do
    if ! ${CAT} README.md | grep -q "^## ${H}$"; then
      e "Heading '## ${H}' missing in README.md."
      HEADING_MISSING='true'
    fi
  done

  # First line of README.md should match module_name: in .tbstore.yml.
  TBSTORE_LINE="# $(${CAT} .tbstore.yml | sed -n 's/^module_name:\s*// p')"
  README_LINE=$(${CAT} README.md | sed -n '1 p')

  if [ "${TBSTORE_LINE}" != "${README_LINE}" ]; then
    e "first line of README.md doesn't match 'module_name' in .tbstore.yml."
    n "by .tbstore.yml: '${TBSTORE_LINE}'"
    n "by    README.md: '${README_LINE}'"
  fi

  # Third line of README.md should match description_short: in .tbstore.yml.
  TBSTORE_LINE=$(${CAT} .tbstore.yml | sed -n 's/^description_short:\s*// p')
  README_LINE=$(${CAT} README.md | sed -n '3 p')

  if [ "${TBSTORE_LINE}" != "${README_LINE}" ]; then
    e "third line of README.md doesn't match 'description_short' in .tbstore.yml."
    n "by .tbstore.yml: '${TBSTORE_LINE}'"
    n "by    README.md: '${README_LINE}'"
  fi

  if [ ${HEADING_MISSING} = 'false' ]; then
    # Section 'Description' ( = stuff between '## Description' and '## License')
    # should match the content of .tbstore.yml/description.md.
    TBSTORE_LINE=$(${CAT} .tbstore/description.md)
    README_LINE=$(${CAT} README.md | sed -n '/^## Description$/, /^## License$/ {
                                               /^## Description$/ n
                                               /^## License$/ ! p
                                             }')
    # Trailing newline was removed by the $() already.
    README_LINE="${README_LINE#$'\n'}"  # Remove leading newline.

    if [ "${TBSTORE_LINE}" != "${README_LINE}" ]; then
      e "Section 'Description' in README.md doesn't match content of description.md."
      n "diff between README.md (+) and .tbstore.yml/description.md (-):"
      u "$(diff -u0 <(echo "${TBSTORE_LINE}") <(echo "${README_LINE}") | \
             tail -n+3)"
    fi

    # Sections 'License' up to 'Packaging' ( = stuff between '## License' and
    # '## Roadmap') should match the content of the README.md template for
    # modules.
    TEMPLATE_LINE=$(cat "${TEMPLATES_DIR}/README.md.module" | \
                      sed -n '/^## License$/, /^## Roadmap$/ {
                        /^## License$/ n
                        /^## Roadmap$/ ! p
                      }')
    README_LINE=$(${CAT} README.md | sed -n '/^## License$/, /^## Roadmap$/ {
                                               /^## License$/ n
                                               /^## Roadmap$/ ! p
                                             }')

    if [ "${TEMPLATE_LINE}" != "${README_LINE}" ]; then
      e "sections 'License' up to 'Packaging' in README.md don't match the template."
      n "diff between README.md (+) and ${TEMPLATES_DIR}/README.md.module (-):"
      u "$(diff -u0 <(echo "${TEMPLATE_LINE}") <(echo "${README_LINE}") | \
             tail -n+3)"
    fi
  fi

  unset HEADINGS HEADING_MISSING TBSTORE_LINE README_LINE TEMPLATE_LINE
fi


### Evaluation of findings.

cat ${REPORT}

if grep -q '^  Error:' ${REPORT}; then
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
