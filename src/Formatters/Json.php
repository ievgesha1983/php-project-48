<?php

namespace Differ\Formatters\Json;

use function Differ\Formatters\getNonComplexValue;
use function Differ\Formatters\getStringValue;

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
