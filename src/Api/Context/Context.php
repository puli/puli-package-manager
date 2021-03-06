<?php

/*
 * This file is part of the puli/manager package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Manager\Api\Context;

use Puli\Manager\Api\Config\Config;
use Puli\Manager\Api\Config\ConfigFile;
use Puli\Manager\Assert\Assert;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Webmozart\PathUtil\Path;

/**
 * The Puli context.
 *
 * This class contains context information for Puli. It provides access to
 * Puli's home directory, the global configuration and the global event
 * dispatcher.
 *
 * Use {@link getConfig()} to access the global configuration.
 *
 * @since  1.0
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class Context
{
    /**
     * @var string|null
     */
    private $homeDir;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ConfigFile|null
     */
    private $configFile;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * Creates the context.
     *
     * @param string|null                   $homeDir    The path to the home
     *                                                  directory or `null` if
     *                                                  none exists.
     * @param Config                        $config     The configuration.
     * @param ConfigFile|null               $configFile The configuration file
     *                                                  or `null` if none
     *                                                  exists.
     * @param EventDispatcherInterface|null $dispatcher The event dispatcher.
     */
    public function __construct($homeDir, Config $config, ConfigFile $configFile = null, EventDispatcherInterface $dispatcher = null)
    {
        Assert::nullOrDirectory($homeDir, 'The home directory %s is not a directory.');

        $this->homeDir = $homeDir ? Path::canonicalize($homeDir) : null;
        $this->config = $config;
        $this->dispatcher = $dispatcher ?: new EventDispatcher();
        $this->configFile = $configFile;
    }

    /**
     * Returns the path to the home directory.
     *
     * This method return `null` if no home directory has been set, which
     * happens frequently on web servers. See
     * {@link System::parseHomeDirectory()} for more information.
     *
     * @return string|null The path to the home directory or `null` if none is
     *                     available.
     */
    public function getHomeDirectory()
    {
        return $this->homeDir;
    }

    /**
     * Returns the configuration.
     *
     * @return Config The configuration.
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Returns the configuration file in the home directory.
     *
     * @return ConfigFile|null The configuration file or `null` if no home
     *                         directory was found.
     */
    public function getConfigFile()
    {
        return $this->configFile;
    }

    /**
     * Returns the event dispatcher.
     *
     * @return EventDispatcherInterface The event dispatcher.
     */
    public function getEventDispatcher()
    {
        return $this->dispatcher;
    }
}
