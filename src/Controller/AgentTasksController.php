<?php

namespace App\Controller;

use App\Entity\AgentTasks;
use App\Form\AgentTasksType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Routing\Annotation\Route;

class AgentTasksController extends AbstractController
{
    /**
     * @Route("/agent/tasks", name="agent_tasks")
     */
    public function index()
    {
        return $this->render('agent_tasks/index.html.twig', [
            'controller_name' => 'AgentTasksController',
        ]);
    }

    /**
     * @Route("/agent/{agent_id}/task/edit/{task_id}", name="agent_task")
     */
    public function editTask($agent_id, $task_id, Request $request)
    {
        $data = array();

        $entityManager  = $this->getDoctrine()->getManager();
        $task           = $entityManager->getRepository(AgentTasks::class)->find($task_id);

        $editForm       = $this->createForm(AgentTasksType::class, $task)
                            ->add('statut', ChoiceType::class, [
                                "choices" => [
                                    "En cours" => 0,
                                    "Terminé" => 1
                                ]
                            ])
                            ->add('save', SubmitType::class, [
                                "label" => "Modifier la tâche",
                                "attr" => ["class" => "btn-dark" ]
                            ]);

        $editForm->handleRequest($request);
        $data["page"] = "Modification de la tache #$task_id";

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $task = $editForm->getData();
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($task);
            $entityManager->flush();

            return $this->redirectToRoute('show_agent',["id" => $agent_id ]);
        }

        $data['editForm'] = $editForm->createView();

        return $this->render('agent_tasks/edit-task.html.twig', $data );
    }

    /**
     *@Route("/remove/task/{task_id}", name="remove_task")
     */
    public function removeTask($task_id) {
        // Remove if only admin
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $entityManager = $this->getDoctrine()->getManager();
        $task = $entityManager->getRepository(AgentTasks::class)->find($task_id);
        $entityManager->remove($task);
        $entityManager->flush();

        return $this->json(['deleted' => true ]);

    }

    /**
     *@Route("/task/{task_id}", name="show_task")
     */
    public function showTask($task_id) {
        $entityManager  = $this->getDoctrine()->getManager();
        $task          = $entityManager->getRepository(AgentTasks::class)->find($task_id);

        if (!$task) {
            throw $this->createNotFoundException(
                'No Task found for id '.$task_id
            );
        }

        $data['page']   = 'Détails de la tâche numéro  '.$task_id;
        $data['task']   = $task;
        $data['agent_id'] = $task->getAgent()->getId();

        return $this->render('agent_tasks/task.html.twig', $data );
    }

    /**
     *@Route("/tasks/{user_id}", name="task_list")
     */
    public function show_all_tasks($user_id) {
        $data = array();

        $entityManager  = $this->getDoctrine()->getManager();
        $task           = $entityManager->getRepository(AgentTasks::class)->find($user_id);
    }
}
