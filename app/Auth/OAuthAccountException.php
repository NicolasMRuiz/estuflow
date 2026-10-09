<?php

namespace App\Auth;

use RuntimeException;

/** Only application-owned, public-safe account messages may use this exception. */
class OAuthAccountException extends RuntimeException {}
