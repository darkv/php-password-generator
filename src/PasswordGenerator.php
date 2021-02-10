<?php

declare(strict_types=1);

namespace Darkv\PhpPasswordGenerator;

use DOMDocument;
use Exception;
use InvalidArgumentException;

/**
 * A password generator that generates memorable passwords similar to the
 * macOS keychain. For this it uses a public RSS feed to build up a list of
 * words to be used in passwords.
 *
 * After successfully creating a list of words that list is written to disk
 * in the file <i>wordlist.json</i>. The next time you create an instance
 * of this class and the URL is unavailable that cached version is then used.
 *
 * Consecutive password generations with the very same instance won't
 * recreate the word list but reuse the former one.
 *
 * @example ../example/PasswordGenerator.example.php example Class in action.
 *
 * @author Johann Werner <johann.werner@posteo.de>
 * @version 2.0.1
 * @since 1.0.1
 * @license MIT License
 */
class PasswordGenerator
{
    private string $url;
    private int $minLength;
    private int $maxLength;
    private array $wordList = [];
    private string $wordCacheFile = 'wordlist.json';

    /**
     * Creates an instance of the password generator. You can pass an optional
     * array with values that should override the default values for the keys:
     * <dl>
     *   <dt>url</dt>
     *   <dd>The URL to fetch XML from which is used to create a wordlist from
     *   it's description nodes.</dd>
     *   <dt>minlength</dt>
     *   <dd>The miminum length of characters a word must have.</dd>
     *   <dt>maxlength</dt>
     *   <dd>The maxinum length of characters a word must have.</dd>
     * </dl>
     *
     * @param array $params optional config array
     * @param boolean $fetch true if data from URL should be fetched, false to
     * use only cached wordlist; defaults to true
     *
     * @throws InvalidArgumentException if the URL is not valid
     */
    public function __construct(array $params = [], bool $fetch = true)
    {
        foreach ($params as $key => $value) {
            $this->$key = $value;
        }
        if ($fetch) {
            if (!isset($this->url) || !filter_var($this->url, FILTER_VALIDATE_URL)) {
                throw new InvalidArgumentException(sprintf('Invalid URL: %s', $this->url));
            }
            if ($this->minLength > $this->maxLength) {
                throw new InvalidArgumentException(sprintf('Invalid word lengths: min=%s max=%s', $this->minLength, $this->maxLength));
            }
            $this->populate_wordlist();
        } else {
            $this->read_wordlist();
        }
    }

    /**
     * Populate the wordlist
     */
    private function populate_wordlist()
    {
        $input = $this->get_url_data($this->url);
        $doc = new DOMDocument();
        @$doc->loadXML($input);
        $descriptions = $doc->getElementsByTagName('description');
        $wordlist = array();
        foreach ($descriptions as $description) {
            $text = $description->textContent;
            $words = explode(' ', $text);
            foreach ($words as $word) {
                $cleanword = preg_replace('/[,.;:?!\'"]+/', '', trim($word));
                $wordlength = strlen($cleanword);

                if ($wordlength >= $this->minLength && $wordlength <= $this->maxLength && ctype_alpha($cleanword)) {
                    $wordlist[strtoupper(substr($cleanword, 0, 1)) . strtolower(substr($cleanword, 1))] = 1;
                }
            }
        }
        $this->wordList = array_keys($wordlist);

        if (count($wordlist) > 0) {
            $this->save_wordlist();
        }
    }

    /**
     *
     * @param $url
     * @return bool|string
     */
    private function get_url_data(string $url): bool|string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        $data = curl_exec($ch);
        curl_close($ch);

        return $data;
    }

    /**
     * Saves the wordlist to filesystem.
     */
    private function save_wordlist()
    {
        file_put_contents($this->wordCacheFile, json_encode($this->wordList));
    }

    /**
     * Reads in the wordlist from the filesystem.
     */
    private function read_wordlist()
    {
        if (file_exists($this->wordCacheFile)) {
            $this->wordList = json_decode(file_get_contents($this->wordCacheFile), true);
        }
    }

    /**
     * Creates an instance of password generator that will use German wordlist.
     *
     * @static
     * @return PasswordGenerator configured instance
     */
    public static function DE(): PasswordGenerator
    {
        return new self([
            'url'       => 'https://www.tagesschau.de/newsticker.rdf',
            'minLength' => 8,
            'maxLength' => 15,
        ]);
    }

    /**
     * Creates an instance of password generator that will use English wordlist.
     *
     * @static
     * @return PasswordGenerator configured instance
     */
    public static function EN(): PasswordGenerator
    {
        return new self([
            'url'       => 'https://rss.dw.com/rdf/rss-en-all',
            'minLength' => 4,
            'maxLength' => 12,
        ]);
    }

    /**
     * Creates an instance of password generator that will use the cached wordlist.
     * No HTTP request to the URL source will be made. Be sure that you have an
     * appropriate file <i>wordlist.json</i> present.
     *
     * @static
     * @return PasswordGenerator configured instance that uses cache only
     */
    public static function CACHED(): PasswordGenerator
    {
        return new self([], false);
    }

    /**
     * Generates a password and returns it. You can control the pattern
     * used for generating passwords by passing a custom pattern string
     * to this function. The pattern consists of control characters:
     * <dl>
     *   <dt>i</dt>
     *   <dd>An integer between 1 and 999.</dd>
     *   <dt>s</dt>
     *   <dd>A punctuation character (ASCII codes 33 to 47).</dd>
     *   <dt>w</dt>
     *   <dd>A word from the wordlist.</dd>
     * </dl>
     * The pattern must contain at least one control character and may
     * contain an arbitrary number of them. The default pattern used is
     * 'wisw'.
     *
     * @param string $pattern the password pattern to use; defaults to 'wisw'
     * @return string generated password or null if there is no wordlist
     * @throws InvalidArgumentException if the given pattern is invalid
     * @throws Exception if no wordlist is present
     */
    public function generate(string $pattern = 'wisw'): string
    {
        if (!preg_match('#^[isw]+$#', $pattern)) {
            throw new InvalidArgumentException("Invalid pattern: '$pattern'");
        }
        $listlength = count($this->wordList);
        if ($listlength < 1) {
            $this->read_wordlist();
            $listlength = count($this->wordList);
            if ($listlength < 1) {
                throw new Exception('Missing wordlist.');
            }
        }

        $result = '';
        $chars = str_split($pattern);
        foreach ($chars as $char) {
            switch ($char) {
                case 'i':
                    $result .= $this->random_int(1, 999);
                    break;
                case 's':
                    $result .= chr($this->random_int(33, 47));
                    break;
                case 'w':
                    $r = $this->random_int(0, $listlength - 1);
                    $result .= $this->wordList[$r];
                    break;
            }
        }

        return $result;
    }

    /**
     * Generates a random integer
     * @param int $low
     * @param int $high
     * @return int
     * @throws Exception
     */
    private function random_int(int $low, int $high): int
    {
        if (version_compare(PHP_VERSION, '7.0.0', '<')) {
            return rand($low, $high);
        }
        return random_int($low, $high);
    }

}
