<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;
use App\Repository\FriendRepository;


class ProfileSettingsController extends AbstractController
{
    #[Route('/profileSettings', name: 'profileSettings')]
    public function index(): Response
    {
        $user = $this->getUser();
        return $this->render('profileSettings.html.twig', [
            'profilePicture' => $user->getProfilePicture() ?: 'uploads/profile_pictures/default.png'
        ]);
    }

    #[Route('/updateProfile', name: 'updateProfile')]
    public function updateProfile(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        $firstName = $request->request->get('firstName');
        $lastName = $request->request->get('lastName');
        $username = $request->request->get('username');
        $email = $request->request->get('email');
        $birthDate = $request->request->get('birthDate');
        $profilePicture = $request->files->get('profilePicture');

        if(!empty($firstName)){
            $user->setFirstName($firstName);
        }

        if(!empty($lastName)){
            $user->setLastName($lastName);
        }

        if(!empty($username)){
            $user->setUsername($username);
        }

        if(!empty($email)){
            $user->setEmail($email);
        }

        if(!empty($birthDate)){
            $user->setBirthDate(new \DateTime($birthDate));
        }

        if ($request->request->get('deleteProfilePicture')) {
            $profilePicture = $user->getProfilePicture();

            $fullPath = $this->getParameter('kernel.project_dir') . '/public/' . $profilePicture;

            if ($profilePicture && $profilePicture !== 'uploads/profile_pictures/default.png' && file_exists($fullPath)) {

                try {
                    unlink($fullPath); // verwijderd bstand van de server
                } catch (\Throwable $e) {
                    // negeer fout bestand mocht niet verwijderd worden dan
                }

            }

            $user->setProfilePicture('uploads/profile_pictures/default.png');

            $em->flush();

            return $this->redirectToRoute('profileSettings');
        }

        // in php.ini extension=fileinfo moeten uncommenten
        if($profilePicture){
            $extension = $profilePicture->getClientOriginalExtension() ?: 'jpg';
            $newFilename = uniqid() . '.' . $extension;
            $uploadDir = $this->getParameter('kernel.project_dir').'/public/uploads/profile_pictures';

            $profilePicture->move($uploadDir, $newFilename);
            $user->setProfilePicture('uploads/profile_pictures/'.$newFilename);
        }

        $em->flush();

        return $this->redirectToRoute('profileSettings');
    }


    // soft delete because we want to keep de matches that the user played because there are also other players
    // and that would not be nice for the other user that their match gets deleted
    #[Route('/deleteAccount', name: 'deleteAccount')]
    public function deleteAccount(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher, FriendRepository $friendRepository): Response
    {
        $user = $this->getUser();

        if(!$user)
        {
            throw $this->createNotFoundException('##Personal message## Problem in database, user not found');
        }

        $user->setFirstName('Deleted');
        $user->setLastName('Deleted');
        $user->setEmail('deleted'.$user->getId().'@deleted.com');
        $user->setUsername('deleted'.$user->getId());
        $user->setIsActive(false);
        $randomPassword = Uuid::v4()->toRfc4122(); // genereate unique string
        $hashed = $passwordHasher->hashPassword($user, $randomPassword);
        $user->setPassword($hashed);
        $user->setProfilePicture('uploads/profile_pictures/deleted.png');

        $em->flush();

        $request->getSession()->invalidate(); // session is deleted and we go back to login screen

        return $this->redirectToRoute('login');
    }

    #[Route('/changePassword', name: 'changePassword')]
    public function changePassword(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $this->getUser();

        if(!$user)
        {
            throw $this->createNotFoundException('##Personal message## Problem in database, user not found');
        }

        $password1 = $request->request->get('password1');
        $password2 = $request->request->get('password2');

        // extra check for safety (so not only frontend check)

        if(empty($password1) || empty($password2)){
            return $this->redirectToRoute('profileSettings');
        }

        if($password1 !== $password2){
            return $this->redirectToRoute('profileSettings');
        }

        $hashedPassword = $passwordHasher->hashPassword($user, $password1);
        $user->setPassword($hashedPassword);

        $em->flush();

        return $this->redirectToRoute('profileSettings');
    }

    #[Route(path: '/check_old_password', name: 'check_old_password')]
    public function checkOldPassword(Request $request, UserPasswordHasherInterface $passwordHasher): Response {
        $value = $request->request->get('value');
        $user = $this->getUser();

        if (!$user || empty($value)) {
            return $this->json(['checkOk' => false]);
        }

        if ($passwordHasher->isPasswordValid($user, $value)) {
            return $this->json(['checkOk' => true]);
        }

        return $this->json(['checkOk' => false]);
    }

    #[Route(path: '/logout', name: 'logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

}
