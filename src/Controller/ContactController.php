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
        $contact_form = $this->createForm(ContactType::class);
        $contact_form->handleRequest($request);

        if($contact_form->isSubmitted() && $contact_form->isValid()){
            $email = $contact_form->get('email')->getData();
            $subject = $contact_form->get('subject')->getData();
            $message = $contact_form->get('message')->getData();
            $sendEmail->contact($email,'becomechef96@gmail.com',$subject,$message,'emails/contact.html.twig');
            $this->addFlash('success', 'Le mail à bien été envoyé');
            return $this->redirectToRoute('home');
        }
        return $this->renderForm('contact/index.html.twig', [
            'contact' => $contact_form,
        ]);
    }
}
