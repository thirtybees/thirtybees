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
  echo "Usage: buildmodule.sh [-h|--help] [<git revision>]"
  echo
  echo "This script builds a module release. It expects to be run in the root"
  echo "of the modules' repository, inside the thirty bees core repository."
  echo
  echo "    -h, --help            Show this help and exit."
  echo
  echo "    <git revision>        Any Git tag, branch or commit. Defaults to"
  echo "                          the latest tag ( = latest release)."
  echo
  echo "Example:"
  echo
  echo "  cd modules/bankwire"
  echo "  ../../tools/buildmodule.sh"
  echo
}


### Options parsing.

GIT_REVISION=''

for OPTION in "$@"; do
  case "${OPTION}" in
    '-h'|'--help')
      usage
      exit 0
      ;;
    *)
      GIT_REVISION="${OPTION}"
      ;;
  esac
done


### Test for prerequisites.

# Test availability of Git.
if ! which git > /dev/null; then
  echo "Git not available. Aborting."
  exit 1
fi

# Test for a Git repository.
if [ ! -f .git ]; then
  echo "Not at the root of a Git repository. Aborting."
  exit 1
fi

# Test wether this is a module directory.
DIR="${PWD%/*}"
DIR="${DIR##*/}"
if [ "${DIR}" != 'modules' ]; then
  echo "Not in modules/<module>/, this is apparently not a module. Aborting."
  exit 1
fi
unset DIR

# There should be no staged changes.
if [ $(git diff | wc -l) -ne 0 ] \
   || [ $(git diff --staged | wc -l) -ne 0 ]; then
  echo "There are uncommitted changes. Aborting."
  exit 1
fi


### Find Git revision to package.

if [ -z "${GIT_REVISION}" ]; then
  # Default to the latest tag.
  GIT_REVISION=$(git tag | tr -d 'v' | sort --reverse --version-sort | head -1)

  if [ -z "${GIT_REVISION}" ]; then
    # No tags? Default to master.
    git branch --list master | grep -q '.' && GIT_REVISION='master'

    if [ -z "${GIT_REVISION}" ]; then
      echo "No revision given, no tags, no branch 'master'. Aborting."
      exit 1
    fi
  fi
else
  if ! git show -q "${GIT_REVISION}" 2>/dev/null | grep -q '.'; then
    echo "Git revision '${GIT_REVISION}' doesn't exist. Aborting."
    exit 1
  fi
fi
echo "Packaging Git revision '${GIT_REVISION}'."
