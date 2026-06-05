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

    public static function toStylishString(array $diff): string
    {
        $differences = $diff['differences'];

        $iter = function (array $differences, int $iteration) use (&$iter): string {
            $tab = str_repeat("    ", $iteration);
            $differencesResult = array_map(
                function ($difference) use ($iter, $iteration) {
                    $tab = str_repeat("    ", $iteration);
                    $sign = match ($difference['type']) {
                        -1 => '-',
                        0 => ' ',
                        1 => '+'
                    };
                    if (is_array($difference['value'])) {
                        $value =  $iter($difference['value'], $iteration + 1);
                    } elseif (is_bool($difference['value'])) {
                        $value = $difference['value'] ? 'true' : 'false';
                    } elseif (is_null($difference['value'])) {
                        $value = 'null';
                    } else {
                        $value = $difference['value'];
                    }
                    return "{$tab}  {$sign} {$difference['key']}: {$value}";
                },
                $differences
            );

            return implode("\n", ['{', ...$differencesResult, "{$tab}}"]);
        };

        return $iter($differences, 0);
    }

    public static function toPlainString(array $diff): string
    {
        $differences = $diff['differences'];
        if (empty($differences)) {
            return '';
        }

        $toPlain = function (array $differences, array $path = []) use (&$toPlain): array {
            $differencesResult = array_reduce(
                $differences,
                function (array $acc, array $difference) use ($toPlain, $differences, $path): array {
                    if (!array_key_exists('type', $difference)) {
                        return $acc;
                    }

                    $getStringValue = function (mixed $value): string {
                        if (is_bool($value)) {
                            return $value ? 'true' : 'false';
                        }
                        if (is_null($value)) {
                            return 'null';
                        }
                        if (is_array($value)) {
                            return '[complex value]';
                        }
                        if (is_string($value)) {
                            return "'{$value}'";
                        }
                        return $value;
                    };

                    $path[] = $difference['key'];
                    switch ($difference['type']) {
                        case -1:
                            $newPath = implode('.', $path);
                            $updatedProp = array_filter(
                                $differences,
                                fn($property) => $property['type'] === 1 && $property['key'] === $difference['key']
                            );
                            $updatedProp = array_values($updatedProp);
                            if (count($updatedProp) > 0) {
                                $oldValue = $getStringValue($difference['value']);
                                $newValue = $getStringValue($updatedProp[0]['value']);
                                $acc[] = "Property '{$newPath}' was updated. From {$oldValue} to {$newValue}";
                            } else {
                                $acc[] = "Property '{$newPath}' was removed";
                            }
                            break;
                        case 0:
                            if (is_array($difference['value'])) {
                                $acc = array_merge($acc, $toPlain($difference['value'], $path));
                            }
                            break;
                        case 1:
                            $updatedProp = array_filter(
                                $differences,
                                fn($property) => $property['type'] === -1 && $property['key'] === $difference['key']
                            );
                            if (count($updatedProp) === 0) {
                                $newPath = implode('.', $path);
                                $newValue = $getStringValue($difference['value']);
                                $acc[] = "Property '{$newPath}' was added with value: {$newValue}";
                            }
                            break;
                        default:
                            break;
                    }
                    return $acc;
                },
                []
            );

            return $differencesResult;
        };

        return implode("\n", $toPlain($differences));
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
        $firstFile->parse();
        $secondFile = new DataFile($args['<secondFile>']);
        $secondFile->parse();

        $diffInfo = [
            "created" => "",
            "type" => "normal",
            "files" => [
                "firstFile" => [
                    "path" => $firstFile->getPath(),
                    "fileName" => $firstFile->getBaseName(),
                ],
                "secondFile" => [
                    "path" => $secondFile->getPath(),
                    "fileName" => $secondFile->getBaseName(),
                ]
            ],
            "differences" => $firstFile->getDifferences($secondFile)
        ];

        $result = match ($args['--format']) {
            'stylish' => self::toStylishString($diffInfo),
            'plain' => self::toPlainString($diffInfo),
        };
        return $result;
    }
}
