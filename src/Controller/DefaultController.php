<?php
// src/Controller/DefaultController.php
namespace App\Controller;

use App\Entity\AgentTasks;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
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
     * @Route("/test", name="test", methods={"GET"})
     */
    public function test()
    {
        $this->encoders         =   [ new JsonEncoder() , new XmlEncoder() ];
        $this->normalizers      =   [ new ObjectNormalizer() ];
        $this->serializer       =   new Serializer($this->normalizers, $this->encoders);

        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));

        $user       = $this->entityManager->getRepository(User::class)->find(3);
        $task       = $this->entityManager->getRepository(AgentTasks::class)->find(1);

        $tasks = $user->getTasks();

        $normalizer = new ObjectNormalizer($classMetadataFactory);
        $serializer = new Serializer([$normalizer]);

        //$jsonContent = $serializer->normalize($user, 'json', ['groups' => ['group1', 'group2', 'group3', 'group4']]);
        //$jsonContent = $serializer->normalize($task, 'json', [ AbstractNormalizer::ATTRIBUTES => ['nom', 'description','date_debut' ] ] );
        $jsonContent = $serializer->normalize($tasks, 'json', [
                                        AbstractNormalizer::IGNORED_ATTRIBUTES => [ 'tasks','agent','dateDebut','dateFin', 'projets' ]
        ] );

        return $this->json(["data" => $jsonContent ]);
    }

    
}
