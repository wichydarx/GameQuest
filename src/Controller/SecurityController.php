<?php

namespace App\Controller;

use App\Service\SendMail;
use App\Form\ResetPassWordType;
use App\Repository\UserRepository;
use App\Form\PasswordResetFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }


    #[Route(path: '/forgotten-password', name: 'app_forgotten_password')]
    public function forgottenPassword(Request $request,
    UserRepository $userRepository,
    TokenGeneratorInterface $tokenGeneratorInterface,
    EntityManagerInterface $eM,
    SendMail $sendMail
    ): Response
    {
        $form = $this->createForm(PasswordResetFormType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()){
            $user = $userRepository->findOneByEmail($form->get('email')->getData());

            if ($user) {
                $token = $tokenGeneratorInterface->generateToken();
                $user->setResetToken($token);
                $eM->persist($user);
                $eM->flush();
                
                //reset link
                $url = $this->generateUrl('app_password_reset', ['token' => $token],UrlGeneratorInterface::ABSOLUTE_URL);
                
                $context =compact('url', 'user');

                $sendMail->sendRegistrationMail(
                    'no-reply@GameGuest.com',
                    $user->getEmail(),
                    'Reset your password',
                    'password-reset',
                    $context
                );
                $this->addFlash('success', 'A password reset link has been sent to your email');
                return $this->redirectToRoute('app_login');
            }
            $this->addFlash('danger', 'An error has occured, please try again.');
            return $this->redirectToRoute('app_login');
        }
        return $this->render(
            'security/reset_password_request.html.twig',
            [
                "reset_form" => $form->createView()
            ]
        );
    }

    #[Route(path: '/forgotten-password/reset', name: 'app_password_reset')]
    public function resetPassword(string $token,
    Request $request,
    UserRepository $userRepository,
    EntityManagerInterface $eM,
    UserPasswordHasherInterface $hasher
    ): Response
    {
        $user = $userRepository->findOneByResetToken($token);
        if ($user) {
            $form = $this->createForm(ResetPassWordType::class);
            $form = $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $user->setResetToken("");
                $user->setPassword($hasher->hashPassword($user, $form->get('password')->getData()));
                
                $eM->persist($user);
                $eM->flush();
                
                $this->addFlash('success', 'Your password has been reset');
                return $this->redirectToRoute('app_login');
            }

            return $this->render('security/reset_password.html.twig', [
                'form' => $form->createView()
            ]);
        }
        $this->addFlash('danger', 'An error has occured, please try again.');
        return $this->redirectToRoute('app_login');
    }
}