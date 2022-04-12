<?php

namespace App\Controller;

use App\Form\ContactType;
use App\Service\SendEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'contact')]
    /* Methode qui prend en parametre le composant request et le service SendMail et qui redirige vers diferentes routes en fonction des cas
    Cette fonction sert contacter les responsables du site
     */
    public function index(Request $request,SendEmail $sendEmail)
    {
        // on cree un formulaire de type ContactForm defini dans Le dossier Form et on le stocke dans une variable contact_form
        $contact_form = $this->createForm(ContactType::class);
        $contact_form->handleRequest($request);

        // si l'utilisateur a appyue sur envoyer, et que les champs sont correctement remplis
        if($contact_form->isSubmitted() && $contact_form->isValid()){
            // on recupere l'email de l'utilisateur dans une variable $email
            $email = $contact_form->get('email')->getData();
            // on recupere le sujet du mail
            $subject = $contact_form->get('subject')->getData();
            // et pour finir le message
            $message = $contact_form->get('message')->getData();
            // en utilisant le service d'envoi de mail, on envoi le mail avec les arguments suivants (l'envoyeur , le destinataire , le sujet,et le message)
            $sendEmail->contact($email,'BecomeChef@admin.com',$subject,$message,'emails/contact.html.twig');
            // on affiche un message de succes
            $this->addFlash('success', 'Le mail à bien été envoyé');
            // et on redirige vers la page d'accueuil
            return $this->redirectToRoute('home');
        }
        // si le formulaire n'est pas encore complete, on renvoie vers la page d'affichage du formulaire
        return $this->renderForm('contact/index.html.twig', [
            'contact' => $contact_form,
        ]);
    }
}
