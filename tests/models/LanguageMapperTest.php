<?php

class LanguageMapperTest extends PHPUnit_Extensions_Database_TestCase
{
    protected $pdo = null;

    /**
     * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
     */
    public function getConnection()
    {
        if (null === $this->pdo) {
            $this->pdo = new PDO('sqlite::memory:');
            $this->pdo->exec('create table lang(lang_name, lang_abbr, id int)');
        }
        return $this->createDefaultDBConnection($this->pdo, ':memory:');
    }

    /**
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    public function getDataSet()
    {
        return $this->createXMLDataSet(__DIR__ . '/_files/languages_seed.xml');
    }

    /**
     * @param string $language The string to check whether the name exists or not
     * @param bool   $result   Whether the test should pass or fail
     *
     * @dataProvider validatingLanguagesProvider
     * @return void
     */
    public function testValidatingLanguages($language, $result)
    {

        $request = new Request([],[]);
        $languageMapper = new LanguageMapper($this->getConnection()->getConnection(), $request);

        $this->assertEquals($result, $languageMapper->isLanguageAvailable($language));
    }

    /**
     * Data provider for testValidatingLanguages
     *
     * @return array
     */
    public function validatingLanguagesProvider()
    {
        return [
            // ['Language full name', 'boolean result whether the language is found'],
            ['German', true],
            ['Polish', false],
        ];
    }
}
