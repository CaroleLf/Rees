<?php

namespace App\Controller;

use App\Entity\Rating;
use App\Entity\Episode;
use App\Entity\Season;
use App\Entity\Series;
use App\Entity\Genre;
use Doctrine\ORM\Query\Parameter;
use Doctrine\Common\Collections\ArrayCollection; 
use App\Form\Series1Type;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface; 
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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


   #[Route('/search', name: 'app_series_search')]
    public function search(EntityManagerInterface $entityManager, Request $request, PaginatorInterface $paginator): Response
    {

        $allGenre = $entityManager->getRepository(Genre::class)->findAll();

        $keywords = $request->query->get('keywords');
        $yearStart = $request->query->get('yearStart');
        $yearEnd = $request->query->get('yearEnd');
        $queryGenres = $request->query->get('genres');

        // Get keywords in array which are separated by whitespace
        $keywords = explode(' ', $keywords);

        // Check user entries
        // Case: if years are not entered
        if ($yearStart == null) {
            $yearStart = 0;
        }
        if ($yearEnd == null) {
            $yearEnd = 9999;
        }

        // SQL query for getting series according to the filters
        $queryBuilder = $entityManager->createQueryBuilder()
            ->select('s')
            ->from(Series::class, 's')
            ->where(
                "(s.yearEnd >= :year_start and s.yearStart <= :year_end)
            or (s.yearEnd is null and s.yearStart <= :year_end and s.yearStart >= :year_start)"
            )
            ->setParameter('year_start', $yearStart)
            ->setParameter('year_end', $yearEnd);

        // SQL LIKE with multiple values. As of right now, this is only an OR filter. If we want to apply an AND, we must use nested DQL queries. 
        $i = 0;
        foreach ($keywords as $kw) {
            $queryBuilder->andWhere("s.title LIKE :kw$i")
                ->setParameter(
                    "kw$i",
                    "%$kw%"
                );
            $i++;
        }

        // Case: if genres are entered
        if ($queryGenres) {
            $genres = explode(' ', $queryGenres);
            $queryBuilder->join("s.genre", "g");
            $queryBuilder->andWhere('g.name IN (:genres)');
            $queryBuilder->setParameter("genres", $genres);
        }

        // Series
        $series = $queryBuilder->getQuery();
        
        // Posts
        $series = $paginator->paginate(
            $series,
            $request->query->getInt('page', 1), 
            10 
        );

        // TODO: fix pagination problem
        return $this->render(
            'series/index.html.twig', [
            'series' => $series,
            'allGenre' => $allGenre,
            ]
        );
    }

    #[Route(['/'], name: 'app_series_index', methods: ['GET', 'POST'])]
    public function index(EntityManagerInterface $entityManager, Request $request, PaginatorInterface $paginator): Response
    {
        $allGenre = $entityManager->getRepository(Genre::class)->findAll();

        $query = $entityManager->createQuery('Select s from App\Entity\Series s');
        
        $series = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1), 
            10 
        );

        return $this->render(
            'series/index.html.twig', [
            'series' => $series,
            'allGenre' => $allGenre,
            'query' => null
            ]
        );
    }


    #[Route(['/tracked'], name: 'app_series_tracked', methods: ['GET', 'POST'])]
    public function tracked(EntityManagerInterface $entityManager,Request $request, PaginatorInterface $paginator): Response
    {
        $user = $this->getUser();
        if($user != null){
            $userid = $user->getId();
            $userSeries = $user->getSeries();
            $seriesComplete = [];
            $seriesStart = [];
            $seriesJustLike = [];
            foreach ($userSeries as $serie) {
        
                $userEpisode = $entityManager->getRepository(Episode::class)
                    ->createQueryBuilder('e')
                    ->select('e')
                    ->join('e.user','u')
                    ->join('e.season', 's')
                    ->where('s.series = :series')
                    ->andWhere('u.id = :user')
                    ->orderBy('e.number', 'ASC')
                    ->setParameters(new ArrayCollection([
                        new Parameter('series' ,$serie),
                        new Parameter('user' , $userid)
                    ]))
                    ->getQuery()
                    ->getResult();
    
                    $episodes = $entityManager->getRepository(Episode::class)
                    ->createQueryBuilder('e')
                    ->select('e')
                    ->join('e.season', 's')
                    ->where('s.series = :series')
                    ->orderBy('e.number', 'ASC')
                    ->setParameter('series', $serie)
                    ->getQuery()
                    ->getResult();
    
                if (count($episodes) == count($userEpisode) ) {
                    array_push($seriesComplete , $serie);  
                    }
                else if(count($userEpisode) == 0 ) {
                    array_push($seriesJustLike , $serie); 
                }
                else {
                    array_push($seriesStart , $serie);
                }
            }
    
            return $this->render(
                'series/tracked/index.html.twig', [
                'user' => $user,
                'likes' => $seriesJustLike,
                'start' => $seriesStart,
                'complete' => $seriesComplete,
                ]
            );
        }
            else {
                return $this->redirectToRoute('app_login');
            }
        
       
    }


    public function countEpisode(Series $serie, EntityManagerInterface $entityManager ) : int {
        $episodes = $entityManager->getRepository(Episode::class)
        ->createQueryBuilder('e')
        ->select('e')
        ->join('e.season', 's')
        ->where('s.series = :series')
        ->orderBy('e.number', 'ASC')
        ->setParameter('series', $serie)
        ->getQuery()
        ->getResult();

        return count($episodes); 
    }

    
    #[Route('/{id}', name: 'app_series_show', methods: ['GET','POST'])]
    public function show(EntityManagerInterface $entityManager,Series $series, Request $request, PaginatorInterface $paginator): Response
    {             
        
        $season = $entityManager->getRepository(Season::class)->findBy(['series' => $series], array('number' => 'ASC'));
        
        $episodes = $entityManager->getRepository(Episode::class)
            ->createQueryBuilder('e')
            ->select('e')
            ->join('e.season', 's')
            ->where('s.series = :series')
            ->orderBy('e.number', 'ASC')
            ->setParameter('series', $series)
            ->getQuery()
            ->getResult();


        $query = $entityManager->createQuery(
            "SELECT r
            FROM App\Entity\Rating r
            INNER JOIN App\Entity\Series s
            WHERE r.series = s
            AND s.id = :id
            ORDER BY r.date DESC"
        )->setParameter('id', $series->getId());    

        $seriesRating = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1), 
            5 
        );

        
        $isRate = $entityManager
            ->getRepository(Rating::class)
            ->findOneBy(['series' => $series, 'user' => $this  ->  getUser()]);
            
        $query = $entityManager->createQuery(
            "SELECT AVG(r.value) as rate
            FROM App\Entity\Rating r
            INNER JOIN App\Entity\Series s
            WHERE r.series = s
            AND s.id = :id"
        )->setParameter('id', $series->getId());    
        $rate = $query->getResult();
         

        return $this->render(
            'series/show.html.twig', [
            'series' => $series,
            'seasons' => $season,
            'episodes' => $episodes,
            'allRates' =>$seriesRating,
            'myRate' => $isRate,
            'reesRate' => $rate
            ]
        );
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

        return $this->renderForm(
            'series/new.html.twig', [
            'series' => $series,
            'form' => $form,
            ]
        );
    }
    /*
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
    }*/

    #[Route('/poster/{id}', name: 'app_poster', methods: ['GET'])]
    public function poster(Series $series): Response
    {
        return new Response(stream_get_contents($series->getPoster()), 200, ['Content-Type' => 'image/png']);
    }


    #[Route('/{id}/like', name: 'app_like_add')]
    public function like(Series $series, EntityManagerInterface $entityManager): Response
    {
        $series->addUser($this->getUser());
        $entityManager->flush();
        return $this->redirectToRoute('app_series_show', ['id' => $series->getId()]);
    }

    #[Route('/{id}/unlike', name: 'app_like_remove')]
    public function unlike(Series $series, EntityManagerInterface $entityManager): Response
    {
        $series->removeUser($this->getUser());
        $entityManager->flush();
        return $this->redirectToRoute('app_series_show', ['id' => $series->getId()]);
    }

    

}
