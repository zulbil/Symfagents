<?php

namespace App\Controller;

use App\Entity\AgentTasks;
use App\Entity\Projet;
use App\Entity\User;
use App\Form\AgentTasksType;
use App\Form\ProjetType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * Require ROLE_ADMIN for every controller method in this class
 *
 * @IsGranted("ROLE_ADMIN")
 */
class ProjetController extends AbstractController
{
    /**
     * @Route("/", name="projets")
     */
    public function index()
    {
        $projets = $this->getDoctrine()->getManager()->getRepository(Projet::class)->findAll();
        $page = "Liste des projets";

        $data = array();

        $data['projets']    = $projets;
        $data['page']       = $page;

        return $this->render('projet/index.html.twig', $data );
    }

    /**
     *@Route("/new/projet", name="new_projet")
     */
    public function new_projet(Request $request) {

        $data   = array();
        $projet = new Projet();

        $form = $this->createForm(ProjetType::class, $projet)
                     ->add('save', SubmitType::class, [
                         "label" => "Ajouter un projet",
                         "attr" => [ "class" => "btn-dark" ]
                     ]);

        $form->handleRequest($request);

        if ( $form->isSubmitted() && $form->isValid()) {
            $projet = $form->getData();
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($projet);
            $entityManager->flush();

            return $this->redirectToRoute("projets");
        }

        $form = $form->createView();

        $data['form'] = $form;
        $data['page'] = "Création d'un nouveau projet";

        return $this->render("projet/new-projet.html.twig", $data);
    }

    /**
     *@Route("/projet/{id}", name="single_projet")
     */
    public function showProjet($id, Request $request) {
        $data               = array();
        $projet             = $this->getDoctrine()->getManager()->getRepository(Projet::class)->find($id);
        $tasks              = $projet->getTasks();
        $task   = new AgentTasks();
        $task->setDateDebut(new \DateTime());
        $task->setDateFin(new \DateTime());
        $form_task          = $this->createForm(AgentTasksType::class, $task)
                                   ->add('save', SubmitType::class, [
                                       "attr" => ["class" => "btn btn-primary btn-pill"],
                                       "label" => "Ajouter la tâche"
                                   ]);
        $form_task->handleRequest($request);
        if ($form_task->isSubmitted() && $form_task->isValid()) {
            $task = $form_task->getData();
            $task->setProjet($projet);
            $task->setStatut(0);
            $projet->addTask($task);

            $this->getDoctrine()->getManager()->persist($task);
            $this->getDoctrine()->getManager()->persist($projet);
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute("single_projet", ["id" => $id ]);

        }

        $data['form_task']       = $form_task->createView();
        $data['projet']     = $projet;
        $data['tasks']      = $tasks;
        $data['page']       = "Détails du projet #$id";
        $data['users']      = $this->getDoctrine()->getManager()->getRepository(User::class)->findAllNormalsUsers();

        return $this->render("projet/one-projet.html.twig", $data);
    }

    /**
     *@Route("/projet/edit/{id}", name="update_projet")
     */
    public function updateProjet(int $id, Request $request) {
        $projet     = $this->getDoctrine()->getManager()->getRepository(Projet::class)->find($id);

        $form       = $this->createForm(ProjetType::class, $projet)
                           ->add('save', SubmitType::class, [
                               "label" => "Modifier le projet",
                               "attr" => ["class" => "btn-dark"]
                           ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $projet = $form->getData();

            $entityManager = $this->getDoctrine()->getManager();

            $entityManager->persist($projet);
            $entityManager->flush();

            return $this->redirectToRoute('projets');
        }

        $data['form'] = $form->createView();
        $data['page'] = "Modifier le projet #$id";
        $data['projet'] = $projet;

        return $this->render('projet/update-one.html.twig', $data);
    }

    /**
     * @param Request $request
     * @Route("/members/invite",name="invite_member")
     * @return Json Array
     */
    public function addMemberToProject(Request $request) {
        $entityManager  = $this->getDoctrine()->getManager();
        //if($request->isMethod("POST")) {
           $user_id     = (int)$request->request->get("user_id");
           $projet_id   = (int)$request->request->get('projet_id');

           $user        = $entityManager->getRepository(User::class)->find($user_id);
           $projet      = $entityManager->getRepository(Projet::class)->find($projet_id);

           $user->setProjet($projet);

           $entityManager->persist($projet);
           $entityManager->persist($user);

           $entityManager->flush();

           return $this->json([ "message" => "success" ]);

    }
}
