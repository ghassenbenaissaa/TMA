<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\Form1Type;
use App\Form\Form2Type;
use App\Form\LoginType;
use App\Form\SignUpType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }


    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
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

            // Vérifier si le type ou le thème est null
            if (is_null($user->getType()) || is_null($user->getTheme())) {
                return $this->redirectToRoute('app_form1', ['userId' => $user->getId()]);
            }

            // Vérifier si le nom de la page ou le logo est null
            if (is_null($user->getPageNom()) || is_null($user->getLogo())) {
                return $this->redirectToRoute('app_form2', ['userId' => $user->getId()]);
            }
            return $this->redirectToRoute('app_profile', ['userId' => $user->getId()]);
        }

        return $this->render('home/login.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    #[Route('/signup', name: 'app_signup', methods: ['GET', 'POST'])]
    public function signup(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher, UserRepository $userRepository): Response
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

    #[Route('/signup1/{userId}', name: 'app_form1', methods: ['GET', 'POST'])]
    public function form1(int $userId, Request $request, EntityManagerInterface $entityManager, UserRepository $userRepository): Response
    {
        $user = $userRepository->find($userId);

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        $form = $this->createForm(Form1Type::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $entityManager->flush();
            return $this->redirectToRoute('app_form2', ['userId' => $userId]);
        }

        return $this->render('home/form1.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/signup2/{userId}', name: 'app_form2', methods: ['GET', 'POST'])]
    public function form2(int $userId, Request $request, UserRepository $userRepository,ParameterBagInterface $params): Response {
        $logos_directory = $params->get('logos_directory');

        $user = $userRepository->find($userId);

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
                    return $this->redirectToRoute('app_form2', ['userId' => $userId]);
                }

                $newFilename = $user->getPageNom() . '.' . $logoFile->guessExtension();
                try {
                    $logoFile->move($logos_directory, $newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'An error occurred while uploading the file.');
                    return $this->redirectToRoute('app_form2', ['userId' => $userId]);
                }
                $user->setLogo($newFilename);
            }


            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->flush();
            return $this->redirectToRoute('app_profile', ['userId' => $user->getId()]);
        }

        return $this->render('home/form2.html.twig', [
            'form' => $form->createView(),
        ]);
    }



}
