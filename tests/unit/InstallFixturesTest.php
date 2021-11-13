<?php


class InstallFixturesTest extends \Codeception\TestCase\Test
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    /**
     * Returns all xml fixtures files
     *
     * @return array
     */
    public function getXmlFiles()
    {
        $rootDir = _PS_ROOT_DIR_ . '/install-dev/data/xml/';
        $langDir = _PS_ROOT_DIR_ . '/install-dev/langs/';

        $langs = array_filter(@scandir($langDir), function ($i) {
            return preg_match('/^[a-z]{2}$/', $i);
        });

        $files = [];
        foreach (scandir($rootDir) as $item) {
            if (preg_match('/^(.*)\.xml$/', $item, $m)) {
                $files[$m[1]] = [
                    'base' => $rootDir . $item
                ];
                $langFiles = [];
                foreach ($langs as $lang) {
                    $langPath = $langDir . $lang . '/data/' . $item;
                    if (file_exists($langPath)) {
                        $langFiles[$lang] = $langPath;
                    }
                }
                if ($langFiles) {
                    $files[$m[1]]['lang'] = $langFiles;
                }
            }
        }
        return $files;
    }

    /**
     * Returns data for testLangEntriesExists test
     *
     * @return array
     */
    public function getLangXmlFiles()
    {
        $result = [];
        foreach ($this->getXmlFiles() as $name => $entry) {
            if (isset($entry['lang'])) {
                foreach ($entry['lang'] as $lang => $langFile) {
                    $testName = "Entity '$name', language '$lang'";
                    $result[$testName] = [$name, $lang, $entry['base'], $langFile];
                }
            }
        }
        return $result;
    }


    /**
     * This tests verifies that for each entry in /data/xml/<entity>.xml
     * there exists an entry in /langs/<lang>/data/<entity>.xml file
     *
     * @dataProvider getLangXmlFiles
     *
     * @param string $entity
     * @param string $lang
     * @param string $baseFile
     * @param string $langFile
     */
    public function testLangEntriesExists($entity, $lang, $baseFile, $langFile)
    {
        if ($entity !== 'configuration') {
            $baseData = @simplexml_load_file($baseFile);
            $langData = @simplexml_load_file($langFile);
            foreach ($baseData->entities->{$entity} as $node) {
                $identifier = (string)$node['id'];
                $xpathQuery = $entity . '[@id="' . $identifier . '"]';
                $nodeLang = $langData->xpath($xpathQuery);
                $localPath = str_replace(_PS_ROOT_DIR_, '', $langFile);
                $this->assertNotEmpty($nodeLang, "Missing entry for '$identifier' in fixture file '$localPath'");
            }
        }
    }
}
