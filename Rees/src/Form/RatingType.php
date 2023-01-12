<?php

namespace App\Form;

use App\Entity\Rating;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class RatingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('value', ChoiceType::class, [  'choices'  => [
                '0 🌑🌑🌑🌑🌑' => 0,
                '0.5 🌗🌑🌑🌑🌑' => 5.0/10.0,
                '1 🌕🌑🌑🌑🌑' => 1,
                '1.5 🌕🌗🌑🌑🌑' => 15.0/10.0,
                '2 ⭐' => 2,
                '2.5 ⭐' => 25.0/10.0,
                '3' => 3,
                '3.5' => 35.0/10.0,
                '🌕🌕🌕🌕🌑' => 4,
                '🌕🌕🌕🌕🌗' => 45.0/10.0,
                '🌕🌕🌕🌕🌕' => 5]
                ,'required' => true,
                'label' => 'Valeur',])

            ->add('comment', TextType::class,['required'   => false,
                'label' => 'Commentaire'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Rating::class,
        ]);
    }
}
