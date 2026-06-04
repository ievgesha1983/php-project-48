<?php

const DOCOPT_SCHEME =
"Generate diff

Usage:
  gendiff (-h|--help)
  gendiff (-v|--version)
  gendiff [--format <fmt>] <firstFile> <secondFile>
  
Options:
  -h --help                     Show this help
  -v --version                  Show version
  --format <fmt>                Report format [default: stylish]
";
const DOCOPT_CONFIG = [
    'version' => 'Generate deff 0.0.1'
];

const VALID_OUTPUT_FORMAT_TYPES = ['stylish', 'plain'];
const VALID_FILES_EXTENSIONS = ['json', 'yml', 'yaml'];
