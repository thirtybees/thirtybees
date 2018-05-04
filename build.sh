#!/usr/bin/env bash

function usage {
  echo "Usage: ./build.sh [-h|--help] [<git revision>]"
  echo
  echo "This script builds an installation package from the current repository."
  echo "Default revision is 'master'."
  echo
  echo "    -h, --help            Show this help and exit."
  echo
}


GIT_REVISION=''

for OPTION in "$@"; do
  case "${OPTION}" in
    '-h'|'--help')
      usage
      exit 0
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


# Because 'git submodule' works with the currently checked out revision, only,
# we have to check that out.
echo "Saving Git repository state."
git stash -q
ORIGINAL_REVISION=$(cat .git/HEAD)
ORIGINAL_REVISION="${ORIGINAL_REVISION##*/}";

echo "Checking out Git revision ${GIT_REVISION}."
git checkout -q "${GIT_REVISION}"                           || exit 1

# Similar for submodules.
echo "Updating submodules. This may take a while."
git submodule foreach -q --recursive 'git stash -q'

# This fetches submodule commits and checks out remote branch 'master'.
git submodule update --recursive --init --remote            || exit 1

# Heuristics on wether newest commits were forgotten to commit in the core
# repository. Heuristics:
# - Less than 20 commits on the branch of the to be packaged commit.
# - Dirty submodules exist.
GIT_BRANCH=$(git branch --contains "${GIT_REVISION}" | \
             grep -v "detached" | head -1 | cut -b 3-)
COMMITS_ON_TOP=$(git log --oneline "${GIT_REVISION}".."${GIT_BRANCH}" | wc -l)
if [ "${COMMITS_ON_TOP}" -lt 20 ] \
   && git submodule | grep -q '^+'; then
  echo "Request to package a recent release and dirty submodules exist,"
  echo "refusing to continue packaging."

  # Revert packaging preparations done so far.
  git checkout -q "${ORIGINAL_REVISION}"
  git stash pop -q
  git submodule foreach -q --recursive 'git stash pop -q 2>/dev/null || true'
  exit 1
fi

# This checks out submodule commits matching the requested package.
git submodule update --recursive --init                     || exit 1


TB_VERSION=$((cat install-dev/install_version.php &&
              echo 'print(_TB_INSTALL_VERSION_);') | \
             php)

echo "Packaging thirty bees version ${TB_VERSION}."


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
    git archive HEAD | tar -C "${DIR}/${D}" -xf-

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


# Restore the repository to the previous state.
# Should always work, because we changed nothing.
echo "Restoring Git repository and submodules states."
git checkout -q "${ORIGINAL_REVISION}"
git stash pop -q
git submodule update -q --recursive
git submodule foreach -q --recursive 'git stash pop -q 2>&1 | \
                                      grep -v "No stash entries found." \
                                      || true'

exit 0
