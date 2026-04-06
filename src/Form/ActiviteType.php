<?php

// Déclaration du namespace : ce fichier appartient au dossier Form
namespace App\Form;

// On importe l'entité Activite pour lier ce formulaire à cette entité
use App\Entity\Activite;
// AbstractType est la classe de base pour tout formulaire Symfony
use Symfony\Component\Form\AbstractType;
// Types de champs utilisés dans ce formulaire
use Symfony\Component\Form\Extension\Core\Type\TextType;      // champ texte simple
use Symfony\Component\Form\Extension\Core\Type\TextareaType;  // zone de texte multiligne
use Symfony\Component\Form\Extension\Core\Type\SubmitType;    // bouton soumettre
// FormBuilderInterface permet de construire les champs du formulaire
use Symfony\Component\Form\FormBuilderInterface;
// OptionsResolver configure les options par défaut du formulaire
use Symfony\Component\OptionsResolver\OptionsResolver;
// Assert contient les règles de validation côté PHP (pas HTML)
use Symfony\Component\Validator\Constraints as Assert;

class ActiviteType extends AbstractType
{
    // Cette méthode construit le formulaire champ par champ
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('Titre', TextType::class, [
                'label' => 'Titre de l\'activité',
                'required' => false,
                'attr' => ['placeholder' => 'Ex: Conférence, Atelier...'],
                'constraints' => [
                    // Règle 1 : le titre ne peut pas être vide
                    new Assert\NotBlank(['message' => 'Le titre est obligatoire.']),
                    // Règle 2 : entre 3 et 100 caractères
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
                'required' => false,                          // ce champ n'est pas obligatoire
                'attr'     => ['rows' => 4, 'placeholder' => 'Décrivez l\'activité...'],
                'constraints' => [
                    // Maximum 1000 caractères
                    new Assert\Length([
                        'max'        => 1000,
                        'maxMessage' => 'La description ne doit pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])


            // Bouton de soumission
            ->add('submit', SubmitType::class, [
                'label' => 'Enregistrer',
            ]);
    }

    // Cette méthode lie le formulaire à l'entité Activite
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Activite::class,                  // formulaire lié à l'entité Activite
            'attr'       => ['novalidate' => 'novalidate'],   // désactive la validation HTML5 du navigateur
        ]);
    }
}
