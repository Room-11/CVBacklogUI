<?php

namespace CvRing\Backlog;

/**
 * Parses and validates specified config file(s), and returns individual options
 *
 * @author Kyra D. <kyra@existing.me>
 * @todo throws exceptions, but we don't do anything with them...yet
 */
class ConfigFile
{
    /** @var array */
    private $config = [];

    /** @var array */
    private $file_paths;

    /** @param array|string $filePaths */
    public function __construct($filePaths)
    {
        $this->file_paths = (array)$filePaths;
        $this->parseConfigFiles();
    }

    /** @return string */
    public function getApiRequestKey()
    {
        $this->verifyOption('api.request_key');
        return $this->config['api.request_key'];
    }

    /**
     * @return string
     * @throws \UnexpectedValueException
     */
    public function getApiSourceTopicTags()
    {
        $this->verifyOption('sources.api_tags');
        $topicTags = $this->config['sources.api_tags'];

        if (!preg_match('/^[a-z\d+#.-]+(?:;[a-z\d+#.-]+)*$/', $topicTags)) {
            throw new \UnexpectedValueException("'sources.api_tags' contains invalid chars, '$topicTags' provided");
        }

        $count = substr_count($topicTags, ';') + 1;

        if (5 < $count) {
            throw new \UnexpectedValueException("'sources.api_tags' more than max of 5 tags, '$count' tags provided");
        }

        return $topicTags;
    }

    /**
     * {@internal https://api.stackexchange.com/docs/sites#pagesize=500&filter=!21u_BTgHIaNnJmchKupo&run=true }}
     * @return string
     * @throws \UnexpectedValueException
     */
    public function getApiStackDomain()
    {
        $this->verifyOption('api.stack_domain');
        $stackDomain = str_replace('meta.', '', $this->config['api.stack_domain']);

        static $validStackDomains = [
            'askubuntu.com',
            'mathoverflow.net',
            'pt.stackoverflow.com',
            'serverfault.com',
            'stackapps.com',
            'stackoverflow.com',
            'superuser.com'
        ];

        if (in_array($stackDomain, $validStackDomains, true) || ('.stackexchange.com' === strstr($stackDomain, '.'))) {
            return $stackDomain;
        }

        throw new \UnexpectedValueException("'api.stack_domain' is not a valid domain, '$stackDomain' provided");
    }

    /**
     * @return string
     * @throws \RuntimeException
     */
    public function getCachePath()
    {
        $this->verifyOption('cache.path');
        $cachePath = __DIR__ . '/../../../' . $this->config['cache.path'];

        if (!file_exists($cachePath)) {
            throw new \RuntimeException("'cache.path' value of '$cachePath' does not exist");
        }

        if (!is_writable($cachePath)) {
            throw new \RuntimeException("'cache.path' value of '$cachePath' is not writable");
        }

        return realpath($cachePath);
    }

    /**
     * @return string
     * @throws \UnexpectedValueException
     */
    public function getChatSourceDomain()
    {
        $this->verifyOption('sources.chat_domain');
        $chatDomain = $this->config['sources.chat_domain'];

        static $validChatDomains = [
            'chat.meta.stackoverflow.com',
            'chat.stackexchange.com',
            'chat.stackoverflow.com'
        ];

        if (in_array($chatDomain, $validChatDomains, true)) {
            return $chatDomain;
        }

        throw new \UnexpectedValueException("'sources.chat_domain' is not a chat domain, '$chatDomain' provided");
    }

    /**
     * @return int
     * @throws \UnexpectedValueException
     */
    public function getChatSourceRoomId()
    {
        $this->verifyOption('sources.chat_room_id');
        $chatRoomId = $this->config['sources.chat_room_id'];

        if (ctype_digit($chatRoomId) && ('0' !== $chatRoomId)) {
            return (int)$chatRoomId;
        }

        throw new \UnexpectedValueException("'sources.chat_room_id' must be an integer, '$chatRoomId' provided");
    }

    /**
     * @param string $filterName
     * @return string
     */
    public function getFilter($filterName)
    {
        $this->verifyOption("api.filters.$filterName");
        return $this->config["api.filters.$filterName"];
    }

    /**
     * @param string $source
     * @param string $type
     * @return int
     * @throws \UnexpectedValueException
     */
    public function getSourceCacheTtl($source, $type)
    {
        $option = "cache.{$source}_{$type}_ttl";
        $this->verifyOption($option);
        $cacheTtl = $this->config[$option];

        if (ctype_digit($cacheTtl)) {
            return (int)$cacheTtl;
        }

        throw new \UnexpectedValueException("'$option' must be an integer, '$cacheTtl' provided");
    }

    /**
     * @param string $source
     * @return int
     * @throws \UnexpectedValueException
     */
    public function getSourceMaxItems($source)
    {
        $option = "sources.{$source}_max_items";
        $this->verifyOption($option);
        $maxItems = $this->config[$option];

        if (ctype_digit($maxItems)) {
            return (int)$maxItems;
        }

        throw new \UnexpectedValueException("'$option' must be an integer, '$maxItems' provided");
    }

    /**
     * @param string $source
     * @return string
     */
    public function getSourceTitle($source)
    {
        $option = "sources.{$source}_title";
        $this->verifyOption($option);
        return $this->config[$option];
    }

    /**
     * @param string $configOption
     * @throws \UnexpectedValueException
     */
    private function verifyOption($configOption)
    {
        if (!array_key_exists($configOption, $this->config) || ('' === $this->config[$configOption])) {
            throw new \UnexpectedValueException("config option '$configOption' has not been set, or is blank");
        }
    }

    /**
     * @param string $filePath
     * @return string
     * @throws \RuntimeException
     */
    private function loadConfigFile($filePath)
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("config file '$filePath' does not exist");
        }

        if (!is_readable($filePath)) {
            throw new \RuntimeException("config file '$filePath' is not readable");
        }

        return file_get_contents($filePath);
    }

    /**
     * @return array
     * @throws \RuntimeException
     */
    private function parseConfigFiles()
    {
        foreach ($this->file_paths as $filePath) {

            $realPath = realpath($filePath);

            if (false === ($config = parse_ini_string($this->loadConfigFile($realPath)))) {
                throw new \RuntimeException("config file '$filePath' failed to parse via path '$realPath'");
            }
            $this->config = array_merge($this->config, $config);
        }
    }
}
