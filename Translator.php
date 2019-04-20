<?php

namespace Jcarrizalez\Translator;

//use Jcarrizalez\Translator;

class Translator
{
    /**
     * @var EmailLexer
     */
    private $lexer;

    public function __construct()
    {
        print_r("hola mundo");
    }

    /**
     * @param                 $email
     * @param EmailValidation $emailValidation
     * @return bool
     */
    public function index()
    {
        return "Hola mundo";
    }

 
}
