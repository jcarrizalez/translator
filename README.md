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
`Desciption configuration.json:` Configuration file with

	`default`: default language example file name "es-AR"
	`column_table`: columns of the tables to read (only columns string, text, varchar*)
	`not_tables`: tables that will not be translated
	`file_config`: configuration translation files that are in your_app_name/config/
	`filter_column`: columns with data that does not want translation

default.json:
```json
{
    "table_a.000": "Hola Mundo",
    "table_b.001": "Mi nombre es",
    "table_b.002": "el mundo es todo"
}
```
`Description default.json:` dynamic data created by the execution of seeders

es-AR.json:
```json
{
    "table_a.000": "Hola Mundo",
    "table_b.001": "Mi nombre es",
    "table_b.002": "el mundo es todo"
}
```
`Description es-AR.json:` manual data and you must add the keys that are in default.json with the translation that applies

en-US.json:
```json
{
    "table_a.000": "Hello World",
    "table_b.001": "My name is",
    "table_b.002": "the world is all"
}
```
`Description en-US.json:` manual data and you must add the keys that are in default.json with the translation that applies

pt-BR.json:
```json
{
    "table_a.000": "Olá mundo",
    "table_b.001": "Meu nome é",
    "table_b.002": "o mundo é tudo"
}
```
`Description pt-BR.json:` manual data and you must add the keys that are in default.json with the translation that applies



Create Traits for more control and use in `App\Traits\Translator.php`:

```php
<?php

namespace App\Traits;

use Translator\Translator;
use Translator\Build;

trait Translator {

	/**
     * @return mixed
    */
	public function dictionaryBuild(){
	
		(new Build())->make();
	}
	/**
     * @return array
    */
	public function dictionaries(){
	
		return (new Translator())->dictionaries();
    }

    public function dictionaryDefault(){
	
		return (new Translator())->default();
    }
   
	/**
     * indicative if it is going to translate in favor of the user or the system (save system as TRUE)
     *
     * @param  $data data to translation
     * @param  $system TRUE => as in database or FALSE => user language
     * @return mixed
    */
	public function dictionary($data, $system=FALSE){

		return (new Translator())->dictionary($data, $system);
	}

	/**
     * @return string
    */
	public function language(){
		
		$languageC = new Translator();
		$language = $languageC->language();
        $language = $languageC->validateLanguage($language);
		return $language;
	}
	
	/**
     * @param  $request
     * @return mixed
    */
	public function dictionaryRequest($request){

		return (new Translator())->dictionaryRequest($request);
    }

    /**
     * Translation method indicating the language of the system
     * @param  $data
     * @param  $language
     * @return mixed
    */
    public function dictionaryDocument($data, $language){

		return (new Translator())->dictionaryDocument($data, $language);
    }
}
```

## Methods

`$ this-> dictionaryDefault ()` to determine the language you are sending from the headers (if it does not exist it's null)
`$ this-> dictionary ($data)` used when we want to return a data with translation
`$ this-> dictionaryRequest ($request)` used when we receive data in some language and it is passed to data according to default.json


## Basic Usage Translator

`frontend` en las headers

	Accept-Language=es-AR

Note: si no se envia Accept-Language se toma por defecto el de `../translator/configuration.json`

`backend`

	Create Traits for greater control and use `/app/Traits/Translator.php`

```php
<?php

use App\Traits\Translator;

class yourClassX
{
    use Translator;

	public function yourMethodXGet(){

		//part of your code
	    $your_data_example = ['producto','llamada',['color'=>'blanca']];

	    //use of translator
	    $data = $this->dictionary($your_data_example);
	    
	    //part of your code
	    return response()->json($data, 200);
	}

	public function yourMethodXPostUpdate(Request $request){

        //translator to all the request
        $request = $this->dictionaryRequest($request);

	    //part of your code
	}

	public function yourMethodX(){

        //part of your code
	    $your_data_example = ['producto','llamada',['color'=>'blanca']];

	    //use of translator
		$language = ($language==NULL)? $this->dictionaryDefault(): $language;
		$data = $this->dictionaryDocument($your_data_example, $language);
	}

}
```

## Basic Usage Build



## About

### Requirements

- Translator works with PHP 5.3 or higher.

### Framework Integrations

- Frameworks and libraries using [PSR-3](https://github.com/jcarrizalez/translator)
- [Laravel 4 & 5](http://laravel.com/) to integrate.
- [Lumen](http://lumen.laravel.com/) to integrate.
- [CakePHP](http://cakephp.org/) is usable with Translator via the [cakephp-translator](https://github.com/jcarrizalez/translator) plugin "disabled".

### Author

Juan Carrizalez - <sitgem@gmail.com> <br />

### License

Translator is licensed under the MIT License - see the `LICENSE` file for details
