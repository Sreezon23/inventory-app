<?php

namespace App\Form;

use App\Entity\Inventory;
use App\Entity\InventoryTag;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class InventoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Inventory Name',
                'constraints' => [
                    new NotBlank(),
                    new Length(min: 3, max: 255),
                ],
                'attr' => [
                    'placeholder' => 'e.g. My Retro Game Collection',
                ],
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'Category',
                'choices' => [
                    'Books' => 'Books',
                    'Electronics' => 'Electronics',
                    'Furniture' => 'Furniture',
                    'Tools' => 'Tools',
                    'Other' => 'Other',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['rows' => 4],
            ])
            ->add('imageUrl', TextType::class, [
                'label' => 'Image URL',
                'required' => false,
                'attr' => [
                    'placeholder' => 'https://example.com/image.jpg',
                ],
            ])
            ->add('tags', EntityType::class, [
                'class' => InventoryTag::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => false,
                'required' => false,
            ])
            ->add('isPublic', CheckboxType::class, [
                'label' => 'Make this inventory public?',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Inventory::class,
        ]);
    }
}
