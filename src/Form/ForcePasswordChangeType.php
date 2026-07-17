<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PasswordStrength;

class ForcePasswordChangeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('newPassword', RepeatedType::class, [
            'type' => PasswordType::class,
            'mapped' => false,
            'first_options' => [
                'label' => 'Nouveau mot de passe',
                'attr' => ['placeholder' => 'Un mot de passe robuste...']
            ],
            'second_options' => [
                'label' => 'Confirmer le mot de passe',
                'attr' => ['placeholder' => 'Saisissez-le à nouveau']
            ],
            'invalid_message' => 'Les deux mots de passe doivent être identiques.',
            'constraints' => [
                new NotBlank(message: 'Veuillez saisir un mot de passe.'),
                new PasswordStrength(
                    minScore: PasswordStrength::STRENGTH_WEAK,
                    message: 'Ce mot de passe est trop faible. Utilisez des majuscules, chiffres et caractères spéciaux.'
                )
            ],
        ]);
    }
}