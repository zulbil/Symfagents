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
     * @Route("/test", name="test", methods={"GET"})
     */
    public function test()
    {
        $permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        // Output: 54esmdr0qf
        echo substr(str_shuffle($permitted_chars), 0, 10);

        exit(); 
    }

    
}
