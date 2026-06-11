<?php

namespace Differ\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function Differ\Differ\genDiff;

class DifferTest extends TestCase
{
    #[DataProvider('getGenDiffExceptionsProvider')]
    public function testGenDiffExceptions($expected, $firstFile, $secondFile, $format): void
    {
        $this->expectExceptionMessage($expected);
        genDiff($firstFile, $secondFile, $format);
    }

    #[DataProvider('getGenDiffProvider')]
    public function testGenDiff($expected, $firstFile, $secondFile, $format): void
    {
        $this->assertEquals($expected, genDiff($firstFile, $secondFile, $format));
    }

    public static function getGenDiffExceptionsProvider(): array
    {
        return [
            ["Формат вывода '' не поддерживается", 'file1.json', 'file2.json', ''],
            ["Формат вывода 'other' не поддерживается", 'file1.json', 'file2.json', 'other'],
            ["'' - файл не существует или не читается", '', 'file2.json', 'stylish'],
            [
                "'tests/fixtures/file1.jsan' - расширение файла не поддерживается",
                'tests/fixtures/file1.jsan',
                '',
                'stylish'
            ],
            [
                "'file2.json' - файл не существует или не читается",
                'tests/fixtures/file1.json',
                'file2.json',
                'stylish'
            ],
        ];
    }

    public static function getGenDiffProvider(): array
    {
        $stylishResult = file_get_contents(__DIR__ . '/fixtures/result_stylish.txt');
        $plainResult = file_get_contents(__DIR__ . '/fixtures/result_plain.txt');
        $jsonResult = file_get_contents(__DIR__ . '/fixtures/result_json.json');
        $currentDir = getcwd();
        $currentDirJson = str_replace('/', '\/', $currentDir);
        $jsonResult = str_replace('FULL_PATH_TO_PROJECT', $currentDirJson, $jsonResult);
        return [
            [
                $stylishResult,
                'tests/../tests/fixtures/file1.json',
                "{$currentDir}/tests/fixtures/file2.yaml",
                'stylish'
            ],
            [
                $plainResult,
                'tests/../tests/fixtures/file1.json',
                "{$currentDir}/tests/fixtures/file2.yaml",
                'plain'
            ],
            [
                $jsonResult,
                'tests/../tests/fixtures/file1.json',
                "{$currentDir}/tests/fixtures/file2.yaml",
                'json'
            ],
        ];
    }
}
