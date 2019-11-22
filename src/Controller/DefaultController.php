<?php
// src/Controller/DefaultController.php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController; 

class DefaultController extends AbstractController
{
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
     * @Route("/test", name="new_agent", methods={"GET"})
     */
    public function test()
    {
        $data               = array(); 
        $page               = "Creation d'un nouvel Agent"; 
        $date               = date("Y-m-d h:i:s"); 

        $data['page']       = ucfirst($page);
        $data['date']       = $date; 

        //return $this->render('app/new-agent.html.twig', $data);

        $current_date = date("Y-m-d H:i:s"); 
        $dt = \DateTime::createFromFormat('Y-m-d h:i:s', $current_date);  

        var_dump($dt); 

        exit(); 
    }

    
}
