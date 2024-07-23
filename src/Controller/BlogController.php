<?php

namespace App\Controller;

use App\Repository\AventureRepository;
use App\Repository\ImageRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BlogController extends AbstractController
{
    #[Route('/blog/{slug}', name: 'app_blog')]
    public function index(string $slug, UserRepository $userRepository,ImageRepository $imageRepository, AventureRepository $aventureRepository): Response
    {

        $user = $userRepository->findOneBy(['pageNom' => $slug]);

        // Si la page n'existe pas, lancer une exception
        if (!$user) {
            return $this->redirectToRoute('app_error404');
        }


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
        $aventures = $aventureRepository->findBy(['IdUser' => $user->getId()]);
        $image = $imageRepository->findAll();

        // Rendre la vue avec les informations de la page
        return $this->render('blog/index.html.twig', [
            'user' => $user,
            'sections' => $sections,
            'coverImage' => $coverImage,
            'AboutMeImage' => $AboutMeImage,
            'AboutMeDescription' => $AboutMeDescription,
            'adventures' => $aventures,
            'images' => $image,
        ]);
    }
    #[Route('/blog', name: 'app_error404')]
    public function index1(): Response
    {
        return $this->render('blog/error-404.html.twig');
    }
    #[Route('/under-construction', name: 'app_under_construction')]
    public function underConstruction(): Response
    {
        return $this->render('blog/under_construction.html.twig');
    }

}
