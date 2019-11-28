<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
}
