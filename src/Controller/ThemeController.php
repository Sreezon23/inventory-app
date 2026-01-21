<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/theme')]
class ThemeController extends AbstractController
{
    #[Route('/switch', name: 'theme_switch', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function switchTheme(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $theme = $request->request->get('theme');
        
        if (!in_array($theme, ['light', 'dark'])) {
            return new JsonResponse(['error' => 'Invalid theme'], 400);
        }

        $user = $this->getUser();
        $user->setTheme($theme);
        
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'theme' => $theme,
            'message' => 'Theme switched to ' . $theme . ' mode'
        ]);
    }

    #[Route('/current', name: 'theme_current', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getCurrentTheme(): JsonResponse
    {
        $user = $this->getUser();
        
        return new JsonResponse([
            'theme' => $user->getTheme(),
        ]);
    }
}
