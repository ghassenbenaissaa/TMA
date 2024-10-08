<?php

namespace App\Form;

use App\Entity\Continent;
use App\Entity\Pays;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AventureType extends AbstractType
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
                'attr' => ['class' => 'custom-switch'], // Ajoutez des classes CSS personnalisées si nécessaire
            ])
            ->add('video', TextType::class, [
                'required' => false, // Facultatif selon votre logique métier
            ])
            ->add('audience', ChoiceType::class, [
                'label' => 'Audience',
                'choices' => [
                    'Public' => 'public',
                    'Friends' => 'friends',
                    'Only Me' => 'only_me',
                ],
                'expanded' => true, // Afficher comme des boutons radio
                'multiple' => false, // Permettre la sélection d'un seul choix
                'mapped' => false,
                'data' => 'public',
            ])
            ->add('dateDebut', DateTimeType::class, [
            ])
            ->add('dateFin', DateTimeType::class, [
            ]);

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
