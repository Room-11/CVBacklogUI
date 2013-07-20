<?php

/**
 * Simple file-based cache
 *
 * @author       Marco Pivetta <ocramius@gmail.com>
 * @copyright    2013 © Kyra D. <kyra@existing.me>
 */
class JsonFileCache
{
    private $path;
    private $ttl;
    private $dir;

    /**
     * Constructor.
     *
     * @param string $path name of the file containing the cached information
     * @param int    $ttl time to live (seconds)
     *
     * @throws InvalidArgumentException
     */
    public function __construct($path, $ttl)
    {
        $this->path = (string) $path;
        $this->ttl  = (int) $ttl;
    }

    /**
     * Retrieves whether the cache is fresh
     *
     * @return bool
     */
    public function isFresh()
    {
        return file_exists($this->path) && ($this->ttl > (time() - filemtime($this->path)));
    }

    /**
     * Retrieves whether the cache is expired
     *
     * @return bool
     */
    public function isExpired()
    {
        return ! $this->isFresh();
    }

    /**
     * Writes arbitrary json serializable data into the cache
     *
     * @param array $data
     */
    public function write(array $data)
    {
        $tmpPath = $this->path . uniqid('tmpPath', true);

        file_put_contents($tmpPath, json_encode($data));
        chmod($tmpPath, 0604);
        rename($tmpPath, $this->path);
    }

    /**
     * Reads data from the cache (does not check validity, just blindly reads! Please use {@see isFresh} to check that)
     *
     * @see isFresh
     *
     * @return mixed
     */
    public function read()
    {
        return json_decode(file_get_contents($this->path));
    }
}
