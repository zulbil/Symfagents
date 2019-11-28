<?php

namespace App\Controller;

use App\Entity\Agent;
use App\Entity\AgentTasks;
use App\Form\AgentType;
use App\Form\AgentTasksType;
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
        $user    = $this->getUser();

        $data['page']       = "Liste des Agents";
        $data['agents']     = $agents;
        $data['user']       = $user;

        return $this->render('agent/index.html.twig', $data );
    }

    /**
     * @Route("/new/agent", name="create_agent")
     */
    public function createAgent(Request $request)
    {
        // Create if only admin
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

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

        $data['form']   = $form->createView(); 
        $data['page']   = 'Création d\'un nouvel agent'; 

        return $this->render('agent/new.html.twig', $data );
    }

    /**
     * @Route("/agent/{id}", name="show_agent")
     */
    public function show(int $id, Request $request)
    {
        $entityManager  = $this->getDoctrine()->getManager();
        $agent          = $entityManager->getRepository(Agent::class)->find($id);

        if (!$agent) {
            throw $this->createNotFoundException(
                'No Agent found for id '.$id
            );
        }

        $data['page']   = 'Agent numéro '.$id;
        $data['agent']  = $agent;
        $data['tasks']  = $this->getDoctrine()->getManager()->getRepository(AgentTasks::class)->findBy(
                            ['agent_id' => $id]
                           );

        $task           = new AgentTasks();
        $task->setDateDebut(new \DateTime());
        $task->setDateFin(new \DateTime());
        $task->setAgentId($agent->getId());
        $task->setStatut(0);

        $form           = $this->createForm(AgentTasksType::class, $task)
                               ->add('save', SubmitType::class, ['label' => 'Ajouter la tâche']);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // $form->getData() holds the submitted values
            // but, the original `$agent` variable has also been updated
            $agent = $form->getData();
            // ... perform some action, such as saving the agent to the database
            // for example, if Agent is a Doctrine entity, save it!
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($task);
            $entityManager->flush();

            return $this->redirectToRoute('show_agent',["id" => $id ]);
        }

        $data['form']   = $form->createView();

        return $this->render('agent/one-agent.html.twig', $data );
    }

    /**
     * @Route("/remove/agent/{id}", name="remove_agent")
     */
    public function remove(Agent $agent) 
    {
        // Remove if only admin
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

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
        // Update if only admin
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

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
