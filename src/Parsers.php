<?php

namespace Differ\Parsers;

use Symfony\Component\Yaml\Yaml;

function parse(array $fileProperties, string $content): array
{
    $fileData = [
        'fileName' => "{$fileProperties['fileName']}.{$fileProperties['extension']}",
        'path' => $fileProperties['path'],
    ];

    try {
        $fileData['data'] = match ($fileProperties['extension']) {
            'json' => parseJson($content),
            'yaml', 'yml' => parseYaml($content),
            default => throw new \Exception("расширение файла не поддерживается"),
        };
    } catch (\Exception $e) {
        $filePath = realpath("{$fileData['path']}/{$fileData['fileName']}");
        throw new \Exception("'{$filePath}' - {$e->getMessage()}");
    }

    return $fileData;
}

function parseJson(string $content): object
{
    $result = json_decode($content);
    if (is_null($result)) {
        throw new \Exception("некорректный формат содержимого или файл пуст");
    }

    return $result;
}

function parseYaml($content): object|null
{
    $parsedData = Yaml::parse($content, Yaml::PARSE_OBJECT_FOR_MAP);
    if (!is_object($parsedData)) {
        throw new \Exception("некорректный формат содержимого или файл пуст");
    }

    return $parsedData;
}
