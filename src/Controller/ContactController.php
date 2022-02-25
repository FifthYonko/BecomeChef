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
    public function index(Request $request,SendEmail $sendEmail): Response
    {
        $contact_form = $this->createForm(ContactType::class);
        $contact_form->handleRequest($request);

        if($contact_form->isSubmitted() && $contact_form->isValid()){
            $email = $contact_form->get('email')->getData();
            $subject = $contact_form->get('subject')->getData();
            $message = $contact_form->get('message')->getData();

            $sendEmail->send($email,'BecomeChef@admin.com',$subject,$message);
            $this->addFlash('success', 'Le mail a bien ete envoye');
            return $this->redirectToRoute('home');
        }

        return $this->renderForm('contact/index.html.twig', [
            'contact' => $contact_form,
        ]);
    }
}
