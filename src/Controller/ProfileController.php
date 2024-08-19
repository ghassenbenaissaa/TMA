<?php

namespace App\Controller;

use App\Entity\AddFriend;
use App\Entity\Aventure;
use App\Entity\Continent;
use App\Entity\Image;
use App\Entity\Pays;
use App\Entity\Podcast;
use App\Entity\Section;
use App\Entity\User;
use App\Repository\AddFriendRepository;
use OpenAI\Client;
use App\Form\AddPodcastType;
use App\Form\AventureType;
use App\Form\ChangePasswordType;
use App\Form\DeleteAccountType;
use App\Form\EditAventureType;
use App\Form\EditProfileType;
use App\Form\Form1Type;
use App\Form\MainSecionsType;
use App\Form\SocialMediaType;
use App\Repository\AventureRepository;
use App\Repository\ContinentRepository;
use App\Repository\ImageRepository;
use App\Repository\PaysRepository;
use App\Repository\PodcastRepository;
use App\Repository\SectionRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Security\Core\Security;
use OpenAI;


class ProfileController extends AbstractController
{
    #[Route('/Profile/{_locale}', name: 'app_profile', defaults: ['_locale' => 'en'])]
    public function index(UserRepository $userRepository, AddFriendRepository $addFriendRepository, ): Response
    {
        $session = $this->get('session');
        $langue = $session->get('_locale');
        $url = 'app_profile';
        if (!$session->has('user')) {
            // Rediriger vers la page de connexion ou une autre page
            return $this->redirectToRoute('app_login', ['_locale' => $langue]);
        }
        $currentUser = $session->get('user');
        $user = $userRepository->find($currentUser);
        if ($session->has('current_slug')) {
            $current_slug = $session->get('current_slug');
            if ($current_slug == $user->getPageNom()){
                $this->addFlash('error', 'Please add the main sections of your site (Cover Image, About Me, Description) and at least one adventure to make the page complete.');
            }
            $session->remove('current_slug');
        }
        $photoProfile = null;

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }
        foreach ($user->getSections() as $section) {
            if ("PhotoProfile" == $section->getType()) {
                $expectedImageName = $user->getId() . 'PhotoProfile.jpg';
                $sectionImage = $section->getImage();
                if ($expectedImageName == $sectionImage) {
                    $photoProfile = $sectionImage;
                    break;
                }
            }
        }
        // Récupérer les nombres des aventures par pays
        $countryAdventureCounts = [];
        foreach ($user->getAventures() as $aventure) {
            $pays = $aventure->getIdPays();
            if ($pays) {
                $countryName = $pays->getNom();
                if (!isset($countryAdventureCounts[$countryName])) {
                    $countryAdventureCounts[$countryName] = 0;
                }
                $countryAdventureCounts[$countryName]++;
            }
        }

        // Créer une chaîne de caractères formatée pour les pays avec les couleurs appropriées
        $formattedCountries = '';
        $paysAventuresList = [];
        foreach ($countryAdventureCounts as $countryName => $count) {
            if ($count <= 10) {
                $color = '#FFCCCC'; // Couleur plus claire
            } elseif ($count <= 20) {
                $color = '#FF6666'; // Couleur intermédiaire
            } else {
                $color = '#FF0000'; // Couleur plus foncée
            }
            $formattedCountries .= "'$countryName', '$color', ";
            $paysAventuresList[] = "$countryName : $count";
        }
        $formattedCountries = rtrim($formattedCountries, ', ');

        // Calculer le nombre total de pays visités
        $totalCountriesVisited = count($countryAdventureCounts);

        // Calculer le nombre total d'aventures
        $totalAdventures = $user->getAventures()->count();

        // Calculer le nombre total de continents visités
        $visitedContinents = [];
        foreach ($user->getAventures() as $aventure) {
            $continent = $aventure->getIdPays()->getIdContinent();
            if ($continent && !in_array($continent, $visitedContinents, true)) {
                $visitedContinents[] = $continent;
            }
        }
        $totalContinentsVisited = count($visitedContinents);

        // Construire la liste des pays avec le nombre d'aventures pour le tableau
        $countryAdventureList = [];
        $index = 1;
        foreach ($countryAdventureCounts as $countryName => $count) {
            $progressPercent = ($count / $totalAdventures) * 100;
            $countryAdventureList[] = [
                'index' => $index++,
                'countryName' => $countryName,
                'numberOfAdventures' => $count,
                'progressPercent' => $progressPercent,
            ];
        }
        $notifications = [];
        $friendRequests = $addFriendRepository->findBy(['user_id2' => $currentUser, 'confirmation' => 0, 'notification' => 0]);

        foreach ($friendRequests as $request) {
            $sender = $request->getUserId();
            $photo = null;

            if ($sender) {
                foreach ($sender->getSections() as $section) {
                    if ("PhotoProfile" == $section->getType()) {
                        $expectedImageName = $sender->getId() . 'PhotoProfile.jpg';
                        $sectionImage = $section->getImage();
                        if ($expectedImageName == $sectionImage) {
                            $photo = $sectionImage;
                            break;
                        }
                    }
                }
                $notifications[] = [
                    'requestId' => $request->getId(),
                    'nom' => $sender->getNom(),
                    'prenom' => $sender->getPrenom(),
                    'photo' => $photo,
                ];

                // Update the notification attribute to 1
                $entityManager = $this->getDoctrine()->getManager();
                $request->setNotification(1);
                $entityManager->persist($request);
                $entityManager->flush();
            }
        }


        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'formattedCountries' => $formattedCountries,
            'countryAdventureCounts' => $paysAventuresList,
            'totalCountriesVisited' => $totalCountriesVisited,
            'totalAdventures' => $totalAdventures,
            'totalContinentsVisited' => $totalContinentsVisited,
            'countryAdventureList' => $countryAdventureList, // Pass the list to Twig
            'photoProfile' => $photoProfile,
            'notifications' => $notifications,
            'url' => $url,
            'langue' => $langue,
        ]);
    }

    #[Route('/profile/Edit/{_locale}', name: 'app_editProfile', defaults: ['_locale' => 'en'])]
    public function editProfile(Request $request,AddFriendRepository $addFriendRepository, ParameterBagInterface $params): Response
    {
        $session = $this->get('session');
        $langue = $session->get('_locale');
        $url = 'app_editProfile';
        if (!$session->has('user')) {
            // Rediriger vers la page de connexion ou une autre page
            return $this->redirectToRoute('app_login', ['_locale' => $langue]);
        }
        $currentUser = $session->get('user');
        $entityManager = $this->getDoctrine()->getManager();
        $user = $entityManager->getRepository(User::class)->find($currentUser);
        $logos_directory = $params->get('logos_directory');

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        foreach ($user->getSections() as $section) {
            if ("PhotoProfile" == $section->getType()) {
                $expectedImageName = $user->getId() . 'PhotoProfile.jpg';
                $sectionImage = $section->getImage();
                if ($expectedImageName == $sectionImage) {
                    $photoProfile = $sectionImage;
                    break;
                }
            }
        }

        $form = $this->createForm(EditProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $logoFile = $request->files->get('logo');
            if ($logoFile) {
                // Vérifier l'extension du fichier
                if ($logoFile->guessExtension() !== 'png') {
                    $this->addFlash('error', 'The file is not compatible. Only PNG files are allowed.');
                    return $this->redirectToRoute('app_editProfile', ['_locale' => $langue]);
                }

                $newFilename = $user->getPageNom() . '.' . $logoFile->guessExtension();
                try {
                    $logoFile->move($logos_directory, $newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'An error occurred while uploading the file.');
                    return $this->redirectToRoute('app_editProfile', ['_locale' => $langue]);
                }
                $user->setLogo($newFilename);
            }
            $newEmail = $form->get('email')->getData();
            $user->setEmail($newEmail);
                $entityManager->flush();
                $this->addFlash('success', 'Profile updated successfully.');


            return $this->redirectToRoute('app_editProfile', ['_locale' => $langue]);
        }
        $notifications = [];
        $friendRequests = $addFriendRepository->findBy(['user_id2' => $currentUser, 'confirmation' => 0, 'notification' => 0]);
        foreach ($friendRequests as $request) {
            $sender = $request->getUserId();
            $photo = null;

            if ($sender) {
                foreach ($sender->getSections() as $section) {
                    if ("PhotoProfile" == $section->getType()) {
                        $expectedImageName = $sender->getId() . 'PhotoProfile.jpg';
                        $sectionImage = $section->getImage();
                        if ($expectedImageName == $sectionImage) {
                            $photo = $sectionImage;
                            break;
                        }
                    }
                }
                $notifications[] = [
                    'requestId' => $request->getId(),
                    'nom' => $sender->getNom(),
                    'prenom' => $sender->getPrenom(),
                    'photo' => $photo,
                ];
            }
        }

        return $this->render('profile/EditProfile.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
            'photoProfile' => $photoProfile,
            'notifications' => $notifications,
            'url' => $url,
            'langue' => $langue,
        ]);
    }

    #[Route('/profile/ChangePassword/{_locale}', name: 'app_CPassword', defaults: ['_locale' => 'en'])]
    public function index2(Request $request, UserPasswordEncoderInterface $passwordEncoder,AddFriendRepository $addFriendRepository, UserRepository $userRepository): Response
    {
        $session = $this->get('session');
        $langue = $session->get('_locale');
        $url = 'app_CPassword';
        if (!$session->has('user')) {
            // Rediriger vers la page de connexion ou une autre page
            return $this->redirectToRoute('app_login', ['_locale' => $langue]);
        }
        $currentUser = $session->get('user');
        $user = $userRepository->find($currentUser);
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }
        foreach ($user->getSections() as $section) {
            if ("PhotoProfile" == $section->getType()) {
                $expectedImageName = $user->getId() . 'PhotoProfile.jpg';
                $sectionImage = $section->getImage();
                if ($expectedImageName == $sectionImage) {
                    $photoProfile = $sectionImage;
                    break;
                }
            }
        }

        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $oldPassword = $data['Omdp'];
            if (!$passwordEncoder->isPasswordValid($user, $oldPassword)) {
                $this->addFlash('error', 'Invalid current password.');
                return $this->redirectToRoute('app_CPassword', ['_locale' => $langue]);
            }

            $newPassword = $data['mdp'];
            $confirmedPassword = $data['Cmdp'];

            if ($oldPassword == $newPassword) {
                $this->addFlash('error', 'New password must be different from the old password.');
                return $this->redirectToRoute('app_CPassword', ['_locale' => $langue]);
            }

            if ($newPassword !== $confirmedPassword) {
                $this->addFlash('error', 'New passwords do not match.');
                return $this->redirectToRoute('app_CPassword', ['_locale' => $langue]);
            }


            $hashedPassword = $passwordEncoder->encodePassword($user, $newPassword);
            $user->setMdp($hashedPassword);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Password updated successfully.');
            return $this->redirectToRoute('app_CPassword', ['_locale' => $langue]);
        }
        $notifications = [];
        $friendRequests = $addFriendRepository->findBy(['user_id2' => $currentUser, 'confirmation' => 0 , 'notification' => 0]);
        foreach ($friendRequests as $request) {
            $sender = $request->getUserId();
            $photo = null;

            if ($sender) {
                foreach ($sender->getSections() as $section) {
                    if ("PhotoProfile" == $section->getType()) {
                        $expectedImageName = $sender->getId() . 'PhotoProfile.jpg';
                        $sectionImage = $section->getImage();
                        if ($expectedImageName == $sectionImage) {
                            $photo = $sectionImage;
                            break;
                        }
                    }
                }
                $notifications[] = [
                    'requestId' => $request->getId(),
                    'nom' => $sender->getNom(),
                    'prenom' => $sender->getPrenom(),
                    'photo' => $photo,
                ];
            }
        }

        return $this->render('profile/ChangePassword.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
            'photoProfile' => $photoProfile,
            'notifications' => $notifications,
            'url' => $url,
            'langue' => $langue,
        ]);
    }

    #[Route('/profile/DeleteAccount/{_locale}', name: 'app_DAccount' , defaults: ['_locale' => 'en'])]
    public function index3(Request $request, UserRepository $userRepository,AddFriendRepository $addFriendRepository, EntityManagerInterface $entityManager): Response
    {
        $session = $this->get('session');
        $langue = $session->get('_locale');
        $url = 'app_DAccount';
        if (!$session->has('user')) {
            // Rediriger vers la page de connexion ou une autre page
            return $this->redirectToRoute('app_login' , ['_locale' => $langue]);
        }
        $currentUser = $session->get('user');

        $user = $userRepository->find($currentUser);
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }
        foreach ($user->getSections() as $section) {
            if ("PhotoProfile" == $section->getType()) {
                $expectedImageName = $user->getId() . 'PhotoProfile.jpg';
                $sectionImage = $section->getImage();
                if ($expectedImageName == $sectionImage) {
                    $photoProfile = $sectionImage;
                    break;
                }
            }
        }

        $form = $this->createForm(DeleteAccountType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->remove($user);
            $entityManager->flush();
            $this->addFlash('success', 'Your account has been deleted successfully.');
            $session->invalidate();
            return $this->redirectToRoute('app_home' , ['_locale' => $langue]);
        }
        $notifications = [];
        $friendRequests = $addFriendRepository->findBy(['user_id2' => $currentUser, 'confirmation' => 0 , 'notification' => 0]);
        foreach ($friendRequests as $request) {
            $sender = $request->getUserId();
            $photo = null;

            if ($sender) {
                foreach ($sender->getSections() as $section) {
                    if ("PhotoProfile" == $section->getType()) {
                        $expectedImageName = $sender->getId() . 'PhotoProfile.jpg';
                        $sectionImage = $section->getImage();
                        if ($expectedImageName == $sectionImage) {
                            $photo = $sectionImage;
                            break;
                        }
                    }
                }
                $notifications[] = [
                    'requestId' => $request->getId(),
                    'nom' => $sender->getNom(),
                    'prenom' => $sender->getPrenom(),
                    'photo' => $photo,
                ];
            }
        }

        return $this->render('profile/DeleteAccount.html.twig', [
            'user' => $user,
            'delete_form' => $form->createView(),
            'photoProfile' => $photoProfile,
            'notifications' => $notifications,
            'url' => $url,
            'langue' => $langue,
        ]);
    }

    #[Route('/profile/MainSections/{_locale}', name: 'app_Msections' , defaults: ['_locale' => 'en'])]
    public function index4(Request $request, UserRepository $userRepository, AddFriendRepository $addFriendRepository, SectionRepository $sectionRepository, ParameterBagInterface $params, EntityManagerInterface $entityManager): Response
    {
        $session = $this->get('session');
        $langue = $session->get('_locale');
        $url = 'app_Msections';
        if (!$session->has('user')) {
            // Rediriger vers la page de connexion ou une autre page
            return $this->redirectToRoute('app_login', ['_locale' => $langue]);
        }
        $currentUser = $session->get('user');

        $user = $userRepository->find($currentUser);
        $logos_directory = $params->get('logos_directory');
        $aboutMeSection = $sectionRepository->findOneBy(['id_user' => $currentUser, 'type' => 'AboutMe']);
        $CoverSection = $sectionRepository->findOneBy(['id_user' => $currentUser, 'type' => 'Couverture']);
        $ProfilePhotoSection = $sectionRepository->findOneBy(['id_user' => $currentUser, 'type' => 'PhotoProfile']);

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }
        foreach ($user->getSections() as $section) {
            if ("PhotoProfile" == $section->getType()) {
                $expectedImageName = $user->getId() . 'PhotoProfile.jpg';
                $sectionImage = $section->getImage();
                if ($expectedImageName == $sectionImage) {
                    $photoProfile = $sectionImage;
                    break;
                }
            }
        }

        if (!$aboutMeSection) {
            $aboutMeSection = new Section();
            $aboutMeSection->setType('AboutMe');
            $aboutMeSection->setIdUser($user);
        }

        if (!$CoverSection) {
            $CoverSection = new Section();
            $CoverSection->setType('Couverture');
            $CoverSection->setIdUser($user);
            $CoverSection->setDescription('Couverture');
        }

        if (!$ProfilePhotoSection) {
            $ProfilePhotoSection = new Section();
            $ProfilePhotoSection->setType('PhotoProfile');
            $ProfilePhotoSection->setIdUser($user);
            $ProfilePhotoSection->setDescription('PhotoProfile');
        }

        $form = $this->createForm(MainSecionsType::class, $aboutMeSection);
        $formSocialMedia = $this->createForm(SocialMediaType::class, $user);
        $form->handleRequest($request);
        $formSocialMedia->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $logoFile = $request->files->get('logo');
            $PhotoFile = $request->files->get('PhotoProfile');
            $aboutMeText = $form->get('description')->getData();
            $imageFile = $request->files->get('image');

            if ($PhotoFile) {
                $newFilename = $user->getId() . 'PhotoProfile' . '.' . $PhotoFile->guessExtension();
                try {
                    $PhotoFile->move($logos_directory, $newFilename);
                    $ProfilePhotoSection->setImage($newFilename);
                } catch (FileException $e) {
                    $errorMessage = sprintf('An error occurred while uploading the file: %s', $e->getMessage());
                    $this->addFlash('error', $errorMessage);
                    return $this->redirectToRoute('app_Msections', ['_locale' => $langue]);
                }

            }

            if ($logoFile) {
                $newFilename = $user->getPageNom() . 'Couver' . '.' . $logoFile->guessExtension();
                try {
                    $logoFile->move($logos_directory, $newFilename);
                    $CoverSection->setImage($newFilename);
                } catch (FileException $e) {
                    $errorMessage = sprintf('An error occurred while uploading the file: %s', $e->getMessage());
                    $this->addFlash('error', $errorMessage);
                    return $this->redirectToRoute('app_Msections', ['_locale' => $langue]);
                }

            }


            if ($aboutMeText && $aboutMeSection->getDescription() !== $aboutMeText) {
                if (strlen($aboutMeText) > 255) {
                    $errorMessage = sprintf('Description must not exceed 255 characters.');
                    $this->addFlash('error', $errorMessage);
                    return $this->redirectToRoute('app_Msections', ['userId' => $user->getId()]);
                }
                $aboutMeSection->setDescription($aboutMeText);
            }


            if ($imageFile) {
                $newFilename = "AboutMe" . $user->getId() . '.' . $imageFile->guessExtension();
                try {
                    $imageFile->move($logos_directory, $newFilename);
                    $aboutMeSection->setImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'An error occurred while uploading the file.');
                    return $this->redirectToRoute('app_Msections' , ['_locale' => $langue]);
                }
            }

            // Persistence of changes
            try {
                if ($logoFile) {
                    $entityManager->persist($CoverSection);
                }
                if ($PhotoFile) {
                    $entityManager->persist($ProfilePhotoSection);
                }
                if ($aboutMeSection->getImage() !== null) {
                    $entityManager->persist($aboutMeSection);
                }
                $entityManager->flush();

                $this->addFlash('success', 'Section updated successfully.');
                return $this->redirectToRoute('app_Msections' , ['_locale' => $langue]);
            } catch (FileException $e) {
                $errorMessage = sprintf('An error occurred while uploading the file: %s', $e->getMessage());
                $this->addFlash('error', $errorMessage);
                return $this->redirectToRoute('app_Msections' , ['_locale' => $langue]);
            }
        }
        else if ($formSocialMedia->isSubmitted()) {

            $facebook = $formSocialMedia->get('facebook')->getData();
            $instagram = $formSocialMedia->get('instagram')->getData();
            $twitter = $formSocialMedia->get('twitter')->getData();

            if ($facebook !== $user->getFacebook()) {
                $user->setFacebook($facebook);
            }
            if ($instagram !== $user->getInstagram()) {
                $user->setInstagram($instagram);
            }
            if ($twitter !== $user->getTwitter()) {
                $user->setTwitter($twitter);
            }
            try {
                $entityManager->persist($user);
                $entityManager->persist($aboutMeSection);
                $entityManager->flush();

                $this->addFlash('success', 'Section updated successfully.');
                return $this->redirectToRoute('app_Msections');
            } catch (\Exception $e) {
                $this->addFlash('error', 'An error occurred while saving.');
                return $this->redirectToRoute('app_Msections');
            }
        }

        $notifications = [];
        $friendRequests = $addFriendRepository->findBy(['user_id2' => $currentUser, 'confirmation' => 0 , 'notification' => 0]);
        foreach ($friendRequests as $request) {
            $sender = $request->getUserId();
            $photo = null;

            if ($sender) {
                foreach ($sender->getSections() as $section) {
                    if ("PhotoProfile" == $section->getType()) {
                        $expectedImageName = $sender->getId() . 'PhotoProfile.jpg';
                        $sectionImage = $section->getImage();
                        if ($expectedImageName == $sectionImage) {
                            $photo = $sectionImage;
                            break;
                        }
                    }
                }
                $notifications[] = [
                    'requestId' => $request->getId(),
                    'nom' => $sender->getNom(),
                    'prenom' => $sender->getPrenom(),
                    'photo' => $photo,
                ];
            }
        }

        return $this->render('profile/MainSections.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
            'formSocialMedia' => $formSocialMedia->createView(),
            'current_logo' => $CoverSection->getImage(),
            'current_photo' => $ProfilePhotoSection->getImage(),
            'aboutMeSection' => $aboutMeSection,
            'CoverSection' => $CoverSection,
            'ProfilePhotoSection' => $ProfilePhotoSection,
            'photoProfile' => $photoProfile,
            'notifications' => $notifications,
            'url' => $url,
            'langue' => $langue,
        ]);
    }



    #[Route('/profile/AddAdventure/{_locale}', name: 'app_AddAdventure' , defaults: ['_locale' => 'en'])]
    public function index5(Request $request, UserRepository $userRepository, AddFriendRepository $addFriendRepository, ParameterBagInterface $params, EntityManagerInterface $entityManager, PaysRepository $paysRepository, ContinentRepository $continentRepository): Response
    {
        $session = $this->get('session');
        $langue = $session->get('_locale');
        $url = 'app_AddAdventure';
        if (!$session->has('user')) {
            // Rediriger vers la page de connexion ou une autre page
            return $this->redirectToRoute('app_login' , ['_locale' => $langue]);
        }
        $currentUser = $session->get('user');

        $logos_directory = $params->get('Adv_directory');
        $user = $userRepository->find($currentUser);
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }
        $this->calculateAndSetStars($currentUser, $userRepository, $entityManager);
        foreach ($user->getSections() as $section) {
            if ("PhotoProfile" == $section->getType()) {
                $expectedImageName = $user->getId() . 'PhotoProfile.jpg';
                $sectionImage = $section->getImage();
                if ($expectedImageName == $sectionImage) {
                    $photoProfile = $sectionImage;
                    break;
                }
            }
        }

        $aventure = new Aventure();
        $form = $this->createForm(AventureType::class, $aventure);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $dateDebutString = $request->request->get('dateDebut');
            $dateFinString = $request->request->get('dateFin');

            // Vérifier que les chaînes de date ne sont pas vides
            if ($dateDebutString && $dateFinString) {
                // Vérifier que la date de fin n'est pas avant la date de début
                if ($dateDebutString > $dateFinString) {
                    $this->addFlash('error', 'End Date cannot be before Start Date. Start Date: ' . $dateFinString);
                    return $this->redirectToRoute('app_AddAdventure', ['_locale' => $langue]);
                }
                $dateDebut = \DateTime::createFromFormat('m-d-Y', $dateDebutString);
                $dateFin = \DateTime::createFromFormat('m-d-Y', $dateFinString);
            }
            $imageFiles = $request->files->get('images');
            $watermarkPath1 = $this->getParameter('kernel.project_dir').'/public/images/watermark.png';
            $watermarkPath2 = $this->getParameter('kernel.project_dir').'/public/logoClient/'.$user->getPageNom().$user->getId().'.png';
            foreach ($imageFiles as $imageFile) {
                if ($imageFile) {
                    $newFilename = $user->getPageNom() . $user->getId() . '_' . uniqid() . '_' . date('His') . '.' . $imageFile->guessExtension();
                    $temporaryFilePath = $logos_directory . '/' . $newFilename;
                    try {
                        $imageFile->move($logos_directory, $newFilename);
                        // Add watermark to the image
                        $this->addWatermarks(new File($temporaryFilePath), $watermarkPath1, $watermarkPath2, $temporaryFilePath, 5);
                    }
                    catch (FileException $e) {
                        $this->addFlash('error', 'An error occurred while uploading one of the images.');
                        return $this->redirectToRoute('app_AddAdventure' , ['_locale' => $langue]);
                    }
                    $image = new Image();
                    $image->setNom($newFilename);
                    $image->setIdAventure($aventure);
                    $aventure->addImage($image);
                }
            }

            $aventure->setIdUser($user);
            $paysNomAbrege = $form->get('Pays')->getData();

            $paysNomComplet = $this->convertAbregeToNomComplet($paysNomAbrege);
            $pays = $paysRepository->findOneBy(['nom' => $paysNomComplet]);

            if (!$pays) {
                $pays = new Pays();
                $pays->setNom($paysNomComplet);

                $continentNom = $this->getContinentByCountry($paysNomComplet);

                if ($continentNom) {
                    $continent = $continentRepository->findOneBy(['nom' => $continentNom]);
                    if (!$continent) {
                        $continent = new Continent();
                        $continent->setNom($continentNom);
                        $entityManager->persist($continent);
                        $entityManager->flush();
                    }
                    $pays->setIdContinent($continent);
                }

                $entityManager->persist($pays);
                $entityManager->flush();
            }

            $aventure->setIdPays($pays);
            $recommander = $form->get('recommander')->getData();
            $aventure->setRecommander($recommander);
            $video = $form->get('video')->getData();
            $aventure->setVideo($video);
            $audience = $form->get('audience')->getData();
            $aventure->setAudiance($audience);
            $aventure->setDateDebut($dateDebut);
            $aventure->setDateFin($dateFin);
            $entityManager->persist($aventure);
            $entityManager->flush();

            $this->addFlash('success', 'Adventure added successfully.');
            return $this->redirectToRoute('app_ShowAdventures' , ['_locale' => $langue]);
        }
        $totalAdventures = $entityManager->getRepository(Aventure::class)->count(['IdUser' => $currentUser]);

        // Déterminer le nombre d'étoiles
        if ($totalAdventures > 50) {
            $stars = 5;

        } elseif ($totalAdventures > 40) {
            $stars = 4;
        } elseif ($totalAdventures > 30) {
            $stars = 3;
        } elseif ($totalAdventures > 15) {
            $stars = 2;
        } elseif ($totalAdventures > 5) {
            $stars = 1;
        } else {
            $stars = 0;
        }

        $notifications = [];
        $friendRequests = $addFriendRepository->findBy(['user_id2' => $currentUser, 'confirmation' => 0 , 'notification' => 0]);
        foreach ($friendRequests as $request) {
            $sender = $request->getUserId();
            $photo = null;

            if ($sender) {
                foreach ($sender->getSections() as $section) {
                    if ("PhotoProfile" == $section->getType()) {
                        $expectedImageName = $sender->getId() . 'PhotoProfile.jpg';
                        $sectionImage = $section->getImage();
                        if ($expectedImageName == $sectionImage) {
                            $photo = $sectionImage;
                            break;
                        }
                    }
                }
                $notifications[] = [
                    'requestId' => $request->getId(),
                    'nom' => $sender->getNom(),
                    'prenom' => $sender->getPrenom(),
                    'photo' => $photo,
                ];
            }
        }

        return $this->render('profile/AddAdventure.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
            'photoProfile' => $photoProfile,
            'notifications' => $notifications,
            'url' => $url,
            'langue' => $langue,
        ]);
    }
    public function calculateAndSetStars(int $userId, UserRepository $userRepository, EntityManagerInterface $entityManager): void
    {
        // Récupérer l'utilisateur
        $user = $userRepository->find($userId);
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        // Compter le nombre total d'aventures de l'utilisateur
        $totalAdventures = $entityManager->getRepository(Aventure::class)->count(['IdUser' => $userId]);

        // Déterminer le nombre d'étoiles
        if ($totalAdventures > 50) {
            $user->setStar(5);
        } elseif ($totalAdventures > 40) {
            $user->setStar(4);
        } elseif ($totalAdventures > 30) {
            $user->setStar(3);
        } elseif ($totalAdventures > 15) {
            $user->setStar(2);
        } elseif ($totalAdventures > 5) {
            $user->setStar(1);
        } else {
            $user->setStar(0);
        }

        // Enregistrer les modifications
        $entityManager->persist($user);
        $entityManager->flush();
    }


    function addWatermarks(File $imagePath, string $watermarkPath1, string $watermarkPath2, string $outputPath, int $numWatermarks = 5): void
    {
        // Load the main image
        $image = imagecreatefromstring(file_get_contents($imagePath->getPathname()));
        $imageWidth = imagesx($image);
        $imageHeight = imagesy($image);

        // Load the first watermark
        $watermark1 = @imagecreatefrompng($watermarkPath1); // Suppress warning
        if (!$watermark1) {
            throw new Exception("Failed to load the first watermark image");
        }
        $watermark1Width = imagesx($watermark1);
        $watermark1Height = imagesy($watermark1);

        // Load the second watermark
        $watermark2 = @imagecreatefrompng($watermarkPath2); // Suppress warning
        if (!$watermark2) {
            throw new Exception("Failed to load the second watermark image". $watermarkPath2);
        }
        $watermark2Width = imagesx($watermark2);
        $watermark2Height = imagesy($watermark2);

        // Resize the watermarks to make them smaller
        $newWatermark2Width = 30; // Adjust the size as needed
        $newWatermark2Height = 30; // Adjust the size as needed
        $resizedWatermark2 = imagecreatetruecolor($newWatermark2Width, $newWatermark2Height);
        imagesavealpha($resizedWatermark2, true);
        $transparency = imagecolorallocatealpha($resizedWatermark2, 0, 0, 0, 127);
        imagefill($resizedWatermark2, 0, 0, $transparency);
        imagecopyresampled($resizedWatermark2, $watermark2, 0, 0, 0, 0, $newWatermark2Width, $newWatermark2Height, $watermark2Width, $watermark2Height);

        // Place the first watermark in the bottom right corner
        $x1 = $imageWidth - $watermark1Width - 10; // 10 pixels from the right edge
        $y1 = $imageHeight - $watermark1Height - 10; // 10 pixels from the bottom edge
        imagecopy($image, $watermark1, $x1, $y1, 0, 0, $watermark1Width, $watermark1Height);

        // Array to store positions of placed watermarks
        $placedPositions = [];

        // Add multiple watermarks in random positions with a maximum number of attempts
        $maxAttempts = 100; // Define the maximum number of attempts for finding a non-overlapping position
        for ($i = 0; $i < $numWatermarks; $i++) {
            $attempts = 0;
            do {
                // Calculate random positions
                $x2 = rand(0, $imageWidth - $newWatermark2Width);
                $y2 = rand(0, $imageHeight - $newWatermark2Height);
                $attempts++;
            } while ($this->isOverlapping($x2, $y2, $newWatermark2Width, $newWatermark2Height, $placedPositions) && $attempts < $maxAttempts);

            if ($attempts >= $maxAttempts) {
                // If a non-overlapping position couldn't be found, skip this watermark
                continue;
            }

            // Merge the watermark with the main image
            imagecopy($image, $resizedWatermark2, $x2, $y2, 0, 0, $newWatermark2Width, $newWatermark2Height);

            // Store the position of the placed watermark
            $placedPositions[] = ['x' => $x2, 'y' => $y2];
        }

        // Save the image with the watermark
        imagejpeg($image, $outputPath);

        // Clean up
        imagedestroy($image);
        imagedestroy($watermark1);
        imagedestroy($watermark2);
        imagedestroy($resizedWatermark2);
    }

    function isOverlapping($x, $y, $watermarkWidth, $watermarkHeight, $placedPositions) {
        foreach ($placedPositions as $position) {
            if ($x < $position['x'] + $watermarkWidth &&
                $x + $watermarkWidth > $position['x'] &&
                $y < $position['y'] + $watermarkHeight &&
                $y + $watermarkHeight > $position['y']) {
                return true;
            }
        }
        return false;
    }

    public function getContinentByCountry(string $country): ?string
    {
        $mapping = [
            'Afghanistan' => 'Asia',
            'Albania' => 'Europe',
            'Algeria' => 'Africa',
            'Andorra' => 'Europe',
            'Angola' => 'Africa',
            'Antigua and Barbuda' => 'North America',
            'Argentina' => 'South America',
            'Armenia' => 'Asia',
            'Australia' => 'Australia',
            'Austria' => 'Europe',
            'Azerbaijan' => 'Asia',
            'Bahamas' => 'North America',
            'Bahrain' => 'Asia',
            'Bangladesh' => 'Asia',
            'Barbados' => 'North America',
            'Belarus' => 'Europe',
            'Belgium' => 'Europe',
            'Belize' => 'North America',
            'Benin' => 'Africa',
            'Bhutan' => 'Asia',
            'Bolivia' => 'South America',
            'Bosnia and Herzegovina' => 'Europe',
            'Botswana' => 'Africa',
            'Brazil' => 'South America',
            'Brunei' => 'Asia',
            'Bulgaria' => 'Europe',
            'Burkina Faso' => 'Africa',
            'Burundi' => 'Africa',
            'Cabo Verde' => 'Africa',
            'Cambodia' => 'Asia',
            'Cameroon' => 'Africa',
            'Canada' => 'North America',
            'Central African Republic' => 'Africa',
            'Chad' => 'Africa',
            'Chile' => 'South America',
            'China' => 'Asia',
            'Colombia' => 'South America',
            'Comoros' => 'Africa',
            'Congo, Democratic Republic of the' => 'Africa',
            'Congo, Republic of the' => 'Africa',
            'Costa Rica' => 'North America',
            'Croatia' => 'Europe',
            'Cuba' => 'North America',
            'Cyprus' => 'Asia',
            'Czech Republic' => 'Europe',
            'Denmark' => 'Europe',
            'Djibouti' => 'Africa',
            'Dominica' => 'North America',
            'Dominican Republic' => 'North America',
            'Ecuador' => 'South America',
            'Egypt' => 'Africa',
            'El Salvador' => 'North America',
            'Equatorial Guinea' => 'Africa',
            'Eritrea' => 'Africa',
            'Estonia' => 'Europe',
            'Eswatini' => 'Africa',
            'Ethiopia' => 'Africa',
            'Fiji' => 'Australia',
            'Finland' => 'Europe',
            'France' => 'Europe',
            'Gabon' => 'Africa',
            'Gambia' => 'Africa',
            'Georgia' => 'Asia',
            'Germany' => 'Europe',
            'Ghana' => 'Africa',
            'Greece' => 'Europe',
            'Grenada' => 'North America',
            'Guatemala' => 'North America',
            'Guinea' => 'Africa',
            'Guinea-Bissau' => 'Africa',
            'Guyana' => 'South America',
            'Haiti' => 'North America',
            'Honduras' => 'North America',
            'Hungary' => 'Europe',
            'Iceland' => 'Europe',
            'India' => 'Asia',
            'Indonesia' => 'Asia',
            'Iran' => 'Asia',
            'Iraq' => 'Asia',
            'Ireland' => 'Europe',
            'Israel' => 'Asia',
            'Italy' => 'Europe',
            'Jamaica' => 'North America',
            'Japan' => 'Asia',
            'Jordan' => 'Asia',
            'Kazakhstan' => 'Asia',
            'Kenya' => 'Africa',
            'Kiribati' => 'Australia',
            'Kuwait' => 'Asia',
            'Kyrgyzstan' => 'Asia',
            'Laos' => 'Asia',
            'Latvia' => 'Europe',
            'Lebanon' => 'Asia',
            'Lesotho' => 'Africa',
            'Liberia' => 'Africa',
            'Libya' => 'Africa',
            'Liechtenstein' => 'Europe',
            'Lithuania' => 'Europe',
            'Luxembourg' => 'Europe',
            'Madagascar' => 'Africa',
            'Malawi' => 'Africa',
            'Malaysia' => 'Asia',
            'Maldives' => 'Asia',
            'Mali' => 'Africa',
            'Malta' => 'Europe',
            'Marshall Islands' => 'Australia',
            'Mauritania' => 'Africa',
            'Mauritius' => 'Africa',
            'Mexico' => 'North America',
            'Micronesia' => 'Australia',
            'Moldova' => 'Europe',
            'Monaco' => 'Europe',
            'Mongolia' => 'Asia',
            'Montenegro' => 'Europe',
            'Morocco' => 'Africa',
            'Mozambique' => 'Africa',
            'Myanmar' => 'Asia',
            'Namibia' => 'Africa',
            'Nauru' => 'Australia',
            'Nepal' => 'Asia',
            'Netherlands' => 'Europe',
            'New Zealand' => 'Australia',
            'Nicaragua' => 'North America',
            'Niger' => 'Africa',
            'Nigeria' => 'Africa',
            'North Korea' => 'Asia',
            'North Macedonia' => 'Europe',
            'Norway' => 'Europe',
            'Oman' => 'Asia',
            'Pakistan' => 'Asia',
            'Palau' => 'Australia',
            'Palestine' => 'Asia',
            'Panama' => 'North America',
            'Papua New Guinea' => 'Australia',
            'Paraguay' => 'South America',
            'Peru' => 'South America',
            'Philippines' => 'Asia',
            'Poland' => 'Europe',
            'Portugal' => 'Europe',
            'Qatar' => 'Asia',
            'Romania' => 'Europe',
            'Russia' => 'Europe',
            'Rwanda' => 'Africa',
            'Saint Kitts and Nevis' => 'North America',
            'Saint Lucia' => 'North America',
            'Saint Vincent and the Grenadines' => 'North America',
            'Samoa' => 'Australia',
            'San Marino' => 'Europe',
            'Sao Tome and Principe' => 'Africa',
            'Saudi Arabia' => 'Asia',
            'Senegal' => 'Africa',
            'Serbia' => 'Europe',
            'Seychelles' => 'Africa',
            'Sierra Leone' => 'Africa',
            'Singapore' => 'Asia',
            'Slovakia' => 'Europe',
            'Slovenia' => 'Europe',
            'Solomon Islands' => 'Australia',
            'Somalia' => 'Africa',
            'South Africa' => 'Africa',
            'South Korea' => 'Asia',
            'South Sudan' => 'Africa',
            'Spain' => 'Europe',
            'Sri Lanka' => 'Asia',
            'Sudan' => 'Africa',
            'Suriname' => 'South America',
            'Sweden' => 'Europe',
            'Switzerland' => 'Europe',
            'Syria' => 'Asia',
            'Taiwan' => 'Asia',
            'Tajikistan' => 'Asia',
            'Tanzania' => 'Africa',
            'Thailand' => 'Asia',
            'Timor-Leste' => 'Asia',
            'Togo' => 'Africa',
            'Tonga' => 'Australia',
            'Trinidad and Tobago' => 'North America',
            'Tunisia' => 'Africa',
            'Turkey' => 'Asia',
            'Turkmenistan' => 'Asia',
            'Tuvalu' => 'Australia',
            'Uganda' => 'Africa',
            'Ukraine' => 'Europe',
            'United Arab Emirates' => 'Asia',
            'United Kingdom' => 'Europe',
            'United States' => 'North America',
            'Uruguay' => 'South America',
            'Uzbekistan' => 'Asia',
            'Vanuatu' => 'Australia',
            'Vatican City' => 'Europe',
            'Venezuela' => 'South America',
            'Vietnam' => 'Asia',
            'Yemen' => 'Asia',
            'Zambia' => 'Africa',
            'Zimbabwe' => 'Africa'
        ];

        return $mapping[$country] ?? null;
    }

    private function convertAbregeToNomComplet(string $abrege): string
    {
        $abbreviations = [
            'AF' => 'Afghanistan',
            'AL' => 'Albania',
            'DZ' => 'Algeria',
            'AD' => 'Andorra',
            'AO' => 'Angola',
            'AG' => 'Antigua and Barbuda',
            'AR' => 'Argentina',
            'AM' => 'Armenia',
            'AU' => 'Australia',
            'AT' => 'Austria',
            'AZ' => 'Azerbaijan',
            'BS' => 'Bahamas',
            'BH' => 'Bahrain',
            'BD' => 'Bangladesh',
            'BB' => 'Barbados',
            'BY' => 'Belarus',
            'BE' => 'Belgium',
            'BZ' => 'Belize',
            'BJ' => 'Benin',
            'BT' => 'Bhutan',
            'BO' => 'Bolivia',
            'BA' => 'Bosnia and Herzegovina',
            'BW' => 'Botswana',
            'BR' => 'Brazil',
            'BN' => 'Brunei',
            'BG' => 'Bulgaria',
            'BF' => 'Burkina Faso',
            'BI' => 'Burundi',
            'CV' => 'Cabo Verde',
            'KH' => 'Cambodia',
            'CM' => 'Cameroon',
            'CA' => 'Canada',
            'CF' => 'Central African Republic',
            'TD' => 'Chad',
            'CL' => 'Chile',
            'CN' => 'China',
            'CO' => 'Colombia',
            'KM' => 'Comoros',
            'CD' => 'Congo, Democratic Republic of the',
            'CG' => 'Congo, Republic of the',
            'CR' => 'Costa Rica',
            'HR' => 'Croatia',
            'CU' => 'Cuba',
            'CY' => 'Cyprus',
            'CZ' => 'Czech Republic',
            'DK' => 'Denmark',
            'DJ' => 'Djibouti',
            'DM' => 'Dominica',
            'DO' => 'Dominican Republic',
            'EC' => 'Ecuador',
            'EG' => 'Egypt',
            'SV' => 'El Salvador',
            'GQ' => 'Equatorial Guinea',
            'ER' => 'Eritrea',
            'EE' => 'Estonia',
            'SZ' => 'Eswatini',
            'ET' => 'Ethiopia',
            'FJ' => 'Fiji',
            'FI' => 'Finland',
            'FR' => 'France',
            'GA' => 'Gabon',
            'GM' => 'Gambia',
            'GE' => 'Georgia',
            'DE' => 'Germany',
            'GH' => 'Ghana',
            'GR' => 'Greece',
            'GD' => 'Grenada',
            'GT' => 'Guatemala',
            'GN' => 'Guinea',
            'GW' => 'Guinea-Bissau',
            'GY' => 'Guyana',
            'HT' => 'Haiti',
            'HN' => 'Honduras',
            'HU' => 'Hungary',
            'IS' => 'Iceland',
            'IN' => 'India',
            'ID' => 'Indonesia',
            'IR' => 'Iran',
            'IQ' => 'Iraq',
            'IE' => 'Ireland',
            'IL' => 'Israel',
            'IT' => 'Italy',
            'JM' => 'Jamaica',
            'JP' => 'Japan',
            'JO' => 'Jordan',
            'KZ' => 'Kazakhstan',
            'KE' => 'Kenya',
            'KI' => 'Kiribati',
            'KW' => 'Kuwait',
            'KG' => 'Kyrgyzstan',
            'LA' => 'Laos',
            'LV' => 'Latvia',
            'LB' => 'Lebanon',
            'LS' => 'Lesotho',
            'LR' => 'Liberia',
            'LY' => 'Libya',
            'LI' => 'Liechtenstein',
            'LT' => 'Lithuania',
            'LU' => 'Luxembourg',
            'MG' => 'Madagascar',
            'MW' => 'Malawi',
            'MY' => 'Malaysia',
            'MV' => 'Maldives',
            'ML' => 'Mali',
            'MT' => 'Malta',
            'MH' => 'Marshall Islands',
            'MR' => 'Mauritania',
            'MU' => 'Mauritius',
            'MX' => 'Mexico',
            'FM' => 'Micronesia',
            'MD' => 'Moldova',
            'MC' => 'Monaco',
            'MN' => 'Mongolia',
            'ME' => 'Montenegro',
            'MA' => 'Morocco',
            'MZ' => 'Mozambique',
            'MM' => 'Myanmar',
            'NA' => 'Namibia',
            'NR' => 'Nauru',
            'NP' => 'Nepal',
            'NL' => 'Netherlands',
            'NZ' => 'New Zealand',
            'NI' => 'Nicaragua',
            'NE' => 'Niger',
            'NG' => 'Nigeria',
            'KP' => 'North Korea',
            'MK' => 'North Macedonia',
            'NO' => 'Norway',
            'OM' => 'Oman',
            'PK' => 'Pakistan',
            'PW' => 'Palau',
            'PS' => 'Palestine',
            'PA' => 'Panama',
            'PG' => 'Papua New Guinea',
            'PY' => 'Paraguay',
            'PE' => 'Peru',
            'PH' => 'Philippines',
            'PL' => 'Poland',
            'PT' => 'Portugal',
            'QA' => 'Qatar',
            'RO' => 'Romania',
            'RU' => 'Russia',
            'RW' => 'Rwanda',
            'KN' => 'Saint Kitts and Nevis',
            'LC' => 'Saint Lucia',
            'VC' => 'Saint Vincent and the Grenadines',
            'WS' => 'Samoa',
            'SM' => 'San Marino',
            'ST' => 'Sao Tome and Principe',
            'SA' => 'Saudi Arabia',
            'SN' => 'Senegal',
            'RS' => 'Serbia',
            'SC' => 'Seychelles',
            'SL' => 'Sierra Leone',
            'SG' => 'Singapore',
            'SK' => 'Slovakia',
            'SI' => 'Slovenia',
            'SB' => 'Solomon Islands',
            'SO' => 'Somalia',
            'ZA' => 'South Africa',
            'KR' => 'South Korea',
            'SS' => 'South Sudan',
            'ES' => 'Spain',
            'LK' => 'Sri Lanka',
            'SD' => 'Sudan',
            'SR' => 'Suriname',
            'SE' => 'Sweden',
            'CH' => 'Switzerland',
            'SY' => 'Syria',
            'TW' => 'Taiwan',
            'TJ' => 'Tajikistan',
            'TZ' => 'Tanzania',
            'TH' => 'Thailand',
            'TL' => 'Timor-Leste',
            'TG' => 'Togo',
            'TO' => 'Tonga',
            'TT' => 'Trinidad and Tobago',
            'TN' => 'Tunisia',
            'TR' => 'Turkey',
            'TM' => 'Turkmenistan',
            'TV' => 'Tuvalu',
            'UG' => 'Uganda',
            'UA' => 'Ukraine',
            'AE' => 'United Arab Emirates',
            'GB' => 'United Kingdom',
            'US' => 'United States',
            'UY' => 'Uruguay',
            'UZ' => 'Uzbekistan',
            'VU' => 'Vanuatu',
            'VA' => 'Vatican City',
            'VE' => 'Venezuela',
            'VN' => 'Vietnam',
            'YE' => 'Yemen',
            'ZM' => 'Zambia',
            'ZW' => 'Zimbabwe'
        ];

        return $abbreviations[$abrege] ?? $abrege;
    }

    #[Route('/profile/ShowAdventures/{_locale}', name: 'app_ShowAdventures' , defaults: ['_locale' => 'en'])]
    public function index6(Request $request, UserRepository $userRepository, AddFriendRepository $addFriendRepository, AventureRepository $aventureRepository,ImageRepository $imageRepository): Response
    {
        $session = $this->get('session');
        $langue = $session->get('_locale');
        $url = 'app_ShowAdventures';
        if (!$session->has('user')) {
            // Rediriger vers la page de connexion ou une autre page
            return $this->redirectToRoute('app_login' , ['_locale' => $langue]);
        }
        $currentUser = $session->get('user');

        $user = $userRepository->find($currentUser);
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }
        foreach ($user->getSections() as $section) {
            if ("PhotoProfile" == $section->getType()) {
                $expectedImageName = $user->getId() . 'PhotoProfile.jpg';
                $sectionImage = $section->getImage();
                if ($expectedImageName == $sectionImage) {
                    $photoProfile = $sectionImage;
                    break;
                }
            }
        }

        // Récupérer toutes les aventures de l'utilisateur
        $aventures = $aventureRepository->findBy(['IdUser' => $user]);
        // Limiter la description à trois mots
        foreach ($aventures as $aventure) {
            $description = $aventure->getDescription();
            $words = explode(' ', $description);
            if (count($words) > 3) {
                $aventure->limitedDescription = implode(' ', array_slice($words, 0, 3)) . '...';
            } else {
                $aventure->limitedDescription = $description;
            }
        }
        $image = $imageRepository->findAll();
        $notifications = [];
        $friendRequests = $addFriendRepository->findBy(['user_id2' => $currentUser, 'confirmation' => 0 , 'notification' => 0]);
        foreach ($friendRequests as $request) {
            $sender = $request->getUserId();
            $photo = null;

            if ($sender) {
                foreach ($sender->getSections() as $section) {
                    if ("PhotoProfile" == $section->getType()) {
                        $expectedImageName = $sender->getId() . 'PhotoProfile.jpg';
                        $sectionImage = $section->getImage();
                        if ($expectedImageName == $sectionImage) {
                            $photo = $sectionImage;
                            break;
                        }
                    }
                }
                $notifications[] = [
                    'requestId' => $request->getId(),
                    'nom' => $sender->getNom(),
                    'prenom' => $sender->getPrenom(),
                    'photo' => $photo,
                ];
            }
        }
        return $this->render('profile/ShowAdventure.html.twig', [
            'user' => $user,
            'aventures' => $aventures,
            'images' => $image,
            'photoProfile' => $photoProfile,
            'notifications' => $notifications,
            'url' => $url,
            'langue' => $langue,
        ]);
    }

    #[Route('/profile/ShowAdventure/{AvId}', name: 'app_DAdventures')]
    public function index7(UserRepository $userRepository,AventureRepository $adventureRepository, EntityManagerInterface $entityManager, int $AvId   ): Response
    {
        $session = $this->get('session');
        if (!$session->has('user')) {
            // Rediriger vers la page de connexion ou une autre page
            return $this->redirectToRoute('app_login');
        }
        $currentUser = $session->get('user');

        $user = $userRepository->find($currentUser);
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }


        $aventure = $adventureRepository->find($AvId);
        if (!$aventure) {
            throw $this->createNotFoundException('Adventure not found');
        }
        $entityManager->remove($aventure);
        $entityManager->flush();
        $this->addFlash('success', 'Your adventure has been deleted successfully.');
        return $this->redirectToRoute('app_ShowAdventures');
    }

    #[Route('/profile/EditAdventure/{AvId}/{_locale}', name: 'app_EditAdventure' , defaults: ['_locale' => 'en'])]
    public function index8(Request $request, UserRepository $userRepository,AddFriendRepository $addFriendRepository, AventureRepository $aventureRepository, ParameterBagInterface $params, EntityManagerInterface $entityManager, PaysRepository $paysRepository, ContinentRepository $continentRepository , int $AvId): Response
    {
        $session = $this->get('session');
        $langue = $session->get('_locale');
        $url = 'app_EditAdventure';
        if (!$session->has('user')) {
            // Rediriger vers la page de connexion ou une autre page
            return $this->redirectToRoute('app_login' , ['_locale' => $langue]);
        }
        $currentUser = $session->get('user');

        $logos_directory = $params->get('Adv_directory');
        $user = $userRepository->find($currentUser);
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }
        foreach ($user->getSections() as $section) {
            if ("PhotoProfile" == $section->getType()) {
                $expectedImageName = $user->getId() . 'PhotoProfile.jpg';
                $sectionImage = $section->getImage();
                if ($expectedImageName == $sectionImage) {
                    $photoProfile = $sectionImage;
                    break;
                }
            }
        }

        $aventure = $aventureRepository->find($AvId);
        if (!$aventure) {
            throw $this->createNotFoundException('Adventure not found');
        }

        $form = $this->createForm(EditAventureType::class, $aventure);
        $form->handleRequest($request);

        if ($form->isSubmitted() ) {
            $dateDebutString = $request->request->get('dateDebut');
            $dateFinString = $request->request->get('dateFin');

            // Vérifier que les chaînes de date ne sont pas vides
            if ($dateDebutString && $dateFinString) {
                // Vérifier que la date de fin n'est pas avant la date de début
                if ($dateDebutString > $dateFinString) {
                    $this->addFlash('error', 'End Date cannot be before Start Date. Start Date: ' . $dateFinString);
                    return $this->redirectToRoute('app_EditAdventure', ['AvId' => $AvId , '_locale' => $langue]);
                }
                $dateDebut = \DateTime::createFromFormat('m-d-Y', $dateDebutString);
                $dateFin = \DateTime::createFromFormat('m-d-Y', $dateFinString);
            }
            $imageFiles = $request->files->get('images');
            $watermarkPath1 = $this->getParameter('kernel.project_dir').'/public/images/watermark.png';
            $watermarkPath2 = $this->getParameter('kernel.project_dir').'/public/logoClient/'.$user->getPageNom().$user->getId().'.png';
            foreach ($imageFiles as $imageFile) {
                if ($imageFile) {
                    $newFilename = $user->getPageNom() . $user->getId() . '_' . uniqid() . '_' . date('His') . '.' . $imageFile->guessExtension();
                    $temporaryFilePath = $logos_directory . '/' . $newFilename;
                    try {
                        $imageFile->move($logos_directory, $newFilename);
                        $this->addWatermarks(new File($temporaryFilePath), $watermarkPath1, $watermarkPath2, $temporaryFilePath, 5);
                    } catch (FileException $e) {
                        $this->addFlash('error', 'An error occurred while uploading one of the images.');
                        return $this->redirectToRoute('app_EditAdventure', ['AvId' => $AvId , '_locale' => $langue]);
                    }
                    $image = new Image();
                    $image->setNom($newFilename);
                    $image->setIdAventure($aventure);
                    $aventure->addImage($image);
                }
            }
            $aventure->setIdUser($user);
            $paysNomAbrege = $form->get('Pays')->getData();
            $paysNomComplet = $this->convertAbregeToNomComplet($paysNomAbrege);
            $pays = $paysRepository->findOneBy(['nom' => $paysNomComplet]);

            if (!$pays) {
                $pays = new Pays();
                $pays->setNom($paysNomComplet);

                $continentNom = $this->getContinentByCountry($paysNomComplet);
                if ($continentNom) {
                    $continent = $continentRepository->findOneBy(['nom' => $continentNom]);
                    if (!$continent) {
                        $continent = new Continent();
                        $continent->setNom($continentNom);
                        $entityManager->persist($continent);
                        $entityManager->flush();
                    }
                    $pays->setIdContinent($continent);
                }

                $entityManager->persist($pays);
                $entityManager->flush();
            }

            $aventure->setIdPays($pays);
            $recommander = $form->get('recommander')->getData();
            $aventure->setRecommander($recommander);
            $video = $form->get('video')->getData();
            $aventure->setVideo($video);
            $audience = $form->get('audiance')->getData();
            $aventure->setAudiance($audience);
            $aventure->setDateDebut($dateDebut);
            $aventure->setDateFin($dateFin);
            $entityManager->persist($aventure);
            $entityManager->flush();

            $this->addFlash('success', 'Adventure updated successfully.');
            return $this->redirectToRoute('app_EditAdventure', [ 'AvId' => $AvId , '_locale' => $langue]);
        }
        $notifications = [];
        $friendRequests = $addFriendRepository->findBy(['user_id2' => $currentUser, 'confirmation' => 0 , 'notification' => 0]);
        foreach ($friendRequests as $request) {
            $sender = $request->getUserId();
            $photo = null;

            if ($sender) {
                foreach ($sender->getSections() as $section) {
                    if ("PhotoProfile" == $section->getType()) {
                        $expectedImageName = $sender->getId() . 'PhotoProfile.jpg';
                        $sectionImage = $section->getImage();
                        if ($expectedImageName == $sectionImage) {
                            $photo = $sectionImage;
                            break;
                        }
                    }
                }
                $notifications[] = [
                    'requestId' => $request->getId(),
                    'nom' => $sender->getNom(),
                    'prenom' => $sender->getPrenom(),
                    'photo' => $photo,
                ];
            }
        }

        return $this->render('profile/EditAdventure.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
            'aventure' => $aventure,
            'photoProfile' => $photoProfile,
            'notifications' => $notifications,
            'url' => $url,
            'langue' => $langue,
        ]);
    }

    #[Route('/profile/delete/image/{AvId}/{imageId}', name: 'delete_image')]
    public function deleteImage(Request $request, EntityManagerInterface $entityManager, ImageRepository $imageRepository, UserRepository $userRepository, AventureRepository $aventureRepository , int $AvId,int $imageId): Response
    {
        $session = $this->get('session');
        if (!$session->has('user')) {
            // Rediriger vers la page de connexion ou une autre page
            return $this->redirectToRoute('app_login');
        }
        $currentUser = $session->get('user');

        $user = $userRepository->find($currentUser);
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        $aventure = $aventureRepository->find($AvId);
        if (!$aventure) {
            throw $this->createNotFoundException('Adventure not found');
        }

        $form = $this->createForm(EditAventureType::class, $aventure);
        $form->handleRequest($request);

        $image = $imageRepository->find($imageId);

            $entityManager->remove($image);
            $entityManager->flush();
            $this->addFlash('success', 'Adventure updated successfully.');

        return $this->redirectToRoute('app_EditAdventure', ['AvId' => $AvId]);
    }


    #[Route('/profile/EditWebSite/{_locale}', name: 'app_Website' , defaults: ['_locale' => 'en'], methods: ['GET', 'POST'])]
    public function editSite( Request $request, AddFriendRepository $addFriendRepository, EntityManagerInterface $entityManager, UserRepository $userRepository): Response
    {
        $session = $this->get('session');
        $langue = $session->get('_locale');
        $url = 'app_Website';
        if (!$session->has('user')) {
            // Rediriger vers la page de connexion ou une autre page
            return $this->redirectToRoute('app_login' , ['_locale' => $langue]);
        }
        $currentUser = $session->get('user');

        $user = $userRepository->find($currentUser);

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }
        foreach ($user->getSections() as $section) {
            if ("PhotoProfile" == $section->getType()) {
                $expectedImageName = $user->getId() . 'PhotoProfile.jpg';
                $sectionImage = $section->getImage();
                if ($expectedImageName == $sectionImage) {
                    $photoProfile = $sectionImage;
                    break;
                }
            }
        }

        $form = $this->createForm(Form1Type::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $entityManager->flush();
            $this->addFlash('success', 'Site web updated successfully.');
            return $this->redirectToRoute('app_Website' , ['_locale' => $langue]);
        }
        $notifications = [];
        $friendRequests = $addFriendRepository->findBy(['user_id2' => $currentUser, 'confirmation' => 0 , 'notification' => 0]);
        foreach ($friendRequests as $request) {
            $sender = $request->getUserId();
            $photo = null;

            if ($sender) {
                foreach ($sender->getSections() as $section) {
                    if ("PhotoProfile" == $section->getType()) {
                        $expectedImageName = $sender->getId() . 'PhotoProfile.jpg';
                        $sectionImage = $section->getImage();
                        if ($expectedImageName == $sectionImage) {
                            $photo = $sectionImage;
                            break;
                        }
                    }
                }
                $notifications[] = [
                    'requestId' => $request->getId(),
                    'nom' => $sender->getNom(),
                    'prenom' => $sender->getPrenom(),
                    'photo' => $photo,
                ];
            }
        }

        return $this->render('profile/EditSiteWeb.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
            'photoProfile' => $photoProfile,
            'notifications' => $notifications,
            'url' => $url,
            'langue' => $langue,
        ]);
    }

    #[Route('/profile/ShowPodcast/{_locale}', name: 'app_ShowPodcast' , defaults: ['_locale' => 'en'])]
    public function showPodcast(Request $request, UserRepository $userRepository,AddFriendRepository $addFriendRepository, PodcastRepository $podcastRepository, ImageRepository $imageRepository ): Response
    {
        $session = $this->get('session');
        $langue = $session->get('_locale');
        $url = 'app_ShowPodcast';
        if (!$session->has('user')) {
            // Rediriger vers la page de connexion ou une autre page
            return $this->redirectToRoute('app_login' , ['_locale' => $langue]);
        }
        $currentUser = $session->get('user');

        $user = $userRepository->find($currentUser);
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }
        foreach ($user->getSections() as $section) {
            if ("PhotoProfile" == $section->getType()) {
                $expectedImageName = $user->getId() . 'PhotoProfile.jpg';
                $sectionImage = $section->getImage();
                if ($expectedImageName == $sectionImage) {
                    $photoProfile = $sectionImage;
                    break;
                }
            }
        }
        // Récupérer toutes les aventures de l'utilisateur
        $podcasts = $podcastRepository->findBy(['idUser' => $user]);
        // Limiter la description à trois mots
        foreach ($podcasts as $podcast) {
            $description = $podcast->getDescription();
            $words = explode(' ', $description);
            if (count($words) > 3) {
                $podcast->limitedDescription = implode(' ', array_slice($words, 0, 3)) . '...';
            } else {
                $podcast->limitedDescription = $description;
            }
        }
        $image = $imageRepository->findAll();
        $notifications = [];
        $friendRequests = $addFriendRepository->findBy(['user_id2' => $currentUser, 'confirmation' => 0 , 'notification' => 0]);
        foreach ($friendRequests as $request) {
            $sender = $request->getUserId();
            $photo = null;

            if ($sender) {
                foreach ($sender->getSections() as $section) {
                    if ("PhotoProfile" == $section->getType()) {
                        $expectedImageName = $sender->getId() . 'PhotoProfile.jpg';
                        $sectionImage = $section->getImage();
                        if ($expectedImageName == $sectionImage) {
                            $photo = $sectionImage;
                            break;
                        }
                    }
                }
                $notifications[] = [
                    'requestId' => $request->getId(),
                    'nom' => $sender->getNom(),
                    'prenom' => $sender->getPrenom(),
                    'photo' => $photo,
                ];
            }
        }
        return $this->render('profile/ShowPodcast.html.twig', [
            'user' => $user,
            'podcasts' => $podcasts,
            'images' => $image,
            'photoProfile' => $photoProfile,
            'notifications' => $notifications,
            'url' => $url,
            'langue' => $langue,
        ]);
    }

    #[Route('/profile/ShowPodcast/{PdId}', name: 'app_DPodcast')]
    public function deletePodcast(UserRepository $userRepository,PodcastRepository $podcastRepository, EntityManagerInterface $entityManager, int $PdId): Response
    {
        $session = $this->get('session');
        if (!$session->has('user')) {
            // Rediriger vers la page de connexion ou une autre page
            return $this->redirectToRoute('app_login');
        }
        $currentUser = $session->get('user');

        $user = $userRepository->find($currentUser);
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }



        $podcast = $podcastRepository->find($PdId);
        if (!$podcast) {
            throw $this->createNotFoundException('Podcast not found');
        }
        $entityManager->remove($podcast);
        $entityManager->flush();
        $this->addFlash('success', 'Your Podcast has been deleted successfully.');
        return $this->redirectToRoute('app_ShowPodcast');
    }

    #[Route('/profile/AddPodcast/{_locale}', name: 'app_AddPodcast' , defaults: ['_locale' => 'en'])]
    public function addPodcast(Request $request, UserRepository $userRepository,AddFriendRepository $addFriendRepository, EntityManagerInterface $entityManager, ParameterBagInterface $params  ): Response
    {
        $session = $this->get('session');
        $langue = $session->get('_locale');
        $url = 'app_AddPodcast';
        if (!$session->has('user')) {
            // Rediriger vers la page de connexion ou une autre page
            return $this->redirectToRoute('app_login' , ['_locale' => $langue]);
        }
        $currentUser = $session->get('user');

        $podcast_directory = $params->get('Podcast_directory');
        $user = $userRepository->find($currentUser);
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }
        foreach ($user->getSections() as $section) {
            if ("PhotoProfile" == $section->getType()) {
                $expectedImageName = $user->getId() . 'PhotoProfile.jpg';
                $sectionImage = $section->getImage();
                if ($expectedImageName == $sectionImage) {
                    $photoProfile = $sectionImage;
                    break;
                }
            }
        }

        $podcast = new Podcast();
        $podcast->setIdUser($user);

        $form = $this->createForm(AddPodcastType::class, $podcast);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $imageFiles = $request->files->get('images');
            $watermarkPath1 = $this->getParameter('kernel.project_dir').'/public/images/watermark.png';
            $watermarkPath2 = $this->getParameter('kernel.project_dir').'/public/logoClient/'.$user->getPageNom().$user->getId().'.png';
            foreach ($imageFiles as $imageFile) {
                if ($imageFile) {
                    $newFilename = $user->getId() . '_' . uniqid() . '_' . date('His') . '.' . $imageFile->guessExtension();
                    $temporaryFilePath = $podcast_directory . '/' . $newFilename;
                    try {
                        $imageFile->move($podcast_directory, $newFilename);
                        // Add watermark to the image
                        $this->addWatermarks(new File($temporaryFilePath), $watermarkPath1, $watermarkPath2, $temporaryFilePath, 5);
                    }
                    catch (FileException $e) {
                        $this->addFlash('error', 'An error occurred while uploading one of the images.');
                        return $this->redirectToRoute('app_AddPodcast' , ['_locale' => $langue]);
                    }
                    $image = new Image();
                    $image->setNom($newFilename);
                    $image->setIdPodcast($podcast);
                    $podcast->addImage($image);
                }
            }
            $podcastFile = $request->files->get('audio');
            if ($podcastFile) {
                $originalFilename = pathinfo($podcastFile->getClientOriginalName(), PATHINFO_FILENAME);
                $newFilename = $originalFilename . '-' . uniqid() . '.' . $podcastFile->guessExtension();

                try {
                    $podcastFile->move(
                        $podcast_directory,
                        $newFilename
                    );
                    $podcast->setSource($newFilename); // Set the filename to the podcast entity
                } catch (FileException $e) {
                    $this->addFlash('error', 'An error occurred while uploading the podcast file.');
                    return $this->redirectToRoute('app_AddPodcast' , ['_locale' => $langue]);
                }
            }
            $entityManager->persist($podcast);
            $entityManager->flush();

            $this->addFlash('success', 'Podcast added successfully.');
            return $this->redirectToRoute('app_ShowPodcast' , ['_locale' => $langue]);
        }
        $notifications = [];
        $friendRequests = $addFriendRepository->findBy(['user_id2' => $currentUser, 'confirmation' => 0 , 'notification' => 0]);
        foreach ($friendRequests as $request) {
            $sender = $request->getUserId();
            $photo = null;

            if ($sender) {
                foreach ($sender->getSections() as $section) {
                    if ("PhotoProfile" == $section->getType()) {
                        $expectedImageName = $sender->getId() . 'PhotoProfile.jpg';
                        $sectionImage = $section->getImage();
                        if ($expectedImageName == $sectionImage) {
                            $photo = $sectionImage;
                            break;
                        }
                    }
                }
                $notifications[] = [
                    'requestId' => $request->getId(),
                    'nom' => $sender->getNom(),
                    'prenom' => $sender->getPrenom(),
                    'photo' => $photo,
                ];
            }
        }

        return $this->render('profile/AddPodcast.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
            'photoProfile' => $photoProfile,
            'notifications' => $notifications,
            'langue' => $langue,
            'url' => $url
        ]);
    }


    #[Route('/profile/EditPodcast/{PdId}/{_locale}', name: 'app_EditPodcast' , defaults: ['_locale' => 'en'])]
    public function editPodcast(Request $request, UserRepository $userRepository,AddFriendRepository $addFriendRepository,PodcastRepository $podcastRepository, EntityManagerInterface $entityManager, ParameterBagInterface $params ,int $PdId): Response
    {
        $session = $this->get('session');
        $langue = $session->get('_locale');
        $url = 'app_EditPodcast';
        if (!$session->has('user')) {
            // Rediriger vers la page de connexion ou une autre page
            return $this->redirectToRoute('app_login' , ['_locale' => $langue]);
        }
        $currentUser = $session->get('user');

        $podcast_directory = $params->get('Podcast_directory');
        $user = $userRepository->find($currentUser);
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }
        foreach ($user->getSections() as $section) {
            if ("PhotoProfile" == $section->getType()) {
                $expectedImageName = $user->getId() . 'PhotoProfile.jpg';
                $sectionImage = $section->getImage();
                if ($expectedImageName == $sectionImage) {
                    $photoProfile = $sectionImage;
                    break;
                }
            }
        }

        $podcast = $podcastRepository->find($PdId);
        if (!$podcast) {
            throw $this->createNotFoundException('Podcast not found');
        }

        $form = $this->createForm(AddPodcastType::class, $podcast);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $imageFiles = $request->files->get('images');
            $watermarkPath1 = $this->getParameter('kernel.project_dir').'/public/images/watermark.png';
            $watermarkPath2 = $this->getParameter('kernel.project_dir').'/public/logoClient/'.$user->getPageNom().$user->getId().'.png';
            foreach ($imageFiles as $imageFile) {
                if ($imageFile) {
                    $newFilename = $user->getPageNom() . $user->getId() . '_' . uniqid() . '_' . date('His') . '.' . $imageFile->guessExtension();
                    $temporaryFilePath = $podcast_directory . '/' . $newFilename;
                    try {
                        $imageFile->move($podcast_directory, $newFilename);
                        $this->addWatermarks(new File($temporaryFilePath), $watermarkPath1, $watermarkPath2, $temporaryFilePath, 5);
                    } catch (FileException $e) {
                        $this->addFlash('error', 'An error occurred while uploading one of the images.');
                        return $this->redirectToRoute('app_EditPodcast', ['PdId' => $PdId]);
                    }
                    $image = new Image();
                    $image->setNom($newFilename);
                    $image->setIdPodcast($podcast);
                    $podcast->addImage($image);
                }
            }
            $podcastFile = $request->files->get('audio');
            if (!$podcastFile && $podcast->getSource()) {
                $entityManager->persist($podcast);
                $entityManager->flush();
            }
            if ($podcastFile) {
                $originalFilename = pathinfo($podcastFile->getClientOriginalName(), PATHINFO_FILENAME);
                $newFilename = $originalFilename . '-' . uniqid() . '.' . $podcastFile->guessExtension();

                try {
                    $podcastFile->move(
                        $podcast_directory,
                        $newFilename
                    );
                    $podcast->setSource($newFilename); // Set the filename to the podcast entity
                } catch (FileException $e) {
                    $this->addFlash('error', 'An error occurred while uploading the podcast file.');
                    return $this->redirectToRoute('app_AddPodcast' , ['_locale' => $langue]);
                }
            }
            $entityManager->persist($podcast);
            $entityManager->flush();

            $this->addFlash('success', 'Podcast edited successfully.');
            return $this->redirectToRoute('app_ShowPodcast' , ['_locale' => $langue]);
        }
        $notifications = [];
        $friendRequests = $addFriendRepository->findBy(['user_id2' => $currentUser, 'confirmation' => 0 , 'notification' => 0]);
        foreach ($friendRequests as $request) {
            $sender = $request->getUserId();
            $photo = null;

            if ($sender) {
                foreach ($sender->getSections() as $section) {
                    if ("PhotoProfile" == $section->getType()) {
                        $expectedImageName = $sender->getId() . 'PhotoProfile.jpg';
                        $sectionImage = $section->getImage();
                        if ($expectedImageName == $sectionImage) {
                            $photo = $sectionImage;
                            break;
                        }
                    }
                }
                $notifications[] = [
                    'requestId' => $request->getId(),
                    'nom' => $sender->getNom(),
                    'prenom' => $sender->getPrenom(),
                    'photo' => $photo,
                ];
            }
        }

        return $this->render('profile/EditPodcast.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
            'podcast' => $podcast,
            'photoProfile' => $photoProfile,
            'notifications' => $notifications,
            'url' => $url,
            'langue' => $langue,
        ]);
    }
    #[Route('/profile/delete/image2/{PdId}/{imageId}', name: 'delete_image2')]
    public function deleteImage2(Request $request, EntityManagerInterface $entityManager, ImageRepository $imageRepository, UserRepository $userRepository, PodcastRepository $podcastRepository , int $PdId,int $imageId): Response
    {
        $session = $this->get('session');
        if (!$session->has('user')) {
            // Rediriger vers la page de connexion ou une autre page
            return $this->redirectToRoute('app_login');
        }
        $currentUser = $session->get('user');

        $user = $userRepository->find($currentUser);
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }


        $podcast = $podcastRepository->find($PdId);
        if (!$podcast) {
            throw $this->createNotFoundException('Podcast not found');
        }

        $form = $this->createForm(AddPodcastType::class, $podcast);
        $form->handleRequest($request);

        $image = $imageRepository->find($imageId);

        $entityManager->remove($image);
        $entityManager->flush();
        $this->addFlash('success', 'Adventure updated successfully.');

        return $this->redirectToRoute('app_EditPodcast', ['PdId' => $PdId]);
    }

    #[Route('/api/reformulate', name: 'api_reformulate', methods: ['POST'])]
    public function reformulate(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $description = $data['description'] ?? '';

        if (empty($description)) {
            return new JsonResponse(['error' => 'Description is empty'], 400);
        }

        // Appel à l'API de reformulation (par exemple, OpenAI)
        $apiKey = $_ENV['OPENAI_KEY'];
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.openai.com/v1/chat/completions",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode([
                "model" => "gpt-3.5-turbo",
                "messages" => [
                    ["role" => "system", "content" => "You are an assistant specialized in text reformulation. Please rephrase the following text without adding any new information."],
                    ["role" => "user", "content" => $description]
                ]
            ]),
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer " . $apiKey,
                "Content-Type: application/json"
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            return new JsonResponse(['error' => 'An error occurred while calling the AI service'], 500);
        } else {
            $responseData = json_decode($response, true);
            $reformulatedText = $responseData['choices'][0]['message']['content'] ?? '';

            return new JsonResponse(['reformulatedText' => $reformulatedText]);
        }
    }

    #[Route('/profile/ShowFriends/{_locale}', name: 'app_friends' , defaults: ['_locale' => 'en'])]
    public function showFriends( UserRepository $userRepository, AddFriendRepository $addFriendRepository ): Response
    {
        $session = $this->get('session');
        $langue = $session->get('_locale');
        $url = 'app_friends';
        if (!$session->has('user')) {
            // Rediriger vers la page de connexion ou une autre page
            return $this->redirectToRoute('app_login' , ['_locale' => $langue]);
        }
        $currentUser = $session->get('user');

        $user = $userRepository->find($currentUser);
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }
        foreach ($user->getSections() as $section) {
            if ("PhotoProfile" == $section->getType()) {
                $expectedImageName = $user->getId() . 'PhotoProfile.jpg';
                $sectionImage = $section->getImage();
                if ($expectedImageName == $sectionImage) {
                    $photoProfile = $sectionImage;
                    break;
                }
            }
        }

        $friends = $this->getFriends($currentUser, $userRepository);
        $notifications = [];
        $friendRequests = $addFriendRepository->findBy(['user_id2' => $currentUser, 'confirmation' => 0 , 'notification' => 0]);
        foreach ($friendRequests as $request) {
            $sender = $request->getUserId();
            $photo = null;

            if ($sender) {
                foreach ($sender->getSections() as $section) {
                    if ("PhotoProfile" == $section->getType()) {
                        $expectedImageName = $sender->getId() . 'PhotoProfile.jpg';
                        $sectionImage = $section->getImage();
                        if ($expectedImageName == $sectionImage) {
                            $photo = $sectionImage;
                            break;
                        }
                    }
                }
                $notifications[] = [
                    'requestId' => $request->getId(),
                    'nom' => $sender->getNom(),
                    'prenom' => $sender->getPrenom(),
                    'photo' => $photo,
                ];
            }
        }

        return $this->render('profile/ShowFriends.html.twig', [
            'user' => $user,
            'friends' => $friends,
            'photoProfile' => $photoProfile,
            'notifications' => $notifications,
            'url' => $url,
            'langue' => $langue
        ]);
    }

    private function getFriends(int $userId, UserRepository $userRepository)
    {
        $friends = [];
        $entityManager = $this->getDoctrine()->getManager();

        // Fetch friendships where the current user initiated the request
        $friendships = $entityManager->getRepository(AddFriend::class)->findBy([
            'User_id' => $userId
        ]);

        foreach ($friendships as $friendship) {
            $friendId = $friendship->getUserId2();
            $friend = $userRepository->find($friendId);
            if ($friend) {
                $friends[] = [
                    'friend' => $friend,
                    'etat' => $friendship->getEtat(),
                    'etat2' => "0",
                    'idRequest' => $friendship->getId(),
                ];
            }
        }

        // Fetch friendships where the current user received the request
        $friendships = $entityManager->getRepository(AddFriend::class)->findBy([
            'user_id2' => $userId
        ]);

        foreach ($friendships as $friendship) {
            $friendId = $friendship->getUserId()->getId();
            $friend = $userRepository->find($friendId);
            if ($friend) {
                $friends[] = [
                    'friend' => $friend,
                    'etat' => $friendship->getEtat(),
                    'etat2' => "1",
                    'idRequest' => $friendship->getId(),
                ];
            }
        }

        return $friends;
    }



    #[Route('/profile/removeFriend/{friendId}', name: 'app_remove_friend')]
    public function removeFriend(int $friendId, EntityManagerInterface $entityManager, SessionInterface $session): Response
    {
        $loggedInUserId = $session->get('user');
        $friendshipRepo = $entityManager->getRepository(AddFriend::class);

        // Chercher l'amitié dans laquelle l'utilisateur est soit id_user soit id_2
        $friendship = $friendshipRepo->findOneBy([
            'User_id' => $loggedInUserId,
            'user_id2' => $friendId,
        ]);

        if (!$friendship) {
            $friendship = $friendshipRepo->findOneBy([
                'User_id' => $friendId,
                'user_id2' => $loggedInUserId,
            ]);
        }

        if ($friendship) {
            $entityManager->remove($friendship);
            $entityManager->flush();

            $this->addFlash('success', 'Friend removed successfully.');
        } else {
            $this->addFlash('error', 'Friend not found.');
        }

        return $this->redirectToRoute('app_friends', ['userId' => $loggedInUserId]);
    }

    #[Route('/profile/AddFriends/{_locale}', name: 'app_addfriends' , defaults: ['_locale' => 'en'])]
    public function addFriends(UserRepository $userRepository,AddFriendRepository $addFriendRepository): Response
    {
        $session = $this->get('session');
        $langue = $session->get('_locale');
        $url = 'app_addfriends';
        if (!$session->has('user')) {
            // Rediriger vers la page de connexion ou une autre page
            return $this->redirectToRoute('app_login' , ['_locale' => $langue]);
        }
        $currentUser = $session->get('user');

        $user = $userRepository->find($currentUser);
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        foreach ($user->getSections() as $section) {
            if ("PhotoProfile" == $section->getType()) {
                $expectedImageName = $user->getId() . 'PhotoProfile.jpg';
                $sectionImage = $section->getImage();
                if ($expectedImageName == $sectionImage) {
                    $photoProfile = $sectionImage;
                    break;
                }
            }
        }

        $users = $userRepository->findAll();
        $usersArray = array_map(function($user) {
            return $user->toArray();
        }, $users);
        $usersJson = json_encode($usersArray);
        $notifications = [];
        $friendRequests = $addFriendRepository->findBy(['user_id2' => $currentUser, 'confirmation' => 0 , 'notification' => 0]);
        foreach ($friendRequests as $request) {
            $sender = $request->getUserId();
            $photo = null;

            if ($sender) {
                foreach ($sender->getSections() as $section) {
                    if ("PhotoProfile" == $section->getType()) {
                        $expectedImageName = $sender->getId() . 'PhotoProfile.jpg';
                        $sectionImage = $section->getImage();
                        if ($expectedImageName == $sectionImage) {
                            $photo = $sectionImage;
                            break;
                        }
                    }
                }
                $notifications[] = [
                    'requestId' => $request->getId(),
                    'nom' => $sender->getNom(),
                    'prenom' => $sender->getPrenom(),
                    'photo' => $photo,
                ];
            }
        }

        return $this->render('profile/AddFriend.html.twig', [
            'user' => $user,
            'usersJson' => $usersJson,
            'photoProfile' => $photoProfile,
            'userId' => $currentUser,
            'notifications' => $notifications,
            'url' => $url,
            'langue' => $langue
        ]);
    }

    #[Route('/profile/addFriendRequest/{userId}', name: 'app_add_friend_request', methods: ['POST'])]
    public function addFriendRequest(UserRepository $userRepository, EntityManagerInterface $entityManager, AddFriendRepository $addFriendRepository, int $userId): Response
    {
        $session = $this->get('session');
        if (!$session->has('user')) {
            return $this->json(['success' => false, 'message' => 'User not logged in.']);
        }

        $currentUser = $session->get('user'); // Assurez-vous que vous obtenez l'utilisateur connecté
        $user = $userRepository->find($currentUser); // Assurez-vous que vous utilisez le bon ID

        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Current user not found.']);
        }

        // Vérifiez si une demande d'ami existe déjà dans un sens ou l'autre
        $existingFriendRequest = $addFriendRepository->findOneBy([
            'User_id' => $user,
            'user_id2' => $userId
        ]) ?? $addFriendRepository->findOneBy([
            'User_id' => $userId,
            'user_id2' => $currentUser
        ]);

        if ($existingFriendRequest) {
            return $this->json(['success' => false, 'message' => 'Friend request already exists.']);
        }

        // Création d'une nouvelle demande d'ami
        $addFriend = new AddFriend();
        $addFriend->setUserId($user);
        $addFriend->setUserId2($userId);
        $addFriend->setConfirmation(0);
        $addFriend->setEtat("Pending");
        $addFriend->setNotification(0);

        $entityManager->persist($addFriend);
        $entityManager->flush();

        return $this->json(['success' => true, 'message' => 'Friend request sent.']);
    }

    #[Route('/profile/addFriendRequest2/{userId}', name: 'app_add_friend_request2', methods: ['POST'])]
    public function addFriendRequest2(Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager, AddFriendRepository $addFriendRepository, int $userId): Response
    {
        $session = $this->get('session');
        if (!$session->has('user')) {
            return $this->json(['success' => false, 'message' => 'User not logged in.']);
        }

        $currentUser = $session->get('user'); // Assurez-vous que vous obtenez l'utilisateur connecté
        $user = $userRepository->find($currentUser); // Assurez-vous que vous utilisez le bon ID
        $user2 = $userRepository->find($userId); // Assurez-vous que vous utilisez le bon ID

        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Current user not found.']);
        }

        // Vérifiez si une demande d'ami existe déjà dans un sens ou l'autre
        $existingFriendRequest = $addFriendRepository->findOneBy([
            'User_id' => $user,
            'user_id2' => $userId
        ]) ?? $addFriendRepository->findOneBy([
            'User_id' => $userId,
            'user_id2' => $currentUser
        ]);

        if ($existingFriendRequest) {
            return $this->json(['success' => false, 'message' => 'Friend request already exists.']);
        }

        // Création d'une nouvelle demande d'ami
        $addFriend = new AddFriend();
        $addFriend->setUserId($user);
        $addFriend->setUserId2($userId);
        $addFriend->setConfirmation(0);
        $addFriend->setEtat("Pending");

        $entityManager->persist($addFriend);
        $entityManager->flush();

        // Redirigez vers l'URL précédente ou une URL par défaut
        $previousUrl = $request->request->get('previousUrl');
        if (empty($previousUrl)) {
            $previousUrl = $this->generateUrl('app_blog', ['slug' => $user2->getPageNom()]); // Changez 'app_profile' selon vos besoins
        }

        return $this->redirect($previousUrl);
    }


    #[Route('/profile/respondToFriendRequest/{requestId}/{action}', name: 'app_respond_to_friend_request')]
    public function respondToFriendRequest(
        int $requestId,
        string $action,
        AddFriendRepository $addFriendRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $session = $this->get('session');
        if (!$session->has('user')) {
            return $this->redirectToRoute('app_login');
        }

        $currentUserId = $session->get('user'); // ID de l'utilisateur connecté

        $friendRequest = $addFriendRepository->find($requestId);
        if (!$friendRequest) {
            $this->addFlash('error', 'Request not found.');
            return $this->redirectToRoute('app_friends', ['userId' => $currentUserId]);
        }
        if ($action === 'accept') {
            // Mettre à jour la demande pour indiquer l'acceptation
            $friendRequest->setConfirmation(1);
            $friendRequest->setEtat("Accepted");
            $entityManager->persist($friendRequest);
            $entityManager->flush();
            $this->addFlash('success', 'Request accepted.');
        } elseif ($action === 'decline') {
            // Supprimer la demande d'ami
            $entityManager->remove($friendRequest);
            $entityManager->flush();
            $this->addFlash('success', 'Request decline.');
        } else {
            $this->addFlash('error', 'Invalid action.');
            return $this->redirectToRoute('app_friends', ['userId' => $currentUserId]);
        }
        return $this->redirectToRoute('app_friends', ['userId' => $currentUserId]);
    }



}
