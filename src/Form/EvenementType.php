<?php

// Déclaration du namespace : ce fichier appartient au dossier Form
namespace App\Form;

// On importe l'entité Evenement pour lier ce formulaire à cette entité
use App\Entity\Evenement;
// On importe l'entité Activite pour le champ de relation ManyToMany
use App\Entity\Activite;
// EntityType permet d'afficher une liste d'objets depuis la base de données
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
// AbstractType est la classe de base pour tout formulaire Symfony
use Symfony\Component\Form\AbstractType;
// Les types de champs du formulaire
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;  // case à cocher
use Symfony\Component\Form\Extension\Core\Type\NumberType;    // nombre décimal
use Symfony\Component\Form\Extension\Core\Type\IntegerType;   // nombre entier
use Symfony\Component\Form\Extension\Core\Type\TextType;      // champ texte simple
use Symfony\Component\Form\Extension\Core\Type\TextareaType;  // zone de texte
use Symfony\Component\Form\Extension\Core\Type\SubmitType;    // bouton soumettre
// FormBuilderInterface permet de construire le formulaire
use Symfony\Component\Form\FormBuilderInterface;
// OptionsResolver configure les options du formulaire
use Symfony\Component\OptionsResolver\OptionsResolver;
// Assert contient toutes les règles de validation (côté PHP, pas HTML)
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class EvenementType extends AbstractType
{
    // Cette méthode construit le formulaire ligne par ligne
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            // On ajoute un champ 'Titre' de type Texte
            ->add('Titre', TextType::class, [
                'label'    => 'Titre de l\'événement',
                'required' => false,
                'attr'     => ['placeholder' => 'Ex: Journée portes ouvertes'],
                'constraints' => [
                    // Règle 1 : le champ ne peut pas être vide
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

            // Champ : Description (facultatif)
            ->add('Description', TextareaType::class, [
                'label'    => 'Description',
                'required' => false,                          // champ non obligatoire
                'attr'     => ['rows' => 4, 'placeholder' => 'Décrivez l\'événement...'],
                'constraints' => [
                    // Maximum 1000 caractères
                    new Assert\Length([
                        'max'        => 1000,
                        'maxMessage' => 'La description ne doit pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])

            // On ajoute la localisation (Lieu)
            ->add('Localisation', TextType::class, [
                'label'    => 'Localisation',
                'required' => false, // On laisse false car on gère par Assert, mais l'erreur s'affichera
                'attr'     => ['placeholder' => 'Ex: Tunis, Salle A...'],
            ])

            // Champ : Image (facultatif)
            ->add('Image', TextType::class, [
                'label'    => 'URL de l\'image',
                'required' => false,
                'attr'     => ['placeholder' => 'Ex: https://image.jpg'],
            ])

            // Champ : est_payant (case à cocher)
            ->add('est_payant', CheckboxType::class, [
                'label'    => 'Événement payant ?',
                'required' => false,
            ])

            // Champ : prix (nombre décimal, facultatif)
            ->add('prix', NumberType::class, [
                'label'    => 'Prix (DT)',
                'required' => false,
                'scale'    => 2,                              // 2 chiffres après la virgule
                'attr'     => ['placeholder' => '0.00'],
                'constraints' => [
                    new Assert\PositiveOrZero(['message' => 'Le prix doit être un nombre positif.']),
                ],
            ])

            // Champ : nbMax (nombre entier, facultatif)
            ->add('nbMax', IntegerType::class, [
                'label'    => 'Nombre maximum de participants',
                'required' => false,
                'attr'     => ['placeholder' => 'Ex: 100'],
                'constraints' => [
                    new Assert\Positive(['message' => 'Le nombre doit être supérieur à zéro.']),
                ],
            ])

            // Liste des activités (Case à cocher : expanded=true, multiple=true)
            ->add('activites', EntityType::class, [
                'class'        => Activite::class,
                'choice_label' => 'Titre',
                'multiple'     => true,
                'expanded'     => true,
                'mapped'       => false, // Géré manuellement dans le contrôleur
                'required'     => false,
                'label'        => 'Choisir les activités',
                'constraints' => [
                    new Assert\Count(['min' => 1, 'minMessage' => 'Veuillez cocher au moins une activité.']),
                ],
            ])

            // Champs de date (attention : non-mappés car on gère l'objet Ordre manuellement)
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

            // Bouton de validation
            ->add('submit', SubmitType::class, [
                'label' => 'Créer l\'événement',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Evenement::class,
            'attr'       => ['novalidate' => 'novalidate'], // Désactive la validation HTML5
            'constraints' => [
                new Assert\Callback([$this, 'validateDates'])
            ],
        ]);
    }

    /**
     * Vérifie que la date de fin est après la date de début
     */
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
