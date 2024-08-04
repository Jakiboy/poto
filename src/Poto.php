<?php
/**
 * @author    : Jakiboy
 * @version   : 1.0.0
 * @copyright : (c) 2024 Jihad Sinnaour <mail@jihadsinnaour.com>
 * @link      : https://jakiboy.github.io/poto/
 * @license   : MIT
 */

namespace Jakiboy;

use Jakiboy\Poto\exc\{
    IoException, TranslateException
};
use Jakiboy\Poto\inc\{
    Sorter, Translator
};

class Poto
{
    /**
     * @access public
     * @var array regex
     */
    public const REGEX = [
        'sanitize-break' => '/(\r?\n){2,}/',
        'sanitize-space' => '/\s+/',
        'search-id'      => '/^msgid\s+/',
        'search-editor'  => '/^"Last-Translator:.*"$/m',
        'empty-id'       => '/^msgid\s*(""|)$/',
        'invalid-id'     => '/msgid ""\n((?:".*?"\n)+)msgstr ".*?"/s',
        'plural-id'      => '/^msgid_plural /',
        'missing-str'    => '/^msgstr\s+"(.*)"$/'
    ];

    /**
     * @access private
     * @var array $lines
     * @var array $header
     * @var array $error
     * @var string $content
     * @var string $log
     */
    protected $lines = [];
    protected $header = [];
    protected $error = [];
    protected $content;
    protected $log = 'log.txt';

    /**
     * @access private
     * @var string $file
     * @var bool $hasRepair
     * @var bool $hasEditor
     * @var bool $throw
     * @var bool $sort
     * @var bool $format
     * @var bool $override
     * @var bool $translate
     * @var string $charset
     * @var string $from
     * @var string $to
     */
    private $file;
    private $hasRepair;
    private $hasEditor;
    private $sort;
    private $format;
    private $throw;
    private $translate;
    private $override;
    private $charset;
    private $from;
    private $to;

    /**
     * Init parser args.
     *
     * @param array $args
     */
    public function __construct(array $args = [])
    {
        $args = array_merge([
            'editor'    => true,
            'sort'      => true,
            'format'    => true,
            'repair'    => false,
            'throw'     => false,
            'translate' => false,
            'override'  => false,
            'charset'   => Translator::CHARSET,
            'from'      => Translator::FROM,
            'to'        => Translator::TO
        ], $args);

        // Internal args
        $this->hasRepair = (bool)$args['repair'];
        $this->hasEditor = (bool)$args['editor'];
        $this->throw     = (bool)$args['throw'];
        $this->override  = (bool)$args['override'];

        // External args
        $this->sort      = (bool)$args['sort'];
        $this->format    = (bool)$args['format'];
        $this->translate = (bool)$args['translate'];
        $this->charset   = (string)$args['charset'];
        $this->from      = (string)$args['from'];
        $this->to        = (string)$args['to'];
    }

    /**
     * Set PO content.
     *
     * @access public
     * @param string $content
     * @return object
     */
    public function set(string $content) : self
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Read PO file.
     *
     * @access public
     * @param string $file
     * @return object
     */
    public function read(string $file) : self
    {
        $this->file = str_replace('\\', '/', $file);

        if ( !file_exists($this->file) ) {

            $error = "File not found: {$this->file}";
            if ( $this->throw ) {
                throw new IoException($error);

            } else {
                $this->log($error);
            }

        } elseif ( !is_readable($this->file) ) {

            $error = "File not readable: {$this->file}";
            if ( $this->throw ) {
                throw new IoException($error);

            } else {
                $this->log($error);
            }
        }

        if ( !($this->content = @file_get_contents($this->file)) ) {

            $error = "File empty or invalid: {$this->file}";
            if ( $this->throw ) {
                throw new IoException($error);

            } else {
                $this->log($error);
            }

        }

        return $this;
    }

    /**
     * Process PO file.
     *
     * @access public
     * @return void
     */
    public function process() : void
    {
        if ( !$this->content ) {
            return;
        }

        // Sanitize line breaks
        $this->sanitizeBreak();

        // Set editor
        if ( $this->hasEditor ) {
            $this->setEditor();
        }

        // Repare message Id
        if ( $this->hasRepair ) {
            $this->repairMessageId();
        }

        // Parse lines
        $this->parseLines();

        // Validate file
        if ( !$this->validate() ) {
            if ( $this->throw ) {
                throw new IoException('PO file is invalid');
            }
            $this->log();
            return;
        }

        // Parse header
        $this->parseHeader();

        // Sort PO file
        if ( $this->sort ) {
            $this->lines = (new Sorter(
                $this->lines,
                $this->format
            ))->sort();
        }

        // Translate PO file
        if ( $this->translate ) {

            if ( !$this->format || !$this->sort ) {

                $error = 'Translation requires sort format';
                if ( $this->throw ) {
                    throw new TranslateException($error);
                }
                $this->log($error);
                return;
            }

            $this->lines = (new Translator(
                $this->lines,
                $this->from,
                $this->to,
                $this->charset
            ))->translate();
        }

        $this->write();
    }

    /**
     * Get error status.
     *
     * @access public
     * @return bool
     */
    public function error() : bool
    {
        return (count($this->error) > 0);
    }

    /**
     * Set error.
     *
     * @access public
     * @param string $error
     * @return string
     */
    public function setError(string $error) : string
    {
        $this->error[] = $error;
        return $error;
    }

    /**
     * Get error.
     *
     * @access public
     * @param string $sep
     * @return string
     */
    public function getError(string $sep = "\n") : string
    {
        return (string)implode($sep, $this->error);
    }

    /**
     * Get array error.
     *
     * @access public
     * @return array
     */
    public function getArrayError() : array
    {
        return $this->error;
    }

    /**
     * Sanitize line breaks.
     *
     * @access protected
     * @return void
     */
    protected function sanitizeBreak() : void
    {
        $break = static::REGEX['sanitize-break'];
        $this->content = trim($this->content);
        $this->content = preg_replace($break,  "\n\n", $this->content);
    }

    /**
     * Sanitize line spaces.
     *
     * @access protected
     * @param string $line
     * @return string
     */
    protected function sanitizeSpace(string $line) : string
    {
        $space = static::REGEX['sanitize-space'];
        $line  = trim($line);
        return preg_replace($space, ' ', $line);
    }

    /**
     * Set PO file editor.
     *
     * @access protected
     * @return void
     */
    protected function setEditor() : void
    {
        $editor = static::REGEX['search-editor'];
        $last = '"Last-Translator: Poto <jakiboy.github.io/poto/>\n"';
        $this->content = preg_replace($editor, $last, $this->content);
    }

    /**
     * Repare message Id (msgid).
     *
     * @access protected
     * @return void
     */
    protected function repairMessageId() : void
    {
        $invalid = static::REGEX['invalid-id'];
        $this->content = preg_replace_callback($invalid, function ($matches) {
            $msgid = str_replace("\n", "", $matches[1]);
            $msgid = str_replace('"', '', $msgid);
            return 'msgid "' . $msgid . "\"\nmsgstr \"\"";
        }, $this->content);
    }

    /**
     * Get PO header.
     *
     * @access protected
     * @return array
     */
    protected function getHeader() : array
    {
        $header = [];
        foreach ($this->lines as $line) {
            if ( $line === '' ) {
                break;
            }
            $header[] = $line;
        }
        return $header;
    }

    /**
     * Parse header.
     *
     * @access protected
     * @return void
     */
    protected function parseHeader() : void
    {
        foreach ($this->lines as $key => $line) {
            if ( $line === '' ) {
                break;
            }
            $this->header[] = $line;
            unset($this->lines[$key]);
        }
    }

    /**
     * Parse lines.
     *
     * @access protected
     * @return void
     */
    protected function parseLines() : void
    {
        $this->lines = explode("\n", $this->content);
        $this->lines = array_map(function($line) {
            return $this->sanitizeSpace($line);
        }, $this->lines);
    }

    /**
     * Validate PO file.
     *
     * @access protected
     * @return bool
     */
    protected function validate() : bool
    {
        $num = 0;
        $header = $this->getHeader();

        foreach ($this->lines as $key => $line) {
            $num = $key + 1;

            // Skip header
            if ( isset($header[$key]) ) {
                continue;
            }

            // Skip comments
            if ( strpos($line, '#') === 0 ) {
                continue;
            }

            // Check for empty msgid
            if ( preg_match(static::REGEX['empty-id'], $line) ) {

                if ( isset($this->lines[$key + 1]) ) {

                    // Skip plural id
                    $next = $this->lines[$key + 1];
                    if ( !preg_match(static::REGEX['plural-id'], $next) ) {
                        $error = "Error at line {$num}: 'msgid' is empty";
                        $this->setError($error);
                    }

                }

            }

            // Check for missing msgstr
            if ( preg_match(static::REGEX['search-id'], $line) ) {

                if ( isset($this->lines[$key + 1]) ) {

                    $next = $this->lines[$key + 1];

                    // Skip msgctxt
                    if ( strpos($next, 'msgctxt') !== 0 ) {
                        continue;
                    }

                    // Skip plural id
                    if ( !preg_match(static::REGEX['plural-id'], $next) ) {
                        if ( !preg_match(static::REGEX['missing-str'], $next) ) {
                            $n = $num + 1;
                            $error = "Error at line {$n}: 'msgstr' is missing";
                            $this->setError($error);
                        }
                    }

                }

            }
        }

        return !$this->error();
    }

    /**
     * Write PO file.
     *
     * @access protected
     * @return void
     */
    protected function write() : void
    {
        if ( !$this->override ) {
            $this->file = "processed-{$this->file}";
        }

        if ( !$this->writeFile($this->file) ) {
            if ( $this->throw ) {
                throw new IoException("Unable to write PO file: {$this->file}");
            }
        }

        // Write header
        $header = implode("\n", $this->header);
        $this->writeFile($this->file, "{$header}\n", true);

        // Write lines
        foreach ($this->lines as $line) {
            if ( $line === '' ) {
                $this->writeFile($this->file, "\n", true);
            } else {
                $this->writeFile($this->file, "{$line}\n", true);
            }
        }
    }

    /**
     * Log error.
     *
     * @access protected
     * @return void
     */
    protected function log(?string $error = null) : void
    {
        $error = ($error) ? $this->setError($error) : $this->getError();
        if ( !$this->writeFile($this->log, $error) ) {
            if ( $this->throw ) {
                throw new IoException("Unable to write to log file: {$this->log}");
            }
        }
    }

    /**
     * Write file.
     *
     * @access protected
     * @param string $file
     * @param string $content
     * @param bool $append
     * @return bool
     */
    protected function writeFile(string $file, ?string $content = null, bool $append = false) : bool
    {
        $flags = ($append) ? 8 : 0;
        if ( @file_put_contents($file, $content, $flags) ) {
            return true;
        }
        return false;
    }
}
