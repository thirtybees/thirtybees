<?php

class AdminConfigurationPagesCest
{
    private $adminPages = [
        'AdminDashboard' => [],
        'AdminCatalog' => [
            'AdminProducts',
            'AdminCategories',
            'AdminTracking',
            'AdminAttributesGroups',
            'AdminFeatures',
            'AdminManufacturers',
            'AdminSuppliers',
            'AdminTags',
            'AdminAttachments',
        ],
        'AdminParentOrders' => [
            'AdminOrders',
            'AdminInvoices',
            'AdminReturn',
            'AdminDeliverySlip',
            'AdminSlip',
            'AdminStatuses',
            'AdminOrderMessage',
        ],
        'AdminParentCustomer' => [
            'AdminCustomers',
            'AdminAddresses',
            'AdminGroups',
            'AdminCarts',
            'AdminCustomerThreads',
            'AdminContacts',
            'AdminGenders',
        ],
        'AdminPriceRule' => [
            'AdminCartRules',
            'AdminSpecificPriceRule',
        ],
        'AdminParentModules' => [
            'AdminModules',
            'AdminModulesPositions',
            'AdminPayment',
        ],
        'AdminParentShipping' => [
            'AdminCarriers',
            'AdminShipping',
        ],
        'AdminParentLocalization' => [
            'AdminLocalization',
            'AdminLanguages',
            'AdminZones',
            'AdminCountries',
            'AdminStates',
            'AdminCurrencies',
            'AdminTaxes',
            'AdminTaxRulesGroup',
            'AdminTranslations',
        ],
        'AdminParentPreferences' => [
            'AdminPreferences',
            'AdminOrderPreferences',
            'AdminPPreferences',
            'AdminCustomerPreferences',
            'AdminThemes',
            'AdminMeta',
            'AdminCmsContent',
            'AdminImages',
            'AdminStores',
            'AdminSearchConf',
            'AdminMaintenance',
            'AdminGeolocation',
            'AdminCustomCode',
        ],
        'AdminTools' => [
            'AdminInformation',
            'AdminPerformance',
            'AdminEmails',
            'AdminImport',
            'AdminBackup',
            'AdminRequestSql',
            'AdminLogs',
            'AdminWebservice',
        ],
        'AdminAdmin' => [
            'AdminAdminPreferences',
            'AdminQuickAccesses',
            'AdminEmployees',
            'AdminProfiles',
            'AdminAccess',
            'AdminTabs',
        ],
        'AdminParentStats' => [
            'AdminStats',
            'AdminSearchEngines',
            'AdminReferrers',
        ],
    ];

    public function _before(AcceptanceTester $I)
    {
        $I->resizeWindow(1920, 1080);
    }

    public function _after(AcceptanceTester $I)
    {
    }

    private function login(AcceptanceTester $I)
    {
        $I->amOnPage('/admin-dev/index.php');
        $I->waitForElementVisible(['css' => '#email']);

        $I->fillField(['css' => '#email'], 'test@thirty.bees');
        $I->fillField(['css' => '#passwd'], 'thirtybees');
        $I->click('Log in');
    }

    private function checkAdminPage(AcceptanceTester $I, $child)
    {
        $childElement = ['css' => "#subtab-{$child}"];

        $I->seeElementInDOM($childElement);
        $I->waitForElementVisible($childElement, 30);
        $I->click($childElement);

        $I->see('Quick Access');
    }

    public function testAdminPages(AcceptanceTester $I)
    {
        $this->login($I);

        foreach ($this->adminPages as $parent => $children) {
            $parentElement = ['css' => "#maintab-{$parent}"];

            $I->waitForElementVisible($parentElement, 30);
            $I->click($parentElement);

            $I->see('Quick Access');
            foreach ($children as $child) {
                // Move mouse out and back in to make the submenu visible.
                $I->moveMouseOver(null, 0, 0);
                $I->moveMouseOver($parentElement);

                $this->checkAdminPage($I, $child);
            }
        }
    }
}
