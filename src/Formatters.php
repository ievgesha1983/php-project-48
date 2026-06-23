<?php

namespace Differ\Formatters;

use function Differ\Formatters\Json\toJsonString;
use function Differ\Formatters\Plain\toPlainString;
use function Differ\Formatters\Stylish\toStylishString;

const OUTPUT_VALID_FORMAT_TYPES = ['stylish', 'plain', 'json'];

function formatDiff(array $diff, string $format): string
{
    if (!in_array($format, OUTPUT_VALID_FORMAT_TYPES)) {
        throw new \Exception("Формат вывода '{$format}' не поддерживается");
    }

    return match ($format) {
        'stylish' => toStylishString($diff),
        'plain' => toPlainString($diff),
        'json' => toJsonString($diff),
        default => throw new \Exception("Формат вывода '{$format}' не поддерживается")
    };
}
