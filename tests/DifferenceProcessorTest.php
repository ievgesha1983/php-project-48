<?php

namespace Hexlet\Code\Tests;

use Hexlet\Code\DifferenceProcessor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DifferenceProcessorTest extends TestCase
{
    #[DataProvider('getDiffInfoProvider')]
    public function testGetDiffInfo($expected, array $arguments): void
    {
        $this->assertEquals($expected, DifferenceProcessor::getDiffInfo($arguments));
    }

    public static function getDiffInfoProvider(): array
    {
        $stylishResult = file_get_contents(__DIR__ . '/fixtures/result_stylish.txt');
        $plainResult = file_get_contents(__DIR__ . '/fixtures/result_plain.txt');
        $jsonResult = file_get_contents(__DIR__ . '/fixtures/result_json.json');
        $currentDir = getcwd();
        $currentDirJson = str_replace('/', '\/', $currentDir);
        $jsonResult = str_replace('FULL_PATH_TO_PROJECT', $currentDirJson, $jsonResult);
        return [
            ['Формат вывода не указан', []],
            ['Формат вывода не указан', ['<firstFile>' => '', '<secondFile>' => '']],
            ['Не указан firstFile', ['--format' => 'stylish']],
            ['Не указан firstFile', ['--format' => 'stylish', '<secondFile>' => 'file2.json']],
            ['Не указан secondFile', ['--format' => 'stylish', '<firstFile>' => 'file1.json']],
            [
                'Формат ввода указан некорректно',
                ['--format' => 'other', '<firstFile>' => 'file1.json', '<secondFile>' => 'file2.json']
            ],
            [
                'tests/date/file1.json - файл не существует или не соответствует формату',
                ['--format' => 'stylish', '<firstFile>' => 'tests/date/file1.json', '<secondFile>' => 'file2.json']
            ],
            [
                'file2.json - файл не существует или не соответствует формату',
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
