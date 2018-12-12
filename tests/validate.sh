#!/usr/bin/env bash
##
# Copyright (C) 2018 thirty bees
#
# @author    thirty bees <modules@thirtybees.com>
# @copyright 2018 thirty bees
# @license   proprietary

function usage {
  echo "Usage: validate.sh [-h|--help]"
  echo
  echo "This script tests whether the testsuite is up to date. It reports"
  echo "the first error found and aborts with exit value 1, unless everything"
  echo "is considered to be fine."
  echo
  echo "Notably this script operates on files committed into the Git repository,"
  echo "revision 'HEAD', not on the files currently checked out. Which means,"
  echo "to fix something, the fix has to be committed before it gets recognized."
  echo
  echo "    -h, --help          Show this help and exit."
  echo
  echo "Example:"
  echo
  echo "  ./validate.sh"
  echo
}


### Options parsing.

while [ ${#} -ne 0 ]; do
  case "${1}" in
    '-h'|'--help')
      usage
      exit 0
      ;;
    *)
      echo "Unknown option '${1}'. Try --help."
      exit 1
      ;;
  esac
  shift
done


### Auxilliary functions.

# $1: revision
# $2: file path
function git-cat {
  git show ${1}:"${2}"
}

# $1: revision
# $2: path
function git-find {
  git ls-tree -r --name-only ${1} "${2}"
}

# $1: error message.
function e {
  echo "${1} Aborting."
  exit 1
}


### Test (and fix some) prerequisites.

# Location.
while [ "${PWD}" != '/' ] && [ ! -d '.git' ]; do
  cd ..
done
if [ ! -d 'tests' ] && [ ! -f 'install-dev/install_version.php' ]; then
  echo "Not inside a thirty bees repository. Aborting."
  exit 1
fi
# This script now runs in the repository root.


### Verify class loaders.
#
# The test suite simply loads all classes and controllers and overrides of
# each to verify they actually load fine. This catches syntax errors and
# similar stuff.

# Make a list of all candidates.
CANDIDATES=()
while read F; do
  [ "${F##*/}" = 'index.php' ] && continue
  [ "${F##*/}" = '.htaccess' ] && continue

  CANDIDATES+=("${F}")
done < <(git-find HEAD 'classes'; git-find HEAD 'controllers';)

# Some classes are not overridable.
UNOVERRIDABLES=()
UNOVERRIDABLES+=('classes/PrestaShopAutoload.php')
# Interfaces.
UNOVERRIDABLES+=('classes/tax/TaxManagerInterface.php')
UNOVERRIDABLES+=('classes/stock/StockManagerInterface.php')
UNOVERRIDABLES+=('classes/tree/ITreeToolbarButton.php')
UNOVERRIDABLES+=('classes/tree/ITreeToolbar.php')
UNOVERRIDABLES+=('classes/webservice/WebserviceOutputInterface.php')
UNOVERRIDABLES+=('classes/webservice/WebserviceSpecificManagementInterface.php')

# Also classes in Adapter and Core, none of which are overridable.
while read F; do
  [ "${F##*/}" = 'index.php' ] && continue
  [ "${F##*/}" = '.htaccess' ] && continue

  CANDIDATES+=("${F}")
  UNOVERRIDABLES+=("${F}")
done < <(git-find HEAD 'Adapter'; git-find HEAD 'Core';)

# Local function to test overridability.
# $1: candidate.
function unoverridable {
  for U in "${UNOVERRIDABLES[@]}"; do
    if [ "${1}" = "${U}" ]; then
      return 0
    fi
  done

  return 1
}

# Verify each candidate has a dummy override.
for C in "${CANDIDATES[@]}"; do
  OVERRIDE="tests/_support/override/${C}"

  unoverridable "${C}" || git-find HEAD "${OVERRIDE}" | grep -q '.' \
    || e "Dummy override ${OVERRIDE} missing."
done
unset OVERRIDE

# Find obsolete overrides.
while read OVERRIDE; do
  [ "${OVERRIDE##*/}" = 'index.php' ] && continue
  [ "${OVERRIDE##*/}" = '.htaccess' ] && continue

  NEEDED='false'
  S="${OVERRIDE#*/}"
  S="${S#*/}"
  S="${S#*/}"

  for C in "${CANDIDATES[@]}"; do
    if [ "${C}" = "${S}" ]; then
      unoverridable "${C}" || NEEDED='true'
      break
    fi
  done

  [ ${NEEDED} = 'true' ] \
    || e "Dummy override ${OVERRIDE} is obsolete."
done < <(git-find HEAD 'tests/_support/override')

# Make sure each override actually contains the class given by its file name.
for C in "${CANDIDATES[@]}"; do
  unoverridable "${C}" && continue

  CLASS="${C##*/}"
  CLASS="${CLASS%.php}"
  OVERRIDE="tests/_support/override/${C}"

  git-cat HEAD "${OVERRIDE}" | grep -q "${CLASS}" \
    || e "Override ${OVERRIDE}: file name and class name mismatch."
  git-cat HEAD "${OVERRIDE}" | grep -q "${CLASS}Core" \
    || e "Override ${OVERRIDE}: file name and extend class name mismatch."
done

# Make sure each override gets loaded in the class load test.
LOAD_FILE='tests/_support/unitloadclasses.php'
for C in "${CANDIDATES[@]}"; do
  git-cat HEAD "${LOAD_FILE}" \
    | grep -q "\$kernel->loadFile(__DIR__.'/../../${C}');" \
    || e "${C} missing in ${LOAD_FILE}."

  unoverridable "${C}" && continue
  git-cat HEAD "${LOAD_FILE}" \
    | grep -q "\$kernel->loadFile(__DIR__.'/override/${C}');" \
    || e "${C} override missing in ${LOAD_FILE}."
done
