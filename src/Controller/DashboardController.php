<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        if (!$this->getUser()) {
            return $this->render('landing/home.html.twig');
        }

        return $this->redirectToRoute('app_dashboard');
    }

    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var Utilisateur $user */
        $user = $this->getUser();

        if (in_array('ROLE_RH', $user->getRoles(), true)) {
            return $this->redirectToRoute('app_rh_user_index');
        }

        if (in_array('ROLE_EMPLOYEE', $user->getRoles(), true)) {
            return $this->redirectToRoute('app_employee_dashboard');
        }

        return $this->redirectToRoute('app_candidate_dashboard');
    }

    #[Route('/admin', name: 'app_admin_dashboard')]
    public function adminRedirect(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_RH');

        return $this->redirectToRoute('app_rh_user_index');
    }

    #[Route('/employee', name: 'app_employee_dashboard')]
    public function employee(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_EMPLOYEE');

        return $this->render('dashboard/employee.html.twig');
    }
}
