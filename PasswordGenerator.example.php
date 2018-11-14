<?php

include 'PasswordGenerator.class.php';


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

?>