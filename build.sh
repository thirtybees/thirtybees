#!/usr/bin/env bash

# This script builds an installation package from the current repository.
#
# Usage: ./build.sh [<git branch>]
#
# Currently, only Git branches 'master' and 'HEAD' make sense, because the
# same branch name is used for each submodule, too. Default is branch 'master'.


GIT_BRANCH="${1:-master}"

TB_VERSION=$((cat install-dev/install_version.php &&
              echo 'print(_TB_INSTALL_VERSION_);') | \
             php)

echo "Packaging thirty bees version ${TB_VERSION}."
echo "Assuming all module repositories are up to date."

# Create packaging directory.
DIR=$(mktemp -d)
trap "rm -rf ${DIR}" 0

DIR+="/thirtybees-v${TB_VERSION}"
mkdir "${DIR}"
export DIR


# Collect Git repositories to deal with. This is a bit slow, but parsing
# .gitmodules directly is unreliable.
REPOS_GIT=($(
  git submodule | \
    cut -b 2- | \
    cut -d ' ' -f 2
))
REPOS_GIT+=(".")


# Files not needed in the release package.
EXCLUDE_FILE=(".coveralls.yml")
EXCLUDE_FILE+=(".gitignore")
EXCLUDE_FILE+=(".gitmodules")
EXCLUDE_FILE+=(".scrutinizer.yml")
EXCLUDE_FILE+=(".travis.yml")
EXCLUDE_FILE+=("architecture.md")
EXCLUDE_FILE+=("build.sh")
EXCLUDE_FILE+=("codeception.yml")
EXCLUDE_FILE+=("composer.lock")
EXCLUDE_FILE+=("Vagrantfile")

# Directories not needed in the release package.
EXCLUDE_DIR=("docs")
EXCLUDE_DIR+=("examples")
EXCLUDE_DIR+=("Examples")
EXCLUDE_DIR+=("tests")
EXCLUDE_DIR+=("Tests")
EXCLUDE_DIR+=("unitTests")
EXCLUDE_DIR+=("vagrant")

# As always, there are some exceptions :-)
KEEP=("lib/Twig/Node/Expression/Test")

KEEP_FLAGS=()
for E in "${KEEP[@]}"; do
  KEEP_FLAGS+=("!")
  KEEP_FLAGS+=("-path")
  KEEP_FLAGS+=("\*${E}\*")
done


# Create copies of all the stuff.
# Try to copy not much more than what's needed.

# Git repositories.
export D
for D in "${REPOS_GIT[@]}"; do
  (
    echo -n "Copying ${D} ... "
    cd ${D} || continue

    mkdir -p "${DIR}/${D}"
    git archive "${GIT_BRANCH}" | tar -C "${DIR}/${D}" -xf-

    cd "${DIR}/${D}"
    if [ -d admin-dev ]; then
      mv admin-dev admin
    fi
    if [ -d install-dev ]; then
      mv install-dev install
    fi

    echo "done."
  )
done

# Composer repositories. Not reasonably doable without network access,
# but fortunately composer maintains a cache, so no heavy downloads.
(
  cd "${DIR}" || exit 1
  composer install --no-dev
  composer dump-autoload -o
)


# Cleaning :-)
(
  cd "${DIR}"
  for E in "${EXCLUDE_FILE[@]}"; do
    find . "${KEEP_FLAGS[@]}" -type f -name "${E}" -delete
  done
  for E in "${EXCLUDE_DIR[@]}"; do
    find . "${KEEP_FLAGS[@]}" -type d -name "${E}" | while read D; do
      rm -rf "${D}"
    done
  done
)


# Make the full package.
(
  echo -n "Creating package ... "
  cd "${DIR}"
  php ./tools/generatemd5list.php
  zip -r -q $(basename "${DIR}").zip .
  echo "done."
)

mv "${DIR}"/$(basename "${DIR}").zip .
echo "Created $(basename "${DIR}").zip successfully."

exit 0
