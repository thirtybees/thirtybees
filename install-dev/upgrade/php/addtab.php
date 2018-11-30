<?php
/**
 * Copyright (C) 2018 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @copyright 2018 thirty bees
 * @license   Open Software License (OSL 3.0)
 */

/**
 * Add a back office menu tab for a specific class and position it below
 * another tab. Does nothing if a tab for the class exists already.
 *
 * @param string $tabClassName    Name of the specific class.
 * @param string $tabName         Name of the tab. Defaults to the class name.
 * @param string $parentClassName Name of the class of the parent menu.
 *                                Defaults to the top level menu.
 * @param string $aboveClassName  Name of the class of the tab just above the
 *                                new tab. Serves for moving a tab upwards after
 *                                creation. Defaults to the bottommost position.
 *
 * @since 1.0.8
 */
function addTab($tabClassName, $tabName = false,
                $parentClassName = false, $aboveClassName = false)
{
    require_once __DIR__.'/environment.php';

    if (Tab::getIdFromClassName($tabClassName)) {
        return;
    }

    try {
        $tab = new Tab();

        $tab->class_name  = $tabClassName;
        if ($parentClassName
            && $idParent = Tab::getIdFromClassName($parentClassName)) {
            $tab->id_parent = $idParent;
        }

        if ($tabName) {
            $langs = Language::getLanguages();
            foreach ($langs as $lang) {
                $translation = Translate::getAdminTranslation($tabName);
                $tab->name[$lang['id_lang']] = $translation;
            }
        }

        $tab->save();
    } catch (Exception $e) {
    }

    // TODO: move the tab upwards if necessary.
}
