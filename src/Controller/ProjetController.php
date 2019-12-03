<?php

namespace App\Controller;

use App\Entity\Projet;
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
    public function showProjet($id) {
        $projet             = $this->getDoctrine()->getManager()->getRepository(Projet::class)->find($id);

        $data               = array();
        $data['projet']     = $projet;
        $data['page']       = "Détails du projet #$id";

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


}
