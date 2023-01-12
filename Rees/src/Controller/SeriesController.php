<?php

namespace App\Controller;

use App\Entity\Rating;
use App\Entity\Episode;
use App\Entity\Season;
use App\Entity\Series;
use App\Entity\User;
use App\Form\Series1Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OrderBy;
use Doctrine\ORM\Tools\Pagination\Paginator;
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
    public function search(EntityManagerInterface $entityManager, Request $request): Response
    {
        // Pages
        $lastData = $entityManager
            ->getRepository(Series::class)
            ->findOneBy([], ['id' => 'desc']);
        $page = $request->query->getInt('page', 1);
        // If the number of the page is below 1, or above (last number of page), it will redirect to an error page
        if ($page < 1) {
            $page = 1;
        } else if ($page > (($lastData->getId())/10)-1) {
            $page = (int)($lastData->getId()/10)-1;
        }

        // Get filters
        $keywords = $request->query->get('keywords');
        $yearStart = $request->query->get('yearStart');
        $yearEnd = $request->query->get('yearEnd');
        $genres = $request->query->get('genres');

        // Get array from string
        $keywords = explode(',', $keywords);

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
            ->where("(s.yearEnd >= :year_start and s.yearStart <= :year_end)
            or (s.yearEnd is null and s.yearStart <= :year_end and s.yearStart >= :year_start)")
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
        if ($genres) {
            $genres = explode(',', $genres);
            $queryBuilder->join("s.genre", "g");
            $queryBuilder->andWhere('g.name IN (:genres)');
            $queryBuilder->setParameter("genres", $genres);
        }

        // Series
        $series = $queryBuilder->getQuery()
            ->getResult();

        // Posts
        $posts = $this->paginate($queryBuilder, $page);
        $posts->setUseOutputWalkers(false);
        $series = $posts->getIterator();

        $limit = 10;
        $maxPages = ceil($posts->count() / $limit);
        $thisPage = $page;

        // TODO: fix pagination problem
        return $this->render('series/index.html.twig', [
            'series' => $series,
            'maxPages' => $maxPages,
            'thisPage' => $thisPage
        ]);
    }
    public function paginate($dql, $page = 1, $limit = 10)
    {
        $paginator = new Paginator($dql);

        $paginator->getQuery()
            ->setFirstResult($limit * ($page - 1)) // Offset
            ->setMaxResults($limit); // Limit

        return $paginator;
    }

    #[Route(['/'], name: 'app_series_index', methods: ['GET', 'POST'])]
    public function index(EntityManagerInterface $entityManager, Request $request): Response
    {
        $lastData = $entityManager
            ->getRepository(Series::class)
            ->findOneBy([], ['id' => 'desc']);
        $page = $request->query->getInt('page', 1);
        //if the number of the page is below 1, or above (last number of page), it will redirect to another page
        if ($page < 1) {
            $page = 1;
        } 
        else if($page > (($lastData->getId())/10)-1) {
            $page = (int)($lastData->getId()/10)-1;
        }
        
        $query = $entityManager->createQuery(
            "SELECT s FROM App\Entity\Series s
             INNER JOIN App\Entity\Genre g
             ORDER BY s.id"
        );
        $posts = $this->paginate($query, $page);
        $posts->setUseOutputWalkers(false);
        $series = $posts->getIterator();

        $limit = 10;
        $maxPages = ceil($posts->count() / $limit);
        $thisPage = $page;

        return $this->render(
            'series/index.html.twig', [
            'series' => $series,
            'maxPages' => $maxPages,
            'thisPage' => $thisPage
            ]
        );
    }


    #[Route(['/tracked'], name: 'app_series_tracked', methods: ['GET', 'POST'])]
    public function tracked(): Response
    {
        $user = $this->getUser();
        return $this->render(
            'series/tracked/index.html.twig', [
            'user' => $user
            ]
        );
    }

    
    #[Route('/{id}', name: 'app_series_show', methods: ['GET','POST'])]
    public function show(EntityManagerInterface $entityManager,Series $series): Response
    {             
        
        $season = $entityManager->getRepository(Season::class)->findBy(['series' => $series], array('number' => 'ASC'));
        
        $episodes = $entityManager->getRepository(Episode::class)
            ->createQueryBuilder('e')
            ->select('e', 's')
            ->join('e.season', 's')
            ->where('s.series = :series')
            ->orderBy('s.number', 'ASC')
            ->addOrderBy('e.number', 'ASC')
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
        $seriesRating = $query->getResult();
        
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
         

        return $this->render('series/show.html.twig', [
            'series' => $series,
            'seasons' => $season,
            'episodes' => $episodes,
            'allRates' =>$seriesRating,
            'myRate' => $isRate,
            'reesRate' => $rate
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
