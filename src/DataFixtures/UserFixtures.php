<?php

namespace App\DataFixtures;

use App\Entity\User; 
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserFixtures extends Fixture
{
    private $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    public function load(ObjectManager $manager)
    {
        // $product = new Product();
        // $manager->persist($product);
        $user   = new User();
        $user->setEmail("alexkhang25@yahoo.fr");
        $user->setPassword($this->passwordEncoder->encodePassword($user,"abcd1234")); 
        $user->setFirstname("Joel");
        $user->setLastname("Khang");
        $manager->persist($user);
        
        $user1   = new User();
        $user1->setEmail("alex_khang@yahoo.fr");
        $user1->setPassword($this->passwordEncoder->encodePassword($user,"1234abcd")); 
        $user1->setFirstname("Alex");
        $user1->setLastname("Khang");
        $manager->persist($user1);

    
        $manager->flush();
    }
}
