<?php

namespace Differ;

use function Differ\Functions\getNonComplexValue;
use function Differ\Functions\getStringValue;
use function Differ\Functions\isValidFile;
use function Differ\Functions\isValidFormat;

class DifferenceProcessor
{
    public static function genDiff(array $args): string
    {
        $validateArgs = self::validateArguments($args);
        if ($validateArgs !== true) {
            return $validateArgs;
        }

        $firstFile = new DataFile($args['<firstFile>']);
        $firstFile->parse();

        $secondFile = new DataFile($args['<secondFile>']);
        $secondFile->parse();

        $diff = [
            'diffInfo' => [
                'created' => '',
                'type' => 'normal',
                'files' => [
                    'firstFile' => [
                        'path' => $firstFile->getPath(),
                        'fileName' => $firstFile->getBaseName(),
                    ],
                    'secondFile' => [
                        'path' => $secondFile->getPath(),
                        'fileName' => $secondFile->getBaseName(),
                    ]
                ]
            ],
            'differences' => $firstFile->getDifferences($secondFile)
        ];

        $result = match ($args['--format']) {
            'stylish' => self::toStylishString($diff),
            'plain' => self::toPlainString($diff),
            'json' => self::toJsonString($diff),
        };
        return $result;
    }

    private static function validateArguments(array $args): true|string
    {
        if (!isValidFormat($args['--format'])) {
            return "Формат ввода '{$args['--format']}' указан некорректно";
        }

        if (!isValidFile($args['<firstFile>'])) {
            return "'{$args['<firstFile>']}' - файл не существует или не соответствует формату";
        }

        if (!isValidFile($args['<secondFile>'])) {
            return "'{$args['<secondFile>']}' - файл не существует или не соответствует формату";
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

                    $path[] = $difference['key'];
                    switch ($difference['type']) {
                        case -1:
                            $newPath = implode('.', $path);
                            $updatedProperty = array_filter(
                                $differences,
                                fn($property) => $property['type'] === 1 && $property['key'] === $difference['key']
                            );
                            $updatedProperty = array_values($updatedProperty);
                            if (!empty($updatedProperty)) {
                                $oldValue = getStringValue($difference['value']);
                                $newValue = getStringValue($updatedProperty[0]['value']);
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
                            $updatedProperty = array_filter(
                                $differences,
                                fn($property) => $property['type'] === -1 && $property['key'] === $difference['key']
                            );
                            if (empty($updatedProperty)) {
                                $newPath = implode('.', $path);
                                $newValue = getStringValue($difference['value']);
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

    public static function toJsonString(array $diff): string
    {
        return self::jsonDiffToString(self::toJsonDiff($diff));
    }
    public static function jsonDiffToString(array $jsonDiff): string
    {
        $json4spaces = json_encode($jsonDiff, JSON_PRETTY_PRINT);
        $json2spaces = preg_replace_callback(
            '#^(?: {4})+#m',
            fn ($str) => str_repeat(' ', 2 * (strlen($str[0]) / 4)),
            $json4spaces
        );
        return $json2spaces;
    }
    public static function toJsonDiff(array $diff): array
    {
        $jsonArray = [];
        $jsonArray['diff'] = $diff['diffInfo'];
        $differences = $diff['differences'];

        $toJsonArray = function (array $differences, array $path = []) use (&$toJsonArray): array {
            $differencesResult = array_reduce(
                $differences,
                function (array $acc, array $difference) use ($toJsonArray, $differences, $path): array {
                    if (!array_key_exists('type', $difference)) {
                        return $acc;
                    }

                    switch ($difference['type']) {
                        case -1:
                            $updatedProperty = array_filter(
                                $differences,
                                fn($property) => $property['type'] === 1 && $property['key'] === $difference['key']
                            );
                            $updatedProperty = array_values($updatedProperty);
                            $oldValue = $difference['value'];
                            $oldValueString = getStringValue($oldValue);
                            if (!empty($updatedProperty)) {
                                $newValue = $updatedProperty[0]['value'];
                                $newValueString = getStringValue($newValue);
                                $message = "Updated from {$oldValueString} to {$newValueString}";
                                $acc["updatedProperties"][] = [
                                    "path" => implode('.', $path),
                                    "name" => $difference['key'],
                                    "message" => $message,
                                    "oldValue" => getNonComplexValue($oldValue),
                                    "newValue" => getNonComplexValue($newValue)
                                ];
                            } else {
                                $acc["removedProperties"][] = [
                                    "path" => implode('.', $path),
                                    "name" => $difference['key'],
                                    "oldValue" => getNonComplexValue($oldValue)
                                ];
                            }
                            break;
                        case 0:
                            if (is_array($difference['value'])) {
                                $newPath = [...$path, $difference['key']];
                                $childrenArray = $toJsonArray($difference['value'], $newPath);
                                $acc["addedProperties"] = array_merge(
                                    $acc["addedProperties"] ?? [],
                                    $childrenArray["addedProperties"] ?? []
                                );
                                $acc["removedProperties"] = array_merge(
                                    $acc["removedProperties"] ?? [],
                                    $childrenArray["removedProperties"] ?? []
                                );
                                $acc["updatedProperties"] = array_merge(
                                    $acc["updatedProperties"] ?? [],
                                    $childrenArray["updatedProperties"] ?? []
                                );
                            }
                            break;
                        case 1:
                            $updatedProperty = array_filter(
                                $differences,
                                fn($property) => $property['type'] === -1 && $property['key'] === $difference['key']
                            );
                            if (empty($updatedProperty)) {
                                $newValue = $difference['value'];
                                $acc["addedProperties"][] = [
                                    "path" => implode('.', $path),
                                    "name" => $difference['key'],
                                    "newValue" => getNonComplexValue($newValue)
                                ];
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

        return array_merge($jsonArray, $toJsonArray($differences));
    }
}
