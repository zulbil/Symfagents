<?php
// src/Controller/DefaultController.php
namespace App\Controller;

use App\Entity\AgentTasks;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Forms;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class DefaultController extends AbstractController
{
    private $normalizers;
    private $serializer;
    private $encoders;
    private $entityManager;

    public function __construct (EntityManagerInterface $entityManager) {
        $this->entityManager    =   $entityManager;
    }
    /**
     * @Route("/page/name/{name}", name="about", methods={"GET"})
     */
    public function page($name)
    {
        $data               = array(); 
        $page               = $name; 
        $date               = date("Y-m-d h:i:s"); 

        $data['page']       = ucfirst($page);
        $data['date']       = $date; 

        return $this->render('app/home.html.twig', $data);
    }

    /**
     * @Route("/new-agent", name="new_agent", methods={"GET"})
     */
    public function createAgent()
    {
        $data               = array(); 
        $page               = "Creation d'un nouvel Agent"; 
        $date               = date("Y-m-d h:i:s"); 

        $data['page']       = ucfirst($page);
        $data['date']       = $date; 

        //return $this->render('app/new-agent.html.twig', $data);

        $datetime = new DateTime(date('Y-m-d')); 

        var_dump($datetime); 
    }

    /**
     * @Route("/test", name="test")
     */
    public function test()
    {
        $formFactory = Forms::createFormFactory();

        $form = $formFactory->createBuilder()
           ->add('username', TextType::class)
           ->add('showEmail', CheckboxType::class)
           ->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
               $user = $event->getData();
               $form = $event->getForm();

               if (!$user) {
                   return;
               }

               // checks whether the user has chosen to display their email or not.
               // If the data was submitted previously, the additional value that is
               // included in the request variables needs to be removed.
               var_dump($user);
               exit();

               if (isset($user['showEmail']) && $user['showEmail']) {
                   $form->add('email', EmailType::class);
               } else {
                   unset($user['email']);
                   $event->setData($user);
               }
           })
           ->add('save', SubmitType::class)
           ->getForm();

       $data = array();
       $data['form'] = $form->createView();
       $data['page'] = "Test";

       return $this->render('app/test.html.twig', $data);
    }

    
}
