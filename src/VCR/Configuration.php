<?php

namespace VCR;

/**
 * Configuration.
 */
class Configuration
{
    private $cassettePath = 'tests/fixtures';

    private $enabledLibraryHooks;
    private $availableLibraryHooks = array(
        '\VCR\LibraryHooks\StreamWrapper',
        '\VCR\LibraryHooks\Curl',
        // '\VCR\LibraryHooks\Soap',
    );

    private $enabledStorage = 'yaml';
    private $availableStorages = array(
        'json' => '\VCR\Storage\Json',
        'yaml' => '\VCR\Storage\Yaml',
    );

    private $enabledRequestMatchers;
    private $availableRequestMatchers = array(
        'method'      => array('\VCR\RequestMatcher', 'matchMethod'),
        'url'         => array('\VCR\RequestMatcher', 'matchUrl'),
        'host'        => array('\VCR\RequestMatcher', 'matchHost'),
        'headers'     => array('\VCR\RequestMatcher', 'matchHeaders'),
        'body'        => array('\VCR\RequestMatcher', 'matchBody'),
        'post_fields' => array('\VCR\RequestMatcher', 'matchPostFields'),
    );

    private $turnOnAutomatically = true;

    public function getTurnOnAutomatically()
    {
        return $this->turnOnAutomatically;
    }

    public function getCassettePath()
    {
        return $this->cassettePath;
    }

    public function getLibraryHooks()
    {
        if (is_null($this->enabledLibraryHooks)) {
            return array_values($this->availableLibraryHooks);
        }

        return array_values(array_intersect_key(
            $this->availableLibraryHooks,
            array_flip($this->enabledLibraryHooks)
        ));
    }

    public function getStorage()
    {
        return $this->availableStorages[$this->enabledStorage];
    }

    public function getRequestMatchers()
    {
        if (is_null($this->enabledRequestMatchers)) {
            return array_values($this->availableRequestMatchers);
        }

        return array_values(array_intersect_key(
            $this->availableRequestMatchers,
            array_flip($this->enabledRequestMatchers)
        ));
    }

    public function addRequestMatcher($name, $callback)
    {
        Assertion::minLength($name, 1, "A request matchers name must be at least one character long. Found '{$name}'");
        Assertion::isCallable($callback, "Request matcher '{$name}' is not callable.");
        $this->availableRequestMatchers[$name] = $callback;
        return $this;
    }

    public function enableRequestMatchers(array $matchers)
    {
        $invalidMatchers = array_diff($matchers, array_keys($this->availableRequestMatchers));
        if ($invalidMatchers) {
            throw new \InvalidArgumentException('Request matches do not exist: ' . join(', ', $invalidMatchers));
        }
        $this->enabledRequestMatchers = $matchers;
    }

    public function setCassettePath($cassettePath)
    {
        Assertion::directory($cassettePath, "Cassette path '{$cassettePath}' does not exist.");
        $this->cassettePath = $cassettePath;
        return $this;
    }

    public function setStorage($storageName)
    {
        Assertion::inArray($storageName, $this->availableStorages, "Storage '{$storageName}' not found.");
        $this->enabledStorage = $storageName;
        return $this;
    }
}
