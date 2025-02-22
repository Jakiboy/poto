<?php
/**
 * @author    : Jakiboy
 * @version   : 0.1.0
 * @copyright : (c) 2025 Jihad Sinnaour <me@jihadsinnaour.com>
 * @link      : https://jakiboy.github.io/poto/
 * @license   : MIT
 */

namespace Jakiboy\Poto\inc;

final class Translator
{
    /**
     * @access public
     * @var string CHARSET
     * @var string FROM
     * @var string TO
     * @var array API
     */
    public const CHARSET = 'UTF-8';
    public const FROM    = 'en-US';
    public const TO      = 'fr-FR';
    public const API     = [
        'url'    => 'https://translate.googleapis.com/translate_a/single',
        'client' => 'gtx',
        'dt'     => 't',
        'ie'     => '{charset}',
        'oe'     => '{charset}',
        'sl'     => '{from}',
        'tl'     => '{to}',
        'q'      => '{query}'
    ];

    /**
     * @access private
     * @var array $lines
     * @var string $from
     * @var string $to
     * @var string $charset
     * @var array $exclude
     * @var int $max
     */
    private $lines = [];
    private $from;
    private $to;
    private $charset;
    private $exclude = [
        ' | ',
        '|',
        '"',
        '%d',
        '%s',
        '$d',
        '$s',
        '$t',
        '$v',
        '<',
        '>',
        '«',
        '»',
        '“',
        '”',
        '‘',
        '’'
    ];
    private $max = 32;

    /**
     * Initialize the translator.
     *
     * @param array $lines
     * @param string $from
     * @param string $to
     * @param string $charset
     */
    public function __construct(
        array $lines = [],
        string $from = self::FROM,
        string $to = self::TO,
        string $charset = self::CHARSET
    ) {
        $this->lines = $lines;
        $this->from = $from;
        $this->to = $to;
        $this->charset = $charset;
    }

    /**
     * Set max string length to translate.
     *
     * @access public
     * @param int $max
     * @return object
     */
    public function setMax(int $max) : self
    {
        $max = ($max <= 64) ? $max : 64;
        $this->max = $max;
        return $this;
    }

    /**
     * Set strings (msgid) to exclude from translation.
     *
     * @access public
     * @param array $exclude
     * @return object
     */
    public function setExclude(array $exclude) : self
    {
        $this->exclude = $exclude;
        return $this;
    }

    /**
     * Translate PO lines.
     *
     * @access public
     * @return array
     */
    public function translate() : array
    {
        $sortedLines = Sorter::group($this->lines, true);
        $preTranslated = $this->preTranslate($sortedLines);

        foreach ($preTranslated as $sub) {
            foreach ($sub as $key => $translation) {
                if ( isset($sortedLines[$key]) && isset($sortedLines[$key]['after']) ) {
                    foreach ($sortedLines[$key]['after'] as $n => $line) {
                        if ( Sorter::isString($line) ) {
                            $msgstr = 'msgstr "' . $translation . '"';
                            $sortedLines[$key]['after'][$n] = $msgstr;
                        }
                    }
                }
            }
        }

        return Sorter::merge($sortedLines);
    }

    /**
     * Group sorted lines for translation.
     *
     * @access private
     * @param array $lines
     * @return array
     */
    private function groupLines(array $lines) : array
    {
        $group = [];
        $pointer = [];
        $counter = 0;

        foreach ($lines as $key => $part) {

            // Parse Id
            $msgid = Sorter::extractId($part['msgid']);

            // Skip long
            if ( strlen($msgid) > $this->max ) {
                continue;
            }

            // Exclude strings
            if ( $this->isExcluded($msgid) ) {
                continue;
            }

            if ( !isset($part['after']) ) {
                continue;
            }

            // Set string to translate
            foreach ($part['after'] as $line) {

                if ( !Sorter::isPluralString($line) ) {

                    // Set string to translate
                    $msgstr = Sorter::extractString($line);
                    if ( empty($msgstr) ) {

                        $pointer[$key] = strip_tags($msgid);
                        $counter++;

                        if ( $counter == 10 ) {
                            $group[] = $pointer;
                            $pointer = [];
                            $counter = 0;
                        }
                    }
                }
            }
        }

        // Add any remaining translations
        if ( !empty($pointer) ) {
            $group[] = $pointer;
        }

        return $group;
    }

    /**
     * Translate strings using the API.
     *
     * @access private
     * @param array $sortedLines
     * @return array
     */
    private function preTranslate(array $sortedLines) : array
    {
        $groupedLines = $this->groupLines($sortedLines);
        $translations = [];

        foreach ($groupedLines as $key => $sub) {
            $values = implode('" | "', array_values($sub));
            $translations[$key] = $this->fetchTranslations('"' . $values . '"');
        }

        foreach ($groupedLines as $key => $sub) {
            $temp = $translations[$key];
            $keys = array_keys($sub);
            $groupedLines[$key] = array_combine($keys, $temp);
        }

        return $groupedLines;
    }

    /**
     * Fetch translations from the API.
     *
     * @access private
     * @param string $query
     * @return array
     */
    private function fetchTranslations(string $query) : array
    {
        sleep(1);

        $translations = [];
        $url = $this->buildApiUrl($query);

        if ( $response = @file_get_contents($url) ) {
            $data = json_decode($response, true)[0] ?? [];
            foreach ($data as $translation) {
                $translation = str_replace($this->exclude, '', $translation[0] ?? '');
                if ( $translation = trim($translation) ) {
                    $translations[] = $translation;
                }
            }
        }

        return $translations;
    }

    /**
     * Build the API URL for translation.
     *
     * @access private
     * @param string $query
     * @return string
     */
    private function buildApiUrl(string $query) : string
    {
        $api = self::API;
        $url = $api['url'];
        unset($api['url']);

        $api = str_replace(
            [
                '{from}',
                '{to}',
                '{charset}',
                '{query}'
            ],
            [$this->from, $this->to, $this->charset, $query],
            $api
        );

        $params = http_build_query($api);
        return "{$url}?{$params}";
    }

    /**
     * Check if string is excluded.
     *
     * @access private
     * @param string $msgid
     * @return bool
     */
    private function isExcluded(string $msgid) : bool
    {
        foreach ($this->exclude as $exclude) {
            if ( strpos($msgid, $exclude) !== false ) {
                return true;
            }
        }
        return false;
    }
}
