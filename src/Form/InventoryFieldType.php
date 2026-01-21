<?php

namespace App\Form;

use App\Entity\InventoryField;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InventoryFieldType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fieldName', TextType::class, ['label' => 'Field Name'])
            ->add('fieldType', ChoiceType::class, [
                'choices'  => [
                    'Text (String)' => 'string',
                    'Number (Integer)' => 'integer',
                    'Date' => 'date',
                    'Checkbox (Boolean)' => 'boolean',
                ],
            ])
            ->add('storageSlot', ChoiceType::class, [
                'label' => 'Storage Slot',
                'choices' => array_combine(InventoryField::ALLOWED_SLOTS, InventoryField::ALLOWED_SLOTS),
                'help' => 'Select where this field will be stored in the database',
            ])
            ->add('isRequired', CheckboxType::class, ['required' => false, 'label' => 'Required Field'])
            ->add('fieldOrder', IntegerType::class, ['data' => 0, 'label' => 'Display Order']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => InventoryField::class]);
    }
}