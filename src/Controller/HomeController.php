<?php

namespace App\Controller;

use App\Entity\Section;
use App\Entity\User;
use App\Form\Form1Type;
use App\Form\Form2Type;
use App\Form\Form3Type;
use App\Form\LoginType;
use App\Form\SignUpType;
use App\Repository\SectionRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;


class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index0(Security $security): Response
    {
        $session = $this->get('session');
        $langue = $session->get('_locale');
        if ($session->has('user')) {
            return $this->redirectToRoute('app_home_authenticated', ['_locale' => $langue]);
        }
        return $this->redirectToRoute('app_home_unauthenticated', ['_locale' => $langue]);
    }

    #[Route('/home/{_locale}', name: 'app_home_authenticated', defaults: ['_locale' => 'en'])]
    public function index00(): Response
    {
        $session = $this->get('session');
        $langue = $session->get('_locale');
        $url = 'app_home_authenticated';
        $user_id = $session->get('user');
        if (!$session->has('user')) {
            return $this->redirectToRoute('app_home_unauthenticated', ['_locale' => $langue]);
        }
        return $this->render('home/index1.html.twig', [
            'userId' => $user_id,
            'url' => $url,
            'langue' => $langue,
        ]);
    }

    #[Route('/change-locale/{locale}/{url}', name: 'change_locale')]
    public function changeLocale($locale, $url, Request $request)
    {
        $request->getSession()->set('_locale', $locale);

        return $this->redirectToRoute($url, [
            '_locale' => $locale,
        ]);
    }

    #[Route('/homeA/{_locale}', name: 'app_home_unauthenticated', defaults: ['_locale' => 'en'])]
    public function index000(): Response
    {
        $session = $this->get('session');
        $langue = $session->get('_locale');
        $url = 'app_home_unauthenticated';
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'url' => $url,
            'langue' => $langue,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Le logout est géré par Symfony, pas besoin de logique ici.
    }


    #[Route('/login/{_locale}', name: 'app_login', defaults: ['_locale' => 'en'], methods: ['GET', 'POST'])]
    public function login(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher, SessionInterface $session): Response
    {
        $session = $this->get('session');
        $langue = $session->get('_locale');
        if (!$langue)
            $langue = 'en';
        $url = 'app_login';
        $form = $this->createForm(LoginType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $email = $form->get('email')->getData();
            $password = $form->get('mdp')->getData();

            $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

            if (!$user) {
                $this->addFlash('error', 'Email not found');
                return $this->redirectToRoute('app_login', ['_locale' => $langue] );
            }

            if (!$passwordHasher->isPasswordValid($user, $password)) {
                $this->addFlash('error', 'Invalid password');
                return $this->redirectToRoute('app_login', ['_locale' => $langue]);
            }

            if (is_null($user->getSubscription()) ) {
                $session->set('user', $user->getId());
                return $this->redirectToRoute('app_form4', ['_locale' => $langue]);
            }

            // Vérifier si le type ou le thème est null
            if (is_null($user->getType()) || is_null($user->getTheme())) {
                $session->set('user', $user->getId());
                return $this->redirectToRoute('app_form1', ['_locale' => $langue]);
            }

            // Vérifier si le nom de la page ou le logo est null
            if (is_null($user->getPageNom()) || is_null($user->getLogo())) {
                $session->set('user', $user->getId());
                return $this->redirectToRoute('app_form2', ['_locale' => $langue]);
            }

            $photoProfile = null;

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
            if (is_null($photoProfile)) {
                $session->set('user', $user->getId());
                return $this->redirectToRoute('app_form3', ['_locale' => $langue]);
            }
            $session->set('user', $user->getId());
            return $this->redirectToRoute('app_profile', ['_locale' => $langue]);
        }

        return $this->render('home/login.html.twig', [
            'form' => $form->createView(),
            'url' => $url,
            'langue' => $langue,
        ]);
    }


    #[Route('/signup/{_locale}', name: 'app_signup', defaults: ['_locale' => 'en'], methods: ['GET', 'POST'])]
    public function signup(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher, UserRepository $userRepository , SessionInterface $session): Response
    {
        $session = $this->get('session');
        $langue = $session->get('_locale');
        $url = 'app_signup';
        $form = $this->createForm(SignUpType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();

            $email = $user->getEmail();

            // Check if email already exists
            if ($userRepository->findOneBy(['email' => $email])) {
                $this->addFlash('error', 'The email already exists.');
                return $this->render('home/SignUp.html.twig', [
                    'form' => $form->createView(),
                    'url' => $url,
                    'langue' => $langue,
                ]);
            }

            $password = $form->get('mdp')->getData();
            $confirmPassword = $form->get('Cmdp')->getData();

            if ($password !== $confirmPassword) {
                $this->addFlash('error', 'The passwords do not match.');
                return $this->render('home/SignUp.html.twig', [
                    'form' => $form->createView(),
                    'url' => $url,
                    'langue' => $langue,
                ]);
            }

            $hashedPassword = $passwordHasher->hashPassword($user, $password);
            $user->setMdp($hashedPassword);

            $user->setSystemDate(new \DateTime());

            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('app_login', ['_locale' => $langue]);
        }

        return $this->render('home/SignUp.html.twig', [
            'form' => $form->createView(),
            'url' => $url,
            'langue' => $langue,
        ]);
    }

    #[Route('/signup1/{_locale}', name: 'app_form1', defaults: ['_locale' => 'en'], methods: ['GET', 'POST'])]
    public function form1( Request $request, EntityManagerInterface $entityManager, UserRepository $userRepository , SessionInterface $session): Response
    {
        $session = $this->get('session');
        $langue = $session->get('_locale');
        $url = 'app_form1';
        if (!$session->has('user')) {
            // Rediriger vers la page de connexion ou une autre page
            return $this->redirectToRoute('app_login', ['_locale' => $langue]);
        }
        $currentUser = $session->get('user');
        $user = $userRepository->find($currentUser);

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        $form = $this->createForm(Form1Type::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $entityManager->flush();
            return $this->redirectToRoute('app_form2', ['_locale' => $langue]);
        }

        return $this->render('home/form1.html.twig', [
            'form' => $form->createView(),
            'url' => $url,
            'langue' => $langue,
        ]);
    }

    #[Route('/signup2/{_locale}', name: 'app_form2', defaults: ['_locale' => 'en'], methods: ['GET', 'POST'])]
    public function form2( Request $request, UserRepository $userRepository,ParameterBagInterface $params , SessionInterface $session): Response {
        $logos_directory = $params->get('logos_directory');
        $session = $this->get('session');
        $langue = $session->get('_locale');
        $url = 'app_form2';
        if (!$session->has('user')) {
            // Rediriger vers la page de connexion ou une autre page
            return $this->redirectToRoute('app_login', ['_locale' => $langue]);
        }
        $currentUser = $session->get('user');

        $user = $userRepository->find($currentUser);

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        $form = $this->createForm(Form2Type::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // Vérifier si le nom de la page existe déjà
            $existingUser = $userRepository->findOneBy(['pageNom' => $user->getPageNom()]);
            if ($existingUser) {
                $this->addFlash('error', 'The page name already exists.');
                return $this->render('home/form2.html.twig', [
                    'form' => $form->createView(),
                    'url' => $url,
                    'langue' => $langue,
                ]);
            }

            $logoFile = $form['logo']->getData();
            if ($logoFile) {
                // Vérifier l'extension du fichier
                if ($logoFile->guessExtension() !== 'png') {
                    $this->addFlash('error', 'The file is not compatible. Only PNG files are allowed.');
                    return $this->redirectToRoute('app_form2' , ['_locale' => $langue]);
                }

                $newFilename = $user->getPageNom() . $user->getId() . '.' . $logoFile->guessExtension();
                try {
                    $logoFile->move($logos_directory, $newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'An error occurred while uploading the file.');
                    return $this->redirectToRoute('app_form2' , ['_locale' => $langue]);
                }
                $user->setLogo($newFilename);
            }


            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->flush();
            return $this->redirectToRoute('app_form3' , ['_locale' => $langue]);
        }

        return $this->render('home/form2.html.twig', [
            'form' => $form->createView(),
            'url' => $url,
            'langue' => $langue,
        ]);
    }

    #[Route('/signup3/{_locale}', name: 'app_form3', defaults: ['_locale' => 'en'], methods: ['GET', 'POST'])]
    public function form3(EntityManagerInterface $entityManager, Request $request, UserRepository $userRepository,SectionRepository $sectionRepository, ParameterBagInterface $params , SessionInterface $session): Response {
        $logos_directory = $params->get('logos_directory');
        $session = $this->get('session');
        $langue = $session->get('_locale');
        $url = 'app_form3';
        if (!$session->has('user')) {
            // Rediriger vers la page de connexion ou une autre page
            return $this->redirectToRoute('app_login' , ['_locale' => $langue]);
        }
        $currentUser = $session->get('user');
        $user = $userRepository->find($currentUser);
        $ProfilePhotoSection = $sectionRepository->findOneBy(['id_user' => $currentUser, 'type' => 'PhotoProfile']);

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        $form = $this->createForm(Form3Type::class, $user);
        $form->handleRequest($request);

        if (!$ProfilePhotoSection) {
            $ProfilePhotoSection = new Section();
            $ProfilePhotoSection->setType('PhotoProfile');
            $ProfilePhotoSection->setIdUser($user);
            $ProfilePhotoSection->setDescription('PhotoProfile');
        }

        if ($form->isSubmitted()) {
            $PhotoFile = $form['PhotoProfile']->getData();
            if ($PhotoFile) {
                $newFilename = $user->getId() . 'PhotoProfile' . '.' . $PhotoFile->guessExtension();
                try {
                    $PhotoFile->move($logos_directory, $newFilename);
                    $ProfilePhotoSection->setImage($newFilename);
                } catch (FileException $e) {
                    $errorMessage = sprintf('An error occurred while uploading the file: %s', $e->getMessage());
                    $this->addFlash('error', $errorMessage);
                    return $this->redirectToRoute('app_form3' , ['_locale' => $langue]);
                }

            }

            $entityManager->persist($ProfilePhotoSection);
            $entityManager->flush();
            $session->set('user', $user->getId());
            return $this->redirectToRoute('app_profile' , ['_locale' => $langue]);
        }

        return $this->render('home/form3.html.twig', [
            'form' => $form->createView(),
            'url' => $url,
            'langue' => $langue,
        ]);
    }

    #[Route('/signup4/{_locale}', name: 'app_form4', defaults: ['_locale' => 'en'], methods: ['GET', 'POST'])]
    public function form4(Request $request, EntityManagerInterface $entityManager, UserRepository $userRepository): Response
    {
        $session = $this->get('session');
        $langue = $session->get('_locale');
        $url = 'app_form4';
        if (!$session->has('user')) {
            // Rediriger vers la page de connexion ou une autre page
            return $this->redirectToRoute('app_login' , ['_locale' => $langue]);
        }
        $currentUser = $session->get('user');
        $user = $userRepository->find($currentUser);

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        if ($request->isMethod('POST')) {
            $subscriptionType = $request->request->get('subscription');

            if ($subscriptionType !== null) {
                // Assuming you have a 'subscription' field in the User entity
                $user->setSubscription($subscriptionType);
                $entityManager->flush();
                if ($subscriptionType==1){
                    $this->addFlash('info', 'For now, our website is free just for testing purposes.');
                    if (is_null($user->getType()) || is_null($user->getTheme())) {
                        $session->set('user', $user->getId());
                        return $this->redirectToRoute('app_form1' , ['_locale' => $langue]);
                    }
                    return $this->redirectToRoute('app_profile' , ['_locale' => $langue]);
                }else{
                    if (is_null($user->getType()) || is_null($user->getTheme())) {
                        $session->set('user', $user->getId());
                        return $this->redirectToRoute('app_form1' , ['_locale' => $langue]);
                    }
                    return $this->redirectToRoute('app_profile' , ['_locale' => $langue]);
                }
            }

            return $this->redirectToRoute('app_form4' , ['_locale' => $langue]);
        }

        return $this->render('home/form4.html.twig', [
            'userId' => $currentUser,
            'url' => $url,
            'langue' => $langue,
        ]);
    }




}
