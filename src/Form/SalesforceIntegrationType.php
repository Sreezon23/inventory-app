<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class SalesforceIntegrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('first_name', TextType::class, [
                'label' => 'First Name',
                'required' => true,
                'attr' => ['class' => 'form-control']
            ])
            ->add('last_name', TextType::class, [
                'label' => 'Last Name',
                'required' => true,
                'attr' => ['class' => 'form-control']
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => true,
                'attr' => ['class' => 'form-control']
            ])
            ->add('phone', TelType::class, [
                'label' => 'Phone Number',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('company_name', TextType::class, [
                'label' => 'Company Name',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('job_title', TextType::class, [
                'label' => 'Job Title',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('website', TextType::class, [
                'label' => 'Website',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'https://example.com']
            ])
            ->add('industry', ChoiceType::class, [
                'label' => 'Industry',
                'required' => false,
                'choices' => [
                    'Technology' => 'Technology',
                    'Healthcare' => 'Healthcare',
                    'Finance' => 'Finance',
                    'Education' => 'Education',
                    'Manufacturing' => 'Manufacturing',
                    'Retail' => 'Retail',
                    'Consulting' => 'Consulting',
                    'Other' => 'Other',
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Additional Information',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 4]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
