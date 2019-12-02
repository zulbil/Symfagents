<?php

namespace App\Form;

use App\Entity\Agent;
use Doctrine\DBAL\Types\DateTimeType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AgentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nom')
            ->add('postnom')
            ->add('prenom')
            ->add('email', EmailType::class, [
                "mapped" => false,
                "label" => "Email"
            ])
            ->add('fonction', ChoiceType::class, [
                'choices' => [
                    'Developpeur' => 'Developpeur',
                    'IT' => 'IT',
                    'Big Data' => 'Big Data',
                    'Community Manager' => 'Community Manager',
                    'SysAdmin' => 'SysAdmin'
                ]
            ])
            ->add('salaire');
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Agent::class,
        ]);
    }
}
