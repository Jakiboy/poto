<?php
/**
 * @author    : Jakiboy
 * @version   : 1.0.0
 * @copyright : (c) 2024 Jihad Sinnaour <mail@jihadsinnaour.com>
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
        ' | ', '|', '"', '%d', '%s', '$d',
        '$s', '$t', '$v', '<', '>',
        '«', '»', '“', '”', '‘', '’'
    ];
    private $max = 32;

    /**
     * Init translator.
     *
     * @param array $lines
     * @param string $from
     * @param string $to
     * @param string $charset
     */
    public function __construct(array $lines = [], string $from = self::FROM, string $to = self::TO, string $charset = self::CHARSET)
    {
        $this->lines   = $lines;
        $this->from    = $from;
        $this->to      = $to;
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
     * @todo
     */
    public function translate() : array
    {
        $sort = Sorter::group($this->lines, true);
        $pre  = $this->preTranslate($sort);

        foreach ($pre as $sub) {
            foreach ($sub as $key => $translate) {
                if ( isset($sort[$key]) && isset($sort[$key]['after']) ) {
                    foreach ($sort[$key]['after'] as $n => $line) {
                        if ( Sorter::isString($line) ) {
                            $msgstr = 'msgstr "' . $translate . '"';
                            $sort[$key]['after'][$n] = $msgstr;
                        }
                    }
                }
            }
        }

        return Sorter::merge($sort);
    }

    /**
     * Group sorted lines.
     *
     * @access private
     * @param array $lines
     * @return array
     */
    private function group(array $lines) : array
    {
        $group   = [];
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
     * Translate strings using API.
     *
     * @access private
     * @param array $sort
     * @return array
     */
    private function preTranslate(array $sort) : array
    {
        $group = $this->group($sort);
        $translate = [];

        foreach ($group as $key => $sub) {
            $values = array_values($sub);
            $values = implode('" | "', $values);
            $values = '"' . $values . '"';
            $translate[$key] = $this->fetch($values);
        }

        foreach ($group as $key => $sub) {
            $temp   = $translate[$key];
            $keys   = array_keys($sub);
            $values = array_values($temp);
            $temp   = array_combine($keys, $values);
            $group[$key] = $temp;
        }

        return $group;
    }

    /**
     * Fetch strings translations from API.
     *
     * @access private
     * @param string $query
     * @return array
     */
    private function fetch(string $query) : array
    {
        sleep(1);

        $group = [];
        $url   = $this->getUrl($query);

        if ( ($response = @file_get_contents($url)) ) {
            $data = json_decode($response, true);
            $data = $data[0] ?? [];
            foreach ($data as $translation) {
                $translation = $translation[0] ?? '';
                $translation = str_replace(
                    $this->exclude, '', $translation
                );
                $translation = trim($translation);
                if ( $translation ) {
                    $group[] = $translation;
                }
            }
        }

        return $group;
    }

    /**
     * Get translation API URL.
     *
     * @access private
     * @param string $query
     * @return string
     */
    private function getUrl(string $query) : string
    {
        $api = self::API;
        $url = $api['url'];
        unset($api['url']);

        $api = str_replace([
            '{from}', '{to}', '{charset}', '{query}'],
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
