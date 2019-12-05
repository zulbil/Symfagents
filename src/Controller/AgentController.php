<?php

namespace App\Controller;

use App\Entity\Agent;
use App\Entity\AgentTasks;
use App\Entity\Projet;
use App\Entity\User;
use App\Form\AgentType;
use App\Form\AgentTasksType;
use Doctrine\DBAL\Types\TextType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;


class AgentController extends AbstractController
{
    private $security;
    private $entityManager;

    public function __construct( EntityManagerInterface $entityManager, Security $security )
    {
        $this->entityManager    =   $entityManager;
        $this->security         =   $security;
    }

    /**
     * @Route("/agents", name="list_agent")
     */
    public function index()
    {
        $agents  = $this->getDoctrine()->getRepository(User::class)->findAll();
        $user    = $this->getUser();

        $data['page']       = "Liste des Agents";
        $data['agents']     = $agents;
        $data['user']       = $user;

        return $this->render('agent/index.html.twig', $data );
    }

    /**
     * @param Request $request
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @return Response
     * @Route("/new/agent", name="create_agent")
     * @throws \Exception
     */
    public function createAgent(Request $request, UserPasswordEncoderInterface $passwordEncoder)
    {
        // Create if only admin
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $agent  = new Agent(); 
        $agent->setDateCreation(new \DateTime()); 

        $form   = $this->createForm(AgentType::class, $agent)
                       ->add('save', SubmitType::class, [
                           'label' => 'Créer un Agent',
                           'attr' => ['class' => 'btn-dark']
                       ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // $form->getData() holds the submitted values
            // but, the original `$agent` variable has also been updated
            $agent = $form->getData();

            $user  = new User();
            $user->setEmail($form->get('email')->getData());
            $plainPassword = $this->generateRandomPassword();
            $user->setPassword(
                $passwordEncoder->encodePassword(
                    $user,
                    $plainPassword
                )
            );
            $user->setRoles(["ROLE_USER"]);
            $user->setFirstname($agent->getPrenom());
            $user->setLastname($agent->getNom());

            // ... perform some action, such as saving the user to the database
            // for example, if Agent is a Doctrine entity, save it!
            $this->entityManager->persist($user);
            //$entityManager->persist($agent);
            $this->entityManager->flush();
            // Send an email to the user
            $this->sendEmail($user, $plainPassword);

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
        $agent          = $entityManager->getRepository(User::class)->find($id);

        if (!$agent) {
            throw $this->createNotFoundException(
                'No User found for id '.$id
            );
        }

        $data['page']   = 'Agent numéro '.$id;
        $data['agent']  = $agent;
        $data['tasks']  = $agent->getTasks();
        //$data['tasks']  = array();
        $task           = new AgentTasks();
        $task->setDateDebut(new \DateTime());
        $task->setDateFin(new \DateTime());
        $task->setStatut(0);
        $task->setAgent($agent);

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
    public function remove(User $agent)
    {
        // Remove if only admin
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($agent);
        $entityManager->flush();

        return $this->json(['deleted' => true ]); 
    }

    /**
     * @Route("/update-one.html.twig/agent/{id}", name="update_agent")
     */
    public function update($id, Request $request) 
    {
        // Update if only admin
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $entityManager  = $this->getDoctrine()->getManager();
        $agent          = $entityManager->getRepository(User::class)->find($id);

        if (!$agent) {
            throw $this->createNotFoundException(
                'No Agent found for id '.$id
            );
        }

        // Filling form with data retrieved from database
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

    public function generateRandomPassword () : string {
        $permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        $plainPassword = substr(str_shuffle($permitted_chars), 0, 10);

        return $plainPassword;
    }

    public function sendEmail(User $user, $password)
    {
        $email = (new TemplatedEmail())
            ->from('jkazdev@gmail.com')
            //->from('joel.khang@hologram.cd')
            ->to($user->getEmail())
            ->subject('Bienvenue à SymfAgent')
            ->htmlTemplate('app/mail.html.twig')
            ->context([
                'expiration_date' => new \DateTime('+7 days'),
                'user' => $user,
                'plain_password' => $password
            ]);

        $this->mailer->send($email);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @Route("/members/list", name="members_list")
     */
    public function getAllUserExceptAdmin() {
        $entityManager  = $this->getDoctrine()->getManager();
        $users          = $entityManager->getRepository(User::class)->findAllNormalsUsers();

        return $this->json(['members' => $users ]);
    }

    /**
     * @Route("/user/projets", name="project_user")
     * @return Response
     * This function allow normal users to see projects in where they are assigned
     */
    public function show_all_project() {
        $data = array();
        $data['page'] = "Mes projets";

        $emailUserConnected = $this->security->getUser()->getUsername();

        $tasks               = $this->entityManager->getRepository(User::class)
            ->findOneBy([ "email" => $emailUserConnected ])
            ->getTasks();
        $projets             = $this->entityManager->getRepository(User::class)
            ->findOneBy([ "email" => $emailUserConnected ])
            ->getProjets();
        $data["tasks"] = $tasks;
        $data["projets"] = $projets;

        return $this->render('agent/my_projects.html.twig', $data);
    }

    /**
     * @param $projet_id
     * @return Response
     * @Route("/user/projet/{projet_id}", name="user_projet")
     */
    public function getProjetId($projet_id) {
        $data           = array();
        $projet         =   $this->entityManager->getRepository(Projet::class)->find($projet_id);

        $user_id             = $this->security->getUser()->getId();

        $data['projet'] =   $projet;
        $data['page']   =   $projet->getNom();
        $data['tasks']  =   $this->entityManager->getRepository(AgentTasks::class)
                                                ->findTasksRelatedToUserPerProjet($projet_id, $user_id);

        return $this->render('agent/single_projet.html.twig', $data );

    }

    /**
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @Route("/user/gettasks", name="get_user_tasks")
     */
    public function getAllTasksByUser() {

        $user_id = $this->security->getUser()->getId();

        $tasks = $this->security->getUser()->getTasks();

        return $this->json(["tasks" => $tasks ]);
    }

    /**
     * @return Response
     * @Route("/user/tasks", name="all_user_tasks")
     */
    public function getTasks() {
        $data = array();
        $data['page'] = "Toutes mes tâches";

        return $this->render('agent_tasks/all-tasks.html.twig', $data);
    }
}
