<?php

namespace App\Controller;

use App\Entity\Season;
use App\Entity\Series;
use App\Form\Series1Type;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;


#[Route('/series')]
class SeriesController extends AbstractController
{


    private $authorizationChecker;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }


    #[Route(['/'], name: 'app_series_index', methods: ['GET', 'POST'])]
    public function index(EntityManagerInterface $entityManager,Request $page, PaginatorInterface $paginator ): Response
    {
        $lastData = $entityManager
            ->getRepository(Series::class)
            ->findOneBy([], ['id' => 'desc']);
        
        //checking if the number of the page is correct
        $nbrPage = $page->query->getInt('page', 1);
        //if the number of the page is below 1, or above (last number of page), it will redirect to an error page
        if($nbrPage<1 || $nbrPage > (($lastData->getId())/10)-1){
            return $this->render('series/error_page/index.html.twig');
        }
        else{
            $data = $entityManager
                ->getRepository(Series::class)
                ->findAll();

            $series = $paginator -> paginate(
                $data,
                $nbrPage,
                10);
    
            return $this->render('series/index.html.twig', [
                'series' => $series,
            ]);
        }
    }



    #[Route('/{id}', name: 'app_series_show', methods: ['GET','POST'])]
    public function show(EntityManagerInterface $entityManager,Series $series,  Request $request): Response
    {

    $seasons = $entityManager
    ->getRepository(Season::class)
    ->findBy(array('series'=>$series),array('number'=>'ASC'));

    $query = $entityManager->createQuery(
        "SELECT se.number as numberSeason, e.number as nbEpisode
        FROM App\Entity\Episode e
        INNER JOIN e.season se
        INNER JOIN se.series s
        WHERE s.id = :id
        GROUP BY numberSeason
        ORDER BY numberSeason"
    )->setParameter('id', $series);;
    $episodesPerSeason = $query->getResult();
    return $this->render('series/show.html.twig', [
        'series' => $series,
        'seasons' => $seasons,
        'episodes' => $episodesPerSeason,
    ]);
    }

    #[Route('/new', name: 'app_series_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $series = new Series();
        $form = $this->createForm(Series1Type::class, $series);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($series);
            $entityManager->flush();

            return $this->redirectToRoute('app_series_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('series/new.html.twig', [
            'series' => $series,
            'form' => $form,
        ]);
    }

    




    #[Route('/{id}/edit', name: 'app_series_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, Series $series, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(Series1Type::class, $series);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_series_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('series/edit.html.twig', [
            'series' => $series,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_series_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Series $series, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$series->getId(), $request->request->get('_token'))) {
            $entityManager->remove($series);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_series_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/poster/{id}', name: 'app_poster', methods: ['GET'])]
    public function poster(Series $series): Response
    {
        return new Response(stream_get_contents($series->getPoster()),200,['Content-Type'=>'image/png']);
    }
}
