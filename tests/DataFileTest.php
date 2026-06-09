<?php

namespace Differ\Tests;

use Differ\DataFile;
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
        $this->assertEquals('value', self::$firstFile->getData()->common->setting6->key);
        $this->assertEquals(45, self::$secondFile->getData()->group3->deep->id->number);

        $this->assertNull(self::$errFileJson->getData());
        $this->assertNull(self::$errFileYaml->getData());
    }

    public function testToJson(): void
    {
        $expected = file_get_contents('tests/fixtures/file1_to_json_result.txt');
        $this->assertEquals($expected, self::$firstFile->toJson());

        $this->assertEquals('null', self::$errFileJson->toJson());
    }

    public function testGetDifferences(): void
    {

        $expected = [
            ['type' => 0, 'key' => 'common', 'value' => [
                ['type' => 1, 'key' => 'follow', 'value' => false],
                ['type' => 0, 'key' => 'setting1', 'value' => 'Value 1'],
                ['type' => -1, 'key' => 'setting2', 'value' => '200'],
                ['type' => -1, 'key' => 'setting3', 'value' => true],
                ['type' => 1, 'key' => 'setting3', 'value' => null],
                ['type' => 1, 'key' => 'setting4', 'value' => 'blah blah'],
                ['type' => 1, 'key' => 'setting5', 'value' => [
                    ['type' => 0, 'key' => 'key5', 'value' => 'value5'],
                ]],
                ['type' => 0, 'key' => 'setting6', 'value' => [
                    ['type' => 0, 'key' => 'doge', 'value' => [
                        ['type' => -1, 'key' => 'wow', 'value' => ''],
                        ['type' => 1, 'key' => 'wow', 'value' => 'so much'],
                    ]],
                    ['type' => 0, 'key' => 'key', 'value' => 'value'],
                    ['type' => 1, 'key' => 'ops', 'value' => 'vops'],
                ]],
            ]],
            ['type' => 0, 'key' => 'group1', 'value' => [
                ['type' => -1, 'key' => 'baz', 'value' => 'bas'],
                ['type' => 1, 'key' => 'baz', 'value' => 'bars'],
                ['type' => 0, 'key' => 'foo', 'value' => 'bar'],
                ['type' => -1, 'key' => 'nest', 'value' => [
                    ['type' => 0, 'key' => 'key', 'value' => 'value'],
                ]],
                ['type' => 1, 'key' => 'nest', 'value' => 'str']
            ]],
            ['type' => -1, 'key' => 'group2', 'value' => [
                ['type' => 0, 'key' => 'abc', 'value' => 12345],
                ['type' => 0, 'key' => 'deep', 'value' => [
                    ['type' => 0, 'key' => 'id', 'value' => 45],
                ]],
            ]],
            ['type' => 1, 'key' => 'group3', 'value' => [
                ['type' => 0, 'key' => 'deep', 'value' => [
                    ['type' => 0, 'key' => 'id', 'value' => [
                        ['type' => 0, 'key' => 'number', 'value' => 45],
                    ]],
                ]],
                ['type' => 0, 'key' => 'fee', 'value' => 100500],
            ]],
        ];
        $result = self::$firstFile->getDifferences(self::$secondFile);
        $this->assertEquals($expected, $result);
        $this->assertNull(self::$errFileJson->getDifferences(self::$secondFile));
        $this->assertNull(self::$firstFile->getDifferences(self::$errFileYaml));
    }
}
