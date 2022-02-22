<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class SendEmail{
    private $mailer;

    public function __construct( MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public function send(string $from,string $to,string $subject,string $message){
        $email = (new Email())
            ->from($from)
            ->to($to)
            ->subject($subject)
            ->text($message)
            ->html('<p>'.$message.'</p>') ;

        $this->mailer->send($email);
    }
}