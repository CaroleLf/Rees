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
                '0' => 0,
                '0,5' => 5/10,
                '1' => 1,
                '1,5' => 15/10,
                '2' => 2,
                '2,5' => 25/10,
                '3' => 3,
                '3,5' => 35/10,
                '4' => 4,
                '4,5' => 45/10,
                '5' => 5]
                ,'required' => true])

            ->add('comment', TextType::class,['required'   => false])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Rating::class,
        ]);
    }
}
