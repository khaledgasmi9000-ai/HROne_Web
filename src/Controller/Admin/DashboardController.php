<?php

namespace App\Controller\Admin;

use App\Entity\Post;
use App\Entity\Comment;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        $url = $this->container->get(AdminUrlGenerator::class)
            ->setController(PostCrudController::class)
            ->generateUrl();
        return $this->redirect($url);
    }
}
