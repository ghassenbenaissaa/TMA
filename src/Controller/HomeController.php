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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Security;


class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index0(Security $security): Response
    {
        $session = $this->get('session');
        if ($session->has('user')) {
            return $this->redirectToRoute('app_home_authenticated');
        }
        return $this->redirectToRoute('app_home_unauthenticated');
    }

    #[Route('/home', name: 'app_home_authenticated')]
    public function index00(): Response
    {
        $session = $this->get('session');
        $user_id = $session->get('user');
        if (!$session->has('user')) {
            return $this->redirectToRoute('app_home_unauthenticated');
        }
        return $this->render('home/index1.html.twig', [
            'userId' => $user_id,
        ]);
    }

    #[Route('/homeA', name: 'app_home_unauthenticated')]
    public function index000(): Response
    {
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Le logout est géré par Symfony, pas besoin de logique ici.
    }


    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher, SessionInterface $session): Response
    {
        $form = $this->createForm(LoginType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $email = $form->get('email')->getData();
            $password = $form->get('mdp')->getData();

            $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

            if (!$user) {
                $this->addFlash('error', 'Email not found');
                return $this->redirectToRoute('app_login');
            }

            if (!$passwordHasher->isPasswordValid($user, $password)) {
                $this->addFlash('error', 'Invalid password');
                return $this->redirectToRoute('app_login');
            }

            if (is_null($user->getSubscription()) ) {
                $session->set('user', $user->getId());
                return $this->redirectToRoute('app_form4');
            }

            // Vérifier si le type ou le thème est null
            if (is_null($user->getType()) || is_null($user->getTheme())) {
                $session->set('user', $user->getId());
                return $this->redirectToRoute('app_form1');
            }

            // Vérifier si le nom de la page ou le logo est null
            if (is_null($user->getPageNom()) || is_null($user->getLogo())) {
                $session->set('user', $user->getId());
                return $this->redirectToRoute('app_form2');
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
                return $this->redirectToRoute('app_form3');
            }
            $session->set('user', $user->getId());
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('home/login.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    #[Route('/signup', name: 'app_signup', methods: ['GET', 'POST'])]
    public function signup(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher, UserRepository $userRepository , SessionInterface $session): Response
    {
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
                ]);
            }

            $password = $form->get('mdp')->getData();
            $confirmPassword = $form->get('Cmdp')->getData();

            if ($password !== $confirmPassword) {
                $this->addFlash('error', 'The passwords do not match.');
                return $this->render('home/SignUp.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            $hashedPassword = $passwordHasher->hashPassword($user, $password);
            $user->setMdp($hashedPassword);

            $user->setSystemDate(new \DateTime());

            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('app_login');
        }

        return $this->render('home/SignUp.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/signup1', name: 'app_form1', methods: ['GET', 'POST'])]
    public function form1( Request $request, EntityManagerInterface $entityManager, UserRepository $userRepository , SessionInterface $session): Response
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

        $form = $this->createForm(Form1Type::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $entityManager->flush();
            return $this->redirectToRoute('app_form2');
        }

        return $this->render('home/form1.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/signup2', name: 'app_form2', methods: ['GET', 'POST'])]
    public function form2( Request $request, UserRepository $userRepository,ParameterBagInterface $params , SessionInterface $session): Response {
        $logos_directory = $params->get('logos_directory');
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

        $form = $this->createForm(Form2Type::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // Vérifier si le nom de la page existe déjà
            $existingUser = $userRepository->findOneBy(['pageNom' => $user->getPageNom()]);
            if ($existingUser) {
                $this->addFlash('error', 'The page name already exists.');
                return $this->render('home/form2.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            $logoFile = $form['logo']->getData();
            if ($logoFile) {
                // Vérifier l'extension du fichier
                if ($logoFile->guessExtension() !== 'png') {
                    $this->addFlash('error', 'The file is not compatible. Only PNG files are allowed.');
                    return $this->redirectToRoute('app_form2');
                }

                $newFilename = $user->getPageNom() . $user->getId() . '.' . $logoFile->guessExtension();
                try {
                    $logoFile->move($logos_directory, $newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'An error occurred while uploading the file.');
                    return $this->redirectToRoute('app_form2');
                }
                $user->setLogo($newFilename);
            }


            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->flush();
            return $this->redirectToRoute('app_form3');
        }

        return $this->render('home/form2.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/signup3', name: 'app_form3', methods: ['GET', 'POST'])]
    public function form3(EntityManagerInterface $entityManager, Request $request, UserRepository $userRepository,SectionRepository $sectionRepository, ParameterBagInterface $params , SessionInterface $session): Response {
        $logos_directory = $params->get('logos_directory');
        $session = $this->get('session');
        if (!$session->has('user')) {
            // Rediriger vers la page de connexion ou une autre page
            return $this->redirectToRoute('app_login');
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
                    return $this->redirectToRoute('app_form3');
                }

            }

            $entityManager->persist($ProfilePhotoSection);
            $entityManager->flush();
            $session->set('user', $user->getId());
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('home/form3.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/signup4', name: 'app_form4', methods: ['GET', 'POST'])]
    public function form4(Request $request, EntityManagerInterface $entityManager, UserRepository $userRepository): Response
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
                        return $this->redirectToRoute('app_form1');
                    }
                    return $this->redirectToRoute('app_profile');
                }else{
                    if (is_null($user->getType()) || is_null($user->getTheme())) {
                        $session->set('user', $user->getId());
                        return $this->redirectToRoute('app_form1');
                    }
                    return $this->redirectToRoute('app_profile');
                }
            }

            return $this->redirectToRoute('app_form4');
        }

        return $this->render('home/form4.html.twig', [
            'userId' => $currentUser,
        ]);
    }




}
