<?php

namespace Hexlet\Code\Tests;

use PHPUnit\Framework\TestCase;

use function Hexlet\Code\Functions\genDiff;
use function Hexlet\Code\Functions\isValidFile;
use function Hexlet\Code\Functions\isValidFormat;

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
    }

    public function testGenDiff(): void
    {
        $currentDir = getcwd();
        $expected = file_get_contents(__DIR__ . '/fixtures/diff_info_result.txt');
        $format = 'stylish';
        $firstFile = 'tests/../tests/fixtures/file1.json';
        $secondFile = "{$currentDir}/tests/fixtures/file2.json";
        $this->assertEquals($expected, genDiff($firstFile, $secondFile, $format));
    }
}
