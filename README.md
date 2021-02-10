# php-password-generator

The PHP class PasswordGenerator serves as a password generator to create memorable passwords like the keychain of macOS ≤ 10.14 did.

## Getting Started

### Add via composer

Use composer to install the generator into your project:

```bash
composer require darkv/php-password-generator
```

### Direct Import

Copy the file *PasswordGenerator.php* into your project and include it in your own PHP file(s) with `include 'PasswordGenerator.php';`. Then create an instance either by using the predefined static methods for specific languages or customize it yourself by using the standard constructor.

```php
include 'PasswordGenerator.class.php';

// Import the namespace
use \Darkv\PhpPasswordGenerator\PasswordGenerator;

// create instance with English word list
$gen = PasswordGenerator::EN();

// generate a password
echo $gen->generate();
```

### Prerequisites

This class works with PHP &gt;= 5.4 and needs a working internet connection.

### Password Syntax

The syntax of generated passwords can be defined by a pattern. That pattern consists of control characters that define the construction of the password string. The available control characters are:

* **i**  
  An integer between 1 and 999.
* **s**  
  A punctuation character (ASCII codes 33 to 47).
* **w**  
  A word from the wordlist.

If you don't provide your own pattern the default pattern **wisw** is used. Some examples of generated passwords with that default pattern are:

* Theyre778+Breakthrough
* Reforms13)Translated
* When249*Awards

## Word Lists

The class uses RSS feeds to build a word list from which random words are used for password generation. The class has some predefined configuration for the languages English and German but can be customized too:

```php
include 'PasswordGenerator.php';

use \Darkv\PhpPasswordGenerator\PasswordGenerator;

// create instance with English word list
$gen = PasswordGenerator::EN();

// create instance with German word list
$gen = PasswordGenerator::DE();

// create instance with custom parameters
$gen = new PasswordGenerator([
	'url'       => 'https://www.tagesschau.de/newsticker.rdf',
	'minLength' => 3,
	'maxLength' => 6,
]);
```

The params *minLength* and *maxLength* denote the allowed lengths of the words from the URL source to get into the word list. If a word list has been successfully built that list is saved into the file `wordlist.json`. The next time you create an instance of PasswordGenerator and the URL source cannot be contacted or does not contain any usable words that cached list is loaded instead. If you reuse the very same instance the word list is reused so no further HTTP requests are generated.

```php
include 'PasswordGenerator.php';

use \Darkv\PhpPasswordGenerator\PasswordGenerator;

$gen = PasswordGenerator::EN();

// reuse word list without rebuilding
echo 'Password 1: ', $gen->generate();
echo 'Password 2: ', $gen->generate();
echo 'Password 3: ', $gen->generate();
```

### Caching

If you need to use that class in contexts where you do not have an internet connection you can prebuild a word list and copy the generated `wordlist.json` file into your project. When using the PasswordGenerator you can tell it to only use that cached list and skip the URL source request:

```php
include 'PasswordGenerator.php';

use \Darkv\PhpPasswordGenerator\PasswordGenerator;

$gen = PasswordGenerator::CACHED();

echo $gen->generate();
```

### URL Sources

As source for word lists this class uses a configurable RSS feed. The feed has to be in XML format and contain *description* tags from which the textual content is extracted.

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details.
