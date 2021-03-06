<?php

namespace App\Form;

use App\Entity\AgentTasks;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AgentTasksType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        //$user = $this->getUser();

        $builder
            ->add('nom', TextType::class, [
                "label" => "Nom de la tâche"
            ])
            ->add('description', TextareaType::class)
            ->add('date_debut')
            ->add('date_fin')
            ->add('priorite', ChoiceType::class, [
                'choices' => [
                    'Basse' => 0,
                    'Moyenne' => 1,
                    'Elevé' => 2
                ],
                'label' => 'Priorité'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => AgentTasks::class,
        ]);
    }
}
