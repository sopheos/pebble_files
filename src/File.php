<?php

namespace Pebble\Files;

class File
{
    private string $filename;
    private $stream = null;

    public function __construct(string $filename)
    {
        $this->filename = $filename;
    }

    public function __destruct()
    {
        $this->close();
    }

    // -------------------------------------------------------------------------

    public static function unlink(string $file)
    {
        if (is_file($file)) {
            unlink($file);
        }
    }

    // -------------------------------------------------------------------------

    /**
     * Get filename
     *
     * @return string
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * Get if file exists
     *
     * @return boolean
     */
    public function isFile(): bool
    {
        return is_file($this->filename);
    }

    public function atime(): int
    {
        return $this->isFile() ? fileatime($this->filename) : 0;
    }

    public function ctime(): int
    {
        return $this->isFile() ? filectime($this->filename) : 0;
    }

    public function mtime(): int
    {
        return $this->isFile() ? filemtime($this->filename) : 0;
    }

    public function stats(): ?array
    {
        return $this->isOpen() ? fstat($this->stream) : null;
    }

    /**
     * Reads entire file into a string
     *
     * @return string|null
     */
    public function getContent(): ?string
    {
        if ($this->isFile()) {
            $this->close();
            $content = file_get_contents($this->filename);
            return $content === false ? null : $content;
        }

        return null;
    }

    /**
     * Reads entire json file and convert the content into an array
     *
     * @return string|null
     */
    public function getJsonContent(): ?array
    {
        if (!($content = $this->getContent())) {
            return null;
        }

        $json = json_decode($content, true);
        return is_array($json) ? $json : null;
    }

    /**
     * Write a string to a file
     *
     * @param string $data
     * @return integer|null
     */
    public function putContent(string $data): ?int
    {
        $this->close();
        $res = file_put_contents($this->filename, $data);
        return $res === false ? null : $res;
    }

    /**
     * Delete file
     *
     * @return static
     */
    public function delete(): static
    {
        $this->close();
        self::unlink($this->filename);

        return $this;
    }

    // -------------------------------------------------------------------------
    // Open & close
    // -------------------------------------------------------------------------

    /**
     * Is file open
     *
     * @return boolean
     */
    public function isOpen(): bool
    {
        return $this->stream ? true : false;
    }

    /**
     * Open file
     *
     * @param string $mode
     * @return boolean
     */
    public function open(string $mode = 'r+'): bool
    {
        $this->stream = fopen($this->filename, $mode) ?: null;
        return $this->isOpen();
    }

    /**
     * Close open file
     *
     * @return boolean
     */
    public function close(): bool
    {
        if ($this->isOpen() && fclose($this->stream)) {
            $this->stream = null;
        }

        return !$this->isOpen();
    }

    /**
     * @return ressource|null
     */
    public function stream()
    {
        return $this->stream;
    }

    // -------------------------------------------------------------------------
    // Reader
    // -------------------------------------------------------------------------

    /**
     * Tests for end-of-file on a file pointer
     *
     * @return bool
     */
    public function eof(): bool
    {
        if (!$this->isOpen()) {
            return true;
        }

        return feof($this->stream);
    }

    /**
     * Gets next line from file
     *
     * @return string|null
     */
    public function getLine(): ?string
    {
        if (!$this->isOpen()) {
            return null;
        }

        $res = fgets($this->stream);
        return $res === false ? null : $res;
    }

    /**
     * Get line from file and parse CSV fields
     *
     * @param string $delimiter
     * @param string $enclosure
     * @param string $escape_char
     * @return array|null
     */
    public function getCSVLine(string $delimiter = ',', string $enclosure = '"', string $escape_char = '\\'): ?array
    {
        if (!$this->isOpen()) {
            return null;
        }

        $res = fgetcsv($this->stream, 0, $delimiter, $enclosure, $escape_char);
        return $res === false ? null : $res;
    }

    // -------------------------------------------------------------------------
    // Writer
    // -------------------------------------------------------------------------

    /**
     * Append a string to a file
     *
     * @param string $text
     * @return integer|null
     */
    public function write(string $text): ?int
    {
        if (!$this->isOpen()) {
            return null;
        }

        $res = fwrite($this->stream, $text);
        return $res === false ? null : $res;
    }

    /**
     * Append a line to a file
     *
     * @param string $text
     * @return integer|null
     */
    public function writeLine(string $text): ?int
    {
        return $this->write($text . "\n");
    }

    /**
     * Format line as CSV and write to file
     *
     * @param array $data
     * @param string $delimiter
     * @param string $enclosure
     * @param string $escape
     * @return integer|null
     */
    public function writeCsv(array $data, string $delimiter = ",", string $enclosure = '"', string $escape = "\\"): ?int
    {
        if (!$this->isOpen()) {
            return null;
        }

        $res = fputcsv($this->stream, $data, $delimiter, $enclosure, $escape);

        return $res === false ? null : $res;
    }

    // -------------------------------------------------------------------------
}
