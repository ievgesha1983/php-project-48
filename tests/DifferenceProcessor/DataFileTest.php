<?php

namespace Hexlet\Code\Tests\DifferenceProcessor;

use Hexlet\Code\DifferenceProcessor\DataFile;
use PHPUnit\Framework\TestCase;

class DataFileTest extends TestCase
{
    private static DataFile $firstFile;
    private static DataFile $secondFile;
    private static DataFile $jsanFile;
    private static DataFile $errFileJson;
    private static DataFile $errFileYaml;

    public static function setUpBeforeClass(): void
    {
        self::$firstFile = new DataFile("tests/fixtures/file1.json");
        self::$firstFile->parse();
        self::$secondFile = new DataFile("tests/fixtures/file2.yaml");
        self::$secondFile->parse();
        self::$jsanFile = new DataFile('tests/fixtures/file1.jsan');
        self::$jsanFile->parse();
        self::$errFileJson = new DataFile('tests/fixtures/error-file.json');
        self::$errFileJson->parse();
        self::$errFileYaml = new DataFile('tests/fixtures/error-file.yml');
        self::$errFileYaml->parse();
    }
    public function testGetters(): void
    {
        $this->assertEquals('file1', self::$firstFile->getName());
        $this->assertEquals('file1.json', self::$firstFile->getBaseName());
        $this->assertEquals('tests/fixtures', self::$firstFile->getPath());
        $this->assertEquals('json', self::$firstFile->getExtension());
    }

    public function testParseAndGetData(): void
    {
        $this->assertEquals('hexlet.io', self::$firstFile->getData()->host);
        $this->assertEquals(true, self::$secondFile->getData()->verbose);

        $this->assertNull(self::$errFileJson->getData());
        $this->assertNull(self::$errFileYaml->getData());
    }

    public function testToJson(): void
    {
        $expected = '{"host":"hexlet.io","timeout":50,"proxy":"123.234.53.22","follow":false}';
        $this->assertEquals($expected, self::$firstFile->toJson());

        $this->assertEquals('null', self::$errFileJson->toJson());
    }

    public function testGetDifferences(): void
    {

        $expected = [
            ['type' => -1, 'key' => 'follow', 'value' => false],
            ['type' => 0, 'key' => 'host', 'value' => 'hexlet.io'],
            ['type' => -1, 'key' => 'proxy', 'value' => '123.234.53.22'],
            ['type' => -1, 'key' => 'timeout', 'value' => 50],
            ['type' => 1, 'key' => 'timeout', 'value' => 20],
            ['type' => 1, 'key' => 'verbose', 'value' => true],
        ];
        $result = self::$firstFile->getDifferences(self::$secondFile);
        $this->assertEquals($expected, $result);
        $this->assertNull(self::$errFileJson->getDifferences(self::$secondFile));
        $this->assertNull(self::$firstFile->getDifferences(self::$errFileYaml));
    }
}
