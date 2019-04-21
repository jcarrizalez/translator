<?php

if((int) $argc==1){
	echo "\n--config or -c  para crear la configuracion inicial";
//	echo "\n--reset or -r  reset  a los archivos de configuracion existentes";
	echo "\n";
	echo "\n";
	exit;
}

$dir = explode('/vendor/', __FILE__)[0];
$i=1;

if(in_array($argv[$i], ['--config', '-c'])){

	if (!file_exists("$dir/translator")) {

		mkdir( "$dir/translator", 0777, true );
	}
	
	createFiles("$dir/translator");
}

function createFiles($dir){

	$files = [
		'configuration' => configuration(),
		'default' => ['table_a.000'=>'Hola Mundo','table_b.001'=>'Mi nombre es','table_b.002'=>'el mundo es todo'],
		'es-AR' => ['table_a.000'=>'Hola Mundo','table_b.001'=>'Mi nombre es','table_b.002'=>'el mundo es todo'],
		'en-US' => ['table_a.000'=>'Hello World','table_b.001'=>'My name is','table_b.002'=>'the world is all'],
		'pt-BR' => ['table_a.000'=>'Olá mundo','table_b.001'=>'Meu nome é','table_b.002'=>'o mundo é tudo']
	];
	
	foreach ($files as $key => $value) {
		$file = "$dir/$key.json";
		if (!file_exists($file)) {
			$data = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
	   		fwrite(fopen("$file", "w+b"),$data);
		}
	}
}


function configuration(){
	return [
		"default"=> "es-AR", 
	    "column_table"=> [
	        "name", 
	        "attributes", 
	        "description"
	    ],
	    "not_tables"=> [
	        "locations",
	        "market_locations",
	        "market_currencies"
	    ],
	    "file_config"=> [
	        "notifications",
	        "pdf"
	    ],
	    "filter_column"=> [ 
	        "slug",
	        "status",
	        "key",
	        "alignment",
	        "logo",
	        "view",
	        "link",
	        "send_to",
	        "on_hold",
	        "query",
	        "id",
	        "conditions"
        ]
    ];
}
