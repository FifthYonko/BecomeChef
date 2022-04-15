<?php

namespace App\Service;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

/**
 * Service d'envoi de mail
 */
class SendEmail{
    private $mailer;


    public function __construct( MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * Methode d'envoie de mail
     * Elle prend en parametre 4 chaines de caracteres , $from qui represente l'envoyeur, $to qui represente le destinataire
     * $subject represente l'objet du mail et pour finir $message pour representer le corps du mail
     */
    public function ResetPassword(string $from,string $to,string $subject,string $message ,string $template, string $cancelUrl){
        $email = (new TemplatedEmail())
            ->from($from)
            ->to($to)
            ->subject($subject)
            ->text($message)
            ->htmlTemplate($template)
            ->context([
                'message'=>$message,
                'cancel'=>$cancelUrl,
            ]);
        $this->mailer->send($email);
    }

    public function contact(string $from,string $to,string $subject,string $message ,string $template){
        $email = (new TemplatedEmail())
            ->from($from)
            ->to($to)
            ->subject($subject)
            ->text($message)
            ->htmlTemplate($template)
            ->context([
                'message'=>$message,
            ]);
        $this->mailer->send($email);
    }
}