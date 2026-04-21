<?php

namespace App\Form;

use App\Entity\Evenement;
use App\Entity\Activite;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class EvenementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('Titre', TextType::class, [
                'label'    => 'Titre de l\'événement',
                'required' => false,
                'attr'     => ['placeholder' => 'Ex: Journée portes ouvertes'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le titre est obligatoire.']),
                    new Assert\Length([
                        'min'        => 3,
                        'max'        => 100,
                        'minMessage' => 'Le titre doit avoir au moins {{ limit }} caractères.',
                        'maxMessage' => 'Le titre ne doit pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('Description', TextareaType::class, [
                'label'    => 'Description',
                'required' => false,
                'attr'     => ['rows' => 4, 'placeholder' => 'Décrivez l\'événement...'],
                'constraints' => [
                    new Assert\Length([
                        'max'        => 1000,
                        'maxMessage' => 'La description ne doit pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('Localisation', TextType::class, [
                'label'    => 'Localisation',
                'required' => false,
                'attr'     => ['placeholder' => 'Ex: Tunis, Salle A...'],
            ])
            ->add('Image', TextType::class, [
                'label'    => 'URL de l\'image',
                'required' => false,
                'attr'     => ['placeholder' => 'Ex: https://image.jpg'],
            ])
            ->add('est_payant', CheckboxType::class, [
                'label'    => 'Événement payant ?',
                'required' => false,
            ])
            ->add('prix', NumberType::class, [
                'label'    => 'Prix (DT)',
                'required' => false,
                'scale'    => 2,
                'attr'     => ['placeholder' => '0.00'],
                'constraints' => [
                    new Assert\PositiveOrZero(['message' => 'Le prix doit être un nombre positif.']),
                ],
            ])
            ->add('nbMax', IntegerType::class, [
                'label'    => 'Nombre maximum de participants',
                'required' => false,
                'attr'     => ['placeholder' => 'Ex: 100'],
                'constraints' => [
                    new Assert\Positive(['message' => 'Le nombre doit être supérieur à zéro.']),
                ],
            ])
            ->add('activites', EntityType::class, [
                'class'        => Activite::class,
                'choice_label' => 'Titre',
                'multiple'     => true,
                'expanded'     => true,
                'mapped'       => false,
                'required'     => false,
                'label'        => 'Choisir les activités',
                'constraints' => [
                    new Assert\Count(['min' => 1, 'minMessage' => 'Veuillez cocher au moins une activité.']),
                ],
            ])
            ->add('dateDebut', DateTimeType::class, [
                'mapped'   => false,
                'label'    => 'Date de début',
                'widget'   => 'single_text',
                'required' => false,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La date de début est obligatoire.']),
                ],
            ])
            ->add('dateFin', DateTimeType::class, [
                'mapped'   => false,
                'label'    => 'Date de fin',
                'widget'   => 'single_text',
                'required' => false,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La date de fin est obligatoire.']),
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Créer l\'événement',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Evenement::class,
            'attr'       => ['novalidate' => 'novalidate'],
            'constraints' => [
                new Assert\Callback([$this, 'validateDates'])
            ],
        ]);
    }

    public function validateDates($data, ExecutionContextInterface $context): void
    {
        $form = $context->getRoot();
        $dateDebut = $form->get('dateDebut')->getData();
        $dateFin = $form->get('dateFin')->getData();

        if ($dateDebut && $dateFin && $dateFin <= $dateDebut) {
            $context->buildViolation('La date de fin doit être postérieure à la date de début.')
                ->atPath('dateFin')
                ->addViolation();
        }
    }
}
