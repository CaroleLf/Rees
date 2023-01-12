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
                '0,5' => 50,
                '1' => 10,
                '1,5' => 15,
                '2' => 20,
                '2,5' => 25,
                '3' => 30,
                '3,5' => 35,
                '4' => 40,
                '4,5' => 45,
                '5' => 50]
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
