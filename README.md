# Translator - for Lumen 
[![Total Downloads](https://img.shields.io/packagist/dt/monolog/monolog.svg)](https://packagist.org/packages/monolog/monolog)
[![Latest Stable Version](https://img.shields.io/packagist/v/monolog/monolog.svg)](https://packagist.org/packages/monolog/monolog)

Translator is a package to generate data and to translate data:

## Build: 
Read the Lumen Application Seeders files and write the json files with a friendly structure for editing "see configuration"

## Translator: 
Has several methods to translate array, obejct and string can receive a matrix of objects with array and the answer will be the translation of it without altering its structure


## Installation

Install the latest version with

```bash
$ composer require lumen/translator
```


## Configuration

```bash
$ php vendor/lumen/translator/configuration.php --config
```
The previous command will create the following directory and files:

```bash
your_app_name/
├── translator/
│ ├── configuration.json
│ ├── default.json
│ ├── en-AR.json
│ ├── en-US.json
│ └── pt-BR.json
```
configuration.json:
```json
{
    "default": "",
    "column_table": [],
    "not_tables": [],
    "file_config": [],
    "filter_column": []
}
```
Desciption: Configuration file with

	default: default language example file name "es-AR"
	column_table: columns of the tables to read (only columns string, text, varchar*)
	not_tables: tables that will not be translated
	file_config: configuration translation files that are in your_app_name/config/
	filter_column: columns with data that does not want translation
	### Configurationdd

default.json:
```json
{
    "table_a.000": "Hola Mundo",
    "table_b.001": "Mi nombre es",
    "table_b.002": "el mundo es todo"
}
```
Description default.json: dynamic data created by the execution of seeders

es-AR.json:
```json
{
    "table_a.000": "Hola Mundo",
    "table_b.001": "Mi nombre es",
    "table_b.002": "el mundo es todo"
}
```
Description es-AR.json: manual data and you must add the keys that are in default.json with the translation that applies

en-US.json:
```json
{
    "table_a.000": "Hello World",
    "table_b.001": "My name is",
    "table_b.002": "the world is all"
}
```
Description en-US.json: manual data and you must add the keys that are in default.json with the translation that applies

pt-BR.json:
```json
{
    "table_a.000": "Olá mundo",
    "table_b.001": "Meu nome é",
    "table_b.002": "o mundo é tudo"
}
```
Description pt-BR.json: manual data and you must add the keys that are in default.json with the translation that applies



























Create file "dictionary" in app/config/ :

```php
<?php
	return [
		
		// colocar uno de los existente en el directorio app/config/dictionary/
		'default' => 'es-AR', 
		//columns a usar por tabla si existe dentro de la tabla
        'column_table' => [
            'name', 
            'attributes', 
            'description'
        ],
        //tablas que no llevan diccionario y son omitidas por la funcion
        'not_tables' => [
            'locations',
            'market_locations',
            'market_currencies'
        ],
        //archivos a traducir que esten dentro de app/config como configuraciones especiales
        'file_config' => [
            'notifications',
            'pdf'
        ]
	];


```
## Basic Usage

```php
<?php

use Translator\Translator;
use Monolog\Handler\StreamHandler;

// create a log channel
$log = new Logger('name');
$log->pushHandler(new StreamHandler('path/to/your.log', Logger::WARNING));

// add records to the log
$log->addWarning('Foo');
$log->addError('Bar');
```

## Documentation

- [Usage Instructions](doc/01-usage.md)
- [Handlers, Formatters and Processors](doc/02-handlers-formatters-processors.md)
- [Utility classes](doc/03-utilities.md)
- [Extending Monolog](doc/04-extending.md)

## Third Party Packages

Third party handlers, formatters and processors are
[listed in the wiki](https://github.com/Seldaek/monolog/wiki/Third-Party-Packages). You
can also add your own there if you publish one.

## About

### Requirements

- Monolog works with PHP 5.3 or above, and is also tested to work with HHVM.

### Submitting bugs and feature requests

Bugs and feature request are tracked on [GitHub](https://github.com/Seldaek/monolog/issues)

### Framework Integrations

- Frameworks and libraries using [PSR-3](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md)
  can be used very easily with Monolog since it implements the interface.
- [Symfony2](http://symfony.com) comes out of the box with Monolog.
- [Silex](http://silex.sensiolabs.org/) comes out of the box with Monolog.
- [Laravel 4 & 5](http://laravel.com/) come out of the box with Monolog.
- [Lumen](http://lumen.laravel.com/) comes out of the box with Monolog.
- [PPI](http://www.ppi.io/) comes out of the box with Monolog.
- [CakePHP](http://cakephp.org/) is usable with Monolog via the [cakephp-monolog](https://github.com/jadb/cakephp-monolog) plugin.
- [Slim](http://www.slimframework.com/) is usable with Monolog via the [Slim-Monolog](https://github.com/Flynsarmy/Slim-Monolog) log writer.
- [XOOPS 2.6](http://xoops.org/) comes out of the box with Monolog.
- [Aura.Web_Project](https://github.com/auraphp/Aura.Web_Project) comes out of the box with Monolog.
- [Nette Framework](http://nette.org/en/) can be used with Monolog via [Kdyby/Monolog](https://github.com/Kdyby/Monolog) extension.
- [Proton Micro Framework](https://github.com/alexbilbie/Proton) comes out of the box with Monolog.

### Author

Jordi Boggiano - <j.boggiano@seld.be> - <http://twitter.com/seldaek><br />
See also the list of [contributors](https://github.com/Seldaek/monolog/contributors) which participated in this project.

### License

Monolog is licensed under the MIT License - see the `LICENSE` file for details

### Acknowledgements

This library is heavily inspired by Python's [Logbook](http://packages.python.org/Logbook/)
library, although most concepts have been adjusted to fit to the PHP world.
