<?php

return [
   'docoptScheme' => "Generate diff

Usage:
  gendiff (-h|--help)
  gendiff (-v|--version)
  gendiff [--format <fmt>] <firstFile> <secondFile>
  
Options:
  -h --help                     Show this help
  -v --version                  Show version
  --format <fmt>                Report format [default: stylish]
",
    'docoptConfig' => [
        'version' => 'Generate deff 0.0.1'
    ],
    'validOutputFormatTypes' => ['stylish', 'plain', 'json'],
    'validFilesExtensions' => ['json', 'yml', 'yaml']
];
