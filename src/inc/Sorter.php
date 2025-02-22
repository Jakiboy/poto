<?php
/**
 * @author    : Jakiboy
 * @version   : 0.1.0
 * @copyright : (c) 2025 Jihad Sinnaour <me@jihadsinnaour.com>
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
        $this->lines = $lines;
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
        $groupedLines = self::group($this->lines, $this->format);
        ksort($groupedLines);
        return self::merge($groupedLines);
    }

    /**
     * Merge PO lines.
     *
     * @access public
     * @param array $groupedLines
     * @return array
     */
    public static function merge(array $groupedLines) : array
    {
        $mergedLines = [];
        foreach ($groupedLines as $group) {
            $mergedLines = array_merge(
                $mergedLines,
                $group['before'],
                [$group['msgid']],
                $group['after']
            );
        }
        return $mergedLines;
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
        $groupedLines = [];
        $currentGroup = ['msgid' => '', 'before' => [], 'after' => []];
        $position = 'before';

        foreach ($lines as $line) {
            if ( self::isId($line) ) {
                $msgid = $format ? self::formatId($line) : $line;
                $currentGroup['msgid'] = $msgid;
                $position = 'after';

            } elseif ( self::isPluralString($line) || self::isString($line) ) {
                $formattedLine = $format ? self::formatString($line) : $line;
                $currentGroup['after'][] = $formattedLine;

                if ( self::isString($line) ) {
                    $msgid = self::extractId($currentGroup['msgid']);
                    $groupedLines[$msgid] = $currentGroup;
                    $currentGroup = ['msgid' => '', 'before' => [], 'after' => []];
                    $position = 'before';
                }

            } elseif ( $line === '' ) {
                if ( $position === 'after' ) {
                    $currentGroup['after'][] = $line;
                    $msgid = self::extractId($currentGroup['msgid']);
                    $groupedLines[$msgid] = $currentGroup;
                    $currentGroup = ['msgid' => '', 'before' => [], 'after' => []];
                    $position = 'before';
                } else {
                    $currentGroup['before'][] = $line;
                }

            } else {
                $currentGroup[$position][] = $line;
            }
        }

        return $groupedLines;
    }

    /**
     * Format message Id from line.
     *
     * @access public
     * @param string $line
     * @return string
     */
    public static function formatId(string $line) : string
    {
        $msgid = self::extractId($line);
        return "msgid \"{$msgid}\"";
    }

    /**
     * Format message string from line.
     *
     * @access public
     * @param string $line
     * @return string
     */
    public static function formatString(string $line) : string
    {
        $msgstr = self::extractString($line);
        return "msgstr \"{$msgstr}\"";
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
        return strpos($line, 'msgid "') === 0;
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
        return strpos($line, 'msgstr "') === 0;
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
        $plural = strpos($line, 'msgid_plural "') === 0;
        $msgstr = strpos($line, 'msgstr[') === 0;
        return $plural || $msgstr;
    }
}
