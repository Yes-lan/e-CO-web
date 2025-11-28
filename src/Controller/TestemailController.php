<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

final class TestemailController extends AbstractController
{
    #[Route('/testemail', name: 'app_testemail')]
    public function index(MailerInterface $mailer): Response
    {
        // Crée un mail de test
        $email = (new Email())
            ->from('test@example.com')
            ->to('user@example.com') // Remplace par ton email de test ou celui d'un utilisateur existant
            ->subject('Test Symfony Mailer')
            ->text('Si tu vois ce message dans Mailpit, le mail fonctionne !');

        try {
            $mailer->send($email);
            $message = '✅ Mail envoyé avec succès. Vérifie Mailpit (http://localhost:8025)';
        } catch (\Exception $e) {
            $message = '❌ Erreur lors de l’envoi du mail : ' . $e->getMessage();
        }

        // Affiche le résultat dans la page
        return $this->render('testemail/index.html.twig', [
            'controller_name' => 'TestemailController',
            'message' => $message,
        ]);
    }
}
