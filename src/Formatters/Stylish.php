<?php

namespace Differ\Formatters\Stylish;

use function Differ\Formatters\getStringValue;

function toStylishString(array $diff): string
{
    $differences = $diff['differences'];

    return toStylishStringIter($differences, 0);
}

function toStylishStringIter(array $differences, int $iteration): string
{
    $toString = function (mixed $value) use ($iteration): string {
        return is_array($value) ?
            toStylishStringIter($value, $iteration + 1) :
            getStringValue($value, 'withoutQuotes');
    };

    $tab = str_repeat("    ", $iteration);
    $differencesResult = array_map(
        function ($difference) use ($iteration, $toString): string {
            $tab = str_repeat("    ", $iteration);

            if ($difference['type'] === 'updatedProperty') {
                $oldValue = $toString($difference['oldValue']);
                $newValue = $toString($difference['newValue']);
                $removedString = "{$tab}  - {$difference['key']}: {$oldValue}";
                $addedString = "{$tab}  + {$difference['key']}: {$newValue}";
                return "{$removedString}\n{$addedString}";
            }

            $sign = match ($difference['type']) {
                'removedProperty' => '-',
                'addedProperty' => '+',
                'unchangedProperty' => ' '
            };
            $value = $toString($difference['value']);

            return "{$tab}  {$sign} {$difference['key']}: {$value}";
        },
        $differences
    );

    return implode("\n", ['{', ...$differencesResult, "{$tab}}"]);
}
