<?php
/**
 * @author    : Jakiboy
 * @version   : 1.0.0
 * @copyright : (c) 2024 Jihad Sinnaour <mail@jihadsinnaour.com>
 * @link      : https://jakiboy.github.io/poto/
 * @license   : MIT
 */

namespace Jakiboy\Poto\inc;

final class Sorter
{
    /**
     * @access private
     * @var array $lines
     * @var array $sort
     */
    private $lines = [];
    private $format = false;

    /**
     * Init sort.
     *
     * @param array $lines
     * @param bool $format
     */
    public function __construct(array $lines = [], bool $format = true)
    {
        $this->lines  = $lines;
        $this->format = $format;
    }

    /**
     * Sort PO lines.
     *
     * @access public
     * @return array
     */
    public function sort() : array
    {
        $group = self::group($this->lines, $this->format);
        ksort($group);
        return self::merge($group);
    }

    /**
     * Merge PO lines.
     *
     * @access public
     * @param array $group
     * @return array
     */
    public static function merge(array $group) : array
    {
        $lines = [];
        foreach ($group as $part) {
            $lines = array_merge(
                $lines,
                $part['before'],
                [$part['msgid']],
                $part['after']
            );
        }
        return $lines;
    }

    /**
     * Group PO lines.
     *
     * @access public
     * @param array $lines
     * @param bool $format
     * @return array
     */
    public static function group(array $lines, bool $format = true) : array
    {
        $group   = [];
        $pointer = ['msgid' => '', 'before' => [], 'after' => []];
        $pos     = 'before';

        foreach ($lines as $line) {
            if ( self::isId($line) ) {
                $msgid = $line;
                if ( $format ) {
                    $msgid = self::extractId($line);
                    $msgid = "msgid \"{$msgid}\"";
                }
                $pointer['msgid'] = $msgid;
                $pos = 'after';

            } elseif ( self::isPluralString($line) ) {
                $pointer['after'][] = $line;

            } elseif ( self::isString($line) ) {
                $msgstr = $line;
                if ( $format ) {
                    $msgstr = self::extractString($msgstr);
                    $msgstr = "msgstr \"{$msgstr}\"";
                }
                $pointer['after'][] = $msgstr;

                $msgid = self::extractId($pointer['msgid']);
                $group[$msgid] = $pointer;
                $pointer = ['msgid' => '', 'before' => [], 'after' => []];
                $pos = 'before';

            } elseif ( $line === '' ) {

                if ( $pos === 'after' ) {
                    $pointer['after'][] = $line;
                    $msgid = self::extractId($pointer['msgid']);
                    $group[$msgid] = $pointer;
                    $pointer = ['msgid' => '', 'before' => [], 'after' => []];
                    $pos = 'before';

                } else {
                    $pointer['before'][] = $line;
                }

            } else {
                $pointer[$pos][] = $line;
            }
        }

        return $group;
    }

    /**
     * Extract message Id from line.
     *
     * @access public
     * @param string $line
     * @return string
     */
    public static function extractId(string $line) : string
    {
        preg_match('/msgid "(.*)"/', $line, $matches);
        $id = $matches[1] ?? $line;
        return preg_replace('/\s+/', ' ', trim($id));
    }

    /**
     * Extract message string from line.
     *
     * @access public
     * @param string $line
     * @return string
     */
    public static function extractString(string $line) : string
    {
        preg_match('/msgstr "(.*)"/', $line, $matches);
        $id = $matches[1] ?? $line;
        return preg_replace('/\s+/', ' ', trim($id));
    }

    /**
     * Check message Id in line.
     *
     * @access public
     * @param string $line
     * @return bool
     */
    public static function isId(string $line) : bool
    {
        return (strpos($line, 'msgid "') === 0);
    }

    /**
     * Check message string in line.
     *
     * @access public
     * @param string $line
     * @return bool
     */
    public static function isString(string $line) : bool
    {
        return (strpos($line, 'msgstr "') === 0);
    }

    /**
     * Check plural message string in line.
     *
     * @access public
     * @param string $line
     * @return bool
     */
    public static function isPluralString(string $line) : bool
    {
        $plural = (strpos($line, 'msgid_plural "') === 0);
        $msgstr = (strpos($line, 'msgstr[') === 0);
        return ($plural || $msgstr);
    }
}
