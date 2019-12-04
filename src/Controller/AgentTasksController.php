<?php

namespace App\Controller;

use App\Entity\AgentTasks;
use App\Entity\Projet;
use App\Entity\User;
use App\Form\AgentTasksType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Response;
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
     * @Route("/task/edit/{task_id}", name="edit_agent_task")
     */
    public function editTask( $task_id, Request $request)
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
        $data["page"] = $task->getNom();

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $task = $editForm->getData();
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($task);
            $entityManager->flush();

            return $this->redirectToRoute('show_task',["task_id" => $task_id ]);
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

        $date_debut = $task->getDateDebut();
        $date_fin   = $task->getDateFin();

        $data['page']   = $task->getNom();
        $data['task']   = $task;
        $data['estimation_time'] = $date_fin->diff($date_debut);

        $data['projet']     = $task->getProjet();
        $data['users']      = $task->getAgent();

        $data['agent']      = $task->getAgent();
        $data['agents']      = $this->getDoctrine()->getManager()->getRepository(User::class)->findAllNormalsUsers();
        //$data['agent']   = isset($data['agent_id']) ? $data['agent_id'] : null ;

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

    /**
     * @IsGranted("ROLE_ADMIN")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @Route("/invite/task/members", name="invite_user_task")
     */
    public function addMemberToTask(Request $request) {
        $entityManager  = $this->getDoctrine()->getManager();

        $user_id     = (int)$request->request->get("user_id");
        $projet_id   = (int)$request->request->get('projet_id');
        $task_id     = (int)$request->request->get('task_id');

        $user        = $entityManager->getRepository(User::class)->find($user_id);

        /**
         * Prevent to add user on a specific task without adding him to project that include this task
         */
        if($user->getProjet()->getId() !== $projet_id ) {
            return $this->json([
                "message" => "Impossible d'ajouter cet agent car il n'appartient pas au projet",
                "error"   => true
            ]);
        }

        $task      = $entityManager->getRepository(AgentTasks::class)->find($task_id);

        /**
         * Check if a task has already an assignee on it
         */
        if($task->getAgent()->getId() === $user_id) {
            return $this->json([
                "message" => "Cette tâche a déjà été assigné à cet utilisateur",
                "error"   => true
            ]);
        }

        $task->setAgent($user);
        $entityManager->persist($user);

        $entityManager->flush();

        return $this->json([
            "message" => "L'utilisateur a été invité avec succès",
            "error"   => false
        ]);

    }

    /**
     * @IsGranted("ROLE_ADMIN")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @Route("/remove/task/{task_id}/member/{user_id}", name="remove_user_task")
     */
    public function removeUseronTask($task_id, $user_id, Request $request) {

        $entityManager  = $this->getDoctrine()->getManager();

        $user        = $entityManager->getRepository(User::class)->find($user_id);
        $task        = $entityManager->getRepository(AgentTasks::class)->find($task_id);

        $user->removeTask($task);

        $entityManager->persist($user);
        $entityManager->persist($task);

        $entityManager->flush();

        return $this->json([
            "message" => "L'utilisateur a été retiré avec succès",
            "error"   => false
        ]);
    }

    /**
     * @param Request $request
     * @return Response $response
     * @Route("/add/task/observation", name="add_observation")
     */
    public function addObservation (Request $request) {
        $entityManager = $this->getDoctrine()->getManager();

        $task_id        = (int)$request->request->get('task_id');
        $observation    = $request->request->get('observation');

        $task     = $entityManager->getRepository(AgentTasks::class)->find($task_id);

        $task->setObservation($observation);
        $entityManager->persist($task);
        $entityManager->flush();

        $response = new Response("Ajout de l'observation réussie", Response::HTTP_OK);

        return $response;
    }
}
