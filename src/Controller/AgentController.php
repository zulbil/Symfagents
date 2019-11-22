<?php

namespace App\Controller;

use App\Entity\Agent;
use App\Form\AgentType; 
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;


class AgentController extends AbstractController
{
    /**
     * @Route("/", name="list_agent")
     */
    public function index()
    {
        $agents  = $this->getDoctrine()->getRepository(Agent::class)->findAll(); 

        $data['page']       = "Liste des Agents";
        $data['agents']     = $agents; 

        return $this->render('agent/index.html.twig', $data );
    }

    /**
     * @Route("/new/agent", name="create_agent")
     */
    public function createAgent(Request $request)
    {
        $agent  = new Agent(); 
        $agent->setDateCreation(new \DateTime()); 

        $form   = $this->createForm(AgentType::class, $agent)
                       ->add('save', SubmitType::class, ['label' => 'Créer un Agent']); 

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // $form->getData() holds the submitted values
            // but, the original `$agent` variable has also been updated
            $agent = $form->getData();

            // ... perform some action, such as saving the agent to the database
            // for example, if Agent is a Doctrine entity, save it!
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($agent);
            $entityManager->flush();

            return $this->redirectToRoute('list_agent');
        }

        $data['form']   = $form; 
        $data['page']   = 'Création d\'un nouvel agent'; 

        return $this->render('agent/new.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/agent/{id}", name="show_agent")
     */
    public function show(int $id)
    {
        $entityManager  = $this->getDoctrine()->getManager();
        $agent          = $entityManager->getRepository(Agent::class)->find($id);

        if (!$agent) {
            throw $this->createNotFoundException(
                'No Agentt found for id '.$id
            );
        }

        $data['page']   = 'Agent numero '.$id;
        $data['agent']  = $agent;  

        return $this->render('agent/one-agent.html.twig', $data );
    }

    /**
     * @Route("/remove/agent/{id}", name="remove_agent")
     */
    public function remove(Agent $agent) 
    {
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($agent);
        $entityManager->flush();

        return $this->json(['deleted' => true ]); 
    }

    /**
     * @Route("/update/agent/{id}", name="update_agent")
     */
    public function update($id, Request $request) 
    {
        $entityManager  = $this->getDoctrine()->getManager();
        $agent          = $entityManager->getRepository(Agent::class)->find($id);

        if (!$agent) {
            throw $this->createNotFoundException(
                'No Agent found for id '.$id
            );
        }

        // Filling form with data retrieved from database
        $agent->setDateCreation(new \DateTime()); 
        $agent->setNom($agent->getNom()); 
        $agent->setPostnom($agent->getPostnom());
        $agent->setPrenom($agent->getPrenom()); 
        $agent->setSalaire($agent->getSalaire());
        $agent->setFonction($agent->getFonction()); 

        $data   = array(); 

        $form   = $this->createForm(AgentType::class, $agent)
                       ->add('save', SubmitType::class, ['label' => 'Modifier l\'Agent']); 

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // $form->getData() holds the submitted values
            // but, the original `$agent` variable has also been updated
            $agent = $form->getData();

            // ... perform some action, such as saving the agent to the database
            // for example, if Agent is a Doctrine entity, save it!
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($agent);
            $entityManager->flush();

            return $this->redirectToRoute('list_agent');
        }

        return $this->render('agent/update.html.twig', [
            'form' => $form->createView(),
            'page' => 'Création d\'un nouvel agent'
        ]);
    }
}
