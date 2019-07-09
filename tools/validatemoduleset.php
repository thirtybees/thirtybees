<?php
/**
 * Copyright (C) 2019 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @copyright 2019 thirty bees
 * @license   Open Software License (OSL 3.0)
 */

if (php_sapi_name() !== 'cli') {
    print("This script wants to run on the command line, only.\n");
    exit(1);
}

function usage()
{
    print("Usage: php validatemoduleset.php [-h|--help] [<git revision>]\n");
    print("\n");
    print("This script validates modules required by themes, as stated in\n");
    print("their config.xml, with those present in config/default_modules.php\n");
    print("(default modules) as well as with all available thirty bees \n");
    print("modules in all.json on https://api.thirtybees.com/. It reports\n");
    print("missing ones as well as obsolete ones.\n");
    print("\n");
    print("Validated are not files on disk, but those committed to the Git\n");
    print("repositoriy. Default revision is HEAD (latest commit).\n");
    print("\n");
    print("    -h, --help            Show this help and exit.\n");
    print("\n");
    print("Return value is 1 if missing or obsolete modules found, else 0.\n");
    print("\n");
}

/**
 * All not theme related modules. The list should match the one in
 * Module::getNotThemeRelatedModules(), plus deprecated modules.
 */
$notThemeRelatedModules = [
  // Payment modules.
  'authorizeaim',
  'bankwire',
  'custompayments',
  'ecbexchange',
  'paypal',
  'stripe',
  'vatnumber',
  // Dashboard modules.
  'dashactivity',
  'dashgoals',
  'dashproducts',
  'dashtrends',
  // Analytics and statistics modules.
  'ganalytics',
  'gapi',
  'mailchimp',
  'piwikanalyticsjs',
  'statsdata',
  'statsmodule',
  'trackingfront',
  // Installation maintenance modules.
  'apcumanager',
  'coreupdater',
  'cronjobs',
  'crowdin',
  'mdimagemagick',
  'opcachemanager',
  'overridecheck',
  'sitemap',
  'tbcleaner',
  'tbupdater',
  // Deprecated modules.
  'donationminer',
  'fixerio',
];

/**
 * Submodules. Some modules install submodules, which neither have their
 * own repository, nor are they listed in all.json or default_modules.php.
 */
$submodules = [
    'beesblog' => [
        'beesblogcategories',
        'beesblogpopularposts',
        'beesblogrecentposts',
        'beesblogrelatedproducts',
    ],
];

/**
 * Theme independent variables.
 */
$flawsFound = false;
$neededModules = [];

/**
 * CLI arguments handling.
 */
$gitRevision = '';

array_shift($argv);
while ($arg = array_shift($argv)) {
    if ($arg === '-h' || $arg === '--help') {
        usage();
        exit(0);
    } else {
        exec('git show -q '.$arg.' 2>/dev/null', $result);
        if ($result) {
            $gitRevision = $arg;
        } else {
            print("Git revision '{$arg}' doesn't exist.\n");
            exit(1);
        }
        unset($result);
    }
}
unset($arg);

if ( ! $gitRevision) {
    $gitRevision = 'HEAD';
}

print("Using Git revision {$gitRevision}.\n");

// Change to the root folder of the repository.
$i = 0;
while ( ! file_exists('.git') && getcwd() !== '/' && $i < 100) {
  chdir('..');
  $i++;
}
if ( ! file_exists('.git')) {
    print("Can't find repository root. Please run this from inside the repo.\n");
    exit(1);
}

/**
 * Get module lists.
 */
// All available thirty bees modules.
$allModules = array_keys(json_decode(
  file_get_contents('https://api.thirtybees.com/updates/modules/all.json'),
  true
));
if ( ! $allModules) {
    print("Couldn't download all.json.\n");
    exit(1);
}

// Default modules.
require 'config/default_modules.php';
$defaultModules = $_TB_DEFAULT_MODULES_;
unset($_TB_DEFAULT_MODULES_);

// Apply submodules.
foreach ($submodules as $moduleName => $submoduleList) {
    if (in_array($moduleName, $allModules)) {
        $allModules = array_merge($allModules, $submoduleList);
    }
    if (in_array($moduleName, $defaultModules)) {
        $defaultModules = array_merge($defaultModules, $submoduleList);
    }
}

// Extract theme related modules.
$themeRelatedModules = array_diff($allModules, $notThemeRelatedModules);
$themeRelatedDefaultModules = array_diff(
    $defaultModules,
    $notThemeRelatedModules
);

// Print a summary.
print((string) count($allModules)." modules and submodules in all.json.\n");
print("Thereof ".count($themeRelatedModules)." theme related modules.\n");
print((string) count($defaultModules)." modules and submodules in default_modules.php.\n");
print("Thereof ".count($themeRelatedDefaultModules)." theme related modules.\n");

/**
 * Collect data about and test individual themes.
 */
exec('git cat-file -p '.$gitRevision.':themes', $themesList);
foreach ($themesList as $themeEntry) {
    // Drop entries not being a submodule, collect parameters.
    if ( ! preg_match(
        '#^160000\scommit\s([0-9a-f]*?)\s([^\s]*?)$#',
        $themeEntry, $matches
    )) {
        continue;
    }
    $themeCommit = $matches[1];
    $themeDir = 'themes/'.$matches[2];

    // Load theme configuration.
    exec(
        'cd '.$themeDir.' && git cat-file -p '.$themeCommit.':config.xml',
        $result
    );
    $themeConfig = simplexml_load_string(implode(' ', $result));
    $themeName = (string) $themeConfig->attributes()->name;
    unset($result);

    // Collect theme modules and hooks.
    $themeEnabled = [];
    $themeDisabled = [];
    $themeHooks = [];
    foreach ($themeConfig->modules->module as $moduleEntry) {
        $attributes = $moduleEntry->attributes();
        if ((string) $attributes->action === 'enable') {
            $themeEnabled[] = (string) $attributes->name;
        } else {
            $themeDisabled[] = (string) $attributes->name;
        }
    }
    foreach ($themeConfig->modules->hooks->hook as $hookEntry) {
        $hookModule = (string) $hookEntry->attributes()->module;
        if ( ! in_array($hookModule, $themeHooks)) {
            $themeHooks[] = $hookModule;
        }
    }
    unset($themeConfig);

    // TEST whether each module is in all.json and a theme related module.
    foreach (array_merge($themeEnabled, $themeDisabled) as $module) {
        if ( ! in_array($module, $allModules)) {
            print("Module {$module} in theme {$themeName} isn't in all.json.\n");
            $flawsFound = true;
        } elseif ( ! in_array($module, $themeRelatedModules)) {
            print("Module {$module} in theme {$themeName} isn't a theme related module.\n");
            $flawsFound = true;
        }
    }

    // TEST whether each theme related module is in config.xml.
    foreach ($themeRelatedModules as $module) {
        if ( ! in_array($module, $themeEnabled)
            && ! in_array($module, $themeDisabled)
        ) {
            print("Theme related module {$module} is in all.json, but not in config.xml of theme {$themeName}.\n");
            $flawsFound = true;
        }
    }

    // TEST whether each hook in the theme belongs to an enabled module.
    foreach ($themeHooks as $module) {
        if ( ! in_array($module, $themeEnabled)) {
            print("Theme {$themeName} has hooks for module {$module}, which isn't an enabled module.\n");
            $flawsFound = true;
        }
    }

    // Collect needed modules.
    foreach ($themeEnabled as $module) {
        if ( ! in_array($module, $neededModules)) {
            $neededModules[] = $module;
        }
    }
} // End loop over themes.

/**
 * Tests for themes combined.
 */
// TEST whether each default module is in all.json.
foreach ($defaultModules as $module) {
    if ( ! in_array($module, $allModules)) {
        print("Module {$module} is in default_modules.php, but not in all.json.\n");
        $flawsFound = true;
    }
}

// TEST whether each needed module is in default_modules.php.
foreach ($neededModules as $module) {
    if ( ! in_array($module, $defaultModules)) {
        print("Module {$module} is needed, but not in default_modules.php.\n");
        $flawsFound = true;
    }
}

// TEST whether each theme related default module is actually needed.
foreach ($themeRelatedDefaultModules as $module) {
    if ( ! in_array($module, $neededModules)) {
        print("Module {$module} is in default_modules.php, but not needed by any theme.\n");
        $flawsFound = true;
    }
}

/**
 * Finish.
 */
if ($flawsFound) {
    print("Flaws found, validation failed.\n");
    exit(1);
}

print("No flaws found, validation succeeded.\n");
exit(0);
?>
