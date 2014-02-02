<?php

namespace CvRing\Backlog;

/**
 * Simple file-based cache
 *
 * @author Marco Pivetta <ocramius@gmail.com>
 * @author Kyra D. <kyra@existing.me>
 * @todo redo file so works with new config file setup
 */
class FileCache
{
    /** @var \CvRing\Backlog\ConfigFile */
    private $config;

    /** @param ConfigFile $config */
    public function __construct(ConfigFile $config)
    {
        $this->config = $config;
    }

    public function isFresh()
    {
        //        return (file_exists($this->cacheFilePath)
        //            && ($this->cacheFileExpiration > (time() - filemtime($this->cacheFilePath))));
    }

    public function isExpired()
    {
        //        return ! $this->isFresh();
    }

    public function write()
    {
        //        file_put_contents($this->cacheFilePath, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX);
    }

    public function read()
    {
        //        return json_decode(file_get_contents($this->cacheFilePath));
    }
}
