<?php

namespace Translator;

use Illuminate\Support\Facades\DB;

class Translator{

    public $dictionaries;
    protected $dictionary_default;
    protected $filter_column;
    protected $column_table;
    protected $not_tables;
    protected $data;
    const USE_DICTIONARY = TRUE;
    
    public function __construct(){

        print_r("xxxxxxxxxxxx");
        die;
        /* USO 
            use App\Traits\Dictionary;
            $response = $this->dictionary($response);
        */

        $this->dictionary_default = 'es-AR';
        //$this->dictionary_default = 'en-US';

        //filtros en las columnas no es necesario colocar las distintas a string y se toma es el ultimo valor del key .name
        $this->filter_column = [
            'slug',
            'status',
            'key',
            'alignment',
            'logo',
            'view',
            'link',
            'send_to',
            'on_hold',
            'query',
            'id',
            //'action',
            'conditions' // esta pertenece a las clausulas, revisar bien  esto 
        ];
        $this->data = [];
    }

    public function build(){

        if(!self::USE_DICTIONARY) return 'Diccionario deshabilitado';
        //columns a usar por tabla si existe dentro de la tabla
        $this->column_table = [
            'name', 
            'attributes', 
            'description',
            //'contract_clauses'
        ];

        //tablas que no llevan diccionario y son omitidas por la funcion
        $this->not_tables = [
            'locations',
            'market_locations',
            'market_currencies'
        ];
        
        $this->validateLanguage();

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

        $data = $this->recursiveArrayIterator($data, 'encode', FALSE);

        $data = $this->filterData($data);
        
        //para comprobar uso, esto solo es de prueba 
        //$data = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        //$this->writeFile("data.3json", $data);
        //$data = file_get_contents("/var/www/Agree/fas/MIFAS/laravel/config/dictionary/data.3json");
        //$data = (array) json_decode($data);

        $data = $this->addNumberReference($data);

        $this->mergeData($data);
    }

    private function dir($key = 'default'){

        $dir =  explode('/laravel/', __FILE__)[0];
        $return = ['seeds'=> $dir.'/laravel/database/seeds','config'=> $dir.'/laravel/config/dictionary','default'=> $dir];
        return $return[$key];
    }

    private function dataDatabaseSeeder(){

        $dbseeder = file_get_contents($this->dir('seeds').'/DatabaseSeeder.php');
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

            $file = fopen($this->dir('seeds')."/$class.php", "r") or exit("Unable to open file $class!");
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

    private function ListFilesJson(){

        $config = $this->dir('config');
        $files  = scandir($config);
        $return_files = [];
        foreach ($files as $file) {

            if(strpos($file, '.json') !== FALSE){

                $json = file_get_contents($config.'/'.$file);
                $json = ($json=='') ? '[]' : $json;
                $json = json_decode($json);
                $json = (is_array($json)) ? (object)[] : $json;
                if(!is_object($json)){
                    
                    abort(500, 'Error en Formato Json del archivo "'.$config.'/'.$file.'"');
                }
                $return_files[] = explode('.json', $file)[0];
            }
        }
        return $return_files;
    }

    private function filterData($data){

        $filter = $this->filter_column;
        foreach ($data as $key => $value) {

            $key_array  = explode('.', $key);
            $key_column = $key_array[(count($key_array)-1)];
            $valid = 0;

            foreach ($filter as $vfilter) {

                $valid += (strpos($key_column, $vfilter) !== FALSE) ? 1 : 0;
            }
            $valid += is_numeric($key_column) ? 1 : 0;
            
            
            $eval_str = str_replace(['%',':',',','.','<','>','=','+','-',' ','kg/hl'], '', $value);

            $valid += ($eval_str === null) ? 1 : 0;
            $valid += ($eval_str == '') ? 1 : 0;
            $valid += is_numeric($eval_str) ? 1 : 0;
            $valid += is_bool($eval_str) ? 1 : 0;
            $valid += ($eval_str==NULL) ? 1 : 0;


            $eval_str = preg_replace('/{{(.*?)}}/', '', $eval_str, 1);
            $valid += ($eval_str=='') ? 1 : 0;


            if ($valid != 0){

                unset($data[$key]);
            }
        }
        return $data;
    }

    private function addNumberReference($data){
        
        $config = $this->dir('config');

        if (!file_exists("$config/default.json")) {

            $this->writeFile("default.json", json_encode((object)[]));
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

        $files = $this->ListFilesJson();

        $config = $this->dir('config');

        if(!in_array('default', $files)){

            $this->writeFile("default.json", json_encode((object)[]));
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

                $this->writeFile("$dictionary.json", $data_dict);
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

    private  function writeFile($name, $data){

        $config = $this->dir('config');
        $n_file = fopen($config.'/'.$name, "w");
        fwrite($n_file, $data);
        fclose($n_file);
    }

    /*SECCION DE PARA OBTENER DICCIONARIO Y USOS EN GET Y REQUEST*/

    public function validateLanguage($language=NULL){

        $default = $this->dictionary_default;

        $dictionaries = $this->ListFilesJson();

        if(!in_array($default, $dictionaries) && $default!='default')
            abort(500, 'El Diccionario "'.$default.'" no existe en "'.$this->dir('config').'/"');
        
        if(!isset($language['language'])) return $default;

        $language = $language['language'];

        if($language['exists_country']==TRUE){

            $return = $language['country'];

        }
        elseif($language['exists_country']!=TRUE && $language['exists_default']==TRUE){
        
            foreach ($dictionaries as $key => $value) {

                $new_key = explode('-', $value)[0];
                if(!array_key_exists($new_key, $dictionaries)){

                    $dictionaries[$new_key]= $value;
                }
                unset($dictionaries[$key]);
            }
            $return = $dictionaries[$language['default']];
        }
        else{

            $return = $default;
        }

        return $return;
    }

    public function language(){

        $default = $this->dictionary_default;

        $dictionaries = $this->ListFilesJson();

        $headers = !function_exists('apache_request_headers')? ['Accept-Language' => $default] : apache_request_headers();
        $language = (array_key_exists('Accept-Language', $headers)) ? $headers['Accept-Language'] : $default;

        $language_country = explode(';', $language)[0];
        $language_default = explode('-', $language_country)[0];

        $dictionaries_glob= [];
        foreach ($dictionaries as $key => $value) {

            $dictionaries_glob[$key]= explode('-', $value)[0];
        }

        $language = [
            'language'=>[
                'country' => $language_country,
                'default' => $language_default,
                'exists_country' => (in_array($language_country, $dictionaries))? TRUE : FALSE,
                'exists_default' => (in_array($language_default, $dictionaries_glob))? TRUE : FALSE
                ]
            ];
        return $language;
    }

    public function recursiveArrayIterator($data, $type_encode, $type_data=TRUE, $system=FALSE){

        $type = gettype($data);
        $return = [];
        $type_encode = strtolower($type_encode);
        if($type_encode=='encode'){
            if($type_data)
                $return['type'] = $type;

            $dotFlatten = static function($item, $context = '') use (&$dotFlatten, $type_data){
            
            $retval = [];
            if(count((array) $item)==0){
                if($type_data)
                    $key = explode(')}{(', rtrim($context, '.').'{('.gettype($item).')}')[0].')}';
                else
                    $key = rtrim($context, '.');
                $retval[$key] = NULL;
            }
            if(is_string($item)){

                $retval['text-function'.'{('.gettype($item).')}'] = $item;
            }
            
            if(is_array($item) || is_object($item)){

                foreach($item as $key => $value){

                    $key = str_replace('.', '(#_p_#)', $key);

                    if($type_data){
                        $key = $key.'{('.gettype($value).')}';
                    }
                    if (is_array($value) || is_object($value)){

                        //INI para eliminar la forma como es usada en los modelos laravel
                        if(is_object($value)){
                        
                            $value = json_encode($value);
                            $value = (object) json_decode($value); 
                        }
                        //FIN
                        foreach($dotFlatten($value, "$context$key.") as $iKey => $iValue){
                            $retval[$iKey] = $iValue;
                        }
                    } 
                    else {
                        $retval["$context$key"] = $value;
                    }
                }
            }
            return $retval;
            };
            if($type_data)
                $return['data'] = $dotFlatten($data);
            else
                $return = $dotFlatten($data);
        }
        elseif($type_encode=='decode' && $type_data){

            $new_array = [];
            foreach($data['data'] as $key => $value) {
                $dots = explode(".", $key);
                foreach ($dots as $dots_key => $dots_value) {
                    $dots[$dots_key] = str_replace('(#_p_#)', '.', $dots_value);
                }

                if(count($dots) > 1) {
                    $last = &$new_array[ $dots[0] ];
                    foreach($dots as $k => $dot) {
                        if($k == 0) continue;
                        $last = &$last[$dot];
                    }
                    $last = $value;
                }
                else {
                    $new_array[$key] = $value;
                }
            }

            $recursive = static function($data) use (&$recursive){
                                
                foreach ($data as $key => $value) {

                    unset($data[$key]);
                    $key = str_replace('(#_p_#)', '.', $key);
                    $key_array = explode('{(', $key);
                    $key = $key_array[0];
                    $key_type = rtrim($key_array[1], ')}');

                    if(is_array($value)){

                        $value = $recursive($value);
                    }

                    $value = ($key_type=='NULL')? NULL : $value;
                    $value = ($key_type=='array')? (array) $value : $value;
                    $value = ($key_type=='integer')? (integer) $value : $value;
                    $value = ($key_type=='object')? (object) $value : $value;
                    $value = ($key_type=='string')? (string) $value : $value;
                    $data[$key] = $value;
                }
                if(array_key_exists('text-function', $data)) $data = $data['text-function']; 

                return $data;
            };
            $return = $recursive($new_array);
            if($system){
                $return = ($data['type']=='object')? (object) $return : $return;
            }
        }
        return $return;
    }

    public function dictionary($data, $system=FALSE, $record_languaje=NULL){

        if(is_array($data) && count((array) $data)==0) return $data;
        if(!is_bool($system)) return $data;
        if(!self::USE_DICTIONARY) return $data;

        $language = $this->language();

        //busco algun idioma compatible con la data envia en el caso que se me envien uno no compatible con la data
        $language = ($record_languaje == NULL) ? $this->validateLanguage($language) : $record_languaje;
        /*
        if(!$system){
            $data = $this->recursiveArrayIterator($data, 'encode', TRUE, TRUE); //ultimo parametro para pasar a  json modelos
        }
        else{
        }*/
        
        $data = $this->recursiveArrayIterator($data, 'encode', TRUE);

        $config = $this->dir('config');

        if (!file_exists("$config/default.json")) {

            $this->writeFile("default.json", json_encode((object)[]));
        }

        //data default donde busco el key a usar
        $default = file_get_contents("$config/default.json");
        $default = (array) json_decode($default);

        //data del diccionario usado para buscar la traduccion 
        $json = file_get_contents("$config/$language.json");
        $json = (array) json_decode($json);


        $data_tmp = $this->filterData($data['data']);

        $arrayA = ($system)? $json : $default;
        $arrayB = ($system)? $default : $json;

        $cont = 0; 
        $cont_bol = FALSE; 
        foreach ($data_tmp as $key => $value) {
                    
            $cont_bol = TRUE; 
            $clave = ($value!=NULL)? array_search($value, $arrayA) : FALSE;
            
            if($clave!==FALSE && array_key_exists($clave,  $arrayB)){

                $traduccion = $arrayB[$clave];
                
                if($traduccion!=NULL && $traduccion!=$value){
                    
                    $data['data'][$key] = $traduccion;
                    $cont++;
                }
            }
        }

        
        $data = $this->recursiveArrayIterator($data, 'decode', TRUE, $system);

        //busco algun idioma compatible con la data envia en el caso que se me envien uno no compatible con la data
        if($system && $cont==0 && $cont_bol){

            $dictionaries = $this->ListFilesJson();

            foreach ($dictionaries as $key => $dictionary) {
                
                if(!in_array($dictionary, $this->data)){

                    $this->data[] = $dictionary;
                    return $this->dictionary($data, $system, $dictionary);
                }
            }
        }
        //esta condicion es mientras soluciono el tema de cuando no  hay data
        $data =(is_object($data) && property_exists($data, '') && count((array)$data)==1) ? [] : $data;
        return $data;
    }

    public function dictionaries(){

        return $this->ListFilesJson();
    }

    public function default(){

        return $this->dictionary_default;
    }

    public function dictionaryRequest($request){

        if(!self::USE_DICTIONARY) return $request;

        $data = $request->all();

        $dictionary = $this->dictionary($data, TRUE);

        $request->replace($dictionary);

        return $request;
    }

    //este metodo debe ser cambiado segun requerimientos de uso de lenguaje por usuario, mercado o estandar
    public function dictionaryDocument($data, $language){

        if(!self::USE_DICTIONARY) return $data;

        $data = $this->dictionary($data, FALSE, $language);

        return $data;
    }
}

 public function index()
    {
        return "Hola mundo\n";
    }

 
}
