<?php

namespace Unit;

use Codeception\Test\Unit;
use http\Exception\RuntimeException;
use Tests\Support\UnitTester;

class LocalizationPacksTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;


    /**
     * This tests verifies that EU Tax Group is the same in all localization packs
     */
    public function testEuTaxGroup()
    {
        // collect EU countries
        $localizationPacksRoot = _PS_ROOT_DIR_ . '/localization';
        $euLocalizationFiles = [];
        $euGlobalRates = [];
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
                    if (!isset($euLocalizationFiles[$entry])) {
                        $name = (string)$tax['name'];
                        $rate = (float)$tax['rate'];
                        $euGlobalRates[$name] = $rate;
                        $euLocalizationFiles[$entry] = $localizationPack;
                    } else {
                        throw new RuntimeException("Too many taxes with eu-tax-group=\"virtual\" found in `$localizationPackFile`.\n");
                    }
                }
            }
        }
        ksort($euGlobalRates);

        // validate that the taxes matches
        foreach ($euLocalizationFiles as $filepath => $localizationPack) {
            $euLocalRates = [];
            foreach ($localizationPack->taxes->tax as $tax) {
                if ((string)$tax['eu-tax-group'] === 'virtual' || ((string)$tax['auto-generated'] === "1" && (string)$tax['from-eu-tax-group'] === 'virtual')) {
                    $name = (string)$tax['name'];
                    $rate = (float)$tax['rate'];
                    $euLocalRates[$name] = $rate;
                }
            }
            ksort($euLocalRates);
            $this->assertSame($euGlobalRates, $euLocalRates, "Localization pack $filepath EU rates are not valid. Run install-dev/dev/update_eu_taxrulegroups.php script to fix this.");
        }

    }
}
