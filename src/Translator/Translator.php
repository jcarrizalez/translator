<?php

namespace Translator;

use Illuminate\Support\Facades\DB;
use Translator\Components;

class Translator{


    public $dictionaries;
    protected $dictionary_default;
    protected $filter_column;
    protected $column_table;
    protected $not_tables;
    protected $data;
    const USE_DICTIONARY = TRUE;
    
    public function __construct(){

        $this->comp = new Components();

        $this->dictionary_default = $this->comp->readConfig('default');

        $this->filter_column = $this->comp->readConfig('filter_column');

        $this->list_files_json = $this->comp->ListFilesJson();

        /* USO 
            use App\Traits\Dictionary;
            $response = $this->dictionary($response);
        */

    }

    public function language($value=NULL){

        $default = $this->dictionary_default;

        $dictionaries = $this->list_files_json;

        if($value==NULL){

            $headers = !function_exists('apache_request_headers')? ['Accept-Language' => $default] : apache_request_headers();

            $language_min = (array_key_exists('accept-language', $headers)) ? $headers['accept-language'] : $default;
            $language = (array_key_exists('Accept-Language', $headers)) ? $headers['Accept-Language'] : $language_min;
        }
        else{
            $language = $value;
        }

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


    public function dictionary($data, $system=FALSE, $record_languaje=NULL){

        if(is_array($data) && count((array) $data)==0) return $data;
        if(!is_bool($system)) return $data;
        if(!self::USE_DICTIONARY) return $data;

        $language = $this->language();

        //busco algun idioma compatible con la data envia en el caso que se me envien uno no compatible con la data
        $language = ($record_languaje == NULL) ? $this->comp->validateLanguage($language) : $record_languaje;
        /*
        if(!$system){
            $data = $this->recursiveArrayIterator($data, 'encode', TRUE, TRUE); //ultimo parametro para pasar a  json modelos
        }
        else{
        }*/
        
        $data = $this->comp->recursiveArrayIterator($data, 'encode', TRUE);

        $config = $this->comp->dir('config');

        
        if (!file_exists("$config/default.json")) {

            $this->comp->writeFile("default.json", json_encode((object)[]));
        }
        

        //data default donde busco el key a usar
        $default = file_get_contents("$config/default.json");
        $default = (array) json_decode($default);

        //data del diccionario usado para buscar la traduccion 
        $json = file_get_contents("$config/$language.json");
        $json = (array) json_decode($json);


        $data_tmp = $this->comp->filterData($data['data']);

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
        
        $data = $this->comp->recursiveArrayIterator($data, 'decode', TRUE, $system);

        //busco algun idioma compatible con la data envia en el caso que se me envien uno no compatible con la data
        if($system && $cont==0 && $cont_bol){

            foreach ($this->list_files_json as $key => $dictionary) {
                
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

        return $this->list_files_json;
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

    public function validateLanguage($language=NULL){

        return $this->comp->validateLanguage($language);
    }
}