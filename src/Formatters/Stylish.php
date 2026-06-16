<?php

namespace Differ\Formatters\Stylish;

function toStylishString(array $diff): string
{
    $differences = $diff['differences'];

    return toStylishStringIter($differences, 0);
}

function toStylishStringIter(mixed $differences, int $iteration): string
{
    if (
        array_key_exists('type', $differences) &&
        ($differences['type'] === 'value' || $differences['type'] === 'complexValue')
    ) {
        return toString($differences['value'], $iteration);
    }

    $tab = str_repeat("    ", $iteration);
    $differencesResult = array_map(
        function ($difference) use ($iteration, $tab): string {

            if ($difference['type'] === 'updatedProperty') {
                $oldValue = toStylishStringIter($difference['oldValue'], $iteration + 1);
                $newValue = toStylishStringIter($difference['newValue'], $iteration + 1);
                $removedString = "{$tab}  - {$difference['key']}: {$oldValue}";
                $addedString = "{$tab}  + {$difference['key']}: {$newValue}";
                return "{$removedString}\n{$addedString}";
            }

            $sign = match ($difference['type']) {
                'removedProperty' => '-',
                'addedProperty' => '+',
                'unchangedProperty' => ' '
            };
            $value = toStylishStringIter($difference['value'], $iteration + 1);

            return "{$tab}  {$sign} {$difference['key']}: {$value}";
        },
        $differences
    );

    return implode("\n", ['{', ...$differencesResult, "{$tab}}"]);
}

function toString(mixed $value, int $iteration = 0): string
{
    if (!is_array($value)) {
        return getStringValue($value);
    }

    $tab = str_repeat("    ", $iteration);
    $resultArray = array_map(
        function (string|int $key) use ($tab, $value, $iteration): string {
            $stringValue = toString($value[$key], $iteration + 1);
            return "{$tab}    {$key}: {$stringValue}";
        },
        array_keys($value)
    );

    return implode("\n", ['{', ...$resultArray, "{$tab}}"]);
}

function getStringValue(mixed $value): string
{
    if (is_bool($value)) {
        return $value ? 'true' : 'false';
    }
    if (is_null($value)) {
        return 'null';
    }
    return $value;
}
