<?php

declare(strict_types=1);

namespace Jpereira\DhcpdLeases;

use RuntimeException;

/** Thrown when the leases file is missing or unreadable. */
final class LeaseFileException extends RuntimeException
{
}
