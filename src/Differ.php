<?php

namespace Differ\Differ;

use function Differ\Formatters\formatDiff;
use function Differ\Parsers\parse;
use function Funct\Collection\sortBy;

use const Differ\Formatters\VALID_OUTPUT_FORMAT_TYPES;

const VALID_FILES_EXTENSIONS = ['json', 'yml', 'yaml'];

function genDiff(string $firstFile, string $secondFile, string $format = 'stylish'): string
{
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

    return formatDiff($diff, $format);
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
                return [
                    'type' => 'unchangedProperty',
                    'key' => $key,
                    'value' => $toArrayOrValue($value)
                ];
            },
            $firstDataArr,
            array_keys($firstDataArr)
        );
    }

    $secondDataArr = get_object_vars($secondData);

    $firstDataUniqueKeys = array_diff(array_keys($firstDataArr), array_keys($secondDataArr));
    $firstDataUniqueDifferences = array_map(
        fn ($key) => [
            'type' => 'removedProperty',
            'key' => $key,
            'value' => $toArrayOrValue($firstDataArr[$key])
        ],
        $firstDataUniqueKeys
    );

    $intersectKeys = array_intersect(array_keys($firstDataArr), array_keys($secondDataArr));
    $intersectDifferences = array_map(
        function (string $key) use ($firstDataArr, $secondDataArr, $toArrayOrValue): array {
            if (
                is_object($firstDataArr[$key]) && $firstDataArr[$key] == $secondDataArr[$key] ||
                $firstDataArr[$key] === $secondDataArr[$key]
            ) {
                return [
                    'type' => 'unchangedProperty',
                    'key' => $key,
                    'value' => $toArrayOrValue($firstDataArr[$key])
                ];
            }

            if (is_object($firstDataArr[$key]) && is_object($secondDataArr[$key])) {
                return [
                    'type' => 'unchangedProperty',
                    'key' => $key,
                    'value' => getDifferences($firstDataArr[$key], $secondDataArr[$key])
                ];
            }

            return [
                'type' => 'updatedProperty',
                'key' => $key,
                'oldValue' => $toArrayOrValue($firstDataArr[$key]),
                'newValue' => $toArrayOrValue($secondDataArr[$key])
            ];
        },
        $intersectKeys
    );

    $secondDataUniqueKeys = array_diff(array_keys($secondDataArr), array_keys($firstDataArr));
    $secondDataUniqueDifferences = array_map(
        fn ($key) => [
            'type' => 'addedProperty',
            'key' => $key,
            'value' => $toArrayOrValue($secondDataArr[$key])
        ],
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
