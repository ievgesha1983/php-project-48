<?php

namespace Differ\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function Differ\Differ\genDiff;

class DifferTest extends TestCase
{
    #[DataProvider('getFileExtensionsProvider')]
    public function testDefault($firstFileExtension, $secondFileExtension): void
    {
        $expected = getFixtureFullPath('result_stylish.txt');
        $firstFixture = getFixtureFullPath("file1.{$firstFileExtension}");
        $secondFixture = getFixtureFullPath("file2.{$secondFileExtension}");
        $this->assertStringEqualsFile($expected, genDiff($firstFixture, $secondFixture));
    }

    #[DataProvider('getFileExtensionsProvider')]
    public function testStylish($firstFileExtension, $secondFileExtension): void
    {
        $expected = getFixtureFullPath('result_stylish.txt');
        $firstFixture = getFixtureFullPath("file1.{$firstFileExtension}");
        $secondFixture = getFixtureFullPath("file2.{$secondFileExtension}");
        $this->assertStringEqualsFile($expected, genDiff($firstFixture, $secondFixture, 'stylish'));
    }

    #[DataProvider('getFileExtensionsProvider')]
    public function testPlain($firstFileExtension, $secondFileExtension): void
    {
        $expected = getFixtureFullPath('result_plain.txt');
        $firstFixture = getFixtureFullPath("file1.{$firstFileExtension}");
        $secondFixture = getFixtureFullPath("file2.{$secondFileExtension}");
        $this->assertStringEqualsFile($expected, genDiff($firstFixture, $secondFixture, 'plain'));
    }

    #[DataProvider('getFileExtensionsProvider')]
    public function testJson($firstFileExtension, $secondFileExtension): void
    {
        $result = file_get_contents(getFixtureFullPath('result_json.json'));
        $firstFixture = getFixtureFullPath("file1.{$firstFileExtension}");
        $secondFixture = getFixtureFullPath("file2.{$secondFileExtension}");
        $firstPathInfo = pathinfo($firstFixture);
        $secondPathInfo = pathinfo($secondFixture);
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

        $this->assertEquals($result, genDiff($firstFixture, $secondFixture, 'json'));
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

    public static function getFileExtensionsProvider(): array
    {
        return [
            'json files' => [
                'json',
                'json',
            ],
            'yaml files' => [
                'yml',
                'yaml',
            ]
        ];
    }
}

function getFixtureFullPath(string $fixtureName): string
{
    return realpath(__DIR__ . "/fixtures/{$fixtureName}");
}
