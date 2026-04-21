<?php

namespace App\Form;

use App\Entity\Activite;
use App\Entity\ParticipationEvenement;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ParticipationEvenementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomComplet', TextType::class, [
                'label' => 'Nom et Prénom',
                'attr' => ['placeholder' => 'Ex: Jean Dupont']
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse Email',
                'attr' => ['placeholder' => 'Ex: jean.dupont@email.com']
            ])
            ->add('modePaiement', ChoiceType::class, [
                'label' => 'Mode de paiement',
                'choices'  => [
                    'En ligne' => 'Online',
                    'Sur place' => 'Sur place',
                ],
                'required' => false
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Remarques (Optionnel)',
                'required' => false,
                'attr' => ['rows' => 3, 'placeholder' => 'Avez-vous des besoins spécifiques ?']
            ])
            ->add('activite', EntityType::class, [
                'class' => Activite::class,
                'choice_label' => 'titre',
                'label' => '🎯 Choisissez une activité :',
                'choices' => $options['activites_evenement'],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ParticipationEvenement::class,
            'activites_evenement' => []
        ]);
    }
}
