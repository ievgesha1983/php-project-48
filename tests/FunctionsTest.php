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
        $this->assertFalse(isValidFile('tests/data/file1.jsan'));
        $this->assertTrue(isValidFile('tests/data/file1.json'));
        $this->assertTrue(isValidFile("{$currentDir}/tests/data/file1.json"));
    }

    public function testGenDiff(): void
    {
        $currentDir = getcwd();
        $expected = "{
  - follow: false
    host: hexlet.io
  - proxy: 123.234.53.22
  - timeout: 50
  + timeout: 20
  + verbose: true
}";
        $format = 'stylish';
        $firstFile = '../php-project-48/tests/data/file1.json';
        $secondFile = $currentDir . '/tests/data/file2.json';
        $this->assertEquals($expected, genDiff($firstFile, $secondFile, $format));
    }
}
