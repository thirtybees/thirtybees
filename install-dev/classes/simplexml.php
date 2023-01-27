<?php

/**
 * 2007-2016 PrestaShop
 *
 * thirty bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017-2018 thirty bees
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
 * @author    thirty bees <contact@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017-2018 thirty bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */
class InstallSimplexmlElement extends SimpleXMLElement
{
    /**
     * Can add SimpleXMLElement values in XML tree
     *
     * @see SimpleXMLElement::addChild()
     *
     * @param string $name
     * @param string|SimpleXMLElement|null $value
     * @param string|null $namespace
     *
     * @return SimpleXMLElement|null
     */
    #[\ReturnTypeWillChange]
    public function addChild($name, $value = null, $namespace = null)
    {
        $content = trim((string) $value);
        if ($value instanceof SimplexmlElement) {
            if (strlen($content) > 0) {
                $newElement = parent::addChild($name, str_replace('&', '&amp;', $content), $namespace);
            } else {
                $newElement = parent::addChild($name);
                foreach ($value->attributes() as $k => $v) {
                    $newElement->addAttribute($k, $v);
                }
            }

            foreach ($value->children() as $child) {
                $newElement->addChild($child->getName(), $child);
            }

            return $newElement;
        } else {
            return parent::addChild($name, str_replace('&', '&amp;', $content), $namespace);
        }
    }

    /**
     * Generate nice and sweet XML
     *
     * @see SimpleXMLElement::asXML()
     *
     * @param string|null $filename
     *
     * @return bool|string
     */
    #[\ReturnTypeWillChange]
    public function asXML($filename = null)
    {
        $dom = new DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML(parent::asXML());

        if ($filename) {
            return (bool) file_put_contents($filename, $dom->saveXML());
        }

        return $dom->saveXML();
    }
}
