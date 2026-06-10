<?php

namespace Differ\Formatters\Plain;

use function Differ\Formatters\getStringValue;

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
