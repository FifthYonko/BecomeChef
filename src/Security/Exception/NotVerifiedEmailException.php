<?php

namespace App\Security\Exception;

use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Throwable;

class NotVerifiedEmailException extends CustomUserMessageAuthenticationException {
    
    public function __construct(
        string $message = 'Ce compte ne semble pas posseder d\'email verifie',
        array $messageData = [],
        int $code = 0,
        Throwable $previous = null
    )
    {
        parent::__construct($message, $messageData,$code,$previous);
    }
}