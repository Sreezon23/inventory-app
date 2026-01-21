<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class SupportTicketType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('summary', TextType::class, [
                'label' => 'Summary',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Brief description of your issue or question'
                ]
            ])
            ->add('priority', ChoiceType::class, [
                'label' => 'Priority',
                'required' => true,
                'choices' => [
                    'Low' => 'Low',
                    'Average' => 'Average',
                    'High' => 'High',
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 6,
                    'placeholder' => 'Please provide detailed information about your issue or question...'
                ]
            ])
            ->add('inventory_title', HiddenType::class, [
                'required' => false
            ])
            ->add('page_url', HiddenType::class, [
                'required' => false
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
