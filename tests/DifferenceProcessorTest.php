<?php

namespace Hexlet\Code\Tests;

use Hexlet\Code\DifferenceProcessor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DifferenceProcessorTest extends TestCase
{
    public function testToStylishString(): void
    {
        $data = [
            ['type' => -1, 'key' => 'follow', 'value' => false],
            ['type' => 0, 'key' => 'host', 'value' => 'hexlet.io'],
            ['type' => -1, 'key' => 'proxy', 'value' => '123.234.53.22'],
        ];
        $expected = "{\n  - follow: false\n    host: hexlet.io\n  - proxy: 123.234.53.22\n}";
        $this->assertEquals($expected, DifferenceProcessor::toStylishString($data));
    }
    #[DataProvider('getDiffInfoProvider')]
    public function testGetDiffInfo($expected, array $arguments): void
    {
        $this->assertEquals($expected, DifferenceProcessor::getDiffInfo($arguments));
    }

    public static function getDiffInfoProvider(): array
    {
        $expected = file_get_contents(__DIR__ . '/fixtures/diff_info_result.txt');
        $currentDir = getcwd();
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
                $expected,
                [
                    '--format' => 'stylish',
                    '<firstFile>' => 'tests/../tests/fixtures/file1.json',
                    '<secondFile>' => "{$currentDir}/tests/fixtures/file2.yml"
                ]
            ],
            [
                'tests/date/file1.json - файл не существует или не соответствует формату',
                ['--format' => 'stylish', '<firstFile>' => 'tests/date/file1.json', '<secondFile>' => 'file2.json']
            ],
        ];
    }
}
