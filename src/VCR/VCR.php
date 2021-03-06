<?php

namespace VCR;

/**
 * Factory.
 */
class VCR
{
    public static $isOn = false;
    protected static $instance;
    protected $cassette;
    protected $httpClient;
    protected $config;
    protected $libraryHooks = array();

    public function __construct($config = null)
    {
        $this->config = $config ?: new Configuration;

        if ($this->config->getTurnOnAutomatically()) {
            $this->turnOn();
        }
    }

    /**
     * Initializes VCR and all it's dependencies.
     * @return void
     */
    public function turnOn()
    {
        if (self::$isOn) {
            return;
        }

        $this->libraryHooks = $this->createLibraryHooks();
        $this->enableLibraryHooks();
        $this->httpClient = $this->createHttpClient();

        self::$isOn = true;
    }

    /**
     * Shuts down VCR and all it's dependencies.
     * @return void
     */
    public function turnOff()
    {
        $this->disableLibraryHooks();
        $this->ejectCassette();

        self::$isOn = false;
    }

    public function ejectCassette()
    {
        unset($this->cassette);
    }

    public function insertCassette($cassetteName)
    {
        // todo check if there is already a cassette
        $filePath = $this->config->getCassettePath() . DIRECTORY_SEPARATOR . $cassetteName;
        $storage = $this->createStorage($filePath);

        $this->cassette = new Cassette($cassetteName, $this->config, $storage);
    }

    public function getConfiguration()
    {
        return $this->config;
    }

    public function getCurrentCassette()
    {
        return $this->cassette;
    }

    public function handleRequest($request)
    {
        if ($this->getCurrentCassette() === null) {
            throw new \BadMethodCallException(
                'Invalid http request. No cassette inserted. '
                . ' Please make sure to insert a cassette in your unit-test using '
                . '$vcr->urlCassette(\'name\'); or annotation @vcr:cassette(\'name\').'
            );
        }

        $cassette = $this->getCurrentCassette();

        if (!$cassette->hasResponse($request)) {
            $this->disableLibraryHooks();
            $response = $this->httpClient->send($request);
            $cassette->record($request, $response);
            $this->enableLibraryHooks();
        }

        return $cassette->playback($request);
    }

    public function createHttpClient()
    {
        return new Client();
    }

    public static function init()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance->getConfiguration();
    }

    public static function useCassette($cassetteName)
    {
        if (is_null(self::$instance)) {
            throw new \BadMethodCallException('VCR is not initialized, please call VCR::init() in a setup method.');
        }

        return self::$instance->insertCassette($cassetteName);
    }

    public static function eject()
    {
        if (is_null(self::$instance)) {
            throw new \BadMethodCallException('VCR is not initialized, please call VCR::init() in a setup method.');
        }

        return self::$instance->ejectCassette();
    }

    public static function getInstance()
    {
        return self::$instance;
    }

    protected function createStorage($filePath)
    {
        $class = $this->config->getStorage();
        return new $class($filePath);
    }

    /**
     * Factory method which returns all configured library hooks.
     * @return array Library hooks.
     */
    protected function createLibraryHooks()
    {
        $hooks = array();
        $self = $this;
        foreach ($this->config->getLibraryHooks() as $hookName) {
            $hooks[] = new $hookName(function(Request $request) use($self) {
                return $self->handleRequest($request);
            });
        }
        return $hooks;
    }

    protected function disableLibraryHooks()
    {
        foreach ($this->libraryHooks as $hook) {
            $hook->disable();
        }
    }

    protected function enableLibraryHooks()
    {
        foreach ($this->libraryHooks as $hook) {
            $hook->enable();
        }
    }

    /**
     * Turns off VCR.
     */
    public function __destruct()
    {
        if (self::$isOn) {
            $this->turnOff();
        }
    }
}

