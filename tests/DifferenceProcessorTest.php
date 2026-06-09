<?php

namespace Differ\Tests;

use Differ\DifferenceProcessor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DifferenceProcessorTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        global $config;
        $config = require(__DIR__ . "/fixtures/config.php");
    }

    #[DataProvider('getGenDiffProvider')]
    public function testGenDiffInfo($expected, array $arguments): void
    {
        $this->assertEquals($expected, DifferenceProcessor::genDiff($arguments));
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
                "Формат ввода '' указан некорректно",
                ['--format' => '', '<firstFile>' => 'file1.json', '<secondFile>' => 'file2.json']
            ],
            [
                "Формат ввода 'other' указан некорректно",
                ['--format' => 'other', '<firstFile>' => 'file1.json', '<secondFile>' => 'file2.json']
            ],
            [
                "'' - файл не существует или не соответствует формату",
                ['--format' => 'stylish', '<firstFile>' => '', '<secondFile>' => 'file2.json']
            ],
            [
                "'file2.json' - файл не существует или не соответствует формату",
                ['--format' => 'stylish', '<firstFile>' => 'tests/fixtures/file1.json', '<secondFile>' => 'file2.json']
            ],
            [
                $stylishResult,
                [
                    '--format' => 'stylish',
                    '<firstFile>' => 'tests/../tests/fixtures/file1.json',
                    '<secondFile>' => "{$currentDir}/tests/fixtures/file2.yaml"
                ]
            ],
            [
                $plainResult,
                [
                    '--format' => 'plain',
                    '<firstFile>' => 'tests/../tests/fixtures/file1.json',
                    '<secondFile>' => "{$currentDir}/tests/fixtures/file2.yaml"
                ]
            ],
            [
                $jsonResult,
                [
                    '--format' => 'json',
                    '<firstFile>' => 'tests/../tests/fixtures/file1.json',
                    '<secondFile>' => "{$currentDir}/tests/fixtures/file2.yaml"
                ]
            ],
        ];
    }
}
