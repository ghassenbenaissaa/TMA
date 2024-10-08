<?php

namespace App\Controller;

use App\Entity\AddFriend;
use App\Entity\Pays;
use App\Repository\AventureRepository;
use App\Repository\ImageRepository;
use App\Repository\PodcastRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BlogController extends AbstractController
{
    #[Route('/Blog/{slug}/{_locale}', name: 'app_blog' , defaults: ['_locale' => 'en'])]
    public function index(string $slug, UserRepository $userRepository, EntityManagerInterface $entityManager, ImageRepository $imageRepository,PodcastRepository $podcastRepository, AventureRepository $aventureRepository, SessionInterface $session): Response
    {
        $currentUser = $session->get('user');
        $langue = $session->get('_locale');
        $session->set('slug', $slug);
        $url = 'app_blog';

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
        if ($currentUser) {
            if ($currentUser === $user->getId()) {
                $aventuresReq = $aventureRepository->findBy([
                    'IdUser' => $user->getId(),
                    'recommander' => 1
                ]);
                $aventures = $aventureRepository->findBy([
                    'IdUser' => $user->getId()
                ]);
            } else {
                // Vérifiez les amitiés confirmées
                $confirmedFriendships = $entityManager->getRepository(AddFriend::class)->findBy([
                        'user_id2' => $currentUser,
                        'User_id' => $user->getId(),
                        'confirmation' => 1
                    ]) + $entityManager->getRepository(AddFriend::class)->findBy([
                        'User_id' => $currentUser,
                        'user_id2' => $user->getId(),
                        'confirmation' => 1
                    ]);

                if ($confirmedFriendships) {
                    $aventuresReq = $aventureRepository->findBy([
                        'IdUser' => $user->getId(),
                        'recommander' => 1,
                        'audiance' => ['Friends', 'public']
                    ]);
                    $aventures = $aventureRepository->findBy([
                        'IdUser' => $user->getId(),
                        'audiance' => ['Friends', 'public']
                    ]);
                } else {
                    // Vérifiez les demandes d'amitié non confirmées
                    $pendingFriendship = $entityManager->getRepository(AddFriend::class)->findOneBy([
                        'user_id2' => $currentUser,
                        'User_id' => $user->getId(),
                        'confirmation' => 0
                    ]) ?? $entityManager->getRepository(AddFriend::class)->findOneBy([
                        'User_id' => $currentUser,
                        'user_id2' => $user->getId(),
                        'confirmation' => 0
                    ]);

                    if ($pendingFriendship) {
                        $this->addFlash('info', 'A friend request is pending approval.');
                    } else {
                        $this->addFlash('warning', 'To view all published adventures, please send a friend request.');
                    }

                    $aventuresReq = $aventureRepository->findBy([
                        'IdUser' => $user->getId(),
                        'recommander' => 1,
                        'audiance' => 'public'
                    ]);
                    $aventures = $aventureRepository->findBy([
                        'IdUser' => $user->getId(),
                        'audiance' => 'public'
                    ]);
                }
            }
        } else {
            $this->addFlash('warning', 'Please Log in to view all published adventures.');
            // Si aucun utilisateur courant, afficher uniquement les aventures publiques
            $aventuresReq = $aventureRepository->findBy([
                'IdUser' => $user->getId(),
                'recommander' => 1,
                'audiance' => 'public'
            ]);
            $aventures = $aventureRepository->findBy([
                'IdUser' => $user->getId(),
                'audiance' => 'public'
            ]);
        }



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
                'profilePhoto' => $photoProfile,
                'page' => $useri->getPageNom(),
                'star' => $useri->getStar(),
            ];

        }

        $countries = [];
        foreach ($aventures as $adventure) {
            $country = $adventure->getIdPays();
            if ($country && !isset($countries[$country->getId()])) {
                $flagUrl = $this->fetchCountryFlag($country->getNom());
                $countries[$country->getId()] = [
                    'id' => $country->getId(),
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
            'url' => $url,
            'langue' => $langue,
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


    #[Route('/{slug}/Podcasts/{_locale}', name: 'all_podcasts', defaults: ['_locale' => 'en'])]
    public function all_podcasts(string $slug, SessionInterface $session, UserRepository $userRepository, AventureRepository $aventureRepository, ImageRepository $imageRepository, PodcastRepository $podcastRepository): Response
    {
        $user = $userRepository->findOneBy(['pageNom' => $slug]);

        // Si la page n'existe pas, lancer une exception
        if (!$user) {
            return $this->redirectToRoute('app_error404');
        }


        $sections = $user->getSections();
        $langue = $session->get('_locale');
        $url = 'all_podcasts';

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
                'profilePhoto' => $photoProfile,
                'page' => $useri->getPageNom(),
                'star' => $useri->getStar(),
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
        return $this->render('blog/AllPodcasts.html.twig', [
            'user' => $user,
            'sections' => $sections,
            'coverImage' => $coverImage,
            'countries' => $countries,
            'podcasts' => $podcasts,
            'adventures' => $aventures,
            'adventuresReq' => $aventuresReq,
            'images' => $image,
            'travelers' => $travelers,
            'url' => $url,
            'langue' => $langue,
        ]);
    }

    #[Route('/{slug}/Countries/{_locale}', name: 'all_countries' , defaults: ['_locale' => 'en'])]
    public function all_countries(string $slug,UserRepository $userRepository, EntityManagerInterface $entityManager, SessionInterface $session, AventureRepository $aventureRepository, ImageRepository $imageRepository, PodcastRepository $podcastRepository): Response
    {
        $currentUser = $session->get('user');
        $langue = $session->get('_locale');
        $url = 'all_countries';
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

        if ($currentUser) {
            if ($currentUser === $user->getId()) {
                $aventuresReq = $aventureRepository->findBy([
                    'IdUser' => $user->getId(),
                    'recommander' => 1
                ]);
                $aventures = $aventureRepository->findBy([
                    'IdUser' => $user->getId()
                ]);
            } else {
                // Vérifiez les amitiés pour déterminer les aventures à afficher
                $friendships = $entityManager->getRepository(AddFriend::class)->findBy([
                        'user_id2' => $currentUser,
                        'User_id' => $user->getId(),
                        'confirmation' => 1
                    ]) + $entityManager->getRepository(AddFriend::class)->findBy([
                        'User_id' => $currentUser,
                        'user_id2' => $user->getId(),
                        'confirmation' => 1
                    ]);

                if ($friendships) {
                    $aventuresReq = $aventureRepository->findBy([
                        'IdUser' => $user->getId(),
                        'recommander' => 1,
                        'audiance' => ['Friends', 'public']
                    ]);
                    $aventures = $aventureRepository->findBy([
                        'IdUser' => $user->getId(),
                        'audiance' => ['Friends', 'public']
                    ]);
                } else {
                    $aventuresReq = $aventureRepository->findBy([
                        'IdUser' => $user->getId(),
                        'recommander' => 1,
                        'audiance' => 'public'
                    ]);
                    $aventures = $aventureRepository->findBy([
                        'IdUser' => $user->getId(),
                        'audiance' => 'public'
                    ]);
                }
            }
        } else {
            // Si aucun utilisateur courant, afficher uniquement les aventures publiques
            $aventuresReq = $aventureRepository->findBy([
                'IdUser' => $user->getId(),
                'recommander' => 1,
                'audiance' => 'public'
            ]);
            $aventures = $aventureRepository->findBy([
                'IdUser' => $user->getId(),
                'audiance' => 'public'
            ]);
        }
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
                'profilePhoto' => $photoProfile,
                'page' => $useri->getPageNom(),
                'star' => $useri->getStar(),
            ];

        }

        $countries = [];
        foreach ($aventures as $adventure) {
            $country = $adventure->getIdPays();
            if ($country && !isset($countries[$country->getId()])) {
                $flagUrl = $this->fetchCountryFlag($country->getNom());
                $countries[$country->getId()] = [
                    'id' => $country->getId(),
                    'name' => $country->getNom(),
                    'flag' => $flagUrl
                ];
            }
        }

        // Rendre la vue avec les informations de la page
        return $this->render('blog/AllCountry.html.twig', [
            'user' => $user,
            'sections' => $sections,
            'coverImage' => $coverImage,
            'countries' => $countries,
            'podcasts' => $podcasts,
            'adventures' => $aventures,
            'adventuresReq' => $aventuresReq,
            'images' => $image,
            'travelers' => $travelers,
            'url' => $url,
            'langue' => $langue,
        ]);
    }

    #[Route('/{slug}/Adventures/{_locale}', name: 'all_adventures' , defaults: ['_locale' => 'en'])]
    public function all_adventures(string $slug,UserRepository $userRepository,SessionInterface $session,EntityManagerInterface $entityManager, AventureRepository $aventureRepository, ImageRepository $imageRepository, PodcastRepository $podcastRepository ): Response
    {
        $user = $userRepository->findOneBy(['pageNom' => $slug]);
        $currentUser = $session->get('user');
        $langue = $session->get('_locale');
        $url = 'all_adventures';
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

        if ($currentUser) {
            if ($currentUser === $user->getId()) {
                $aventuresReq = $aventureRepository->findBy([
                    'IdUser' => $user->getId(),
                    'recommander' => 1
                ]);
                $aventures = $aventureRepository->findBy([
                    'IdUser' => $user->getId()
                ]);
            } else {
                // Vérifiez les amitiés pour déterminer les aventures à afficher
                $friendships = $entityManager->getRepository(AddFriend::class)->findBy([
                        'user_id2' => $currentUser,
                        'User_id' => $user->getId(),
                        'confirmation' => 1
                    ]) + $entityManager->getRepository(AddFriend::class)->findBy([
                        'User_id' => $currentUser,
                        'user_id2' => $user->getId(),
                        'confirmation' => 1
                    ]);

                if ($friendships) {
                    $aventuresReq = $aventureRepository->findBy([
                        'IdUser' => $user->getId(),
                        'recommander' => 1,
                        'audiance' => ['Friends', 'public']
                    ]);
                    $aventures = $aventureRepository->findBy([
                        'IdUser' => $user->getId(),
                        'audiance' => ['Friends', 'public']
                    ]);
                } else {
                    $aventuresReq = $aventureRepository->findBy([
                        'IdUser' => $user->getId(),
                        'recommander' => 1,
                        'audiance' => 'public'
                    ]);
                    $aventures = $aventureRepository->findBy([
                        'IdUser' => $user->getId(),
                        'audiance' => 'public'
                    ]);
                }
            }
        } else {
            // Si aucun utilisateur courant, afficher uniquement les aventures publiques
            $aventuresReq = $aventureRepository->findBy([
                'IdUser' => $user->getId(),
                'recommander' => 1,
                'audiance' => 'public'
            ]);
            $aventures = $aventureRepository->findBy([
                'IdUser' => $user->getId(),
                'audiance' => 'public'
            ]);
        }
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
                'profilePhoto' => $photoProfile,
                'page' => $useri->getPageNom(),
                'star' => $useri->getStar(),
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
            'url' => $url,
            'langue' => $langue,
        ]);
    }

    #[Route('/{slug}/Adventure/{id}/{_locale}', name: 'adventure_detail' , defaults: ['_locale' => 'en'])]
    public function adventure_detail(string $id, SessionInterface $session, UserRepository $userRepository, string $slug, PodcastRepository $podcastRepository, AventureRepository $aventureRepository , ImageRepository $imageRepository): Response
    {
        $user = $userRepository->findOneBy(['pageNom' => $slug]);
        $langue = $session->get('_locale');
        $url = 'adventure_detail';
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
        $aventureClic = $aventureRepository->findOneBy(['nom' => $id]);
        $podcasts = $podcastRepository->findBy(['idUser' => $user]);
        $images = $imageRepository->findBy(['idAventure' => $aventureClic->getId()]); // Change variable name to match
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
                'profilePhoto' => $photoProfile,
                'page' => $useri->getPageNom(),
                'star' => $useri->getStar(),
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
            'url' => $url,
            'langue' => $langue,
        ]);
    }

    #[Route('/{slug}/Podcast/{id}/{_locale}', name: 'podcast_detail' , defaults: ['_locale' => 'en'])]
    public function podcast_detail(string $id, SessionInterface $session, UserRepository $userRepository, string $slug, PodcastRepository $podcastRepository, AventureRepository $aventureRepository , ImageRepository $imageRepository): Response
    {

        $user = $userRepository->findOneBy(['pageNom' => $slug]);
        $langue = $session->get('_locale');
        $url = 'podcast_detail';

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
        $podcastsClic = $podcastRepository->findOneBy(['name'=> $id]); // Change here to find a single adventure
        $podcasts = $podcastRepository->findBy(['idUser' => $user]);
        $images = $imageRepository->findBy(['idPodcast' => $podcastsClic->getId()]); // Change variable name to match
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
                'profilePhoto' => $photoProfile,
                'page' => $useri->getPageNom(),
                'star' => $useri->getStar(),
            ];

        }


        return $this->render('blog/Podcast.html.twig', [
            'user' => $user,
            'sections' => $sections,
            'coverImage' => $coverImage,
            'podcasts' => $podcasts,
            'adventures' => $aventures,
            'podcastsClic' => $podcastsClic,
            'adventuresReq' => $aventuresReq,
            'images' => $images,
            'travelers' => $travelers,
            'url' => $url,
            'langue' => $langue,
        ]);

    }

    #[Route('/{slug}/Country/{id}/{_locale}', name: 'country_detail' , defaults: ['_locale' => 'en'])]
    public function country_detail(string $id, UserRepository $userRepository, EntityManagerInterface $entityManager, SessionInterface $session, string $slug, PodcastRepository $podcastRepository, AventureRepository $aventureRepository , ImageRepository $imageRepository): Response
    {
        $currentUser = $session->get('user');
        $langue = $session->get('_locale');
        $url = 'country_detail';
        $user = $userRepository->findOneBy(['pageNom' => $slug]);

        // Si la page n'existe pas, lancer une exception
        if (!$user) {
            return $this->redirectToRoute('app_error404');
        }


        $sections = $user->getSections();

        $coverImage = null;
        $pays = $entityManager->getRepository(Pays::class)->findOneBy(['nom'=> $id]);

        // Parcourir les sections pour trouver l'image de couverture
        foreach ($sections as $section) {
            if ($section->getType() === 'Couverture') {
                $coverImage = $section->getImage();
                break;
            }
        }
        if ($currentUser) {
            if ($currentUser === $user->getId()) {
                $aventuresReq = $aventureRepository->findBy([
                    'IdUser' => $user->getId(),
                    'recommander' => 1
                ]);
                $aventures = $aventureRepository->findBy([
                    'IdUser' => $user->getId(), 'id_pays' => $pays->getId()
                ]);
            } else {
                // Vérifiez les amitiés pour déterminer les aventures à afficher
                $friendships = $entityManager->getRepository(AddFriend::class)->findBy([
                        'user_id2' => $currentUser,
                        'User_id' => $user->getId(),
                        'confirmation' => 1
                    ]) + $entityManager->getRepository(AddFriend::class)->findBy([
                        'User_id' => $currentUser,
                        'user_id2' => $user->getId(),
                        'confirmation' => 1
                    ]);

                if ($friendships) {
                    $aventuresReq = $aventureRepository->findBy([
                        'IdUser' => $user->getId(),
                        'recommander' => 1,
                        'audiance' => ['Friends', 'public']
                    ]);
                    $aventures = $aventureRepository->findBy([
                        'IdUser' => $user->getId(),
                        'audiance' => ['Friends', 'public'], 'id_pays' => $pays->getId()
                    ]);
                } else {
                    $aventuresReq = $aventureRepository->findBy([
                        'IdUser' => $user->getId(),
                        'recommander' => 1,
                        'audiance' => 'public'
                    ]);
                    $aventures = $aventureRepository->findBy([
                        'IdUser' => $user->getId(),
                        'audiance' => 'public', 'id_pays' => $pays->getId()
                    ]);
                }
            }
        } else {
            // Si aucun utilisateur courant, afficher uniquement les aventures publiques
            $aventuresReq = $aventureRepository->findBy([
                'IdUser' => $user->getId(),
                'recommander' => 1,
                'audiance' => 'public'
            ]);
            $aventures = $aventureRepository->findBy([
                'IdUser' => $user->getId(),
                'audiance' => 'public', 'id_pays' => $pays->getId()
            ]);
        }
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
                'profilePhoto' => $photoProfile,
                'page' => $useri->getPageNom(),
                'star' => $useri->getStar(),
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
        return $this->render('blog/Country.html.twig', [
            'user' => $user,
            'sections' => $sections,
            'coverImage' => $coverImage,
            'countries' => $countries,
            'podcasts' => $podcasts,
            'adventures' => $aventures,
            'adventuresReq' => $aventuresReq,
            'images' => $image,
            'travelers' => $travelers,
            'url' => $url,
            'langue' => $langue,
        ]);
    }

    #[Route('/change-local/{locale}/{url}', name: 'change_locale_langue')]
    public function changeLocale($locale, $url, Request $request, SessionInterface $session)
    {
        $request->getSession()->set('_locale', $locale);
        $slug = $session->get('slug');
        return $this->redirectToRoute($url, [
            '_locale' => $locale,
            'slug' => $slug,
        ]);
    }


}
