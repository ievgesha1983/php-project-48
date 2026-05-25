<?php

namespace Hexlet\Code;

use Docopt\Response;

class DifferenceProcessor
{
    public static function getDiffInfo(array $args): string
    {
        if (!isset($args['--format'])) {
            return 'Формат вывода не указан';
        }

        if (!isset($args['<firstFile>'])) {
            return 'Не указан firstFile';
        }

        if (!isset($args['<secondFile>'])) {
            return 'Не указан secondFile';
        }

        return 'Пока все хорошо';
    }
}