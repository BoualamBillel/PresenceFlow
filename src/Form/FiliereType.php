<?php

namespace App\Form;

use App\Entity\Filiere;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FiliereType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom')
            ->add('description')
            ->add('niveau', ChoiceType::class, [
                'choices' => [
                    'Niveau 5 (Bac+2)' => 'Niveau 5 (Bac+2)',
                    'Niveau 6 (Bac+3/4)' => 'Niveau 6 (Bac+3/4)',
                    'Niveau 7 (Bac+5)' => 'Niveau 7 (Bac+5)',
                ],
                'placeholder' => 'Sélectionner un niveau...'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Filiere::class,
        ]);
    }
}
