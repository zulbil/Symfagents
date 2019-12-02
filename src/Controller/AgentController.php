<?php

namespace App\Controller;

use App\Entity\Agent;
use App\Entity\AgentTasks;
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
        $agents  = $this->getDoctrine()->getRepository(User::class)->findAll();
        $user    = $this->getUser();

        $data['page']       = "Liste des Agents";
        $data['agents']     = $agents;
        $data['user']       = $user;

        return $this->render('agent/index.html.twig', $data );
    }

    /**
     * @Route("/new/agent", name="create_agent")
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
            $user->setAgent($agent);

            // ... perform some action, such as saving the agent to the database
            // for example, if Agent is a Doctrine entity, save it!
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->persist($agent);
            $entityManager->flush();

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
}
