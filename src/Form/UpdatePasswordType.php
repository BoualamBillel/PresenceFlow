<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class UpdatePasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('oldPassword', PasswordType::class, [
                'mapped' => false,
                'constraints' => [
                    new UserPassword(['message' => 'Le mot de passe actuel est incorrect.']),
                ],
            ])
            ->add('newPassword', PasswordType::class, [
                'mapped' => false,
                'constraints' => [
                    new NotBlank(message: 'Veuillez entrer un nouveau mot de passe.'),
                    new Length(
                        min: 12,
                        minMessage: 'Ton mot de passe doit contenir au moins {{ limit }} caractères.'
                    ),
                ],
            ])
        ;
    }
}