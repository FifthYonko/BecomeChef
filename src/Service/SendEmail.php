<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

/**
 * Service d'envoi de mail
 */
class SendEmail{
    // attribut qui contiendra un objet de type Mailer
    private $mailer;


    public function __construct( MailerInterface $mailer)
    {
        // on attribue l'objet mailer a l'attribut de la classe
        $this->mailer = $mailer;
    }

    /**
     * Methode d'envoie de mail
     * Elle prend en parametre 4 chaines de caracteres , $from qui represente l'envoyeur, $to qui represente le destinataire
     * $subject represente l'objet du mail et pour finir $message pour representer le corps du mail
     */
    public function send(string $from,string $to,string $subject,string $message){
        // on instancie un objet de type Email
        $email = (new Email())
        // on defini remplis les champs avec valeurs recues en argument
            ->from($from)
            ->to($to)
            ->subject($subject)
            ->text($message)
            ->html('<p>'.$message.'</p>') ;
        // on envoie le mail grace au mailer
        $this->mailer->send($email);
    }
}