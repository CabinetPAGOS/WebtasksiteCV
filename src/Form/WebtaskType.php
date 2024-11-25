<?php

namespace App\Form;

use App\Entity\Webtask;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WebtaskType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        ->add('titre', TextType::class, [
            'label' => 'Titre de la Tâche :',
            'attr' => [
                'placeholder' => 'Renseigner le titre de la tâche',
                'style' => 'text-transform: uppercase;', // Forcer l'affichage en majuscules
            ],
        ])
            ->add('description', TextType::class, [
                'label' => 'Description :',
                'attr' => ['placeholder' => 'Renseigner la description de la tâche'],
            ])
            ->add('date_fin_demandee', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de Fin Demandée :',
                'attr' => ['placeholder' => 'Renseigner la date de fin demandée 🗓️'],
            ])
            ->add('baseclient', TextType::class, [
                'label' => 'Base de données :',
                'required' => false,
                'attr' => ['placeholder' => 'Renseigner la base de données📀'],
            ])
            ->add('tag', TextType::class, [
                'label' => 'Tag :',
                'required' => false,
                'attr' => ['placeholder' => 'Renseigner un tag'],
            ])
            ->add('lien_drive_1', TextType::class, [
                'label' => 'Ajouter des Documents :',
                'required' => false,
                'attr' => ['placeholder' => 'Lien du document 01'],
            ])
            ->add('lien_drive_2', TextType::class, [
                'required' => false,
                'attr' => ['placeholder' => 'Lien du document 02'],
            ])
            ->add('lien_drive_3', TextType::class, [
                'required' => false,
                'attr' => ['placeholder' => 'Lien du document 03'],
            ])
            ->add('code', TextType::class, [
                'label' => 'Code :',
                'attr' => ['placeholder' => 'Renseigner le code de la tâche'],
            ])
            ->add('libelle', TextType::class, [
                'label' => 'Libellé :',
                'attr' => ['placeholder' => 'Renseigner le libellé de la tâche'],
            ])
            ->add('avancement_de_la_tache', TextType::class, [
                'label' => 'Avancement de la Tâche :',
                'required' => false,
                'attr' => ['placeholder' => 'Renseigner l\'avancement de la tâche'],
            ])
            ->add('commentaire_webtask_client', TextType::class, [
                'label' => 'Commentaire Client :',
                'required' => false,
                'attr' => ['placeholder' => 'Renseigner le commentaire du client'],
            ])
            ->add('etat_de_la_webtask', TextType::class, [
                'label' => 'État de la Webtask :',
                'required' => false,
                'attr' => ['placeholder' => 'Renseigner l\'état de la webtask'],
            ])
            ->add('documents_attaches', TextType::class, [
                'label' => 'Documents Attachés :',
                'required' => false,
                'attr' => ['placeholder' => 'Renseigner les documents attachés'],
            ])
            ->add('filtre', TextType::class, [
                'label' => 'Filtre :',
                'required' => false,
                'attr' => ['placeholder' => 'Renseigner le filtre'],
            ])
            ->add('entite', TextType::class, [
                'label' => 'Entité :',
                'required' => false,
                'attr' => ['placeholder' => 'Renseigner l\'entité'],
            ])
            ->add('estimation_temps', TextType::class, [
                'label' => 'Estimation du Temps :',
                'required' => false,
                'attr' => ['placeholder' => 'Renseigner l\'estimation du temps'],
            ])
            ->add('demande_de_recettage', TextType::class, [
                'label' => 'Demande de Recettage :',
                'required' => false,
                'attr' => ['placeholder' => 'Renseigner la demande de recettage'],
            ])
            ->add('ordonnele', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date Ordonnée :',
                'required' => false,
                'attr' => ['placeholder' => 'Renseigner la date ordonnée'],
            ])
            ->add('archiver', TextType::class, [
                'label' => 'Archiver :',
                'required' => false,
                'attr' => ['placeholder' => 'Renseigner l\'archive'],
            ])
            ->add('ordre', TextType::class, [
                'label' => 'Ordre :',
                'required' => false,
                'attr' => ['placeholder' => 'Renseigner l\'ordre'],
            ])
            ->add('recommandations', TextType::class, [
                'label' => 'Recommandations :',
                'required' => false,
                'attr' => ['placeholder' => 'Renseigner les recommandations'],
            ])
            ->add('webtask_mere', TextType::class, [
                'label' => 'Webtask Mère :',
                'required' => false,
                'attr' => ['placeholder' => 'Renseigner la webtask mère'],
            ])
            ->add('commentaireinternepagos', TextType::class, [
                'label' => 'Commentaire Interne :',
                'required' => false,
                'attr' => ['placeholder' => 'Renseigner le commentaire interne'],
            ])
            ->add('sylob5', TextType::class, [
                'label' => 'Site Internet :',
                'required' => false,
                'attr' => ['placeholder' => 'Renseigner le site internet'],
            ])
            ->add('idtracabilite', TextType::class, [
                'label' => 'Tracabilité :',
                'required' => false,
                'attr' => ['placeholder' => 'Renseigner la tracabilité'],
            ])
            ->add('idversion', TextType::class, [
                'label' => 'Version :',
                'required' => false,
                'attr' => ['placeholder' => 'Renseigner la version'],
            ])
            ->add('etatVersion', TextType::class, [
                'label' => 'État de la Version :',
                'required' => false,
                'attr' => ['placeholder' => 'Renseigner l\'état de la version'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Webtask::class,
        ]);
    }
}