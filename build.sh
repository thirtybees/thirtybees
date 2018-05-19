#!/usr/bin/env bash

function usage {
  echo "Usage: ./build.sh [-h|--help] [<git revision>]"
  echo
  echo "This script builds an installation package from the current repository."
  echo "Default revision is 'master'."
  echo
  echo "    -h, --help            Show this help and exit."
  echo "    -d, --allow-dirty     Package even with dirty submodules existing."
  echo "                          This is for packaging older releases when"
  echo "                          the dirty submodule heuristics fails."
  echo
}


### Repository restoring.
#
# Triggered by a trap, because we have multiple exit points.
function cleanup {
  if [ -n "${PACKAGING_DIR}" ]; then
    echo "Deleting temporary packaging directory."
    rm -rf ${PACKAGING_DIR}
  fi

  if [ -n "${ORIGINAL_REVISION}" ]; then
    # Should always work, because we changed nothing.
    echo "Restoring Git repository and submodules states."
    git checkout -q "${ORIGINAL_REVISION}"
    git stash pop -q | grep -v "Already up to date!"
    git submodule update -q --recursive
    git submodule foreach -q --recursive 'git stash pop -q 2>&1 | \
                                          grep -v "Already up to date!" | \
                                          grep -v "No stash entries found." \
                                          || true'
  fi
}
trap cleanup 0


### Options parsing.

GIT_REVISION=''
ALLOW_DIRTY='false'

for OPTION in "$@"; do
  case "${OPTION}" in
    '-h'|'--help')
      usage
      exit 0
      ;;
    '-d'|'--allow-dirty')
      ALLOW_DIRTY='true'
      ;;
    *)
      if ! git show -q "${OPTION}" >/dev/null 2>&1; then
        echo "Git revision '${OPTION}' doesn't exist. Aborting."
        exit 1
      fi
      GIT_REVISION="${OPTION}"
      ;;
  esac
done

GIT_REVISION="${GIT_REVISION:-master}"
PACKAGE_NAME="thirtybees-v${GIT_REVISION}"
rm -f "${PACKAGE_NAME}".zip


### Saving repository state.
#
# Because 'git submodule' works with the currently checked out revision, only,
# we have to check that out.

echo "Saving Git repository state."
git stash -q --include-untracked 2>&1 | grep -v '^Ignoring path'
ORIGINAL_REVISION=$(cat .git/HEAD)
ORIGINAL_REVISION="${ORIGINAL_REVISION##*/}";

echo "Checking out Git revision ${GIT_REVISION}."
git checkout -q "${GIT_REVISION}"                           || exit 1

# Similar for submodules.
echo "Updating submodules. This may take a while."
git submodule foreach -q --recursive 'git stash -q --include-untracked'


### Plausibility heuristics.
#
# Suboptimal releases have been packaged due to (whatever), so let's try to
# catch all the situations where some pre-release work steps have been
# forgotten.

# Heuristics on wether newest commits were forgotten to commit in the core
# repository. Heuristics:
# - Less than 30 commits on the branch of the to be packaged commit.
# - Dirty submodules exist.

# This fetches submodule commits and checks out remote branch 'master'.
git submodule update --recursive --init --remote            || exit 1

GIT_BRANCH=$(git branch --contains "${GIT_REVISION}" | \
             grep -v "detached" | head -1 | cut -b 3-)
COMMITS_ON_TOP=$(git log --oneline "${GIT_REVISION}".."${GIT_BRANCH}" | wc -l)
if [ "${ALLOW_DIRTY}" = 'false' ] \
   && [ "${COMMITS_ON_TOP}" -lt 30 ] \
   && git submodule | grep -q '^+'; then
  echo "Request to package a recent release and dirty submodules exist,"
  echo "refusing to continue packaging."
  exit 1
fi

# Heuristics: if the requested revision is a Git tag ( = release), it should
# match the version in install-dev/install_version.php.
TB_VERSION=$((cat install-dev/install_version.php &&
              echo 'print(_TB_INSTALL_VERSION_);') | \
             php)
if git tag | grep -q "${GIT_REVISION}" \
   && [ "${GIT_REVISION}" != "${TB_VERSION}" ]; then
  echo "Request to package a release with _TB_INSTALL_VERSION_ not matching,"
  echo "see install-dev/install_version.php. Refusing to continue packaging."
  exit 1
fi


### Actual packaging.

echo "Packaging thirty bees version ${GIT_REVISION}."

# This checks out submodule commits matching the requested package.
git submodule update --recursive --init                     || exit 1

# Create packaging directory.
PACKAGING_DIR=$(mktemp -d)

PACKAGING_DIR+="${PACKAGE_NAME}"
mkdir "${PACKAGING_DIR}"
export PACKAGING_DIR

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
EXCLUDE_FILE+=("codeception.yml")
EXCLUDE_FILE+=("composer.lock")
EXCLUDE_FILE+=("Vagrantfile")
EXCLUDE_FILE+=("build.sh")

# Directories not needed in the release package.
EXCLUDE_DIR=("examples")
EXCLUDE_DIR+=("Examples")
EXCLUDE_DIR+=("tests")
EXCLUDE_DIR+=("Tests")
EXCLUDE_DIR+=("unitTests")
EXCLUDE_DIR+=("vagrant")

# As always, there are some exceptions from the above :-) Full paths, please.
KEEP=("lib/Twig/Node/Expression/Test")

# Exclude paths, for individual files and directories to be excluded.
# EXCLUDE_PATH=("generatemd5list.php")  <- Can't get removed.
EXCLUDE_PATH=("tools/validatemodule.sh")


# Build a list of parameters for 'find' to actually keep ${KEEP}.
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

    mkdir -p "${PACKAGING_DIR}/${D}"
    git archive HEAD | tar -C "${PACKAGING_DIR}/${D}" -xf-

    cd "${PACKAGING_DIR}/${D}"
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
  cd "${PACKAGING_DIR}" || exit 1
  composer install --no-dev
  composer dump-autoload -o
)


# Cleaning :-)
(
  cd "${PACKAGING_DIR}"
  for E in "${EXCLUDE_FILE[@]}"; do
    find . "${KEEP_FLAGS[@]}" -type f -name "${E}" -delete
  done
  for E in "${EXCLUDE_DIR[@]}"; do
    find . "${KEEP_FLAGS[@]}" -type d -name "${E}" | while read D; do
      rm -rf "${D}"
    done
  done
  for E in "${EXCLUDE_PATH[@]}"; do
    rm -rf "${E}"
  done
)

# Make the full package.
(
  echo -n "Creating package ... "
  cd "${PACKAGING_DIR}"
  php ./tools/generatemd5list.php
  zip -r -q "${PACKAGE_NAME}".zip .
  echo "done."
)

mv "${PACKAGING_DIR}"/"${PACKAGE_NAME}".zip .
echo "Created ${PACKAGE_NAME}.zip successfully."


# Cleanup happens via a trap.
exit 0
