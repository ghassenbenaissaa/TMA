<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BlogController extends AbstractController
{
    #[Route('/blog/{slug}', name: 'app_blog')]
    public function index(string $slug, UserRepository $userRepository): Response
    {
        // Chercher la page par son slug dans la base de données
        $user = $userRepository->findOneBy(['pageNom' => $slug]);

        // Si la page n'existe pas, lancer une exception
        if (!$user) {
            throw $this->createNotFoundException('Page not found');
        }

        // Rendre la vue avec les informations de la page
        return $this->render('blog/index.html.twig', [
            'user' => $user,
        ]);
    }
}
