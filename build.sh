#!/usr/bin/env bash

function usage {
  echo "Usage: build.sh [-h|--help] [--php-version=<version>] [--[no-]validate] [--target-dir=<dir>]"
  echo "          [<git revision>]"
  echo
  echo "This script builds an installation package from the current repository."
  echo
  echo "    -h, --help            Show this help and exit."
  echo
  echo "    --[no-]validate       Enforce [no] validation. Default is to"
  echo "                          validate when packaging 'master' or the"
  echo "                          latest tag, but not when packaging others."
  echo
  echo "    --php-version=<ver>   Build package for specific php version. If not"
  echo "                          specified, minimal supported php version will be used"
  echo
  echo "    --target-dir=<dir>    Instead of building a package, drop the to be"
  echo "                          packaged files in <dir>."
  echo
  echo "    <git revision>        Any Git tag, branch or commit. Defaults to"
  echo "                          the latest tag ( = latest release)."
  echo
}


### Repository restoring.
#
# Triggered by a trap, because we have multiple exit points.
function cleanup {
  if [ -n "${PACKAGING_DIR}" ] && [ -z "${TARGET_DIR}" ]; then
    echo "Deleting temporary packaging directory."
    rm -rf ${PACKAGING_DIR}
  fi

  if [ -n "${SUBMODULES_ADDED[*]}" ]; then
    echo "Removing submodules added earlier."
    for S in "${SUBMODULES_ADDED[@]}"; do
      rm -rf "${S}"
    done
  fi

  if [ -n "${PHP_TMP}" ]; then
    rm -f ${PHP_TMP}
  fi
}
trap cleanup 0


### Options parsing.

OPTION_VALIDATE='auto'
PHP_VERSION='auto'
GIT_REVISION=''
TARGET_DIR=''

while [ ${#} -ne 0 ]; do
  case "${1}" in
    '-h'|'--help')
      usage
      exit 0
      ;;
    '--validate')
      OPTION_VALIDATE='true'
      ;;
    '--no-validate')
      OPTION_VALIDATE='false'
      ;;
    '--target-dir')
      if [ -z "${2}" ]; then
        echo "Option --target-dir missing parameter. Aborting."
        exit 1
      fi
      TARGET_DIR="${2}"
      shift
      ;;
    '--target-dir='*)
      TARGET_DIR="${1#*=}"
      if [ -z "${TARGET_DIR}" ]; then
        echo "Option --target-dir= missing parameter. Aborting."
        exit 1
      fi
      ;;
    '--php-version='*)
      PHP_VERSION="${1#*=}"
      if [ -z "${PHP_VERSION}" ]; then
        echo "Option --php-version= missing parameter. Aborting."
        exit 1
      fi
      ;;
    *)
      if ! git show -q "${1}" 2>/dev/null | grep -q '.'; then
        echo "Git revision '${1}' doesn't exist. Aborting."
        exit 1
      fi
      GIT_REVISION="${1}"
      ;;
  esac
  shift
done

SUPPORTED_PHP_VERSIONS=$(find composer -type d -name "php*" | sed -e 's/^.*php\([0-9]\.[0-9]\)$/\1/g' | sort --version-sort);
if [ "$PHP_VERSION" == "auto" ]; then
  PHP_VERSION=$(find composer -type d -name "php*" | sed -e 's/^.*php\([0-9]\.[0-9]\)$/\1/g' | sort --version-sort | head -n 1);
fi
if [[ ! "${SUPPORTED_PHP_VERSIONS[@]}" =~ "${PHP_VERSION}" ]]; then
  echo "Unsupported php version ${PHP_VERSION}";
  exit 1;
fi
unset SUPPORTED_PHP_VERSIONS;

echo "Building release package for PHP version ${PHP_VERSION}";

# Latest tag = latest release. Ignore PS version tags.
LATEST_TAG=$(git tag | \
               sed -n '/^[1-9]\.[0-4]\.[0-9]*$/ p' | \
               sort --reverse --version-sort | \
               head -1)
[ -n "${GIT_REVISION}" ] || GIT_REVISION=${LATEST_TAG}
echo "Packaging Git revision '${GIT_REVISION}'."
unset LATEST_TAG

PACKAGE_NAME="thirtybees-v${GIT_REVISION}"
rm -f "${PACKAGE_NAME}".zip


### Test for prerequisites.

# Test availability of Git.
if ! which git > /dev/null; then
  echo "Git not available. Aborting."
  exit 1
fi

# Test for a Git repository.
if [ ! -e .git ]; then
  echo "Not at the root of a Git repository. Aborting."
  exit 1
fi

# Test for the existence of the target directory.
if [ -n "${TARGET_DIR}" ] && ! [ -d "${TARGET_DIR}" ]; then
  echo "Requested target directory doesn't exist. Aborting."
  exit 1
fi

# There should be no staged changes.
if ([ ${OPTION_VALIDATE} = 'true' ] \
    || [ ${OPTION_VALIDATE} = 'auto' ]) \
   && ([ $(git diff | wc -l) -ne 0 ] \
       || [ $(git diff --staged | wc -l) -ne 0 ]); then
  echo "There are uncommitted changes. Aborting."
  exit 1
fi


### Plausibility heuristics.
#
# Suboptimal releases have been packaged due to (whatever), so let's try to
# catch all the situations where some pre-release work steps have been
# forgotten.

# Heuristics on wether to validate the stuff to be packaged. This should detect
# 'forgotten' commits in core as well as modules in need of a new release.
# Heuristics:
# - Less than 30 commits on the branch of the to be packaged commit.

if [ ${OPTION_VALIDATE} = 'auto' ]; then
  GIT_BRANCH=$(git branch --contains "${GIT_REVISION}" | \
               grep -v 'detached' | grep -v 'no branch' | head -1 | cut -b 3-)
  COMMITS_ON_TOP=$(git log --oneline "${GIT_REVISION}".."${GIT_BRANCH}" | wc -l)
  if [ ${COMMITS_ON_TOP} -lt 30 ]; then
    OPTION_VALIDATE='true'
  else
    echo "You're about to package a revision more than 30 commits old. You may"
    echo "want to make sure to check out thirty bees core of that age to get"
    echo "the package build tools used back then."
    OPTION_VALIDATE='false'
  fi
fi

# Heuristics: if the requested revision is a Git tag ( = release), it should
# match the version in install-dev/install_version.php.
TB_VERSION=$((git cat-file -p ${GIT_REVISION}:install-dev/install_version.php &&
              echo 'print(_TB_INSTALL_VERSION_);') | \
             php)
if [ ${OPTION_VALIDATE} != 'false' ] \
   && git tag | grep -q "${GIT_REVISION}" \
   && [ "${GIT_REVISION}" != "${TB_VERSION}" ]; then
  echo "Request to package revision '${GIT_REVISION}', but _TB_INSTALL_VERSION_"
  echo "in install-dev/install_version.php is '${TB_VERSION}'. Aborting."
  exit 1
fi


### Submodule preparations.

# If available, get a list of modules needed in the release package.
TBMODULE_LIST=()
if git ls-tree --name-only ${GIT_REVISION} config/default_modules.php \
  | grep -q '.'; then
  # 'php -r' is pretty lame, we need a temporary file.
  PHP_TMP=$(mktemp)
  git cat-file -p ${GIT_REVISION}:config/default_modules.php >> ${PHP_TMP}
  echo 'foreach ($_TB_DEFAULT_MODULES_ as $module) {'        >> ${PHP_TMP}
  echo '  print($module."\n");'                              >> ${PHP_TMP}
  echo '}'                                                   >> ${PHP_TMP}

  TBMODULE_LIST=($(php -f ${PHP_TMP}))
  rm ${PHP_TMP}
  unset PHP_TMP
fi

# Make sure each submodule needed by the requested Git revision exists.
#
# Agreed, this is a bit cumbersome. But it's pretty much the only cumbersome
# part needed to allow packaging arbitrary revisions without checking them out.
SUBMODULE_LIST=()
while read M; do
  SUBMODULE_LIST+=("modules/${M}")
done < <(
  git cat-file -p ${GIT_REVISION}:modules \
  | grep '^160000' \
  | cut -d ' ' -f 3 \
  | cut -f 2 \
  | while read M; do
    if [ -n "${TBMODULE_LIST[*]}" ]; then
      for MM in "${TBMODULE_LIST[@]}"; do
        if [ "${MM}" = "${M}" ]; then
          echo ${M}
          break
        fi
      done
    else
      echo ${M}
    fi
  done
)
while read T; do
  SUBMODULE_LIST+=("themes/${T#*$'\t'}")
done < <(
  git cat-file -p ${GIT_REVISION}:themes | grep '^160000' | cut -d ' ' -f 3
)

SUBMODULES_ADDED=()
for S in "${SUBMODULE_LIST[@]}"; do
  if [ ! -e "${S}/.git" ]; then
    SUBMODULES_ADDED+=("${S}")
    SUBMODULE_URL=$(git cat-file -p ${GIT_REVISION}:.gitmodules | sed -n '
                      /^\[submodule.*'"${S/\//\\\/}"'/, /^\[submodule/ {
                        s/\s*url = // p
                      }
                    ')
    git clone ${SUBMODULE_URL} "${S}" 2>&1 | grep -v '^remote:'   || exit ${?}
  fi
done


### Actual packaging.

if [ -z "${TARGET_DIR}" ]; then
  # Create packaging directory.
  PACKAGING_DIR=$(mktemp -d)

  PACKAGING_DIR+="/${PACKAGE_NAME}"
  mkdir "${PACKAGING_DIR}"
else
  PACKAGING_DIR="${TARGET_DIR}"
fi
export PACKAGING_DIR


### Build packaging filters.
#
# As we have not much control over what composer does, we first put everything
# into a directory, then remove surplus file.

# Files not needed in the release package.
EXCLUDE_FILE=('.coveralls.yml')
EXCLUDE_FILE+=('.gitignore')
EXCLUDE_FILE+=('.gitmodules')
EXCLUDE_FILE+=('.travis.yml')
EXCLUDE_FILE+=('architecture.md')
EXCLUDE_FILE+=('codeception.yml')
EXCLUDE_FILE+=('Vagrantfile')
EXCLUDE_FILE+=('CHANGELOG*')
EXCLUDE_FILE+=('ChangeLog*')
EXCLUDE_FILE+=('Changelog*')
EXCLUDE_FILE+=('changelog*')
EXCLUDE_FILE+=('build.sh')

# Directories not needed in the release package.
EXCLUDE_DIR=('docs')
EXCLUDE_DIR+=('examples')
EXCLUDE_DIR+=('Examples')
EXCLUDE_DIR+=('tests')
EXCLUDE_DIR+=('Tests')
EXCLUDE_DIR+=('unitTests')
EXCLUDE_DIR+=('vagrant')

# As always, there are some exceptions from the above :-)
# Paths starting at repository root, directories without trailing '/', please.
KEEP=('docs')               # For CSV import samples, linked in back office.

# Exclude paths, for individual files and directories to be excluded.
# EXCLUDE_PATH=('generatemd5list.php')  <- Can't get removed.
EXCLUDE_PATH=('tools/buildmodule.sh')
EXCLUDE_PATH+=('tools/validatemodule.sh')
EXCLUDE_PATH+=('tools/templates/')
EXCLUDE_PATH+=('composer/')


# Build a list of parameters for 'find' to actually keep ${KEEP}.
KEEP_FLAGS=()
for E in "${KEEP[@]}"; do
  KEEP_FLAGS+=("-path")
  KEEP_FLAGS+=("./${E}")
  KEEP_FLAGS+=("-prune")
  KEEP_FLAGS+=("-o")
done


# Create copies of all the stuff.
# Try to copy not much more than what's needed.

# Core repository.
(
  echo -n "Copying core ... "
  git archive ${GIT_REVISION} | tar -C "${PACKAGING_DIR}" -xf-    || exit ${?}

  cd "${PACKAGING_DIR}"                                           || exit ${?}
  if [ -d admin-dev ]; then
    mv admin-dev admin
  fi
  if [ -d install-dev ]; then
    mv install-dev install
  fi
  echo "done."
) || exit ${?}

# Composer repositories. Not reasonably doable without network access,
# but fortunately composer maintains a cache, so no heavy downloads.
(
  cd "${PACKAGING_DIR}"                                                        || exit ${?}
  COMPOSER=composer/php${PHP_VERSION}/composer.json composer install --no-dev  || exit ${?}
  COMPOSER=composer/php${PHP_VERSION}/composer.json composer dump-autoload -o  || exit ${?}
) || exit ${?}

# Theme repositories.
git cat-file -p ${GIT_REVISION}:themes | grep '^160000' | cut -d ' ' -f 3 | \
  while read T; do
  (
    THEME="themes/${T#*$'\t'}"
    HASH=${T%$'\t'*}
    DEFAULT_BRANCH=$(git cat-file -p ${GIT_REVISION}:.gitmodules | sed -n '
                      /^\[submodule.*'"${THEME/\//\\\/}"'/, /^\[submodule/ {
                        s/\s*branch = // p
                      }
                    ')
    DEFAULT_BRANCH=${DEFAULT_BRANCH:-master}

    echo "Copying ${THEME} ... "
    cd "${THEME}"                                                 || exit ${?}

    # Validation section. Does a 'git fetch', but doesn't change anything else.
    if [ ${OPTION_VALIDATE} = 'true' ]; then
      if [ $(git diff | wc -l) -ne 0 ] \
         || [ $(git diff --staged | wc -l) -ne 0 ]; then
        echo "There are uncommitted changes in ${THEME}. Aborting."
        exit 1
      fi

      LOCAL=$(git show -q ${DEFAULT_BRANCH} | head -1 | cut -d ' ' -f 2)
      if [ ${HASH} != ${LOCAL} ]; then
        echo "Repository ${THEME} not up to date, branch ${DEFAULT_BRANCH} not"
        echo "committed in thirty bees core. Aborting."
        exit 1
      fi

      git fetch
      REMOTE=$(git show -q origin/${DEFAULT_BRANCH} | head -1 | cut -d ' ' -f 2)
      if [ ${LOCAL} != ${REMOTE} ]; then
        echo "Repository ${THEME} not up to date, branches ${DEFAULT_BRANCH}"
        echo "and origin/${DEFAULT_BRANCH} don't match. Aborting."
        exit 1
      fi

      unset LOCAL MASTER
    fi
    unset DEFAULT_BRANCH

    mkdir -p "${PACKAGING_DIR}/${THEME}"
    git archive ${HASH} | tar -C "${PACKAGING_DIR}/${THEME}" -xf- || exit ${?}

    echo "done."
  ) || exit ${?}
done || exit ${?}

# Cleaning :-)
(
  cd "${PACKAGING_DIR}"                                           || exit ${?}
  for E in "${EXCLUDE_FILE[@]}"; do
    find . "${KEEP_FLAGS[@]}" -type f -name "${E}" -print | while read F; do
      rm -f "${F}"
    done
  done
  for E in "${EXCLUDE_DIR[@]}"; do
    find . "${KEEP_FLAGS[@]}" -type d -name "${E}" -print | while read D; do
      rm -rf "${D}"
    done
  done
  for E in "${EXCLUDE_PATH[@]}"; do
    rm -rf "${E}"
  done
)

# Module repositories. After cleaning, because they have their own build
# script, producing already clean output (and cleaning differently).
git cat-file -p ${GIT_REVISION}:modules | grep '^160000' | cut -d ' ' -f 3 \
| while read M; do
  HASH=${M%$'\t'*}
  M="${M#*$'\t'}"
  MODULE="modules/${M}"

  # Don't package modules not required for installation.
  PACKAGE='true'
  if [ -n "${TBMODULE_LIST[*]}" ]; then
    PACKAGE='false'
    for MM in "${TBMODULE_LIST[@]}"; do
      if [ "${MM}" = "${M}" ]; then
        PACKAGE='true'
        break
      fi
    done
  fi
  [ "${PACKAGE}" = 'false' ] && continue

  (
    echo "Copying ${MODULE} ... "
    cd "${MODULE}"                                                || exit ${?}

    mkdir -p "${PACKAGING_DIR}/${MODULE}"

    VALIDATE_FLAGS='--validate'
    [ ${OPTION_VALIDATE} = 'false' ] && VALIDATE_FLAGS='--no-validate'

    ../../tools/buildmodule.sh --target-dir "${PACKAGING_DIR}/${MODULE}" \
      --quiet ${VALIDATE_FLAGS} ${HASH}                           || exit ${?}
    unset VALIDATE_FLAGS

    echo "done."
  ) || exit ${?}
done || exit ${?}


### Make the full package.

# Generate the MD5 list and zip up everything. That simple.
(
  echo -n "Creating package ... "
  cd "${PACKAGING_DIR}"                                           || exit ${?}
  php ./tools/generatemd5list.php                                 || exit ${?}
  rm -f cache/class_index.php # Gets created by running generatemd5list.php.
  if [ -z "${TARGET_DIR}" ]; then
    zip -r -q "${PACKAGE_NAME}".zip .                             || exit ${?}
  else
    # Make links normal files to match what unpacking a ZIP would give.
    TEMPNAME=$(mktemp -u)
    find . -type l -print | while read F; do
      cp -p "${F}" ${TEMPNAME}                                    || exit ${?}
      mv ${TEMPNAME} "${F}"                                       || exit ${?}
    done || exit ${?}
    unset TEMPNAME
  fi
  echo "done."
) || exit ${?}

if [ -z "${TARGET_DIR}" ]; then
  mv "${PACKAGING_DIR}"/"${PACKAGE_NAME}".zip .                   || exit ${?}
  echo "Created ${PACKAGE_NAME}.zip successfully."
else
  echo "Created files in ${TARGET_DIR} successfully."
fi


# Cleanup happens via a trap.
exit 0
