<?php

namespace Differ\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function Differ\Differ\genDiff;

class DifferTest extends TestCase
{
    #[DataProvider('getDefaultProvider')]
    public function testDefault($expected, $firstFile, $secondFile): void
    {
        $this->assertStringEqualsFile($expected, genDiff($firstFile, $secondFile));
    }

    #[DataProvider('getStylishProvider')]
    public function testStylish($expected, $firstFile, $secondFile, $format = false): void
    {
        $this->assertStringEqualsFile($expected, genDiff($firstFile, $secondFile, $format));
    }

    #[DataProvider('getPlainProvider')]
    public function testPlain($expected, $firstFile, $secondFile, $format = false): void
    {
        $this->assertStringEqualsFile($expected, genDiff($firstFile, $secondFile, $format));
    }

    #[DataProvider('getJsonProvider')]
    public function testJson($expected, $firstFile, $secondFile, $format = false): void
    {
        $result = file_get_contents(getFixtureFullPath('result_json.json'));
        $firstPathInfo = pathinfo($firstFile);
        $secondPathInfo = pathinfo($secondFile);
        $fileInfo = json_encode([
            'firstFile' => [
                'path' => $firstPathInfo['dirname'],
                'name' => $firstPathInfo['basename'],
            ],
            'secondFile' => [
                'path' => $secondPathInfo['dirname'],
                'name' => $secondPathInfo['basename'],
            ],
        ]);
        $result = str_replace('FILES_INFO_JSON', $fileInfo, $result);

        $this->assertEquals($result, genDiff($firstFile, $secondFile, $format));
    }

    #[DataProvider('getGenDiffExceptionsProvider')]
    public function testGenDiffExceptions($expected, $firstFile, $secondFile, $format): void
    {
        $this->expectExceptionMessage($expected);
        genDiff($firstFile, $secondFile, $format);
    }

    public static function getGenDiffExceptionsProvider(): array
    {
        $errorExtensionPath = getFixtureFullPath('error-file.jsan');
        $errorJsonFormatPath = getFixtureFullPath('error-file.json');
        $errorYamlFormatPath = getFixtureFullPath('error-file.yml');

        return [
            "'' format not supported" => [
                "Формат вывода '' не поддерживается",
                'file1.json',
                'file2.json',
                ''],
            "'other' format not supported" => [
                "Формат вывода 'other' не поддерживается",
                'file1.json',
                'file2.json',
                'other'
            ],
            'first file not exists' => [
                "'' - файл не существует или не читается",
                '',
                'file2.json',
                'stylish'
            ],
            'second file not exists' => [
                "'file2.json' - файл не существует или не читается",
                getFixtureFullPath('file1.json'),
                'file2.json',
                'stylish'
            ],
            'file extension not supported' => [
                "'{$errorExtensionPath}' - расширение файла не поддерживается",
                $errorExtensionPath,
                '',
                'stylish'
            ],
            'json file format error' => [
                "'{$errorJsonFormatPath}' - некорректный формат содержимого или файл пуст",
                $errorJsonFormatPath,
                $errorYamlFormatPath,
                'stylish'
            ],
            'yaml file format error' => [
                "'{$errorYamlFormatPath}' - некорректный формат содержимого или файл пуст",
                $errorYamlFormatPath,
                $errorJsonFormatPath,
                'stylish'
            ],
        ];
    }

    public static function getDefaultProvider(): array
    {
        return [
            'json files' => [
                getFixtureFullPath('result_stylish.txt'),
                getFixtureFullPath('file1.json'),
                getFixtureFullPath('file2.json'),
            ],
            'yaml files' => [
                getFixtureFullPath('result_stylish.txt'),
                getFixtureFullPath('file1.yml'),
                getFixtureFullPath('file2.yaml'),
            ]
        ];
    }

    public static function getStylishProvider(): array
    {
        return [
            'json files' => [
                getFixtureFullPath('result_stylish.txt'),
                getFixtureFullPath('file1.json'),
                getFixtureFullPath('file2.json'),
                'stylish'
            ],
            'yaml files' => [
                getFixtureFullPath('result_stylish.txt'),
                getFixtureFullPath('file1.yml'),
                getFixtureFullPath('file2.yaml'),
                'stylish'
            ]
        ];
    }

    public static function getPlainProvider(): array
    {
        return [
            'json files' => [
                getFixtureFullPath('result_plain.txt'),
                getFixtureFullPath('file1.json'),
                getFixtureFullPath('file2.json'),
                'plain'
            ],
            'yaml files' => [
                getFixtureFullPath('result_plain.txt'),
                getFixtureFullPath('file1.yml'),
                getFixtureFullPath('file2.yaml'),
                'plain'
            ],
        ];
    }

    public static function getJsonProvider(): array
    {
        return [
            'json files' => [
                getFixtureFullPath('result_json.json'),
                getFixtureFullPath('file1.json'),
                getFixtureFullPath('file2.json'),
                'json'
            ],
            'yaml files' => [
                getFixtureFullPath('result_json.json'),
                getFixtureFullPath('file1.yml'),
                getFixtureFullPath('file2.yaml'),
                'json'
            ]
        ];
    }
}

function getFixtureFullPath(string $fixtureName): string
{
    return realpath(__DIR__ . "/fixtures/{$fixtureName}");
}
