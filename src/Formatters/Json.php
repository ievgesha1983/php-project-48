<?php

namespace Differ\Formatters\Json;

use function Differ\Formatters\getNonComplexValue;
use function Differ\Formatters\getStringValue;

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

function toJsonArray(array $differences, array $path = []): array
{
    $differencesResult = array_reduce(
        $differences,
        function (array $acc, array $difference) use ($path): array {
            if (!array_key_exists('type', $difference)) {
                return $acc;
            }

            switch ($difference['type']) {
                case 'addedProperty':
                    $newValue = $difference['value'];
                    $acc['addedProperties'][] = [
                        'addedProperty' => [
                            'path' => implode('.', $path),
                            'name' => $difference['key'],
                            'newValue' => getNonComplexValue($newValue)
                        ]
                    ];
                    break;
                case 'removedProperty':
                    $oldValue = $difference['value'];
                    $acc['removedProperties'][] = [
                        'removedProperty' => [
                            'path' => implode('.', $path),
                            'name' => $difference['key'],
                            'oldValue' => getNonComplexValue($oldValue)
                        ]
                    ];
                    break;
                case 'updatedProperty':
                    $newValue = $difference['newValue'];
                    $oldValue = $difference['oldValue'];
                    $newValueString = getStringValue($newValue);
                    $oldValueString = getStringValue($oldValue);
                    $message = "Updated from {$oldValueString} to {$newValueString}";
                    $acc['updatedProperties'][] = [
                        'updatedProperty' => [
                            'path' => implode('.', $path),
                            'name' => $difference['key'],
                            'message' => $message,
                            'oldValue' => getNonComplexValue($oldValue),
                            'newValue' => getNonComplexValue($newValue)
                        ]
                    ];
                    break;
                case 'unchangedProperty':
                    if (is_array($difference['value'])) {
                        $newPath = [...$path, $difference['key']];
                        $childrenArray = toJsonArray($difference['value'], $newPath);
                        $acc['addedProperties'] = array_merge(
                            $acc['addedProperties'] ?? [],
                            $childrenArray['addedProperties'] ?? []
                        );
                        $acc['removedProperties'] = array_merge(
                            $acc['removedProperties'] ?? [],
                            $childrenArray['removedProperties'] ?? []
                        );
                        $acc['updatedProperties'] = array_merge(
                            $acc['updatedProperties'] ?? [],
                            $childrenArray['updatedProperties'] ?? []
                        );
                    }
                    break;
            }
            return $acc;
        },
        []
    );

    return $differencesResult;
}
