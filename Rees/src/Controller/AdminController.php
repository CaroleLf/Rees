<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin')]
    public function index(EntityManagerInterface $entityManager,Request $request): Response
    {
        $is_admin = $request->query->get('is_admin');
        $users = $entityManager
        ->getRepository(User::class)
        ->findBy(array(),array('name'=>'ASC'));

        return $this->render('admin/admin.html.twig', [
            'users' => $users,
            'is_admin' => $is_admin,
        ]);
    }
}
