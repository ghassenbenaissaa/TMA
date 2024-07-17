<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EditAventureType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class)
            ->add('description', TextareaType::class, [
                'attr' => [
                    'maxlength' => 255,
                ],
            ])
            ->add('images', FileType::class, [
                'label' => 'Image (PNG, JPEG)',
                'mapped' => false,
                'required' => false,
                'multiple' => true,
                'attr' => [
                    'accept' => 'image/png, image/jpeg',
                    'id' => 'images',
                ],
            ])
            ->add('Pays', CountryType::class,['mapped' => false,])
            ->add('recommander', CheckboxType::class, [
                'label' => 'Recommendation',
                'required' => false, // Peut être configuré selon vos besoins
                'attr' => ['class' => 'custom-switch'],
            ])
            ->add('video', TextType::class, [
                'required' => false, // Facultatif selon votre logique métier
            ]) ->add('audiance', ChoiceType::class, [
                'label' => 'Audiance',
                'choices' => [
                    'Public' => 'public',
                    'Friends' => 'friends',
                    'Only Me' => 'only_me',
                ],
                'expanded' => true, // Afficher comme des boutons radio
                'multiple' => false, // Permettre la sélection d'un seul choix
            ])
            ->add('dateDebut', DateTimeType::class, [
                'mapped' => true,
            ])
            ->add('dateFin', DateTimeType::class, [
                'mapped' => true,
            ]);

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
