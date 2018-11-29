#!/usr/bin/env bash

function usage {
  echo "Usage: ./build.sh [-h|--help] [<git revision>]"
  echo
  echo "This script builds installation and extra packages from the current"
  echo "repository. Default revision is the latest tag ( = latest release)."
  echo
  echo "    -h, --help            Show this help and exit."
  echo
  echo "    --skip-installer      Skip building the installation package. This"
  echo "                          brings a substantial speedup for building"
  echo "                          just the extra package."
  echo
  echo "    --[no-]validate       Enforce [no] validation. Default is to"
  echo "                          validate when packaging 'master' or the"
  echo "                          latest tag, but not when packaging others."
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
  if [ -n "${PACKAGING_DIR_EXTRA}" ]; then
    echo "Deleting temporary packaging Extra directory."
    rm -rf ${PACKAGING_DIR_EXTRA}
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

OPTION_BUILD_INSTALLER='true'
OPTION_VALIDATE='auto'
GIT_REVISION=''

while [ ${#} -ne 0 ]; do
  case "${1}" in
    '-h'|'--help')
      usage
      exit 0
      ;;
    '--skip-installer')
      OPTION_BUILD_INSTALLER='false'
      ;;
    '--validate')
      OPTION_VALIDATE='true'
      ;;
    '--no-validate')
      OPTION_VALIDATE='false'
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

# Latest tag = latest release. Ignore PS version tags.
LATEST_TAG=$(git tag | \
               sed -n '/^[1-9]\.[0-4]\.[0-9]*$/ p' | \
               sort --reverse --version-sort | \
               head -1)
[ -n "${GIT_REVISION}" ] || GIT_REVISION=${LATEST_TAG}
echo "Packaging Git revision '${GIT_REVISION}'."
unset LATEST_TAG


### Build the installation package.

if [ ${OPTION_BUILD_INSTALLER} = 'true' ]; then

  PACKAGE_NAME="thirtybees-v${GIT_REVISION}"
  rm -f "${PACKAGE_NAME}".zip

  ### Plausibility heuristics.
  #
  # Suboptimal releases have been packaged due to (whatever), so let's try to
  # catch all the situations where some pre-release work steps have been
  # forgotten.

  # Heuristics on wether to validate the stuff to be packaged. This should detect
  # 'forgotten' commits in core as well as modules in need of a new release.
  # Heuristics:
  # - Less than 30 commits on the branch of the to be packaged commit.

  GIT_BRANCH=$(git branch --contains "${GIT_REVISION}" | \
               grep -v "detached" | head -1 | cut -b 3-)
  COMMITS_ON_TOP=$(git log --oneline "${GIT_REVISION}".."${GIT_BRANCH}" | wc -l)
  if [ ${OPTION_VALIDATE} = 'auto' ]; then
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

  # Create packaging directory.
  PACKAGING_DIR=$(mktemp -d)

  PACKAGING_DIR+="/${PACKAGE_NAME}"
  mkdir "${PACKAGING_DIR}"
  export PACKAGING_DIR


  ### Build packaging filters.
  #
  # As we have not much control over what composer does, we first put everything
  # into a directory, then remove surplus file.

  # Files not needed in the release package.
  EXCLUDE_FILE=('.coveralls.yml')
  EXCLUDE_FILE+=('.gitignore')
  EXCLUDE_FILE+=('.gitmodules')
  EXCLUDE_FILE+=('.scrutinizer.yml')
  EXCLUDE_FILE+=('.travis.yml')
  EXCLUDE_FILE+=('architecture.md')
  EXCLUDE_FILE+=('codeception.yml')
  EXCLUDE_FILE+=('composer.json')
  EXCLUDE_FILE+=('composer.lock')
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
  KEEP+=('composer.json')     # Allow merchants to upgrade vendor packages.

  # Exclude paths, for individual files and directories to be excluded.
  # EXCLUDE_PATH=('generatemd5list.php')  <- Can't get removed.
  EXCLUDE_PATH=('tools/buildmodule.sh')
  EXCLUDE_PATH+=('tools/validatemodule.sh')
  EXCLUDE_PATH+=('tools/templates/')


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
    cd "${PACKAGING_DIR}"                                           || exit ${?}
    composer install --no-dev                                       || exit ${?}
    composer dump-autoload -o                                       || exit ${?}
  ) || exit ${?}

  # Theme repositories.
  git cat-file -p ${GIT_REVISION}:themes | grep '^160000' | cut -d ' ' -f 3 | \
    while read T; do
    (
      THEME="themes/${T#*$'\t'}"
      HASH=${T%$'\t'*}
      DEFAULT_BRANCH=$(git cat-file -p ${GIT_REVISION}:.gitmodules | sed -n '
                        /^\[submodule.*'"${S/\//\\\/}"'/, /^\[submodule/ {
                          s/\s*branch = // p
                        }
                      ')

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
  git cat-file -p ${GIT_REVISION}:modules | grep '^160000' | cut -d ' ' -f 3 | \
    while read M; do
    (
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
    zip -r -q "${PACKAGE_NAME}".zip .                               || exit ${?}
    echo "done."
  ) || exit ${?}

  mv "${PACKAGING_DIR}"/"${PACKAGE_NAME}".zip .                     || exit ${?}
  echo "Created ${PACKAGE_NAME}.zip successfully."
fi # Done building the installation package.


### Also build the Extras package.

# As 'git archive' always packages the full path repository, we have to use
# a packaging directory.
PACKAGING_DIR_EXTRA=$(mktemp -d)

# Copy what we need.
git archive ${GIT_REVISION} "install-dev/upgrade" \
| tar -C "${PACKAGING_DIR_EXTRA}" -xf- --strip-components=2       || exit ${?}

# Package.
EXTRA_PACKAGE="thirtybees-extra-v${GIT_REVISION}".zip
(
  cd "${PACKAGING_DIR_EXTRA}"                                     || exit ${?}

  # No need for index files.
  find . -name index.php -delete                                  || exit ${?}
  # No need for the README.md either.
  rm -f README.md                                                 || exit ${?}
  # 1.0.0.sql is needed in the migration package, only.
  rm -f sql/1.0.0.sql                                             || exit ${?}

  zip -r -q "${EXTRA_PACKAGE}" .                                  || exit ${?}
)

mv "${PACKAGING_DIR_EXTRA}"/"${EXTRA_PACKAGE}" .                  || exit ${?}
echo "Created ${EXTRA_PACKAGE} successfully."


# Cleanup happens via a trap.
exit 0
