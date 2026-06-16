<?php

namespace Differ\Formatters;

use function Differ\Formatters\Json\toJsonString;
use function Differ\Formatters\Plain\toPlainString;
use function Differ\Formatters\Stylish\toStylishString;

const VALID_OUTPUT_FORMAT_TYPES = ['stylish', 'plain', 'json'];

function formatDiff(array $diff, string $format): string
{
    return match ($format) {
        'stylish' => toStylishString($diff),
        'plain' => toPlainString($diff),
        'json' => toJsonString($diff),
    };
}

function getNonComplexValue(mixed $value): mixed
{
    return is_array($value) ? '[complex value]' : $value;
}
