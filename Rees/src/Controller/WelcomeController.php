<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Series;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;


class WelcomeController extends AbstractController
{
    #[Route('/', name: 'app_welcome')]
    public function index(EntityManagerInterface $entityManager,Request $request): Response
    {
        $SeriesFinal = [];
        $series = $entityManager
        ->getRepository(Series::class)
        ->findAll();

        $randomSeries = array_rand($series,3);
        for ($i=0; $i < 3; $i++) { 
             $rand =  $entityManager
                                ->getRepository(Series::class)
                                ->findOneBy(array('id'=>$randomSeries[$i]));
            if($rand!=null){
                $SeriesFinal[$i] = $rand;
            }
            else {
                while ($rand==null){
                    $randCorrection = array_rand($series,1);
                    $rand =  $entityManager
                                    ->getRepository(Series::class)
                                    ->findOneBy(array('id'=>$randCorrection));
                }
                $SeriesFinal[$i] = $rand;
            }
                
            
        }
        
        return $this->render('welcome/index.html.twig', [
           'series'=> $SeriesFinal,
        ]);
    }



}
