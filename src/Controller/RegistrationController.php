<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\SendMail;
use App\Service\JWTService;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, SendMail $sendMail, JWTService $jwt, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $entityManager->persist($user);
            $entityManager->flush();
            // do anything else you need here, like send an email
            //token generation

            $header = [
                'alg' => 'HS256',
                'typ' => 'JWT',
            ];

            $payload = [
                'user_id' => $user->getId(),
            ];

            $token = $jwt->generate( $header,$payload, $this->getParameter('app.jwtencryptkey'));
            dd($token);
            //send mail

            $sendMail->sendRegistrationMail(
                'no-reply@GameQuest.com',
                $user->getEmail(),
                'Verify your email',
                'verified',
                compact('user', 'token')
            );

            return $this->redirectToRoute('app_home');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }


    #[Route('/verif/{token}', name: 'verify_user')]
   
    public function verifyUser($token, JWTService $jwt, UserRepository $userRepository, EntityManagerInterface $em): Response
    {

        if ($jwt->isValid($token) && !$jwt->isExpired($token) && $jwt->checkSignature($token, $this->getParameter('app.jwtencryptkey'))) {
            dd($token);
            $payload = $jwt->getPayloadFromToken($token);

            dd($payload);
            $user = $userRepository->find($payload['user_id']);


            if ($user && !$user->getIsVerified()) {
                $user->setIsVerified(true);
                $em->flush($user);
                $this->addFlash('success', 'Your email has been verified!');
                return $this->redirectToRoute('app_home');
            }
        }

        $this->addFlash('danger', 'Your token is invalid or has expired.');
        return $this->redirectToRoute('app_login');
    }

    #[Route('/sendveirfylink', name: 'send_veirfy_link')]
    public function sendVerificationResetLink(JWTService $jwt, SendMail $sendMail, UserRepository $userRepository): Response
    {
        $user =$this->getUser();
        if (!$user) {
            $this->addFlash('danger', 'Please login');
            return $this ->redirectToRoute('app_login');
        }

        if ($user->getIsVerified()) {
            $this->addFlash('danger', 'Your email has been verified!');
            return $this->redirectToRoute('app_home');
        }

        $header = [
            'type' => 'JWT',
            'alg' => 'HS256'
        ];

        $payload = [
            'user_id' => $user->getId(),
        ];

        $token = $jwt->generate($payload, $header, $this->getParameter('app.jwtencryptkey'));

        //send mail

        $sendMail->sendRegistrationMail(
            'no-reply@GameQuest.com',
            $user->getEmail(),
            'Verify your email',
            'verified',
            compact('user', 'token')
        );
        
        $this ->addFlash('success', 'The email has been sent!');
        return $this->redirectToRoute('app_home');
    }
}