<?php

namespace App\Controller;

use App\Entity\AgentTasks;
use App\Entity\Projet;
use App\Entity\User;
use App\Form\AgentTasksType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class AgentTasksController extends AbstractController
{
    private $security;
    private $entityManager;

    public function __construct(Security $security, EntityManagerInterface $entityManager ) {
        $this->security         = $security;
        $this->entityManager    = $entityManager;
    }
    /**
     * @Route("/agent/tasks", name="agent_tasks")
     * @return Response
     */
    public function index()
    {
        return $this->render('agent_tasks/index.html.twig', [
            'controller_name' => 'AgentTasksController',
        ]);
    }

    /**
     * @Route("/task/edit/{task_id}", name="edit_agent_task")
     * @param $task_id
     * @param Request $request
     * @return Response
     */
    public function editTask( $task_id, Request $request)
    {
        $data = array();

        $task           = $this->entityManager->getRepository(AgentTasks::class)->find($task_id);

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
     * @Route("/remove/task/{task_id}", name="remove_task")
     * @param  $task_id
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function removeTask($task_id) {
        // Remove if only admin
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $task = $this->entityManager->getRepository(AgentTasks::class)->find($task_id);
        $this->entityManager->remove($task);
        $this->entityManager->flush();

        return $this->json(['deleted' => true ]);

    }

    /**
     * @Route("/task/{task_id}", name="show_task")
     * @param  $task_id
     * @return Response
     */
    public function showTask($task_id) {
        $task          = $this->entityManager->getRepository(AgentTasks::class)->find($task_id);

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
        $data['agents']      = $this->entityManager->getRepository(User::class)->findAllNormalsUsers();
        //$data['agent']   = isset($data['agent_id']) ? $data['agent_id'] : null ;

        return $this->render('agent_tasks/task.html.twig', $data );
    }

    /**
     * @IsGranted("ROLE_ADMIN")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @Route("/invite/task/members", name="invite_user_task")
     * This function is used to add a user on a task
     */
    public function addMemberToTask(Request $request) {

        $user_id     = (int)$request->request->get("user_id");
        $projet_id   = (int)$request->request->get('projet_id');
        $task_id     = (int)$request->request->get('task_id');

        $user        = $this->entityManager->getRepository(User::class)->find($user_id);
        $projet      =  $this->entityManager->getRepository(Projet::class)->find($projet_id);

        /**
         * Prevent to add user on a specific task without adding him to project that include this task
         */
        if(!$user->getProjets()->contains($projet) ) {
            return $this->json([
                "message" => "Impossible d'ajouter cet agent car il n'appartient pas au projet",
                "error"   => true
            ]);
        }

        $task      = $this->entityManager->getRepository(AgentTasks::class)->find($task_id);

        /**
         * Check if a task has already an assignee on it
         */
        if ($task->getAgent()) {
            if($task->getAgent()->getId() === $user_id) {
                return $this->json([
                    "message" => "Cette tâche a déjà été assigné à cet utilisateur",
                    "error"   => true
                ]);
            }
        }

        $task->setAgent($user);
        $this->entityManager->persist($user);

        $this->entityManager->flush();

        return $this->json([
            "message" => "L'utilisateur a été invité avec succès",
            "error"   => false
        ]);

    }

    /**
     * @IsGranted("ROLE_ADMIN")
     * @param $task_id
     * @param $user_id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @Route("/remove/task/{task_id}/member/{user_id}", name="remove_user_task")
     */
    public function removeUseronTask($task_id, $user_id, Request $request) {

        $user        = $this->entityManager->getRepository(User::class)->find($user_id);
        $task        = $this->entityManager->getRepository(AgentTasks::class)->find($task_id);

        $user->removeTask($task);

        $this->entityManager->persist($user);
        $this->entityManager->persist($task);

        $this->entityManager->flush();

        return $this->json([
            "message" => "L'utilisateur a été retiré avec succès",
            "error"   => false
        ]);
    }

    /**
     * @param Request $request
     * @return Response $response
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @Route("/add/task/observation", name="add_observation")
     */
    public function addObservation (Request $request) {

        $task_id        = (int)$request->request->get('task_id');
        $observation    = $request->request->get('observation');

        $task     = $this->entityManager->getRepository(AgentTasks::class)->find($task_id);

        $task->setObservation($observation);
        $this->entityManager->persist($task);
        $this->entityManager->flush();

        $response = new Response("Ajout de l'observation réussie", Response::HTTP_OK);

        return $response;
    }

    /**
     * @param $task_id
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @Route("/task/complete/{task_id}", name="complete_task")
     */
    public function changeStatut ($task_id) {
        $task       =   $this->entityManager->getRepository(AgentTasks::class)->find($task_id);

        $task->setStatut(1);

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return $this->json([
            "message"   => "Votre tâche est completé",
            "error"     => false
        ]);
    }
}
