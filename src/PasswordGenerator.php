<?php

declare(strict_types=1);

namespace Darkv\PhpPasswordGenerator;

use DOMDocument;
use Exception;
use InvalidArgumentException;

/**
 * A password generator that generates memorable passwords similar to the
 * keychain of macOS ≤ 10.14.
 * For this, it uses a public RSS feed to build up a list of
 * words to be used in passwords.
 *
 * After successfully creating a list of words, that list is written to disk
 * in the file <i>wordlist.json</i>.
 * The next time you create an instance
 * of this class and the URL is unavailable that cached version is then used.
 *
 * Consecutive password generations with the very same instance won't
 * recreate the word list but reuse the former one.
 *
 * @example ./example/PasswordGenerator.example.php example Class in action.
 *
 * @author Johann Häger <johann.haeger@posteo.de>
 * @version 2.0.3
 * @since 1.0.1
 * @license MIT License
 */
class PasswordGenerator
{
    /** @var string $url Url of word ressource */
    private string $url;

    /** @var int $minLength Minimum length of a password */
    private int $minLength;

    /** @var int $maxLength Maximum length of a password */
    private int $maxLength;

    /** @var array $wordList Cache of the words */
    private array $wordList = [];

    /** @var string $wordCacheFile Filename of the cache file */
    private string $wordCacheFile = 'wordlist.json';

    /** @var int $httpRedirects Number of maximum HTTP redirects the script will follow */
    private int $httpRedirects = 2;

    /** @var bool $verbose */
    private bool $verbose;

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
     *   <dt>wordCacheFile</dt>
     *   <dd>Filename of the cache file.</dd>
     *   <dt>httpRedirects</dt>
     *   <dd>Number of maximum HTTP redirects the script will follow.</dd>
     * </dl>
     *
     * @param array $params optional config array
     * @param boolean $fetch true if data from URL should be fetched, false to
     * prefer cached wordlist; defaults to true
     * @param boolean $verbose true if suppressed error messages should be
     * printed; defaults to false
     *
     * @throws InvalidArgumentException if the URL is not valid
     */
    public function __construct(array $params = [], bool $fetch = true, bool $verbose = false)
    {
        $this->verbose = $verbose;
        set_error_handler(array($this, 'error_handler'));
        foreach ($params as $key => $value) {
            $this->$key = $value;
        }
        if (!$fetch) {
            $this->read_wordlist();
            if (!empty($this->wordList)) {
                return;
            }
        }
        // if cache failed retrieves anyway
        if (!isset($this->url) || !filter_var($this->url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException(sprintf('Invalid URL: %s', $this->url));
        }
        if ($this->minLength > $this->maxLength) {
            throw new InvalidArgumentException(sprintf('Invalid word lengths: min=%s max=%s', $this->minLength, $this->maxLength));
        }
        $this->populate_wordlist();
    }

    /**
     * Reads in the wordlist from the filesystem.
     */
    private function read_wordlist()
    {
        if (file_exists($this->wordCacheFile)) {
            try {
                $this->wordList = json_decode(file_get_contents($this->wordCacheFile), true);
            } catch (Exception $e) {
                $this->warn($e->getMessage());
            }
        }
    }

    /**
     * Populate the wordlist from the URL.
     * @return void
     */
    private function populate_wordlist(): void
    {
        try {
            $input = $this->get_url_data($this->url);

            if (empty($input)) {
                throw new Exception('Empty input. Check URL.');
            }

            $doc = new DOMDocument();
            $doc->loadXML($input);
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
        } catch (Exception $e) {
            $this->warn($e->getMessage());
        }
    }

    /**
     * Fetches data from the given URL.
     * @param string $url
     * @return string
     */
    private function get_url_data(string $url): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

        if ($this->httpRedirects > 0) {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, $this->httpRedirects);
        }

        $data = curl_exec($ch);
        curl_close($ch);

        return is_bool($data) ? '' : $data;
    }

    /**
     * Saves the wordlist to filesystem.
     * @return void
     */
    private function save_wordlist(): void
    {
        try {
            file_put_contents($this->wordCacheFile, json_encode($this->wordList));
        } catch (Exception $e) {
            $this->warn($e->getMessage());
        }
    }

    /**
     * Creates an instance of password generator that will use German wordlist.
     * @param array|null $params optional config array
     * @static
     * @return PasswordGenerator configured instance
     */
    public static function DE(?array $params = []): PasswordGenerator
    {
        return new self([
                'url'       => 'https://www.tagesschau.de/newsticker.rdf',
                'minLength' => 8,
                'maxLength' => 15,
            ] + $params);
    }

    /**
     * Creates an instance of password generator that will use English wordlist.
     * @param array|null $params optional config array
     * @static
     * @return PasswordGenerator configured instance
     */
    public static function EN(?array $params = []): PasswordGenerator
    {
        return new self([
                'url'       => 'https://rss.dw.com/rdf/rss-en-all',
                'minLength' => 4,
                'maxLength' => 12,
            ] + $params);
    }

    /**
     * Creates an instance of password generator that will use the cached wordlist.
     * No HTTP request to the URL source will be made. Be sure that you have an
     * appropriate file <i>wordlist.json</i> present.
     *
     * @static
     * @param string|null $wordCacheFile path of custom cache file to use, defaults to default wordlist.json file
     * @param array|null $params
     * @return PasswordGenerator configured instance
     */
    public static function CACHED(string $wordCacheFile = null, ?array $params = []): PasswordGenerator
    {
        $a = [];
        if (!empty($wordCacheFile)) {
            $a['wordCacheFile'] = $wordCacheFile;
        }
        return new self($a + $params, false);
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
                    $result .= random_int(1, 999);
                    break;
                case 's':
                    $result .= chr(random_int(33, 47));
                    break;
                case 'w':
                    $r = random_int(0, $listlength - 1);
                    $result .= $this->wordList[$r];
                    break;
            }
        }

        return $result;
    }

    /**
     * Handles errors.
     * @param int $error_level
     * @param string $error_message
     * @return bool
     */
    private function error_handler(int $error_level, string $error_message): bool
    {
        if (error_reporting() !== 0) {
            return false;
        }
        $this->warn($error_message);
        return true;
    }

    private function warn(string $error_message): void
    {
        if ($this->verbose) {
            echo 'WARN ', $error_message, "\n";
        }
    }
}
