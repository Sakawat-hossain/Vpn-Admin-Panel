<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A wg-easy API call failed (server unreachable, wrong password, API error).
 */
class WgEasyException extends RuntimeException
{
}
