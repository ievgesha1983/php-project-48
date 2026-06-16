<?php

namespace Differ\Formatters\Plain;

function toPlainString(array $diff): string
{
    $differences = $diff['differences'];
    if (empty($differences)) {
        return '';
    }

    return toPlain($differences);
}

function toPlain(array $differences, array $path = []): string
{
    if (array_key_exists('type', $differences)) {
        return '';
    }

    $differencesResult = array_map(
        function (array $difference) use ($path): string {
            if (!array_key_exists('type', $difference)) {
                return '';
            }
            $path[] = $difference['key'];
            switch ($difference['type']) {
                case 'removedProperty':
                    $newPath = implode('.', $path);
                    $result = "Property '{$newPath}' was removed";
                    break;
                case 'addedProperty':
                    $newPath = implode('.', $path);
                    $newValue = getStringValue($difference['value']);
                    $result = "Property '{$newPath}' was added with value: {$newValue}";
                    break;
                case 'updatedProperty':
                    $newPath = implode('.', $path);
                    $newValue = getStringValue($difference['newValue']);
                    $oldValue = getStringValue($difference['oldValue']);
                    $result = "Property '{$newPath}' was updated. From {$oldValue} to {$newValue}";
                    break;
                case 'unchangedProperty':
                    $result = is_array($difference['value']) ? toPlain($difference['value'], $path) : '';
                    break;
                default:
                    throw new \Exception('Неподдерживаемый тип свойства');
            }
            return $result;
        },
        $differences
    );

    return implode("\n", array_filter($differencesResult));
}

function getStringValue(array $valueObj): string
{
    if ($valueObj['type'] === 'complexValue') {
        return '[complex value]';
    }

    $value = $valueObj['value'];
    if (is_bool($value)) {
        return $value ? 'true' : 'false';
    }
    if (is_null($value)) {
        return 'null';
    }
    if (is_string($value)) {
        return  "'{$value}'";
    }
    return $value;
}
