<?php

namespace Differ\Tests\Fixtures;

function getFixtureFullPath(string $fixtureName): string
{
    return realpath(__DIR__ . "/fixtures/{$fixtureName}");
}
