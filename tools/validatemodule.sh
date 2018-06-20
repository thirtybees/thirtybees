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
  echo "    -r, --release         Run additional tests for making a release,"
  echo "                          like testing Git tags and versions declared."
  echo
  echo "    -v, --verbose         Show (hopefully) helpful hints regarding the"
  echo "                          errors found, like diffs for file content"
  echo "                          mismatches and/or script snippets to fix such"
  echo "                          misalignments."
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

OPTION_RELEASE='false'
OPTION_VERBOSE='false'

while [ ${#} -ne 0 ]; do
  case "${1}" in
    '-h'|'--help')
      usage
      exit 0
      ;;
    '-r'|'--release')
      OPTION_RELEASE='true'
      ;;
    '-v'|'--verbose')
      OPTION_VERBOSE='true'
      ;;
    *)
      echo "Unknown option '${1}'. Try ${0} --help."
      exit 1
      ;;
  esac
  shift
done


### Preparations.

# We write into a report file to allow us to a) collect multiple findings and
# b) evaluate the collection before exiting.
REPORT=$(mktemp)
export REPORT

if [ -e .git ]; then
  IS_GIT='true'
  echo "Git repository detected. Looking at branch 'master'."

  # Abstract 'cat' and 'find' to allow validating non-repositories as well.
  function git-cat { for F in "${@}"; do git show master:"${F}"; done }
  CAT='git-cat'
  FIND='git ls-tree -r --name-only master'

  # Don't continue if there is no branch 'master'. This currently applies to
  # the default theme, only.
  if ! git branch | grep -q 'master'; then
    echo "Error: there is no branch 'master', can't continue."
    # Exiting with 0 anyways to not stop 'git submodule foreach' runs.
    exit 0
  fi

  # Don't continue if there are staged changes.
  if [ $(git diff | wc -l) -ne 0 ] \
     || [ $(git diff --staged | wc -l) -ne 0 ]; then
    echo "Error: there are uncommitted changes, can't continue."
    exit 1
  fi
else
  IS_GIT='false'
  echo "Not a Git repository. Validating bare file trees not tested. Aborting."

  CAT='cat'
  FIND='find'

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
  [ ${OPTION_VERBOSE} = 'true' ] && echo "   Note: ${1}" >> ${REPORT}
}

# Report unchanged.
function u {
  [ ${OPTION_VERBOSE} = 'true' ] && echo "${1}" >> ${REPORT}
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
  ${CAT} "${MODULE_NAME}".php | \
    sed -n '/__construct/,/^{    |\t}\}$/ {
      /\$this->'"${1}"'\s*=/ {
        # Extract strings in single quotes. Also ones in l()
        # and ones containing escaped single quotes.
        s/.*[ (]'"'"'\(.*\)'"'"'[);]*$/\1/
        /\$this->/ {
          # Above didnt match, not a string.
          s/.*\=\s*\(.*\);$/\1/
        }
        p
      }
    }'
}

# Remove copyright years in lines declaring a copyright. This makes file
# contents of different vintages comparable.
function removecopyrightyears {
  sed '/Copyright (C)/ s/ [0-9-]* //
       /@copyright/ s/ [0-9-]* //'
}

# Compare a list of files, e.g. index.phps or a code files, against two
# templates. Typically a template for a thirty bees only version and a version
# for thirty bees and PrestaShop combined.
#
# Parameters get accepted by variables:
#
#   COMPARE_TB: Path of the template containing the thirty bees only version.
# COMPARE_TBPS: Path of the template containing the combined version.
# COMPARE_SKIP: Optional. Number of initial lines in the candidate file to
#               skip. Typically 1 for PHP files, 0 or unset for other languages.
# COMPARE_HINT: Optional. User hint on which part mismatches.
# COMPARE_LIST: Array with paths of files to compare.
#
# Parameters get unset after the operation.
function templatecompare {
  local TB_VERSION TBPS_VERSION TB_LEN TBPS_LEN TB_THIS TBPS_THIS

  TB_VERSION=$(cat "${COMPARE_TB}" | removecopyrightyears)
  TBPS_VERSION=$(cat "${COMPARE_TBPS}" | removecopyrightyears)
  TB_LEN=$(wc -l < "${COMPARE_TB}")
  TBPS_LEN=$(wc -l < "${COMPARE_TBPS}")

  COMPARE_SKIP=${COMPARE_SKIP:-0}
  let TB_LEN=${TB_LEN}+${COMPARE_SKIP}
  let TBPS_LEN=${TBPS_LEN}+${COMPARE_SKIP}
  let COMPARE_SKIP=${COMPARE_SKIP}+1  # 'tail' does "start at line ...".

  COMPARE_HINT=${COMPARE_HINT:-''}
  [ "${COMPARE_HINT}" = "${COMPARE_HINT# }" ] && \
    COMPARE_HINT=" ${COMPARE_HINT}"

  for F in "${COMPARE_LIST[@]}"; do
    TB_THIS=$(${CAT} "${F}" | \
                head -${TB_LEN} | tail -n+${COMPARE_SKIP} | \
                removecopyrightyears
              )
    TBPS_THIS=$(${CAT} "${F}" | \
                  head -${TBPS_LEN} | tail -n+${COMPARE_SKIP} | \
                  removecopyrightyears
                )
    if [ "${TB_THIS}" != "${TB_VERSION}" ] \
       && [ "${TBPS_THIS}" != "${TBPS_VERSION}" ]; then
      e "${F}${COMPARE_HINT} matches none of the templates."
      if grep -q 'PrestaShop SA' <<< "${TBPS_THIS}"; then
        # Should be a combined thirty bees / PS version.
        n "diff between ${F} (+) and ${COMPARE_TBPS} (-):"
        u "$(diff -u0 <(echo "${TBPS_VERSION}") <(echo "${TBPS_THIS}") | \
               tail -n+3)"
      else
        # thirty bees only version.
        n "diff between ${F} (+) and ${COMPARE_TB} (-):"
        u "$(diff -u0 <(echo "${TB_VERSION}") <(echo "${TB_THIS}") | \
               tail -n+3)"
      fi
    fi
  done
  unset COMPARE_TB COMPARE_TBPS COMPARE_SKIP COMPARE_HINT COMPARE_LIST
}

# Test wether we should skip this file from tests.
#
# Parameter 1: Path of the file in question, relative to repository root.
# Parameter 2: 'true' or default: print warnings about some files. To avoid
#              duplicate warnings about the same file.
#
#      Return: 0/true if the file should be skipped, 1/false otherwise.
function testignore {
  local SUFFIX WARN

  SUFFIX="${1##*.}"
  SUFFIX="${SUFFIX,,}"
  WARN=${2:-'true'}

  # Ignore empty CSS and JS files. They exist only to show developers
  # that such a file gets served, if not empty.
  ( [ ${SUFFIX} = 'js' ] || [ ${SUFFIX} = 'css' ] ) \
    && [ $(${CAT} "${1}" | wc -c) -eq 0 ] \
    && return 0

  # Ignore minimized files.
  [ "${1}" != "${1%.min.js}" ] \
    && return 0
  [ "${1}" != "${1%.min.css}" ] \
    && return 0

  # Skip most PHP classes in module tbupdater, which happen to be copies
  # of files in the core repository and as such, have an OSL license.
  if [ ${SUFFIX} = 'php' ] \
     && [ "${PWD##*/}" = 'tbupdater' ] \
     && [ "${1%%/*}" = 'classes' ] \
     && ! ${CAT} "${1}" | grep -q '(AFL 3.0)'; then
    [ ${WARN} = 'true' ] && w "Skipping not AFL-licensed file ${1}."
    return 0
  fi

  # If the file contains a 'thirty bees' or a 'prestashop' it's most
  # likely one of our files.
  [ -n "$(${CAT} "${1}" | \
            sed -n 's/thirty bees/&/i p; s/prestashop/&/i p;')" ] \
    && return 1

  # If the path contains a well known name it's likely a vendor file.
  [ -n "$(sed -n '/^vendor\// p;
                  /\/GuzzleHttp\// p;
                  /\/Psr\// p;
                  /\/SemVer\// p' <<< "${1}")" ] \
    && return 0

  # Warn about and ignore not minimized vendor files.
  B="${1##*/}"
  if [ "${B}" != "${B#jquery.}" ] \
     || [ "${B}" != "${B#superfish}" ] \
     || [ "${B}" != "${B#hoverIntent}" ]; then
    [ ${WARN} = 'true' ] && w "vendor file ${1} should be minimized."
    return 0
  fi

  # Known CSS exceptions.
  #
  # Module themeconfigurator, it's FontAwesome.
  [ "${1}" = 'views/css/font/font.css' ] \
    && return 0

  return 1
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

# Note: 'grep -q .' is needed because Git commands always return success.
${FIND} translations/index.php | grep -q '.' || \
  e "file translations/index.php doesn't exist."
${FIND} translations/ | grep -vq '^translations/index\.php$' && \
  e "files other than index.php in translations/."


### Mail templates stuff.

if ${FIND} mails/ | grep -q '.'; then
  ${FIND} mails/index.php | grep -q '.' || \
    e "mails folder, but no file mails/index.php."
  ${FIND} mails/en/index.php | grep -q '.' || \
    e "mails folder, but no file mails/en/index.php."
  ${FIND} mails/ | grep -v '^mails/index\.php$' | grep -vq '^mails/en' && \
    e "mail templates other than english exist."
fi


### config.xml
#
# We insist on no such file to exist. These files get auto-generated. Trusting
# the auto-generated one means there can't be a content mismatch against the
# module's main class definitions.

${FIND} config.xml | grep -q '.' && \
  e "file config.xml exists."
${FIND} . | grep -q 'config_.*\.xml' && \
  e "at least one file config_<lang>.xml exists."


### General text file maintenance.

# All files we consider to be text files.
readarray -t FILES <<< $(${FIND} . | sed -n '/\.php$/ p
                                             /\.css$/ p
                                             /\.js$/ p
                                             /\.tpl$/ p
                                             /\.phtml$/ p
                                             /\.sh$/ p
                                             /\.xml$/ p
                                             /\.yml$/ p
                                             /\.md$/ p')
[ -z "${FILES[*]}" ] && FILES=()

FAULT='false'
for F in "${FILES[@]}"; do
  # Ignore empty files.
  [ $(${CAT} "${F}" | wc -c) -gt 0 ] || continue

  # Test against DOS line endings.
  ${CAT} "${F}" | grep -q $'\r' && \
    e "file ${F} contains DOS/Windows line endings."

  # Test against trailing whitespace.
  if ${CAT} "${F}" | grep -q $'[ \t]$'; then
    e "file ${F} contains trailing whitespace."
    FAULT='true'
  fi

  # Test for a newline at end of file.
  if [ $(${CAT} "${F}" | sed -n '$ p' | wc -l) -eq 0 ]; then
    e "file ${F} misses a newline at end of file."
    FAULT='true'
  fi
done

if [ ${FAULT} = 'true' ]; then
  n "Most code editors have an option to remove trailing whitespace and"
  u "         add a newline at end of file on save automatically."
fi
unset FILES FAULT


### Main class validity.

# Test wether mandatory constructor entries exist.
ENTRIES=('name')
ENTRIES+=('tab')
ENTRIES+=('version')
ENTRIES+=('author')
ENTRIES+=('need_instance')
ENTRIES+=('displayName')
ENTRIES+=('description')
ENTRIES+=('tb_versions_compliancy')

FAULT='false'
for E in "${ENTRIES[@]}"; do
  if [ -z "$(constructorentry ${E})" ]; then
    e "mandatory PHP main class constructor entry '${E}' missing."
    FAULT='true'
  fi
done

# TODO: replace this lame text with a documentation link.
[ ${FAULT} = 'true' ] && \
  n "see PHP main class constructor, '\$this-><entry>'."
unset ENTRIES FAULT


### Capitalization.

# 'thirty bees' should be lowercase everywhere.
${FIND} . | while read F; do
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

  # Each mandatory file should exist in the repository and be not empty.
  for F in "${FILES[@]}"; do
    if ${FIND} "${F}" | grep -q '.'; then
      [ $(${CAT} "${F}" | wc -c) -gt 1 ] || \
        e "file ${F} exists, but is empty."
    else
      e "mandatory file ${F} missing."
    fi
  done
  unset FILES

  # Test for all the mandatory keys.
  # See https://docs.thirtybees.com/store/free-modules/#explanation-of-files
  if ${FIND} .tbstore.yml | grep -q '.'; then
    KEYS=('module_name')
    KEYS+=('compatible_versions')
    KEYS+=('author')
    KEYS+=('category')
    # KEYS+=('localization')  # Not mandatory.
    KEYS+=('tags')
    KEYS+=('description_short')
    KEYS+=('description')
    KEYS+=('images')
    KEYS+=('license')
    KEYS+=('php_version')
    KEYS+=('gdpr_compliant')

    FAULT='false'
    for K in "${KEYS[@]}"; do
      if ! ${CAT} .tbstore.yml | grep -q "^${K}:"; then
        e "key '${K}' missing in .tbstore.yml."
        FAULT='true'
      fi
    done

    [ ${FAULT} = 'true' ] && \
      n "see https://docs.thirtybees.com/store/free-modules/#explanation-of-files"
    unset KEYS FAULT
  fi

  # .tbstore.yml and .tbstore/configuration.yml should be identical.
  if ${FIND} .tbstore.yml | grep -q '.' \
     && ${FIND} .tbstore/configuration.yml | grep -q '.'; then
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

  if ${FIND} .tbstore.yml | grep -q '.'; then
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
README=$(${FIND} . | grep -i '^readme.md$' | grep -v '^README.md$')
if [ -z ${README} ]; then
  ${FIND} README.md | grep -q '.' || \
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
  UNWANTED=$(${FIND} . | grep -i '^'"${F}"'$')
  if [ -n "${UNWANTED}" ]; then
    e "file ${UNWANTED} shouldn't exist."
    FAULT='true'
  fi
done
[ ${FAULT} = 'true' ] && \
  n "content of such former documentation files goes into README.md now."
unset FILES FAULT UNWANTED

if ${FIND} README.md | grep -q '.'; then
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
    # should match the content of .tbstore/description.md.
    TBSTORE_LINE=$(${CAT} .tbstore/description.md)
    README_LINE=$(${CAT} README.md | sed -n '/^## Description$/, /^## License$/ {
                                               /^## Description$/ n
                                               /^## License$/ ! p
                                             }')
    # Trailing newline was removed by the $() already.
    README_LINE="${README_LINE#$'\n'}"  # Remove leading newline.

    if [ "${TBSTORE_LINE}" != "${README_LINE}" ]; then
      e "Section 'Description' in README.md doesn't match content of description.md."
      n "diff between README.md (+) and .tbstore/description.md (-):"
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

  # There should be a '#### Short Term' and a '#### Long Term' heading in
  # the Roadmap section.
  README_LINE=$(${CAT} README.md | \
                sed -n '/^## Roadmap$/,$ { /^#### Short Term$/ p }')
  [ -n "${README_LINE}" ] ||
    e "header '#### Short Term' missing in the 'Roadmap' section in README.md."
  README_LINE=$(${CAT} README.md | \
                sed -n '/^## Roadmap$/,$ { /^#### Long Term$/ p }')
  [ -n "${README_LINE}" ] ||
    e "header '#### Long Term' missing in the 'Roadmap' section in README.md."

  # Section 'Roadmap' should be at least 8 lines long.
  [ $(${CAT} README.md | sed -n '/^## Roadmap$/,$ p' | wc -l) -ge 8 ] || \
    e "section 'Roadmap' in README.md should be at least 8 lines long."

  unset HEADINGS HEADING_MISSING TBSTORE_LINE README_LINE TEMPLATE_LINE
fi

# File LICENSE.md should exist and match the template.
if ${FIND} LICENSE.md | grep -q '.'; then
  TEMPLATE=$(cat "${TEMPLATES_DIR}/LICENSE.md.module")
  LICENSE=$(${CAT} LICENSE.md)

  if [ "${TEMPLATE}" != "${LICENSE}" ]; then
    e "content of LICENSE.md doesn't match the template."
    n "diff between LICENSE.md (+) and ${TEMPLATES_DIR}/LICENSE.md (-):"
    u "$(diff -u0 <(echo "${TEMPLATE}") <(echo "${LICENSE}") | tail -n+3)"
  fi
  unset TEMPLATE LICENSE
else
  e "file LICENSE.md doesn't exist."
  n "a template is in tools/templates/ in thirty bees core."
fi

# Alternative license file variations should be absent.
LICENSE=$(${FIND} . | grep -i '^license' | grep -v '^LICENSE.md$')
for F in ${LICENSE}; do
  e "file ${F} shouldn't exist."
  n "The license of this module goes into file LICENSE.md."
done
unset LICENSE


### Build infrastructure files.

# A build.sh should be absent.
if ${FIND} build.sh | grep -q '.'; then
  e "there is a file build.sh."
  n "building the module should be handled in buildmodule.sh in core."
  n "module specific adjustments go into buildfilter.sh in the module root."
fi

# Header of buildfilter.sh should match the template.
#
# Well, for the time being, warn about no validation existing. Currently no
# module uses such a file.
${FIND} buildfilter.sh | grep -q '.' && \
  w "there is a file buildfilter.sh, validating that is not yet implemented."


### index.php files.

# There should be an index.php file in every (packaged) directory.
DIRS=('.')
for D in $(${FIND} .); do
  [ "${D::8}" = '.tbstore' ] && continue

  while [ "${D}" != "${D%/*}" ]; do
    D="${D%/*}"
    DIRS+=("${D}")
  done
done
( for E in "${DIRS[@]}"; do echo "${E}"; done ) | sort | uniq | while read D; do
  if [ -d "${D}" ]; then
    ${FIND} "${D}/index.php" | grep -q '.' || \
      e "file index.php missing in ${D}/."
  fi
done
unset DIRS

# Each index.php should match either the version for thirty bees or the version
# for thirty bees and PrestaShop combined.
COMPARE_TB="${TEMPLATES_DIR}/index.php.tb.module"
COMPARE_TBPS="${TEMPLATES_DIR}/index.php.tbps.module"
COMPARE_SKIP=0
COMPARE_HINT=''
readarray -t COMPARE_LIST <<< $(${FIND} . | grep 'index\.php$')
[ -z "${COMPARE_LIST[*]}" ] && COMPARE_LIST=()
templatecompare


### Code file headers.
#
# Each code file's header is compared against the template for either thirty
# bees or thirty bees and PrestaShop combined and should match one of them.

# PHP and PHTML files.
COMPARE_TB="${TEMPLATES_DIR}/header.php-js-css.tb.module"
COMPARE_TBPS="${TEMPLATES_DIR}/header.php-js-css.tbps.module"
COMPARE_SKIP=1
COMPARE_HINT='header'
readarray -t LIST <<< $(${FIND} . | sed -n '/\.php$/ p; /\.phtml$/ p;')
[ -z "${LIST[*]}" ] && LIST=()

for F in "${LIST[@]}"; do
  # index.php files were validated earlier already.
  [ "${F##*/}" = 'index.php' ] && continue

  testignore "${F}" && continue
  COMPARE_LIST+=("${F}")
done
unset LIST
templatecompare

# JavaScript files.
COMPARE_TB="${TEMPLATES_DIR}/header.php-js-css.tb.module"
COMPARE_TBPS="${TEMPLATES_DIR}/header.php-js-css.tbps.module"
COMPARE_SKIP=0
COMPARE_HINT='header'
readarray -t LIST <<< $(${FIND} . | grep '\.js$')
[ -z "${LIST[*]}" ] && LIST=()

for F in "${LIST[@]}"; do
  testignore "${F}" && continue
  COMPARE_LIST+=("${F}")
done
unset LIST
templatecompare

# CSS files.
COMPARE_TB="${TEMPLATES_DIR}/header.php-js-css.tb.module"
COMPARE_TBPS="${TEMPLATES_DIR}/header.php-js-css.tbps.module"
COMPARE_SKIP=0
COMPARE_HINT='header'
readarray -t LIST <<< $(${FIND} . | grep '\.css$')
[ -z "${LIST[*]}" ] && LIST=()

for F in "${LIST[@]}"; do
  testignore "${F}" && continue
  COMPARE_LIST+=("${F}")
done
unset LIST
templatecompare

# Smarty templates.
COMPARE_TB="${TEMPLATES_DIR}/header.tpl.tb.module"
COMPARE_TBPS="${TEMPLATES_DIR}/header.tpl.tbps.module"
COMPARE_SKIP=0
COMPARE_HINT='header'
readarray -t COMPARE_LIST <<< $(${FIND} . | grep '\.tpl$')
[ -z "${COMPARE_LIST[*]}" ] && COMPARE_LIST=()
templatecompare


### Copyright mentions.
#
# As time goes on, the years in copyright mentions have to get updated. Make
# sure this doesn't get forgotten.

# All files we consider to mention the copyright.
readarray -t FILES <<< $(${FIND} . | sed -n '/\.php$/ p
                                             /\.css$/ p
                                             /\.js$/ p
                                             /\.tpl$/ p
                                             /\.phtml$/ p
                                             /\.sh$/ p')
[ -z "${FILES[*]}" ] && FILES=()

for F in "${FILES[@]}"; do
  testignore "${F}" 'false' && continue

  THIS_YEAR=$(date +%Y)
  CR_LINES=$(${CAT} "${F}" | \
               sed -n '1, /\*\/$/ { /thirty bees/ { s/copyright/&/i p } }')

  # Test lines with 'Copyright (C)'.
  if grep -q 'Copyright (C)' <<< "${CR_LINES}"; then
    CR_YEAR=$(sed -n '1, /\*\/$/ {
                        /Copyright (C)/ {
                          s/.* \([0-9-]*\) .*/\1/;
                          s/[0-9]*-//;
                          p;
                        }
                      }' <<< "${CR_LINES}")
    [ "${CR_YEAR}" = "${THIS_YEAR}" ] || \
      e "'Copyright (C)' in ${F} goes up to ${CR_YEAR}, should be ${THIS_YEAR}."
    unset CR_YEAR
  else
    e "file ${F} has no 'Copyright (C)' line for thirty bees in the header."
  fi

  # Test lines with '@copyright'.
  if grep -q '@copyright' <<< "${CR_LINES}"; then
    CR_YEAR=$(sed -n '1, /\*\/$/ {
                        /@copyright/ {
                          s/.* \([0-9-]*\) .*/\1/;
                          s/[0-9]*-//;
                          p;
                        }
                      }' <<< "${CR_LINES}")
    [ "${CR_YEAR}" = "${THIS_YEAR}" ] || \
      e "'@copyright' in ${F} goes up to ${CR_YEAR}, should be ${THIS_YEAR}."
    unset CR_YEAR
  else
    e "file ${F} has no '@copyright' line for thirty bees in the header."
  fi
done
unset FILES THIS_YEAR CR_LINES


### Repository and release related stuff.

if [ ${IS_GIT} = 'true' ] && [ ${OPTION_RELEASE} = 'true' ]; then
  # First, grab remote branches and tags. That's a real
  # remote operation, so let's cache the result.
  REMOTE=$(git branch -a | sed -n 's/ *remotes\/\([a-zA-Z_-]*\)\/master/\1/ p')
  [ -z "${REMOTE}" ] && REMOTE='origin'
  REMOTE_CACHE=$(git ls-remote --refs ${REMOTE})

  # Warn if there are remote branches besides 'master'.
  SURPLUS=$(sed '/\trefs\/heads/ !d
                 /\trefs\/heads\/master/ d
                 s/^[0-9a-f]*//
                 s/refs\/heads/   '${REMOTE}'/' <<< "${REMOTE_CACHE}"
            )
  if [ -n "${SURPLUS}" ]; then
    w "there are remote branches besides 'master'."
    n "These are:"
    u "${SURPLUS}"
  fi
  unset SURPLUS

  # Branch 'master' should be pushed and up to date.
  MASTER_LOCAL=$(git show -q master | head -1 | cut -d ' ' -f 2)
  MASTER_REMOTE=$(grep 'refs/heads/master' <<< "${REMOTE_CACHE}" | \
                    cut -d $'\t' -f 1)
  [ ${MASTER_LOCAL} = ${MASTER_REMOTE} ] || \
    e "branches 'master' and '${REMOTE}/master' don't match, a push is needed."
  unset MASTER_REMOTE

  # Latest tag should be a version tag.
  LATEST_NAME=$(git tag | tr -d 'v' | sort --reverse --version-sort | head -1)
  [ -n "$(git tag --list ${LATEST_NAME})" ] || \
    LATEST_NAME="v${LATEST_NAME}"  # Re-add the 'v'.
  [ -z "$(tr -d '.[:digit:]' <<< ${LATEST_NAME})" ] || \
    e "Git tag '${LATEST_NAME}' isn't a well formatted release tag."

  # Latest tag should match $this->version in the main class constructor.
  CODE_VERSION=$(constructorentry 'version')
  [ "${LATEST_NAME}" = "${CODE_VERSION}" ] || \
    e "latest tag '${LATEST_NAME}' should match \$this->version in the main class."
  unset CODE_VERSION

  # Latest tag should be pushed.
  grep -q $'\trefs/tags/'${LATEST_NAME} <<< "${REMOTE_CACHE}" || \
    e "latest tag '${LATEST_NAME}' not in the remote repository, needs a push."

  # All remote tags should exist locally.
  grep $'\trefs/tags/' <<< "${REMOTE_CACHE}" | while read T; do
    T=${T##*/}
    [ -n "$(git tag -l ${T})" ] || \
      e "remote tag ${T} doesn't exist locally."
  done

  # If there are significant changes between the latest tag ( = the latest
  # release) and current 'master', call for a release.
  #
  # Key is the definition of 'significant changes' here. For the time being, we
  # define this as a change to files going into the distribution package.

  LATEST_LOCAL=$(git show -q ${LATEST_NAME} | head -1 | cut -d ' ' -f 2)
  CHANGED_FILES=$(git diff --name-only ${LATEST_LOCAL}..${MASTER_LOCAL})

  # Get PATH_FILTER from buildmodule.sh.
  . "${TEMPLATES_DIR}/../buildmodule.sh" --filter-only master

  CHANGED_FILES=$(sed "${PATH_FILTER}" <<< "${CHANGED_FILES}")
  [ -z "${CHANGED_FILES}" ] || \
    e "significant changes since the last release, a new release is needed."
  unset MASTER_LOCAL LATEST_NAME CHANGED_FILES
  unset EXCLUDE_FILE EXCLUDE_DIR KEEP EXCLUDE_PATH PATH_FILTER

  # Latest tag ( = latest release) should be committed in the core repository,
  # if this module is a submodule there.
  THIS_REPO="${PWD}"
  CORE_REPO="$(cd "${TEMPLATES_DIR}/../.." && pwd)"
  CORE_REPO_COPY="${CORE_REPO}"
  while [ "${THIS_REPO:0:1}" = "${CORE_REPO_COPY:0:1}" ]; do
    THIS_REPO="${THIS_REPO:1}"
    CORE_REPO_COPY="${CORE_REPO_COPY:1}"
    [ -z "${THIS_REPO}" ] && break;
  done
  THIS_REPO="${THIS_REPO##/}"

  COMMIT_STATUS=$(cd "${CORE_REPO}" && \
                    git submodule status --cached "${THIS_REPO}" 2> /dev/null)
  if [ -n "${COMMIT_STATUS}" ]; then
    # This module is a submodule in core.
    COMMIT_STATUS="${COMMIT_STATUS:1}"
    COMMIT_STATUS="${COMMIT_STATUS%% *}"
    [ "${COMMIT_STATUS}" = "${LATEST_LOCAL}" ] || \
      e "module is submodule in core, but latest tag not committed there."
  fi
  unset LATEST_LOCAL THIS_REPO CORE_REPO CORE_REPO_COPY COMMIT_STATUS

  unset REMOTE REMOTE_CACHE
fi


### Evaluation of findings.

cat ${REPORT}

if grep -q '^  Error:' ${REPORT}; then
  if [ ${OPTION_VERBOSE} = 'true' ]; then
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

    echo
    echo "If these errors were introduced with your last commit, fix them,"
    echo "then use 'git commit --amend' to correct that last commit."
  else
    echo "Errors found. Use --verbose for additional hints."
  fi

  exit 1
else
  echo "Validation succeeded."
  exit 0
fi
