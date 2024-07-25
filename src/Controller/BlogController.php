<?php

namespace App\Controller;

use App\Entity\Pays;
use App\Repository\AventureRepository;
use App\Repository\ImageRepository;
use App\Repository\PodcastRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BlogController extends AbstractController
{
    #[Route('/blog/{slug}', name: 'app_blog')]
    public function index(string $slug, UserRepository $userRepository,ImageRepository $imageRepository,PodcastRepository $podcastRepository, AventureRepository $aventureRepository, SessionInterface $session): Response
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
        $aventuresReq = $aventureRepository->findBy(['IdUser' => $user->getId(), 'recommander' => 1]);
        $aventures = $aventureRepository->findBy(['IdUser' => $user->getId()]);
        $podcasts = $podcastRepository->findBy(['idUser' => $user]);
        $image = $imageRepository->findAll();
        $users = $userRepository->findAll();
        $travelers = [];

        foreach ($users as $useri) {
            $photoProfile = null;

            // Iterate through the user's sections to find the profile photo
            foreach ($useri->getSections() as $section) {
                if ($section->getType() === 'PhotoProfile') {
                    // Check if the image name matches the expected pattern
                    $expectedImageName = $useri->getId() . 'PhotoProfile.jpg';
                    $sectionImage = $section->getImage();
                    if ($expectedImageName === $sectionImage) {
                        $photoProfile = $sectionImage;
                        break;
                    }
                }
            }

            // Store the traveler's data in the array
            $travelers[] = [
                'name' => $useri->getNom() . ' ' . $useri->getPrenom(), // Nom and Prenom with a space between
                'profilePhoto' => $photoProfile
            ];

        }

        $countries = [];
        foreach ($aventures as $adventure) {
            $country = $adventure->getIdPays();
            if ($country && !isset($countries[$country->getId()])) {
                $flagUrl = $this->fetchCountryFlag($country->getNom());
                $countries[$country->getId()] = [
                    'name' => $country->getNom(),
                    'flag' => $flagUrl
                ];
            }
        }

        // Check if any required data is missing and redirect to Under Construction page if needed
        if (empty($coverImage) || empty($AboutMeImage) || empty($AboutMeDescription) || empty($aventures)) {
            $session->set('current_slug', $slug);
            return $this->redirectToRoute('app_under_construction');
        }


        // Rendre la vue avec les informations de la page
        return $this->render('blog/index.html.twig', [
            'user' => $user,
            'sections' => $sections,
            'coverImage' => $coverImage,
            'AboutMeImage' => $AboutMeImage,
            'countries' => $countries,
            'AboutMeDescription' => $AboutMeDescription,
            'podcasts' => $podcasts,
            'adventures' => $aventures,
            'adventuresReq' => $aventuresReq,
            'images' => $image,
            'travelers' => $travelers,
        ]);
    }

    private function fetchCountryFlag(string $countryName): ?string
    {
        $apiUrl = 'https://restcountries.com/v3.1/name/' . urlencode($countryName);
        $response = @file_get_contents($apiUrl);
        if ($response === false) {
            return null; // or a default flag URL
        }

        $countryData = json_decode($response, true);
        if (isset($countryData[0]['flags']['svg'])) {
            return $countryData[0]['flags']['svg'];
        }

        return null; // or a default flag URL
    }


    #[Route('/error', name: 'app_error404')]
    public function index1(): Response
    {
        return $this->render('blog/error-404.html.twig');
    }
    #[Route('/under-construction', name: 'app_under_construction')]
    public function underConstruction(): Response
    {
        return $this->render('blog/under_construction.html.twig');
    }


    #[Route('/blog/{slug}/podcasts', name: 'all_podcasts')]
    public function all_podcasts(): Response
    {
        return $this->render('blog/AllPodcasts.html.twig');
    }

    #[Route('/blog/{slug}/countries', name: 'all_countries')]
    public function all_countries(): Response
    {
        return $this->render('blog/AllCountries.html.twig');
    }

    #[Route('/blog/{slug}/adventures', name: 'all_adventures')]
    public function all_adventures(string $slug,UserRepository $userRepository, AventureRepository $aventureRepository, ImageRepository $imageRepository, PodcastRepository $podcastRepository ): Response
    {
        $user = $userRepository->findOneBy(['pageNom' => $slug]);

        // Si la page n'existe pas, lancer une exception
        if (!$user) {
            return $this->redirectToRoute('app_error404');
        }


        $sections = $user->getSections();

        $coverImage = null;

        // Parcourir les sections pour trouver l'image de couverture
        foreach ($sections as $section) {
            if ($section->getType() === 'Couverture') {
                $coverImage = $section->getImage();
                break;
            }
        }

        $aventuresReq = $aventureRepository->findBy(['IdUser' => $user->getId(), 'recommander' => 1]);
        $aventures = $aventureRepository->findBy(['IdUser' => $user->getId()]);
        $podcasts = $podcastRepository->findBy(['idUser' => $user]);
        $image = $imageRepository->findAll();
        $users = $userRepository->findAll();
        $travelers = [];

        foreach ($users as $useri) {
            $photoProfile = null;

            // Iterate through the user's sections to find the profile photo
            foreach ($useri->getSections() as $section) {
                if ($section->getType() === 'PhotoProfile') {
                    // Check if the image name matches the expected pattern
                    $expectedImageName = $useri->getId() . 'PhotoProfile.jpg';
                    $sectionImage = $section->getImage();
                    if ($expectedImageName === $sectionImage) {
                        $photoProfile = $sectionImage;
                        break;
                    }
                }
            }

            // Store the traveler's data in the array
            $travelers[] = [
                'name' => $useri->getNom() . ' ' . $useri->getPrenom(), // Nom and Prenom with a space between
                'profilePhoto' => $photoProfile
            ];

        }

        $countries = [];
        foreach ($aventures as $adventure) {
            $country = $adventure->getIdPays();
            if ($country && !isset($countries[$country->getId()])) {
                $flagUrl = $this->fetchCountryFlag($country->getNom());
                $countries[$country->getId()] = [
                    'name' => $country->getNom(),
                    'flag' => $flagUrl
                ];
            }
        }

        // Rendre la vue avec les informations de la page
        return $this->render('blog/AllAdventures.html.twig', [
            'user' => $user,
            'sections' => $sections,
            'coverImage' => $coverImage,
            'countries' => $countries,
            'podcasts' => $podcasts,
            'adventures' => $aventures,
            'adventuresReq' => $aventuresReq,
            'images' => $image,
            'travelers' => $travelers,
        ]);
    }

    #[Route('/blog/{slug}/adventure/{id}', name: 'adventure_detail')]
    public function adventure_detail(int $id, UserRepository $userRepository, string $slug, PodcastRepository $podcastRepository, AventureRepository $aventureRepository , ImageRepository $imageRepository): Response
    {
        $user = $userRepository->findOneBy(['pageNom' => $slug]);

        // Si la page n'existe pas, lancer une exception
        if (!$user) {
            return $this->redirectToRoute('app_error404');
        }
        $sections = $user->getSections();

        $coverImage = null;

        // Parcourir les sections pour trouver l'image de couverture
        foreach ($sections as $section) {
            if ($section->getType() === 'Couverture') {
                $coverImage = $section->getImage();
                break;
            }
        }

        $aventuresReq = $aventureRepository->findBy(['IdUser' => $user->getId(), 'recommander' => 1]);
        $aventures = $aventureRepository->findBy(['IdUser' => $user->getId()]);
        $aventureClic = $aventureRepository->find($id); // Change here to find a single adventure
        $podcasts = $podcastRepository->findBy(['idUser' => $user]);
        $images = $imageRepository->findBy(['idAventure' => $id]); // Change variable name to match
        $users = $userRepository->findAll();
        $travelers = [];

        foreach ($users as $useri) {
            $photoProfile = null;

            // Iterate through the user's sections to find the profile photo
            foreach ($useri->getSections() as $section) {
                if ($section->getType() === 'PhotoProfile') {
                    // Check if the image name matches the expected pattern
                    $expectedImageName = $useri->getId() . 'PhotoProfile.jpg';
                    $sectionImage = $section->getImage();
                    if ($expectedImageName === $sectionImage) {
                        $photoProfile = $sectionImage;
                        break;
                    }
                }
            }

            // Store the traveler's data in the array
            $travelers[] = [
                'name' => $useri->getNom() . ' ' . $useri->getPrenom(), // Nom and Prenom with a space between
                'profilePhoto' => $photoProfile
            ];

        }

        // Get country only for the clicked adventure
        $country = null;
        if ($aventureClic) {
            $country = $aventureClic->getIdPays();
        }
        $countries = [];
        if ($country) {
            $flagUrl = $this->fetchCountryFlag($country->getNom());
            $countries[$country->getId()] = [
                'name' => $country->getNom(),
                'flag' => $flagUrl
            ];
        }

        // Extract the video ID from the URL
        $videoUrl = $aventureClic->getVideo();
        preg_match('/(?:https?:\/\/)?(?:www\.)?youtube\.com\/watch\?v=([^\&\?\/]+)/', $videoUrl, $matches);
        $videoId = $matches[1] ?? null;


        return $this->render('blog/Adventure.html.twig', [
            'user' => $user,
            'sections' => $sections,
            'coverImage' => $coverImage,
            'countries' => $countries,
            'podcasts' => $podcasts,
            'adventures' => $aventures,
            'adventureClic' => $aventureClic,
            'adventuresReq' => $aventuresReq,
            'images' => $images,
            'travelers' => $travelers,
            'videoId' => $videoId,
        ]);
    }



}
