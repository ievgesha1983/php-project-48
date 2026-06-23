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
        $expected = $this->getFixtureFullPath('result_stylish.txt');
        $firstFixture = $this->getFixtureFullPath("file1.{$firstFileExtension}");
        $secondFixture = $this->getFixtureFullPath("file2.{$secondFileExtension}");
        $this->assertStringEqualsFile($expected, genDiff($firstFixture, $secondFixture));
    }

    #[DataProvider('getFileExtensionsProvider')]
    public function testStylish($firstFileExtension, $secondFileExtension): void
    {
        $expected = $this->getFixtureFullPath('result_stylish.txt');
        $firstFixture = $this->getFixtureFullPath("file1.{$firstFileExtension}");
        $secondFixture = $this->getFixtureFullPath("file2.{$secondFileExtension}");
        $this->assertStringEqualsFile($expected, genDiff($firstFixture, $secondFixture, 'stylish'));
    }

    #[DataProvider('getFileExtensionsProvider')]
    public function testPlain($firstFileExtension, $secondFileExtension): void
    {
        $expected = $this->getFixtureFullPath('result_plain.txt');
        $firstFixture = $this->getFixtureFullPath("file1.{$firstFileExtension}");
        $secondFixture = $this->getFixtureFullPath("file2.{$secondFileExtension}");
        $this->assertStringEqualsFile($expected, genDiff($firstFixture, $secondFixture, 'plain'));
    }

    #[DataProvider('getFileExtensionsProvider')]
    public function testJson($firstFileExtension, $secondFileExtension): void
    {
        $result = file_get_contents($this->getFixtureFullPath('result_json.json'));
        $firstFixture = $this->getFixtureFullPath("file1.{$firstFileExtension}");
        $secondFixture = $this->getFixtureFullPath("file2.{$secondFileExtension}");
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
        $firstFixture = $this->getFixtureFullPath($firstFile);
        $secondFixture = $this->getFixtureFullPath($secondFile);
        $expected = str_replace('FIRST_FILE', $firstFixture, $expected);
        $expected = str_replace('SECOND_FILE', $secondFixture, $expected);

        $this->expectExceptionMessage($expected);
        genDiff($firstFixture, $secondFixture, $format);
    }

    public static function getGenDiffExceptionsProvider(): array
    {
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
                "'FIRST_FILE' - файл не существует или не читается",
                '',
                'file2.json',
                'stylish'
            ],
            'second file not exists' => [
                "'SECOND_FILE' - файл не существует или не читается",
                'file1.json',
                'file2.err',
                'stylish'
            ],
            'file extension not supported' => [
                "'FIRST_FILE' - расширение файла не поддерживается",
                'error-file.jsan',
                '',
                'stylish'
            ],
            'json file format error' => [
                "'FIRST_FILE' - некорректный формат содержимого или файл пуст",
                'error-file.json',
                'error-file.yml',
                'stylish'
            ],
            'yaml file format error' => [
                "'FIRST_FILE' - некорректный формат содержимого или файл пуст",
                'error-file.yml',
                'error-file.json',
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

    public function getFixtureFullPath(string $fixtureName): string
    {
        return realpath(__DIR__ . "/fixtures/{$fixtureName}");
    }
}
