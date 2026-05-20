<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\FormLoginAuthenticator;
use Symfony\Component\Validator\Constraints\PasswordStrength;

class LoginController extends AbstractController
{
    #[Route('/check_user', name: 'check_user', methods: ['GET', 'POST'])] // Dit is geen pagina maar een post api net als we maakten in de aparte check_user.php file
        // Zowel GET als POST toegevoegd als accepted method om check_user_test.http te kunnen uitvoeren
    public function checkUser(Request $request, UserRepository $userRepository): Response {
        // request is via json dus eerst nog decoden
        $field = $request->request->get('field'); // Kan 'username' of 'email' zijn
        $value = $request->request->get('value'); //  De meegegeven 'username' of 'password'
        $allowedFields = ['email', 'username'];
        if(!in_array($field, $allowedFields) || empty($value)) { // we willen natuurlijk geen andere requests aannemen die niet zijn zoals we verwachten
            return $this->json(['available' => false, 'error' => 'Ongeldig verzoek.'], Response::HTTP_BAD_REQUEST);
        } else {
            $user = $userRepository->findOneBy([$field => $value]); // checken of de username al in gebruik is in de database
            if(!$user) {
                return $this->json(['available' => true], Response::HTTP_OK);
            } else {
                return $this->json(['available' => false], Response::HTTP_OK);
            }
        }
    }
    #[Route(path: '/login', name: 'login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }
    #[Route('/register', name: 'register')]
    public function register(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher, UserAuthenticatorInterface $userAuthenticator, FormLoginAuthenticator $authenticator): Response
    {
        $user = new User();
        $registerForm = $this->createFormBuilder($user)
            ->add('firstName', TextType::class, ['attr' => ['required' => 'true']])
            ->add('lastName', TextType::class, ['attr' => ['required' => 'true']])
            ->add('username', TextType::class, ['attr' => ['required' => 'true']])
            ->add('email', EmailType::class, ['attr' => ['required' => 'true', 'placeholder' => 'example@example.com']])
            ->add('password', PasswordType::class, [
                'attr' => ['required' => true],
                'constraints' => [
                    new PasswordStrength(
                        minScore: PasswordStrength::STRENGTH_WEAK, // Hiermee kan je de strength aangeven. Als het niet sterk genoeg is wordt het weergegeven als een statusMessage
                        message: 'Your password is too weak. Try adding numbers or special characters.',
                    ),
                ],
            ])
            ->add('passwordCheck', PasswordType::class, [
                'mapped' => false,
                'attr' => ['required' => true],
            ])
            ->add('birthDate', DateType::class, [
                'widget' => 'single_text',
                'attr' => [
                    'required' => true,
                    'max' => (new \DateTimeImmutable('today'))->format('Y-m-d'), // want geboortedatum mag niet in de toekomst liggen
                ],
            ])
            ->getForm();
        $registerForm->handleRequest($request);
        if ($registerForm->isSubmitted() && $registerForm->isValid()) {
            $user = $registerForm->getData();
            $hashedPassword = $passwordHasher->hashPassword($user, $user->getPassword());
            $user->setPassword($hashedPassword);
            $user->setRoles(['ROLE_USER']); // Standaard geven we een nieuwe user de rol USER
            $em->persist($user); // De EntityManager van doctrine ziet dat dit een Feedback object is dus weet zo in welke tabel hij de data moet toevoegen
            $em->flush();
            return $userAuthenticator->authenticateUser(
                $user,
                $authenticator,
                $request
            );
        }
        return $this->render('register.html.twig', ['registerForm' => $registerForm->createView()]);
    }
}
