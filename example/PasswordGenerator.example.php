<?php

declare(strict_types=1);

/**
 * The following examples show the usage if the Password Generator.
 *
 */

include dirname(__DIR__) . '/src/PasswordGenerator.php';

use \Darkv\PhpPasswordGenerator\PasswordGenerator;

// create instance with German word list
$gen = PasswordGenerator::DE();
// generate a password
echo $gen->generate(), "\n";

// reuse the existing wordlist without triggering a new HTTP request
echo $gen->generate(), "\n";


// create instance with English word list
$gen = PasswordGenerator::EN();
// generate a password
echo $gen->generate(), "\n";
echo $gen->generate(), "\n";
echo $gen->generate(), "\n";


// new instance with custom params
$gen = new PasswordGenerator([
    'url'       => 'https://www.tagesschau.de/newsticker.rdf',
    'minLength' => 3,
    'maxLength' => 6,
],false);
// generate a password
echo $gen->generate(), "\n";

// new instance with custom file location
// only retrieve wordlist from internet when needed
$gen = new PasswordGenerator([
    'wordCacheFile'=>'mywords.json',
    'url'       => 'https://www.tagesschau.de/newsticker.rdf',
    'minLength' => 3,
    'maxLength' => 6,
],false);

// use cached location
$gen = PasswordGenerator::CACHED('mywords.json');

// generate a password with custom pattern
echo $gen->generate('wiwsw'), "\n";
