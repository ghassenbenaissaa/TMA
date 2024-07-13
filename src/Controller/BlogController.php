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

        $user = $userRepository->findOneBy(['pageNom' => $slug]);

        // Si la page n'existe pas, lancer une exception
        if (!$user) {
            return $this->redirectToRoute('app_error404');
        }

        // Rendre la vue avec les informations de la page
        return $this->render('blog/index.html.twig', [
            'user' => $user,
        ]);
    }
    #[Route('/blog', name: 'app_error404')]
    public function index1(): Response
    {
        return $this->render('blog/error-404.html.twig');
    }

}
