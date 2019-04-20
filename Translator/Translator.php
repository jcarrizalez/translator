<?php

namespace Jcarrizalez\Translator;

class Translator
{
    /**
     * @var EmailLexer
     */
    private $lexer;

    public function __construct()
    {
        print_r("hola mundo >>>>>>>>>>>\n");
        print_r("\n");
    }

    /**
     * @param                 $email
     * @param EmailValidation $emailValidation
     * @return bool
     */
    public function index()
    {
        return "Hola mundo\n";
    }

 
}
