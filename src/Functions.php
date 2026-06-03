<?php

namespace Hexlet\Code\Functions;

use Hexlet\Code\DifferenceProcessor;

function isValidFormat(string $format): bool
{
    return in_array($format, VALID_OUTPUT_FORMAT_TYPES);
}

function isValidFile(string $filePath): bool
{
    if (!is_file($filePath) || !is_readable($filePath)) {
        return false;
    }

    $checkExtension = array_reduce(
        VALID_FILES_EXTENSIONS,
        fn($acc, $ext) => $acc ? $acc : str_ends_with($filePath, ".{$ext}"),
        false
    );

    return $checkExtension;
}

function genDiff(string $firstFile, string $secondFile, string $format = 'stylish'): string
{
    $args = ['<firstFile>' => $firstFile, '<secondFile>' => $secondFile, '--format' => $format];
    return DifferenceProcessor::getDiffInfo($args);
}
