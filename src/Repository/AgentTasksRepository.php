<?php

namespace App\Repository;

use App\Entity\AgentTasks;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method AgentTasks|null find($id, $lockMode = null, $lockVersion = null)
 * @method AgentTasks|null findOneBy(array $criteria, array $orderBy = null)
 * @method AgentTasks[]    findAll()
 * @method AgentTasks[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AgentTasksRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AgentTasks::class);
    }

    // /**
    //  * @return AgentTasks[] Returns an array of AgentTasks objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?AgentTasks
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    /**
     * @param $projet_id
     * @param $user_id
     * @return array
     */
    public function findTasksRelatedToUserPerProjet($projet_id, $user_id) : array
    {
        return $this->createQueryBuilder('a')
                    ->where('a.projet = :projet_id')
                    ->setParameter('projet_id', $projet_id)
                    ->orderBy('a.priorite', 'DESC')
                    ->andWhere('a.agent = :agent_id')
                    ->setParameter('agent_id', $user_id)
                    ->getQuery()
                    ->execute();
    }

    /**
     * @param $user_id
     * @return array
     */
    public function findTasksRelatedToUser ( $user_id ) : array
    {
        return $this->createQueryBuilder('a')
                    ->where('a.agent = :agent_id')
                    ->setParameter('agent_id', $user_id)
                    ->getQuery()
                    ->execute();
    }
}
