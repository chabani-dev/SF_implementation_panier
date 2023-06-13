<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'attr'=>[
                    'class'=>'form-control',
                ],
                'label'=>'Entrez votre mail :',

            ])
            ->add('roles', ChoiceType::class, [
                'choices'=>[
                    'admin'=>'ROLE_ADMIN',
                    'client'=>'ROLE_USER',
                ],
                'multiple'=>true,
                'expanded'=>true,
                'attr'=>[
                    'class'=>'form-control',    
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
