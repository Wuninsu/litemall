<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Base class for business-rule violations thrown by services.
 *
 * Entry points (Livewire components, controllers, commands) catch these and
 * present the message to the user; they are never a sign of a programming error.
 */
abstract class DomainException extends RuntimeException {}
