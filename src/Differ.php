<?php

namespace Differ\Differ;

use function Differ\Formatters\formatDiff;
use function Differ\Parsers\parseFileContent;
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

    $firstFileData = parseFileContent($firstFileProperties, $firstContent);
    $secondFileData = parseFileContent($secondFileProperties, $secondContent);

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

function getDifferences(object $firstData, object $secondData): array
{
    $firstDataArr = get_object_vars($firstData);
    $secondDataArr = get_object_vars($secondData);
    $uniqueKeys = array_unique(array_merge(array_keys($firstDataArr), array_keys($secondDataArr)));

    $differences = array_map(
        function (string|int $key) use ($firstDataArr, $secondDataArr): array {
            if (!array_key_exists($key, $firstDataArr)) {
                return [
                    'type' => 'addedProperty',
                    'key' => $key,
                    'value' => toValue($secondDataArr[$key])
                ];
            }

            if (!array_key_exists($key, $secondDataArr)) {
                return [
                    'type' => 'removedProperty',
                    'key' => $key,
                    'value' => toValue($firstDataArr[$key])
                ];
            }

            if (
                is_object($firstDataArr[$key]) && $firstDataArr[$key] == $secondDataArr[$key] ||
                $firstDataArr[$key] === $secondDataArr[$key]
            ) {
                return [
                    'type' => 'unchangedProperty',
                    'key' => $key,
                    'value' => toValue($firstDataArr[$key])
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
                'oldValue' => toValue($firstDataArr[$key]),
                'newValue' => toValue($secondDataArr[$key])
            ];
        },
        $uniqueKeys,
    );

    $sortDifferences = sortBy($differences, fn($item) => $item['key']);

    return array_values($sortDifferences);
}

function toValue(mixed $value): array
{
    return is_object($value) || is_array($value) ?
        [
            'type' => 'complexValue',
            'value' => json_decode(json_encode($value), true),
        ] :
        [
            'type' => 'value',
            'value' => $value
        ];
}
