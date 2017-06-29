<?php
/**
 * 2007-2016 PrestaShop
 *
 * Thirty Bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017 Thirty Bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://www.thirtybees.com for more information.
 *
 *  @author    Thirty Bees <contact@thirtybees.com>
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2017 Thirty Bees
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * This script will update the tax rule groups for virtual products from all EU localization packs.
 * All it needs is that the correct tax in each localization pack is marked with `eu-tax-group="virtual"`.
 *
 * Usage: just execute this script.
 *
 *
 * 1)
 *  Parse all files under /localization,
 *  looking for <tax> elements that have the attribute eu-tax-group="virtual".
 *
 *  Store the list of files (`$euLocalizationFiles`) where such taxes have been found,
 *  in a next step we'll store the new tax group in each of them.
 *
 * 2)
 * Remove all taxRulesGroup's that have the attribute eu-tax-group="virtual".
 *
 * 3)
 * Build a new taxRulesGroup containing all the taxes found in the first step.
 *
 * 4)
 * Inject the new taxRulesGroup into all packs of `$euLocalizationFiles`, not forgetting
 * to also inject the required taxes.
 *
 * Warning: do not duplicate the tax with attribute eu-tax-group="virtual" of the pack being updated.
 *
 * Mark the injected group with the attributes eu-tax-group="virtual" and auto-generated="1"
 * Mark the injected taxes witth the attributes from-eu-tax-group="virtual" and auto-generated="1"
 *
 * Clean things up by removing all the previous taxes that had the attributes eu-tax-group="virtual" and auto-generated="1"
 */

@ini_set('display_errors', 'on');

$localizationPacksRoot = realpath(dirname(__FILE__) . '/../../localization');

if (!$localizationPacksRoot) {
    die("Could not find the folder containing the localization files (should be 'localization' at the root of the thirty bees folder).\n");
}

$euLocalizationFiles = [];

foreach (scandir($localizationPacksRoot) as $entry) {
    if (!preg_match('/\.xml$/', $entry)) {
        continue;
    }


    $localizationPackFile = $localizationPacksRoot . DIRECTORY_SEPARATOR . $entry;

    $localizationPack = @simplexml_load_file($localizationPackFile);

    // Some packs do not have taxes
    if (!$localizationPack || !$localizationPack->taxes->tax) {
        continue;
    }

    foreach ($localizationPack->taxes->tax as $tax) {
        if ((string)$tax['eu-tax-group'] === 'virtual') {
            if (!isset($euLocalizationFiles[$localizationPackFile])) {
                $euLocalizationFiles[$localizationPackFile] = [
                    'virtualTax' => $tax,
                    'pack' => $localizationPack,
                    'iso_code_country' => basename($entry, '.xml')
                ];
            } else {
                die("Too many taxes with eu-tax-group=\"virtual\" found in `$localizationPackFile`.\n");
            }
        }
    }
}

function addTax(SimpleXMLElement $taxes, SimpleXMLElement $tax, array $attributesToUpdate = [], array $attributesToRemove = [])
{
    $newTax = new SimpleXMLElement('<tax/>');

    $taxRulesGroups = $taxes->xpath('//taxRulesGroup[1]');
    $insertBefore = $taxRulesGroups[0];

    if (!$insertBefore) {
        die("Could not find any `taxRulesGroup`, don't know where to append the tax.\n");
    }

    /**
     * Add the `tax` node before the first `taxRulesGroup`.
     * Yes, the dom API is beautiful.
     */
    $dom = dom_import_simplexml($taxes);

    $new = $dom->insertBefore(
        $dom->ownerDocument->importNode(dom_import_simplexml($newTax)),
        dom_import_simplexml($insertBefore)
    );

    $newTax = simplexml_import_dom($new);

    $newAttributes = [];

    foreach ($tax->attributes() as $attribute) {
        $name = $attribute->getName();

        // This attribute seems to cause trouble, skip it.
        if ($name === 'account_number' || in_array($name, $attributesToRemove)) {
            continue;
        }

        $value = (string)$attribute;

        $newAttributes[$name] = $value;
    }

    $newAttributes = array_merge($newAttributes, $attributesToUpdate);

    foreach ($newAttributes as $name => $value) {
        $newTax->addAttribute($name, $value);
    }

    return $newTax;
}

function addTaxRule(SimpleXMLElement $taxRulesGroup, SimpleXMLElement $tax, $iso_code_country)
{
    $taxRule = $taxRulesGroup->addChild('taxRule');

    $taxRule->addAttribute('iso_code_country', $iso_code_country);
    $taxRule->addAttribute('id_tax', (string)$tax['id']);

    return $taxRule;
}

foreach ($euLocalizationFiles as $path => $file) {
    $nodesToKill = [];

    // Get max tax id, and list of nodes to kill
    $taxId = 0;
    foreach ($file['pack']->taxes->tax as $tax) {
        if ((string)$tax['auto-generated'] === "1" && (string)$tax['from-eu-tax-group'] === 'virtual') {
            $nodesToKill[] = $tax;
        } else {
            // We only count the ids of the taxes we're not going to remove!
            $taxId = max($taxId, (int)$tax['id']);
        }
    }

    foreach ($file['pack']->taxes->taxRulesGroup as $trg) {
        if ((string)$trg['auto-generated'] === "1" && (string)$trg['eu-tax-group'] === 'virtual') {
            $nodesToKill[] = $trg;
        }
    }

    // This is the first tax id we're allowed to use.
    $taxId++;

    // Prepare new taxRulesGroup

    $taxRulesGroup = $file['pack']->taxes->addChild('taxRulesGroup');
    $taxRulesGroup->addAttribute('name', 'EU VAT For Virtual Products');
    $taxRulesGroup->addAttribute('auto-generated', '1');
    $taxRulesGroup->addAttribute('eu-tax-group', 'virtual');

    addTaxRule($taxRulesGroup, $file['virtualTax'], $file['iso_code_country']);

    foreach ($euLocalizationFiles as $foreignPath => $foreignFile) {
        if ($foreignPath === $path) {
            // We already added the tax that belongs to this pack
            continue;
        }

        $tax = addTax($file['pack']->taxes, $foreignFile['virtualTax'], [
            'id' => (string)$taxId,
            'auto-generated' => '1',
            'from-eu-tax-group' => 'virtual'
        ], ['eu-tax-group']
        );

        addTaxRule($taxRulesGroup, $tax, $foreignFile['iso_code_country']);

        $taxId++;
    }

    foreach ($nodesToKill as $node) {
        unset($node[0]);
    }

    $dom = new DOMDocument("1.0");
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->loadXML($file['pack']->asXML());
    file_put_contents($path, $dom->saveXML());
}

$nUpdated = count($euLocalizationFiles);

echo "Updated the virtual tax groups for $nUpdated localization files.\n";
