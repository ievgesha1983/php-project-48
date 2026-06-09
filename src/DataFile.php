<?php

namespace Differ;

use Symfony\Component\Yaml\Yaml;

use function Funct\Collection\sortBy;

class DataFile
{
    private string $name;
    private string $baseName;
    private string $path;
    private string $extension;
    private ?\stdClass $data;


    public function __construct(string $filePath)
    {
        $pathInfo = pathinfo($filePath);
        $this->name = $pathInfo['filename'];
        $this->baseName = $pathInfo['basename'];
        $this->path = $pathInfo['dirname'];
        $this->extension = $pathInfo['extension'];
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getBaseName(): string
    {
        return $this->baseName;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getExtension(): string
    {
        return $this->extension;
    }

    public function getData(): ?\stdClass
    {
        return is_null($this->data) ? null : clone($this->data);
    }

    public function parse(): void
    {
        switch ($this->extension) {
            case 'json':
                $this->parseJson();
                break;
            case 'yaml':
            case 'yml':
                $this->parseYaml();
                break;
            default:
                break;
        }
    }

    public function parseJson(): void
    {
        $this->data = json_decode(file_get_contents("{$this->path}/{$this->baseName}"));
    }

    public function parseYaml(): void
    {
        $parsedData = Yaml::parseFile("{$this->path}/{$this->baseName}", Yaml::PARSE_OBJECT_FOR_MAP);
        $this->data = is_object($parsedData) ? $parsedData : null;
    }

    public function toJson(): string
    {
        return json_encode($this->data);
    }

    public function getDifferences(DataFile $secondFile): ?array
    {
        if ($this->data === null) {
            return null;
        }

        $secondData = $secondFile->getData();

        if ($secondData === null) {
            return null;
        }

        $iter = function (\stdClass $firstData, \stdClass|false $secondData = false) use (&$iter): array {
            $toArrayOrValue = function (mixed $value) use (&$iter): mixed {
                return is_object($value) ? $iter($value) : $value;
            };
            $firstDataArr = get_object_vars($firstData);
            if ($secondData === false) {
                return array_map(
                    function (mixed $value, string $key) use ($toArrayOrValue): array {
                        return ['type' => 0, 'key' => $key, 'value' => $toArrayOrValue($value)];
                    },
                    $firstDataArr,
                    array_keys($firstDataArr)
                );
            }

            $secondDataArr = get_object_vars($secondData);

            $firstDataUniqueKeys = array_diff(array_keys($firstDataArr), array_keys($secondDataArr));
            $firstDataUniqueDifferences = array_map(
                fn ($key) => ['type' => -1, 'key' => $key, 'value' => $toArrayOrValue($firstDataArr[$key])],
                $firstDataUniqueKeys
            );

            $intersectKeys = array_intersect(array_keys($firstDataArr), array_keys($secondDataArr));
            $intersectDifferences = array_reduce(
                $intersectKeys,
                function (array $acc, string $key) use (&$iter, $firstDataArr, $secondDataArr, $toArrayOrValue): array {
                    if (
                        is_object($firstDataArr[$key]) && $firstDataArr[$key] == $secondDataArr[$key] ||
                        $firstDataArr[$key] === $secondDataArr[$key]
                    ) {
                        $acc[] = ['type' => 0, 'key' => $key, 'value' => $toArrayOrValue($firstDataArr[$key])];
                    } elseif (is_object($firstDataArr[$key]) && is_object($secondDataArr[$key])) {
                        $acc[] = ['type' => 0, 'key' => $key, 'value' =>
                            $iter($firstDataArr[$key], $secondDataArr[$key])
                        ];
                    } else {
                        $acc[] = ['type' => -1, 'key' => $key, 'value' => $toArrayOrValue($firstDataArr[$key])];
                        $acc[] = ['type' => 1, 'key' => $key, 'value' => $toArrayOrValue($secondDataArr[$key])];
                    }
                    return $acc;
                },
                []
            );

            $secondDataUniqueKeys = array_diff(array_keys($secondDataArr), array_keys($firstDataArr));
            $secondDataUniqueDifferences = array_map(
                fn ($key) => ['type' => 1, 'key' => $key, 'value' =>
                    is_object($secondDataArr[$key]) ? $iter($secondDataArr[$key]) : $secondDataArr[$key]
                ],
                $secondDataUniqueKeys
            );

            $differences = array_merge(
                $firstDataUniqueDifferences,
                $intersectDifferences,
                $secondDataUniqueDifferences
            );
            $sortDifferences = sortBy($differences, fn($item) => $item['key']);

            return array_values($sortDifferences);
        };

        return $iter($this->data, $secondData);
    }
}
