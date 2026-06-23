<?php

namespace Differ\Parsers;

use Symfony\Component\Yaml\Yaml;

function parse(array $fileProperties, string $content): array
{
    $fileData = [
        'fileName' => "{$fileProperties['fileName']}.{$fileProperties['extension']}",
        'path' => $fileProperties['path'],
    ];
    $fileData['data'] = match ($fileProperties['extension']) {
        'json' => parseJson($content),
        'yaml', 'yml' => parseYaml($content),
        default => null,
    };

    if (is_null($fileData['data'])) {
        $filePath = realpath("{$fileData['path']}/{$fileData['fileName']}");
        throw new \Exception("'{$filePath}' - некорректный формат содержимого или файл пуст");
    }

    return $fileData;
}

function parseJson(string $content): \stdClass|null
{
    return json_decode($content);
}

function parseYaml($content): \stdClass|null
{
    $parsedData = Yaml::parse($content, Yaml::PARSE_OBJECT_FOR_MAP);
    return is_object($parsedData) ? $parsedData : null;
}
