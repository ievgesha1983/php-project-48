<?php

namespace Differ\Formatters\Json;

use function Differ\Formatters\getNonComplexValue;
use function Differ\Formatters\getStringValue;
use function Funct\Collection\flatten;
use function Funct\Collection\groupBy;

function toJsonString(array $diff): string
{
    return jsonDiffToString(toJsonDiff($diff));
}

function jsonDiffToString(array $jsonDiff, int $numberOfSpaces = 2): string
{
    $json4spaces = json_encode($jsonDiff, JSON_PRETTY_PRINT);
    $json2spaces = preg_replace_callback(
        '#^(?: {4})+#m',
        fn ($str) => str_repeat(' ', $numberOfSpaces * (strlen($str[0]) / 4)),
        $json4spaces
    );
    return $json2spaces;
}

function toJsonDiff(array $diff): array
{
    $jsonArray = [];
    $jsonArray['diff'] = $diff['diffInfo'];
    $differences = $diff['differences'];

    return array_merge($jsonArray, toJsonArray($differences));
}

function toJsonArray(array $differences): array
{
    $jsonArray = groupBy(toFlatJson($differences), fn(array $difference) => array_keys($difference)[0]);
    $keys = array_keys($jsonArray);
    $newKeys = array_map(fn($key) => str_replace('Property', 'Properties', $key), $keys);
    $changeKeysArray = array_combine($newKeys, $keys);
    return array_map(
        fn ($key) => array_values($jsonArray[$key]),
        $changeKeysArray
    );
}

function toFlatJson(array $differences, array $path = []): array
{
    $differencesResult = array_map(
        function ($difference) use ($path) {
            if (!array_key_exists('type', $difference)) {
                return [];
            }

            switch ($difference['type']) {
                case 'addedProperty':
                    $newValue = $difference['value'];
                    return [
                        [
                            'addedProperty' => [
                                'path' => implode('.', $path),
                                'name' => $difference['key'],
                                'newValue' => getNonComplexValue($newValue)
                            ]
                        ]
                    ];
                case 'removedProperty':
                    $oldValue = $difference['value'];
                    return [
                        [
                            'removedProperty' => [
                                'path' => implode('.', $path),
                                'name' => $difference['key'],
                                'oldValue' => getNonComplexValue($oldValue)
                            ]
                        ]
                    ];
                case 'updatedProperty':
                    $newValue = $difference['newValue'];
                    $oldValue = $difference['oldValue'];
                    $newValueString = getStringValue($newValue);
                    $oldValueString = getStringValue($oldValue);
                    $message = "Updated from {$oldValueString} to {$newValueString}";
                    return [
                        [
                            'updatedProperty' => [
                                'path' => implode('.', $path),
                                'name' => $difference['key'],
                                'message' => $message,
                                'oldValue' => getNonComplexValue($oldValue),
                                'newValue' => getNonComplexValue($newValue)
                            ]
                        ]
                    ];
                case 'unchangedProperty':
                    if (is_array($difference['value'])) {
                        $newPath = [...$path, $difference['key']];
                        return toFlatJson($difference['value'], $newPath);
                    }
                    return [];
                default:
                    return [];
            }
        },
        $differences
    );

    return flatten($differencesResult);
}
