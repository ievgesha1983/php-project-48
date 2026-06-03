<?php

namespace Hexlet\Code;

use Docopt\Response;
use Hexlet\Code\DifferenceProcessor\DataFile;

use function Hexlet\Code\Functions\isValidFile;
use function Hexlet\Code\Functions\isValidFormat;

class DifferenceProcessor
{
    private static function checkingArguments(array $args): true|string
    {
        if (empty($args['--format'])) {
            return 'Формат вывода не указан';
        }

        if (empty($args['<firstFile>'])) {
            return 'Не указан firstFile';
        }

        if (empty($args['<secondFile>'])) {
            return 'Не указан secondFile';
        }

        return true;
    }

    private static function validateArguments(array $args): true|string
    {
        if (!isValidFormat($args['--format'])) {
            return 'Формат ввода указан некорректно';
        }

        if (!isValidFile($args['<firstFile>'])) {
            return "{$args['<firstFile>']} - файл не существует или не соответствует формату";
        }

        if (!isValidFile($args['<secondFile>'])) {
            return "{$args['<secondFile>']} - файл не существует или не соответствует формату";
        }

        return true;
    }

    public static function toStylishString(array $differences): string
    {
        $differencesResult = array_map(
            function ($difference) {
                $sign = match ($difference['type']) {
                    -1 => '-',
                    0 => ' ',
                    1 => '+'
                };
                if (is_bool($difference['value'])) {
                    $value = $difference['value'] ? 'true' : 'false';
                } else {
                    $value = $difference['value'];
                }
                return "  {$sign} {$difference['key']}: {$value}";
            },
            $differences
        );
        return implode("\n", ['{', ...$differencesResult, '}']);
    }

    public static function getDiffInfo(array $args): string
    {
        $checkArgs = self::checkingArguments($args);
        if ($checkArgs !== true) {
            return $checkArgs;
        }

        $validateArgs = self::validateArguments($args);
        if ($validateArgs !== true) {
            return $validateArgs;
        }

        $firstFile = new DataFile($args['<firstFile>']);
        $firstFile->parseJson();
        $secondFile = new DataFile($args['<secondFile>']);
        $secondFile->parseJson();

        $diffInfo = $firstFile->getDifferences($secondFile);

        return self::toStylishString($diffInfo);
    }
}
