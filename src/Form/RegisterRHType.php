<?php

namespace App\Form;

use App\Entity\Utilisateur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class RegisterRHType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('Nom_Utilisateur', TextType::class, [
                'label' => 'Nom d\'utilisateur',
                'attr' => [
                    'placeholder' => 'Nom d\'utilisateur',
                    'minlength' => 3,
                    'maxlength' => 100,
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le nom d\'utilisateur est obligatoire.']),
                    new Length([
                        'min' => 3,
                        'minMessage' => 'Le nom d\'utilisateur doit contenir au moins {{ limit }} caracteres.',
                        'max' => 100,
                    ]),
                ],
            ])
            ->add('Mot_Passe', PasswordType::class, [
                'label' => 'Mot de passe',
                'attr' => [
                    'placeholder' => 'Mot de passe',
                    'minlength' => 6,
                    'maxlength' => 255,
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le mot de passe est obligatoire.']),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caracteres.',
                        'max' => 255,
                    ]),
                ],
            ])
            ->add('Email', EmailType::class, [
                'label' => 'Email professionnel',
                'attr' => [
                    'placeholder' => 'email@entreprise.com',
                    'maxlength' => 255,
                ],
                'constraints' => [
                    new NotBlank(['message' => 'L\'email est obligatoire.']),
                    new Email(['message' => 'Veuillez saisir un email valide.']),
                ],
            ])
            ->add('Num_Tel', TelType::class, [
                'label' => 'Numero de telephone',
                'required' => false,
                'attr' => [
                    'placeholder' => '12345678',
                    'maxlength' => 8,
                    'inputmode' => 'numeric',
                ],
                'constraints' => [
                    new Regex([
                        'pattern' => '/^$|^[0-9]{8}$/',
                        'message' => 'Le numero de telephone doit contenir exactement 8 chiffres.',
                    ]),
                ],
            ])
            ->add('Adresse', TextType::class, [
                'label' => 'Adresse',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Adresse',
                    'maxlength' => 255,
                ],
                'constraints' => [
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'L\'adresse ne doit pas depasser {{ limit }} caracteres.',
                    ]),
                ],
            ])
            ->add('CIN', TextType::class, [
                'label' => 'CIN',
                'required' => false,
                'attr' => [
                    'placeholder' => 'CIN',
                    'maxlength' => 8,
                    'inputmode' => 'numeric',
                ],
                'constraints' => [
                    new Regex([
                        'pattern' => '/^$|^[0-9]{8}$/',
                        'message' => 'Le CIN doit contenir exactement 8 chiffres.',
                    ]),
                ],
            ])
            ->add('Date_Naissance', DateType::class, [
                'label' => 'Date de naissance',
                'widget' => 'single_text',
                'required' => false,
                'constraints' => [
                    new LessThanOrEqual([
                        'value' => 'today',
                        'message' => 'La date de naissance ne peut pas etre dans le futur.',
                    ]),
                ],
            ])
            ->add('Gender', ChoiceType::class, [
                'label' => 'Genre',
                'choices' => [
                    'Homme' => 'M',
                    'Femme' => 'F',
                ],
                'expanded' => false,
                'multiple' => false,
                'required' => false,
                'placeholder' => 'Selectionnez votre genre',
            ])
            ->add('entreprise', EntrepriseType::class)
            ->add('submit', SubmitType::class, [
                'label' => 'Creer un compte RH',
                'attr' => ['class' => 'btn btn-primary'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Utilisateur::class,
        ]);
    }
}
