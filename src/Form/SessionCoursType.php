<?php

// src/Form/SessionCoursType.php
namespace App\Form;

use App\Entity\Classe;
use App\Entity\Matiere;
use App\Entity\SessionCours;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SessionCoursType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('matiere', EntityType::class, [
                'class' => Matiere::class,
                'choice_label' => 'nom',
                'placeholder' => 'Sélectionner une matière...',
                'label' => 'NOM DE LA MATIÈRE',
            ])
            ->add('dateCours', DateType::class, [
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'label' => 'DATE',
                'attr' => [
                    'min' => (new \DateTimeImmutable())->format('Y-m-d') 
                ]
            ])
            ->add('heureDebut', TimeType::class, [
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'label' => 'DÉBUT',
            ])
            ->add('heureFin', TimeType::class, [
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'label' => 'FIN',
            ])
            ->add('classe', EntityType::class, [
                'class' => Classe::class,
                'choice_label' => 'nom',
                'placeholder' => 'Sélectionner une classe...',
                'label' => 'CLASSE',
            ])
            ->add('formateur', EntityType::class, [
                'class' => User::class,
                'choice_label' => function (User $user) {
                    return $user->getPrenom() . ' ' . $user->getNom();
                },
                'placeholder' => 'Sélectionner un formateur...',
                'label' => 'FORMATEUR',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->andWhere('CAST_AS_TEXT(u.roles) LIKE :role')
                        ->andWhere('u.isArchived = false')
                        ->setParameter('role', '%"ROLE_FORMATEUR"%')
                        ->orderBy('u.nom', 'ASC');
                },
            ])
            ->add('emplacement', TextType::class, [
                'label' => 'SALLE / LIEU',
                'attr' => ['placeholder' => 'ex : Amphi B'],
            ])
            ->add('toleranceRetard', IntegerType::class, [
                'required' => false,
                'empty_data' => '15',
                'label' => 'TOLÉRANCE RETARD (MINUTES)',
                'attr' => ['placeholder' => 'ex : 5'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SessionCours::class,
        ]);
    }
}