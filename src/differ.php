<?php

namespace Differ\Differ;

use Differ\DifferenceProcessor;

function genDiff(string $firstFile, string $secondFile, string $format = 'stylish'): string
{
    $args = ['<firstFile>' => $firstFile, '<secondFile>' => $secondFile, '--format' => $format];
    return DifferenceProcessor::getDiffInfo($args);
}
