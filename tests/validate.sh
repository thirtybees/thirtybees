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
