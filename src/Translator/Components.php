<?php

namespace Translator;

use Illuminate\Support\Facades\DB;

class Components{
        
    public function __construct(){

        $this->root = $this->root();
    }

    private function root(){
    
        return explode('/vendor/', __FILE__)[0];
    }

    public function dir($key = 'default'){

        $return = [
            'seeds'=> $this->root.'/database/seeds',
            'config'=> $this->root.'/translator',
            'default'=> $this->root
        ];
        return $return[$key];
    }
    
    public function readConfig($key=FALSE){

        $config = file_get_contents($this->root().'/translator/configuration.json');
        $config = json_decode($config, TRUE);
        return ($key) ? $config[$key] : $config;
    }

    public function ListFilesJson(){

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

    public function validateLanguage($language=NULL){

        $default = $this->readConfig('default');

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

    public function filterData($data){

        $filter = $this->readConfig('filter_column');
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

    public  function writeFile($name, $data){

        $config = $this->dir('config');
        $n_file = fopen($config.'/'.$name, "w");
        fwrite($n_file, $data);
        fclose($n_file);
    }
}