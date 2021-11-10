<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Exception;

use Spiral\Database\Exception\ConfigException as SpiralConfigException;

class ConfigException extends DBALException
{
}
\class_alias(ConfigException::class, SpiralConfigException::class, false);
