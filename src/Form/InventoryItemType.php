<?php

namespace App\Form;

use App\Entity\InventoryField;
use App\Entity\InventoryItem;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class InventoryItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $item = $builder->getData();

        if ($item && $item->getCustomId()) {
            $builder->add('customId', TextType::class, [
                'label' => 'Item ID',
                'disabled' => true,
                'mapped' => false,
                'data' => $item->getCustomId(),
            ]);
        }

        $inventoryFields = $options['custom_fields'];

        foreach ($inventoryFields as $fieldDef) {
            $fieldName = $fieldDef->getStorageSlot();
            $label = $fieldDef->getFieldName();
            $isRequired = $fieldDef->isRequired();
            $constraints = [];

            if ($isRequired) {
                $constraints[] = new NotBlank(['message' => "$label is required."]);
            }

            switch ($fieldDef->getFieldType()) {
                case InventoryField::TYPE_TEXT:
                    $builder->add($fieldName, TextType::class, [
                        'label' => $label,
                        'required' => $isRequired,
                        'constraints' => $constraints,
                        'attr' => ['placeholder' => $fieldDef->getDescription()],
                    ]);
                    break;

                case InventoryField::TYPE_TEXTAREA:
                    $builder->add($fieldName, TextareaType::class, [
                        'label' => $label,
                        'required' => $isRequired,
                        'constraints' => $constraints,
                        'attr' => ['rows' => 4, 'placeholder' => $fieldDef->getDescription()],
                    ]);
                    break;

                case InventoryField::TYPE_NUMBER:
                    $builder->add($fieldName, NumberType::class, [
                        'label' => $label,
                        'required' => $isRequired,
                        'constraints' => $constraints,
                        'html5' => true,
                        'scale' => 2,
                    ]);
                    break;

                case InventoryField::TYPE_BOOLEAN:
                    $builder->add($fieldName, CheckboxType::class, [
                        'label' => $label,
                        'required' => false,
                    ]);
                    break;

                case InventoryField::TYPE_DOCUMENT_LINK:
                    $constraints[] = new Length(['max' => 1024]);
                    $builder->add($fieldName, UrlType::class, [
                        'label' => $label . ' (Link)',
                        'required' => $isRequired,
                        'constraints' => $constraints,
                        'default_protocol' => 'https',
                    ]);
                    break;
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => InventoryItem::class,
            'custom_fields' => [],
        ]);
        $resolver->setAllowedTypes('custom_fields', 'array');
    }
}
