<?php

namespace Translator;

use Illuminate\Support\Facades\DB;
use Translator\Components;

class Build{

	const USE_DICTIONARY = TRUE;

    public function __construct(){

    	$this->comp = new Components();

	    $this->dictionary_default = $this->comp->readConfig('default');
	    
	    $this->filter_column = $this->comp->readConfig('filter_column');
	    
	    $this->list_files_json = $this->comp->ListFilesJson();
	    
	    $this->data = []; 
    }

    public function make(){

    	
        if(!self::USE_DICTIONARY) return 'Diccionario deshabilitado';
        //columns a usar por tabla si existe dentro de la tabla
        $this->column_table = $this->comp->readConfig('column_table');

        //tablas que no llevan diccionario y son omitidas por la funcion
        $this->not_tables = $this->comp->readConfig('not_tables');

        $this->comp->validateLanguage();

        $dbtables = $this->dataDatabaseSeeder();

        $dbtables = $this->tablesSeeder($dbtables);

        $dbtables = $this->dataTables($dbtables);

        $dbtables = $this->filterColumnData($dbtables);
        //BD base de datos
        $data = $this->addKey('BD', $dbtables);

        //NT notificaciones
        $notify = array_filter(config('notifications'));
        $data = $this->addKey('NT', $notify);

        //PF archivos pdf 
        $pdf = array_filter(config('pdf'));
        $data = $this->addKey('PF', $pdf);

        $data = $this->comp->recursiveArrayIterator($data, 'encode', FALSE);

        $data = $this->comp->filterData($data);
        
        //para comprobar uso, esto solo es de prueba 
        //$data = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        //$this->writeFile("data.3json", $data);
        //$data = file_get_contents("/var/www/Agree/fas/MIFAS/laravel/config/dictionary/data.3json");
        //$data = (array) json_decode($data);

        $data = $this->addNumberReference($data);

        $this->mergeData($data);
    }

    private function dataDatabaseSeeder(){

        $dbseeder = file_get_contents($this->comp->dir('seeds').'/DatabaseSeeder.php');
        $dbseeder = explode(PHP_EOL, $dbseeder);
        foreach ($dbseeder as $key => $value) {

            if(strpos( $value, '$this->call(') === FALSE || strpos($value, '//$this->call(') !== FALSE){

                unset($dbseeder[$key]);
            }
            else{

                $value = trim(explode("'", explode('$this->call('."'", $value)[1])[0]);
                $dbseeder[$key] = $value;
            }
        }        
        return $dbseeder;
    }

    private function tablesSeeder($data){

        $tables = [];
        foreach ($data as $class) {

            $file = fopen($this->comp->dir('seeds')."/$class.php", "r") or exit("Unable to open file $class!");
            while(!feof($file)){

                $line = fgets($file);
                if(strpos($line, 'DB::table(') !== FALSE){

                    $table = trim(explode("'", explode("DB::table('", $line)[1])[0]);
                    $tables[] = $table;
                }
            }
            fclose($file);
        }
        return array_values(array_unique($tables));
    }

    private function dataTables($data){

        $return = [];
        foreach ($data as $table) {

            if(!in_array($table, $this->not_tables)){
             
                $data =  DB::table($table)->selectRaw('*')->orderbyRaw('1')->get()->toArray();
                $data = json_decode(json_encode($data), true);
                $return[$table] = $data;
            }
        }
        return $return;
    }

    private function filterColumnData($data){

        $return = [];
        foreach ($data as $table => $row_t) {
    
            foreach ($row_t as $key => $column_data) {

                //if(!array_key_exists('id', $column_data)) abort(404, 'No existe column id en tabla '.$table);

                if(array_key_exists('id', $column_data))
                    $id = $column_data['id'];
                elseif(array_key_exists('slug', $column_data))
                    $id = $column_data['slug'];
                elseif(array_key_exists('name', $column_data))
                    $id = $column_data['name'];
                else
                    abort(404, 'No existe al menos una column ("id", "slug", "name") en tabla '.$table);

                
                foreach ($column_data as $column => $row) {

                    $data_json = json_decode($row, true);

                    if(in_array($column, $this->column_table)){

                        $return[$table][$id][$column] = is_array($data_json)? $data_json : $row;
                    }
                }
            }
        }
        return $return;
    }

    private function addKey($key , $new_data = []){

        $this->data[$key] = $new_data;
        return $this->data;
    }
    
    private function addNumberReference($data){
        
        $config = $this->comp->dir('config');

        if (!file_exists("$config/default.json")) {

        	$this->comp->writeFile("default.json", json_encode((object)[]));
        } 

        //almaceno la data existente como default_old
        $default_old = file_get_contents("$config/default.json");
        $default_old = (array) json_decode($default_old);

        /*INI mientras programo        
        $data = file_get_contents("$config/basedatos.json");
        $data = (array) json_decode($data);
        //FIN */
        $default_new = array_unique($data);

        //INI marco de $default_new que NO existe en $default_old 
        $aggregated = $deleted = [];
        foreach ($default_new as $key => $value) {

            if(!array_search($value, $default_old)){

                $aggregated[$key] = $value;
            }
        }
        //FIN
        //INI marco de $default_old que NO existe en $default_new 
        foreach ($default_old as $key => $value) {

            if(!array_search($value, $default_new)){

                $deleted[$key] = $value;
            }
        }
        //FIN

        //obtengo el ultimo key para saber que count agregar como inicio en la nueva data
        $key_old = array_search(end($default_old), $default_old);
        $key_old = explode('.', $key_old);
        $key_old = end($key_old);
        $count = $key_old;

        foreach ($aggregated as $key => $value) {

            $count++;
            $key_a = explode('.', $key);
            $key_p = (count($key_a)-1);
            $root  = $key_a[0];

            $table = '';
            if(isset($key_a[1])){

                $table_a = explode('_', $key_a[1]);
                $c = count($table_a);
                foreach ($table_a as $t) {

                    $table .= ($c==1)? ucfirst(substr($t, 0, 10)) : '';
                    $table .= ($c==2)? ucfirst(substr($t, 0, 5)) : '';
                    $table .= ($c==3)? ucfirst(substr($t, 0, 3)) : '';
                    $table .= ($c==4)? ucfirst(substr($t, 0, 2)) : '';
                    $table .= ($c==5)? ucfirst(substr($t, 0, 2)) : '';
                }
            }
            
            $table = str_pad(substr($table, 0, 8), 8, '_', STR_PAD_RIGHT);

            $prope = str_pad(substr($key_a[$key_p], 0, 4), 4, '_', STR_PAD_LEFT);
            $type = $root.'.'.$table.'.'.$prope;

            unset($aggregated[$key]);
            $aggregated[$type.'.'.str_pad($count, 3, 0, STR_PAD_LEFT)] = $value;
        }

        $return = [
                'aggregated' => $aggregated, 
                'deleted' => $deleted
        ]; 
        return $return;     
    }

    private function mergeData($data){

        $files = $this->list_files_json;

        $config = $this->comp->dir('config');

        if(!in_array('default', $files)){

            $this->comp->writeFile("default.json", json_encode((object)[]));
        }
        $default_old = file_get_contents("$config/default.json");
        $default_old = (array) json_decode($default_old, true);

        $aggregated = $data['aggregated'];
        $deleted = $data['deleted'];

        $data = array_merge($default_old, $aggregated);
        $data_new = array_unique($data);
        
        $dictionaries = $files;

        foreach ($dictionaries as $dictionary) {

            $json = file_get_contents("$config/$dictionary.json");
            $json = (array) json_decode($json);

            foreach ($data_new as $key => $value) {

                if(array_key_exists($key, $json)){

                    $data_new[$key] = $json[$key];
                }
            }

            $data_dict = json_encode($data_new, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

            if($dictionary=='default'){

                $this->comp->writeFile("$dictionary.json", $data_dict);
            }

            
            $file = "$config/$dictionary.json";
            $data_file = fopen($file, "r") or exit("Unable to open file $class!");
            $text_file = '';

            while(!feof($data_file)){

                $line = fgets($data_file);

                foreach ($deleted as $keyfile => $valuefile) {

                    if(strpos($line, $keyfile) !== FALSE && strpos($line, 'DELETED.') === FALSE){

                        $line = str_replace($keyfile, 'DELETED.'.$keyfile, $line);
                    }
                }
                $text_file .= $line;
            }
            fclose($data_file);

            if($dictionary=='default'){
                $data_file = fopen($file, "w") or exit("Unable to open file $class!");
                fwrite($data_file, $text_file);
                fclose($data_file);
            }
        }
    }
}