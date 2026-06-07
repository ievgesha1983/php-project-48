<?php

namespace Differ\Tests;

use PHPUnit\Framework\TestCase;

use function Differ\Differ\genDiff;
use function Differ\Functions\isValidFile;
use function Differ\Functions\isValidFormat;

class FunctionsTest extends TestCase
{
    public function testIsValidFormat(): void
    {
        $this->assertFalse(isValidFormat(''));
        $this->assertTrue(isValidFormat('stylish'));
    }

    public function testIsValidFile(): void
    {
        $currentDir = getcwd();
        $this->assertFalse(isValidFile('tests/fixtures/file1.jsan'));
        $this->assertTrue(isValidFile('tests/fixtures/file1.json'));
        $this->assertTrue(isValidFile("{$currentDir}/tests/fixtures/file1.json"));
        $this->assertTrue(isValidFile('tests/fixtures/file1.yml'));
        $this->assertTrue(isValidFile("{$currentDir}/tests/fixtures/file2.yaml"));
    }

    public function testGenDiff(): void
    {
        $currentDir = getcwd();
        $expected = file_get_contents(__DIR__ . '/fixtures/result_stylish.txt');
        $format = 'stylish';
        $firstFile = 'tests/../tests/fixtures/file1.json';
        $secondFile = "{$currentDir}/tests/fixtures/file2.json";
        $this->assertEquals($expected, genDiff($firstFile, $secondFile, $format));
    }
}
