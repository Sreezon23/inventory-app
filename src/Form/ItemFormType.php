<?php

namespace App\Form;

use App\Entity\InventoryItem;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ItemFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('textField1', TextType::class, ['required' => false, 'label' => 'Text Field 1'])
            ->add('textField2', TextType::class, ['required' => false, 'label' => 'Text Field 2'])
            ->add('textField3', TextType::class, ['required' => false, 'label' => 'Text Field 3'])
            ->add('numberField1', NumberType::class, ['required' => false, 'label' => 'Number Field 1', 'scale' => 2])
            ->add('numberField2', NumberType::class, ['required' => false, 'label' => 'Number Field 2', 'scale' => 2])
            ->add('numberField3', NumberType::class, ['required' => false, 'label' => 'Number Field 3', 'scale' => 2])
            ->add('dateField1', DateType::class, ['required' => false, 'label' => 'Date Field 1', 'widget' => 'single_text'])
            ->add('dateField2', DateType::class, ['required' => false, 'label' => 'Date Field 2', 'widget' => 'single_text'])
            ->add('dateField3', DateType::class, ['required' => false, 'label' => 'Date Field 3', 'widget' => 'single_text']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => InventoryItem::class,
        ]);
    }
}
