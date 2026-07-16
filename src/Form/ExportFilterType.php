<?php

namespace App\Form;

use App\Entity\Classe;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ExportFilterType extends AbstractType
{
    public function __construct(private UserRepository $userRepository)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $allUsers = $this->userRepository->findAll();
        
        $etudiants = array_filter($allUsers, function(User $user) {
            return in_array('ROLE_ETUDIANT', $user->getRoles());
        });

        usort($etudiants, function(User $a, User $b) {
            return strcmp($a->getNom(), $b->getNom());
        });

        $builder
            ->add('classe', EntityType::class, [
                'class' => Classe::class,
                'choice_label' => 'nom',
                'required' => false,
                'placeholder' => 'Toutes les classes',
                'label' => 'Filtrer par Classe'
            ])
            ->add('etudiant', EntityType::class, [
                'class' => User::class,
                'choices' => $etudiants,
                'choice_label' => function (User $user) {
                    return $user->getNom() . ' ' . $user->getPrenom();
                },
                'choice_attr' => function (User $user) {
                    $classe = $user->getClasses()->first();
                    return ['data-classe' => $classe ? $classe->getId() : ''];
                },
                'required' => false,
                'placeholder' => 'Tous les étudiants',
                'label' => 'Ou filtrer un élève spécifique'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}