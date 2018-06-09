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


### Set up packaging filters.
#
# These filters define which files get ignored during packaging. We set up
# a standard set here, which should be fine for most modules. To extend this
# list for a particular module, place (and commit) a file 'buildfilter.sh' in
# its root directory. It gets included after defining the standards, before
# assembling the filter script.

# Files filtered by name, in any directory.
EXCLUDE_FILE=('.gitignore')
EXCLUDE_FILE+=('build.sh')
EXCLUDE_FILE+=('buildfilter.sh')
EXCLUDE_FILE+=('.tbstore.yml')
EXCLUDE_FILE+=('LICENSE.md')
EXCLUDE_FILE+=('README.md')

# Directories filtered by name, in any parent directory.
EXCLUDE_DIR=('.tbstore')

# Paths to not filter (to exempt from above). Starting at the module root
# directory.
KEEP_PATH=()

# Paths to exclude, typically used for single files or directories. Starting
# at the module root directory.
EXCLUDE_PATH=()

# Allow additions.
EXTRAS=$(git show ${GIT_REVISION}:buildfilter.sh 2>/dev/null)
[ -n "${EXTRAS}" ] && eval "${EXTRAS}"
unset EXTRAS

# Assemble a sed script as filter.
PATH_FILTER=''
for I in "${KEEP_PATH[@]}"; do
  I=$(echo "${I}" | sed 's/\//\\\//g')
  PATH_FILTER+=' /^'"${I}"'/ { p; d; };'
done
for I in "${EXCLUDE_FILE[@]}"; do
  PATH_FILTER+=' /^'"${I}"'$/ d;'
  PATH_FILTER+=' /\/'"${I}"'$/ d;'
done
for I in "${EXCLUDE_DIR[@]}"; do
  PATH_FILTER+=' /'"${I}"'\// d;'
done
for I in "${EXCLUDE_PATH[@]}"; do
  I=$(echo "${I}" | sed 's/\//\\\//g')
  PATH_FILTER+=' /^'"${I}"'$/ d;'
  PATH_FILTER+=' /^'"${I}"'\// d;'
done
unset EXCLUDE_FILE EXCLUDE_DIR KEEP_PATH EXCLUDE_PATH


### Quality assurance.
#
# Validate the package if 'master' or the latest tag ( = latest release) are
# going to be packaged. Validation of older revisions is neither supported by
# validatemodule.sh nor does it make sense.

VALIDATE='false'
VALIDATEMODULE="${0/buildmodule.sh/validatemodule.sh}"

LATEST_TAG=$(git tag | tr -d 'v' | sort --reverse --version-sort | head -1)
if [ "${GIT_REVISION}" = "${LATEST_TAG}" ]; then
  VALIDATE='true'
  VALIDATE_PARAMETERS+=('-r')
fi
if [ "${GIT_REVISION}" = 'master' ]; then
  VALIDATE='true'
  VALIDATE_PARAMETERS+=()
fi

if [ ${VALIDATE} = 'true' ]; then
  echo "Running validatemodule.sh ${VALIDATE_PARAMETERS[*]}."
  if ! "${VALIDATEMODULE}" "${VALIDATE_PARAMETERS[@]}"; then
    echo "buildmodule.sh: validatemodule.sh detected errors. Aborting."
    exit 1
  fi
else
  echo "Packaging older revision, skipping validation."
fi
unset VALIDATE VALIDATEMODULE LATEST_TAG VALIDATE_PARAMETERS


### Actually build the package.

MODULE_NAME="${PWD##*/}"
if [ -z "$(tr -d '.[:digit:]' <<< "${GIT_REVISION}")" ]; then
  # ${GIT_REVISION} is a release number.
  PACKAGE_NAME="${MODULE_NAME}-v${GIT_REVISION}.zip"
else
  # Without 'v'.
  PACKAGE_NAME="${MODULE_NAME}-${GIT_REVISION}.zip"
fi
rm -f "${PACKAGE_NAME}"

git archive --format=zip -9 \
            --prefix="${MODULE_NAME}/" \
            --output="${PACKAGE_NAME}" \
            "${GIT_REVISION}" \
            $(git ls-tree -r --name-only "${GIT_REVISION}" . | \
                sed "${PATH_FILTER}")

if [ -s "${PACKAGE_NAME}" ]; then
  echo "Created package ${PACKAGE_NAME} successfully."
else
  echo "Tried hard, but package ${PACKAGE_NAME} ended up empty."
  exit 1
fi
unset MODULE_NAME PACKAGE_NAME
