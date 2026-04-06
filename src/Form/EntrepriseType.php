<?php

namespace App\Form;

use App\Entity\Entreprise;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class EntrepriseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('Nom_Entreprise', TextType::class, [
                'label' => 'Nom de l\'entreprise',
                'attr' => [
                    'placeholder' => 'Nom entreprise',
                    'maxlength' => 255,
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le nom de l\'entreprise est obligatoire.']),
                    new Length([
                        'min' => 2,
                        'minMessage' => 'Le nom de l\'entreprise doit contenir au moins {{ limit }} caracteres.',
                        'max' => 255,
                    ]),
                ],
            ])
            ->add('Reference', TextType::class, [
                'label' => 'Reference',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Reference',
                    'maxlength' => 100,
                ],
                'constraints' => [
                    new Length([
                        'max' => 100,
                        'maxMessage' => 'La reference ne doit pas depasser {{ limit }} caracteres.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Entreprise::class,
        ]);
    }
}
