<?php 
namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType 
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('prenom', TextType::class, [
                'attr' => [
                    'placeholder' => 'Ex: Marie'
                ]
            ])
            ->add('nom', TextType::class, [
                'attr' => [
                    'placeholder' => 'Ex: Curie'
                ]
            ])
            ->add('email', EmailType::class, [
                'attr' => [
                    'placeholder' => 'Ex: marie.curie@domaine.fr'
                ]
            ])
            ->add('role', ChoiceType::class, [
                'mapped' => false,
                'choices' => [
                    'APPRENANT' => 'ROLE_ETUDIANT',
                    'FORMATEUR' => 'ROLE_FORMATEUR',
                ],
                'expanded' => true, // Force les boutons radio
                'multiple' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}