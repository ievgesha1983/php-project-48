### Hexlet tests and linter status:
[![Actions Status](https://github.com/ievgesha1983/php-project-48/actions/workflows/hexlet-check.yml/badge.svg)](https://github.com/ievgesha1983/php-project-48/actions)
[![test-and-lint](https://github.com/ievgesha1983/php-project-48/actions/workflows/test-and-lint.yml/badge.svg)](https://github.com/ievgesha1983/php-project-48/actions/workflows/test-and-lint.yml)
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=ievgesha1983_php-project-48&metric=alert_status)](https://sonarcloud.io/summary/new_code?id=ievgesha1983_php-project-48)
[![Bugs](https://sonarcloud.io/api/project_badges/measure?project=ievgesha1983_php-project-48&metric=bugs)](https://sonarcloud.io/summary/new_code?id=ievgesha1983_php-project-48)
[![Code Smells](https://sonarcloud.io/api/project_badges/measure?project=ievgesha1983_php-project-48&metric=code_smells)](https://sonarcloud.io/summary/new_code?id=ievgesha1983_php-project-48)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=ievgesha1983_php-project-48&metric=coverage)](https://sonarcloud.io/summary/new_code?id=ievgesha1983_php-project-48)
[![Duplicated Lines (%)](https://sonarcloud.io/api/project_badges/measure?project=ievgesha1983_php-project-48&metric=duplicated_lines_density)](https://sonarcloud.io/summary/new_code?id=ievgesha1983_php-project-48)

## Difference Generator

Uses to generate the differences between two input files and output the result.

### Prerequisites
- Linux, MacOS, WSL
- PHP >=8.3
- Make
- Git

### Install
```
git clone https://github.com/ievgesha1983/php-project-48.git
cd php-project-48

make install
```

#### Install Difference Generator packages

[![asciicast](https://asciinema.org/a/SLaD0LPg0NUIozSH.svg)](https://asciinema.org/a/SLaD0LPg0NUIozSH)

#### Using the Difference Generator with .json files

- In the CLI:
```
./bin/gendiff data/file1.json data/file2.json
```
- As a function:
```
<php

echo \Differ\Differ\genDiff('data/file1.json', 'data/file2.json');
```

[![asciicast](https://asciinema.org/a/TlLiWWQZi4xbBaoy.svg)](https://asciinema.org/a/TlLiWWQZi4xbBaoy)

#### Using the Difference Generator with .json and .yaml files

[![asciicast](https://asciinema.org/a/iK9l6DLPA2kuutti.svg)](https://asciinema.org/a/iK9l6DLPA2kuutti)

#### Using the plain output format

- In the CLI:
```
./bin/gendiff --format plain data/file1.json data/file2.json
```
- As a function:
```
<php

echo \Differ\Differ\genDiff('data/file1.json', 'data/file2.json', 'plain');
```
[![asciicast](https://asciinema.org/a/gPmyrL7RShLz3tzp.svg)](https://asciinema.org/a/gPmyrL7RShLz3tzp)

#### Using the json output format

- In the CLI:
```
./bin/gendiff --format json data/file1.json data/file2.yaml
```

- As a function:
```
<php

echo \Differ\Differ\genDiff('data/file1.json', 'data/file2.yaml', 'json');
```
[![asciicast](https://asciinema.org/a/1seU9gmUdVETi4GQ.svg)](https://asciinema.org/a/1seU9gmUdVETi4GQ)
