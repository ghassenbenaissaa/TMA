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

        $socialHandles = [
            'twitter' => $user->getTwitter() ? $this->extractHandle($user->getTwitter(), 'twitter') : null,
            'facebook' => $user->getFacebook() ? $this->extractHandle($user->getFacebook(), 'facebook') : null,
            'instagram' => $user->getInstagram() ? $this->extractHandle($user->getInstagram(), 'instagram') : null,
        ];

        $sections = $user->getSections();

        $coverImage = null;
        $AboutMeImage = null;
        $AboutMeDescription = null;

        // Parcourir les sections pour trouver l'image de couverture
        foreach ($sections as $section) {
            if ($section->getType() === 'Couverture') {
                $coverImage = $section->getImage();
                break;
            }
        }
        foreach ($sections as $section) {
            if ($section->getType() === 'AboutMe') {
                $AboutMeImage = $section->getImage();
                $AboutMeDescription = $section->getDescription();
                break;
            }
        }

        // Rendre la vue avec les informations de la page
        return $this->render('blog/index.html.twig', [
            'user' => $user,
            'sections' => $sections,
            'coverImage' => $coverImage,
            'AboutMeImage' => $AboutMeImage,
            'AboutMeDescription' => $AboutMeDescription,
            'socialHandles' => $socialHandles,
        ]);
    }
    #[Route('/blog', name: 'app_error404')]
    public function index1(): Response
    {
        return $this->render('blog/error-404.html.twig');
    }
    private function extractHandle(string $url, string $platform): string
    {
        if (empty($url)) {
            return ''; // Retourne une chaîne vide si l'URL est vide
        }


        if ($platform === 'twitter') {
            $parts = explode('/', $url);
            $handle = end($parts);
        } elseif ($platform === 'instagram') {
            if (preg_match('/https?:\/\/(?:www\.)?instagram\.com\/([^\/?]+)/', $url, $matches)) {
                $handle = $matches[1];// Extrait avant le point
            }
        } elseif ($platform === 'facebook') {
            // Enlever les paramètres après le '?'
            if (preg_match('/https?:\/\/(?:www\.)?facebook\.com\/([^\/?]+)/', $url, $matches)) {
            $handle = $matches[1]; // Extrait avant le '?'
            }
            // Si l'identifiant Instagram a un underscore, on le garde tel quel
        }

        return $handle; // Ajoute le '@' devant
    }


}
