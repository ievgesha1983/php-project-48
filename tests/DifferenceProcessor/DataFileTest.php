<?php

namespace Hexlet\Code\Tests\DifferenceProcessor;

use Hexlet\Code\DifferenceProcessor\DataFile;
use PHPUnit\Framework\TestCase;

class DataFileTest extends TestCase
{
    private DataFile $firstFile;
    private DataFile $secondFile;
    private DataFile $jsanFile;

    public function setUp(): void
    {
        $this->firstFile = new DataFile("tests/fixtures/file1.json");
        $this->firstFile->parseJson();
        $this->secondFile = new DataFile("tests/fixtures/file2.json");
        $this->secondFile->parseJson();
        $this->jsanFile = new DataFile('tests/fixtures/file1.jsan');
        $this->jsanFile->parseJson();
    }
    public function testGetters(): void
    {
        $this->assertEquals('file1', $this->firstFile->getName());
        $this->assertEquals('file1.json', $this->firstFile->getBaseName());
        $this->assertEquals('tests/fixtures', $this->firstFile->getPath());
        $this->assertEquals('json', $this->firstFile->getExtension());
    }

    public function testParseAndGetData(): void
    {
        $this->assertEquals('hexlet.io', $this->firstFile->getData()->host);

        $this->assertNull($this->jsanFile->getData());
    }

    public function testToJson(): void
    {
        $expected = '{"host":"hexlet.io","timeout":50,"proxy":"123.234.53.22","follow":false}';
        $this->assertEquals($expected, $this->firstFile->toJson());

        $this->assertEquals('null', $this->jsanFile->toJson());
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
        $result = $this->firstFile->getDifferences($this->secondFile);
        $this->assertEquals($expected, $result);
    }
}
