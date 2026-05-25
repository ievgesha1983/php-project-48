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

const VALID_FORMAT_OUTPUT_TYPES = ['stylish'];
const VALID_FILE_EXTENSIONS = ['json'];
