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
    'url'       => 'http://www.tagesschau.de/newsticker.rdf',
    'minlength' => 3,
    'maxlength' => 6,
]);
// generate a password
echo $gen->generate(), "\n";

// generate a password with custom pattern
echo $gen->generate('wiwsw'), "\n";