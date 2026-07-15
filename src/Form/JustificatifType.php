<?php
namespace App\Form;

use App\Entity\Justificatif;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class JustificatifType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('motifAbsence', TextareaType::class, [
                'label' => 'Motif de l\'absence',
                'required' => true,
            ])
            ->add('fichier', FileType::class, [
                'label' => 'Justificatif (PDF, JPG, PNG)',
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new File(
                        maxSize: '10M',
                        mimeTypes: [
                            'application/pdf',
                            'application/x-pdf',
                            'image/jpeg',
                            'image/png',
                        ],
                        mimeTypesMessage: 'Format non autorisé. Utilisez PDF ou image.',
                        maxSizeMessage: 'Fichier trop lourd (5 Mo max).'
                    )
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Justificatif::class,
        ]);
    }
}