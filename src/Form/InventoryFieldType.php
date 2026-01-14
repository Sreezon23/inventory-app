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
            ->add('isRequired', CheckboxType::class, ['required' => false])
            ->add('fieldOrder', IntegerType::class, ['data' => 0]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => InventoryField::class]);
    }
}