<?php

namespace App\Controller;

use App\Entity\ToyRequest;
use App\Form\ToyRequestType;
use App\Repository\ToyRequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Workflow\WorkflowInterface;

class ToyRequestController extends AbstractController
{
    private $toyRequestWorkflow;

    public function __construct(WorkflowInterface $toyRequestWorkflow)
    {
        $this->toyRequestWorkflow = $toyRequestWorkflow;
    }

    #[Route('/new', name: 'app_new')]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $toy = new ToyRequest();

        $toy->setUser($this->getUser());

        $form = $this->createForm(ToyRequestType::class, $toy);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $toy = $form->getData();

            try {
                $this->toyRequestWorkflow->apply($toy, 'to_pending');
            } catch (LogicException $exception) {
                //
            }

            $entityManager->persist($toy);
            $entityManager->flush();

            $this->addFlash('success', 'Demande enregistrée !');

            return $this->redirectToRoute('app_new');
        }

        return $this->render('toy_request/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/parent', name: 'app_parent')]

    public function parent(ToyRequestRepository $toyRequestRepository): Response
    {
        return $this->render('toy_request/parent.html.twig', [
            'toys' => $toyRequestRepository->findAll(),
        ]);
    }

    #[Route('/change/{id}/{to}', name: 'app_change')]

    public function change(ToyRequest $toyRequest, String $to, EntityManagerInterface $entityManager): Response
    {
        try {
            $this->toyRequestWorkflow->apply($toyRequest, $to);
        } catch (LogicException $exception) {
            //
        }

        $entityManager->persist($toyRequest);
        $entityManager->flush();

        $this->addFlash('success', 'Action enregistrée !');

        return $this->redirectToRoute('app_parent');
    }
}
