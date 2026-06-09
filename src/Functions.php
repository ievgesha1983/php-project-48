<?php

namespace Differ\Functions;

use Differ\DifferenceProcessor;

function isValidFormat(string $format): bool
{
    global $config;

    return in_array($format, $config['validOutputFormatTypes']);
}

function isValidFile(string $filePath): bool
{
    global $config;

    if (!is_file($filePath) || !is_readable($filePath)) {
        return false;
    }

    $checkExtension = array_reduce(
        $config['validFilesExtensions'],
        fn($acc, $ext) => $acc ? $acc : str_ends_with($filePath, ".{$ext}"),
        false
    );

    return $checkExtension;
}

function getNonComplexValue(mixed $value): mixed
{
    return is_array($value) ? '[complex value]' : $value;
}

function getStringValue(mixed $value): string
{
    if (is_bool($value)) {
        return $value ? 'true' : 'false';
    }
    if (is_null($value)) {
        return 'null';
    }
    if (is_array($value)) {
        return '[complex value]';
    }
    if (is_string($value)) {
        return "'{$value}'";
    }
    return $value;
}
