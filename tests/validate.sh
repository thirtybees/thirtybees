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
  echo "not on the files currently checked out. Which means, to fix something,"
  echo "the fix has to be committed before it gets recognized."
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

# Verify each candidate has a dummy override.
for C in "${CANDIDATES[@]}"; do
  OVERRIDE="tests/_support/override/${C}"
  [ -s "${OVERRIDE}" ] \
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
      NEEDED='true'
      break
    fi
  done

  [ ${NEEDED} = 'true' ] \
    || e "Dummy override ${OVERRIDE} is obsolete."
done < <(git-find HEAD 'tests/_support/override')
