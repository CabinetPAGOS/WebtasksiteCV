<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\Client;
use App\Repository\UserRepository;
use App\Repository\ClientRepository; // Ajoutez cette ligne
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Doctrine\ORM\EntityManagerInterface;

class RegistrationFormType extends AbstractType
{
    private $clientRepository;

    public function __construct(ClientRepository $clientRepository) // Injection de dépendance
    {
        $this->clientRepository = $clientRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('id', TextType::class, [
                'label' => 'ID',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter an ID',
                    ]),
                ],
            ])

            ->add('email', TextType::class, [
                'label' => 'Email Address',
            ])

            ->add('nom', TextType::class, [
                'label' => 'Nom',
            ])

            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
            ])

            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a password',
                    ]),
                ],
                'label' => 'Password',
            ])

            ->add('webtaskOuvertureContact', CheckboxType::class, [
                'label' => 'Autorisation ouverture webtask',
                'required' => false,
            ])

            ->add('roles', ChoiceType::class, [
                'choices' => [
                    'Rôle utilisateur' => 'ROLE_USER',
                    'Rôle administrateur' => 'ROLE_ADMIN',
                ],
                'multiple' => true,
                'expanded' => true,
                'label' => 'Rôles :',
                'attr' => [
                    'class' => 'roles-checkbox', // Ajout d'une classe CSS pour le style
                ],
            ])

            ->add('idclient', EntityType::class, [
                'class' => Client::class,
                'choice_label' => function (Client $client) {
                    return $client->getRaisonSociale();
                },
                'label' => 'Sélectionner un Client',
                'placeholder' => 'Choisissez un client',
                'required' => true,
                'query_builder' => function () {
                    return $this->clientRepository->createQueryBuilder('c')
                        ->orderBy('c.raison_sociale', 'ASC'); // Tri par raison sociale
                },
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}

