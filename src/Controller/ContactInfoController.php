<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ContactInfoController extends AbstractController
{
    #[Route('/contactInfo', name: 'contactInfo')]
    public function root(): Response
    {
        return $this->render('contactInfo.html.twig');
    }
}
