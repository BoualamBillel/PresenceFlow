<?php

namespace App\Form;

use App\Entity\Classe;
use App\Entity\Filiere;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClasseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'NOM DE LA CLASSE',
                'attr' => [
                    'placeholder' => 'Ex : DWWM - Promo 2'
                ]
            ])
            ->add('annee', TextType::class, [
                'label' => 'ANNÉE SCOLAIRE',
                'attr' => [
                    'placeholder' => 'Ex : 2026-2027'
                ]
            ])
            ->add('filiere', EntityType::class, [
                'class' => Filiere::class,
                'choice_label' => 'nom',
                'placeholder' => 'Sélectionner une filière...',
                'label' => 'FILIÈRE DE RATTACHEMENT'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Classe::class,
        ]);
    }
}
