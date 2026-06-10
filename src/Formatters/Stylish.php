<?php

namespace Differ\Formatters\Stylish;

function toStylishString(array $diff): string
{
    $differences = $diff['differences'];

    $iter = function (array $differences, int $iteration) use (&$iter): string {
        $tab = str_repeat("    ", $iteration);
        $differencesResult = array_map(
            function ($difference) use ($iter, $iteration) {
                $tab = str_repeat("    ", $iteration);
                $sign = match ($difference['type']) {
                    -1 => '-',
                    0 => ' ',
                    1 => '+'
                };
                if (is_array($difference['value'])) {
                    $value =  $iter($difference['value'], $iteration + 1);
                } elseif (is_bool($difference['value'])) {
                    $value = $difference['value'] ? 'true' : 'false';
                } elseif (is_null($difference['value'])) {
                    $value = 'null';
                } else {
                    $value = $difference['value'];
                }
                return "{$tab}  {$sign} {$difference['key']}: {$value}";
            },
            $differences
        );

        return implode("\n", ['{', ...$differencesResult, "{$tab}}"]);
    };

    return $iter($differences, 0);
}
