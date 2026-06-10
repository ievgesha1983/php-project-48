<?php

namespace Differ\Differ;

use Symfony\Component\Yaml\Yaml;

use function Differ\Formatters\formatDiff;
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

        return formatDiff($diff, $format);
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
