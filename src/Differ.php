<?php

namespace Differ\Differ;

use Symfony\Component\Yaml\Yaml;

use function Funct\Collection\sortBy;

use const Differ\Formatters\VALID_OUTPUT_FORMAT_TYPES;

const VALID_FILES_EXTENSIONS = ['json', 'yml', 'yaml'];

function genDiff(string $firstFile, string $secondFile, string $format = 'stylish'): string
{
    try {
        if (!in_array($format, VALID_OUTPUT_FORMAT_TYPES)) {
            throw new \Exception("Формат вывода '{$format}' не поддерживается");
        }

        $firstFileProperties = getFileProperties($firstFile);
        $secondFileProperties = getFileProperties($secondFile);

        $firstContent = getContent($firstFile);
        $secondContent = getContent($secondFile);

        $firstFileData = parse($firstFileProperties, $firstContent);
        $secondFileData = parse($secondFileProperties, $secondContent);

        $diff = makeDiff($firstFileData, $secondFileData);
        $result = match ($format) {
            'stylish' => toStylishString($diff),
            'plain' => toPlainString($diff),
            'json' => toJsonString($diff),
        };

        return $result;
    } catch (\Exception $exception) {
        return $exception->getMessage();
    }
}

function getFileProperties(string $filePath): array
{
    if (!is_file($filePath) || !is_readable($filePath)) {
        throw new \Exception("'{$filePath}' - файл не существует или не читается");
    }

    $pathData = pathinfo($filePath);

    if (!in_array($pathData["extension"], VALID_FILES_EXTENSIONS)) {
        throw new \Exception("'{$filePath}' - расширение файла не поддерживается");
    }

    return [
        "fileName" => $pathData['filename'],
        "extension" => $pathData['extension'],
        "path" => $pathData['dirname']
    ];
}
function getContent(string $filePath): string
{

    $content = file_get_contents($filePath);
    if ($content === false) {
        throw new \Exception('Файл не читается');
    }

    return $content;
}

function parse(array $fileProperties, string $content): array
{
    $fileData = [
        'fileName' => "{$fileProperties['fileName']}.{$fileProperties['extension']}",
        'path' => $fileProperties['path'],
    ];
    $fileData['data'] = match ($fileProperties['extension']) {
        'json' => parseJson($content),
        'yaml', 'yml' => parseYaml($content)
    };

    if (is_null($fileData['data'])) {
        $filePath = "{$fileData['path']}/{$fileData['fileName']}";
        throw new \Exception("'{$filePath} - 'некорректный формат содержимого или файл пуст");
    }

    return $fileData;
}

function parseJson(string $content): \stdClass|null
{
    return json_decode($content);
}

function parseYaml($content): \stdClass|null
{
    $parsedData = Yaml::parse($content, Yaml::PARSE_OBJECT_FOR_MAP);
    return is_object($parsedData) ? $parsedData : null;
}

function makeDiff(array $firstFileData, array $secondFileData): array
{
    $differences = getDifferences($firstFileData['data'], $secondFileData['data']);
    $diff = [
        'diffInfo' => [
            'created' => '',
            'type' => 'normal',
            'files' => [
                'firstFile' => [
                    'path' => $firstFileData['path'],
                    'name' => $firstFileData['fileName'],
                ],
                'secondFile' => [
                    'path' => $secondFileData['path'],
                    'name' => $secondFileData['fileName'],
                ]
            ]
        ],
        'differences' => $differences
    ];

    return $diff;
}

function getDifferences(\stdClass $firstData, \stdClass|false $secondData = false): ?array
{
    $toArrayOrValue = function (mixed $value): mixed {
        return is_object($value) ? getDifferences($value) : $value;
    };

    $firstDataArr = get_object_vars($firstData);
    if ($secondData === false) {
        return array_map(
            function (mixed $value, string $key) use ($toArrayOrValue): array {
                return ['type' => 0, 'key' => $key, 'value' => $toArrayOrValue($value)];
            },
            $firstDataArr,
            array_keys($firstDataArr)
        );
    }

    $secondDataArr = get_object_vars($secondData);

    $firstDataUniqueKeys = array_diff(array_keys($firstDataArr), array_keys($secondDataArr));
    $firstDataUniqueDifferences = array_map(
        fn ($key) => ['type' => -1, 'key' => $key, 'value' => $toArrayOrValue($firstDataArr[$key])],
        $firstDataUniqueKeys
    );

    $intersectKeys = array_intersect(array_keys($firstDataArr), array_keys($secondDataArr));
    $intersectDifferences = array_reduce(
        $intersectKeys,
        function (array $acc, string $key) use ($firstDataArr, $secondDataArr, $toArrayOrValue): array {
            if (
                is_object($firstDataArr[$key]) && $firstDataArr[$key] == $secondDataArr[$key] ||
                $firstDataArr[$key] === $secondDataArr[$key]
            ) {
                $acc[] = ['type' => 0, 'key' => $key, 'value' => $toArrayOrValue($firstDataArr[$key])];
                return $acc;
            }
            if (is_object($firstDataArr[$key]) && is_object($secondDataArr[$key])) {
                $acc[] = ['type' => 0, 'key' => $key, 'value' =>
                    getDifferences($firstDataArr[$key], $secondDataArr[$key])
                ];
                return $acc;
            }
            $acc[] = ['type' => -1, 'key' => $key, 'value' => $toArrayOrValue($firstDataArr[$key])];
            $acc[] = ['type' => 1, 'key' => $key, 'value' => $toArrayOrValue($secondDataArr[$key])];
            return $acc;
        },
        []
    );

    $secondDataUniqueKeys = array_diff(array_keys($secondDataArr), array_keys($firstDataArr));
    $secondDataUniqueDifferences = array_map(
        fn ($key) => ['type' => 1, 'key' => $key, 'value' => $toArrayOrValue($secondDataArr[$key])],
        $secondDataUniqueKeys
    );

    $differences = array_merge(
        $firstDataUniqueDifferences,
        $intersectDifferences,
        $secondDataUniqueDifferences
    );
    $sortDifferences = sortBy($differences, fn($item) => $item['key']);

    return array_values($sortDifferences);
}

function toStylishString(array $diff): string
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

function toPlainString(array $diff): string
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

function getNonComplexValue(mixed $value): mixed
{
    return is_array($value) ? '[complex value]' : $value;
}

function getStringValue(mixed $value): string
{
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
}

function toJsonString(array $diff): string
{
    return jsonDiffToString(toJsonDiff($diff));
}

function jsonDiffToString(array $jsonDiff): string
{
    $json4spaces = json_encode($jsonDiff, JSON_PRETTY_PRINT);
    $json2spaces = preg_replace_callback(
        '#^(?: {4})+#m',
        fn ($str) => str_repeat(' ', 2 * (strlen($str[0]) / 4)),
        $json4spaces
    );
    return $json2spaces;
}

function toJsonDiff(array $diff): array
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
