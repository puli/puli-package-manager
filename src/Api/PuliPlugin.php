<?php

/*
 * This file is part of the puli/manager package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Manager\Api;

/**
 * A plugin for the repository manager.
 *
 * @since  1.0
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface PuliPlugin
{
    /**
     * Activates the plugin.
     *
     * @param Container $container The Puli service container.
     */
    public function activate(Container $container);
}
