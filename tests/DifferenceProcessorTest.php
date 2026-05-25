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
        return [
            ['Формат вывода не указан', []],
            ['Формат вывода не указан', ['<firstFile>' => '', '<secondFile>' => '']],
            ['Не указан firstFile', ['--format' => 'stylish']],
            ['Не указан firstFile', ['--format' => 'stylish', '<secondFile>' => '']],
            ['Не указан secondFile', ['--format' => 'stylish', '<firstFile>' => '']],
            ['Пока все хорошо', ['--format' => 'stylish', '<firstFile>' => '', '<secondFile>' => '']],
        ];
    }
}