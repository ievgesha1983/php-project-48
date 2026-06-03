<?php

namespace Hexlet\Code\DifferenceProcessor;

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

    public function parseJson(): void
    {
        $this->data = json_decode(file_get_contents("{$this->path}/{$this->baseName}"));
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

        $firstDataArr = get_object_vars($this->data);
        $secondDataArr = get_object_vars($secondData);

        $firstDataUniqueKeys = array_diff(array_keys($firstDataArr), array_keys($secondDataArr));
        $firstDataUniqueDifferences = array_map(
            fn ($key) => ['type' => -1, 'key' => $key, 'value' => $firstDataArr[$key]],
            $firstDataUniqueKeys
        );

        $intersectKeys = array_intersect(array_keys($firstDataArr), array_keys($secondDataArr));
        $intersectDifferences = array_reduce(
            $intersectKeys,
            function ($acc, $key) use ($firstDataArr, $secondDataArr) {
                if ($firstDataArr[$key] === $secondDataArr[$key]) {
                    $acc[] = ['type' => 0, 'key' => $key, 'value' => $firstDataArr[$key]];
                } else {
                    $acc[] = ['type' => -1, 'key' => $key, 'value' => $firstDataArr[$key]];
                    $acc[] = ['type' => 1, 'key' => $key, 'value' => $secondDataArr[$key]];
                }
                return $acc;
            },
            []
        );

        $secondDataUniqueKeys = array_diff(array_keys($secondDataArr), array_keys($firstDataArr));
        $secondDataUniqueDifferences = array_map(
            fn ($key) => ['type' => 1, 'key' => $key, 'value' => $secondDataArr[$key]],
            $secondDataUniqueKeys
        );

        $differences = array_merge($firstDataUniqueDifferences, $intersectDifferences, $secondDataUniqueDifferences);
        $sortDifferences = sortBy($differences, fn($item) => $item['key']);

        return array_values($sortDifferences);
    }
}