<?php

namespace App\Form;

use App\Entity\Beacon;
use App\Entity\Course;
use App\Entity\User;
use BcMath\Number;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;


class CourseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'required' => true,
            ])
            ->add('description', TextareaType::class, [
                'required' => true,
            ])
            //->add('status')
            //->add('createAt')
            //->add('placementCompletedAt')
            //->add('updateAt')
            ->add('sameStartFinish', CheckboxType::class, [
                'required' => false,
            ])
            ->add('nbBeacons', NumberType::class, [
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new NotNull([
                        'message' => 'Le nombre de balises ne peut pas être vide.',
                    ]),
                    new GreaterThan([
                        'value' => 0,
                        'message' => 'Le nombre de balises doit être supérieur à 0.',
                    ]),
                    new LessThanOrEqual([
                        'value' => 50,
                        'message' => 'Le nombre de balises ne peut pas dépasser 50.',
                    ]),
                ],
            ])




            /*->add('beacons', EntityType::class, [
                'class' => Beacon::class,
                'choice_label' => 'id',
                'multiple' => true,
            ])
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'id',
            ])*/
            ->add('submit', SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Course::class,
        ]);
    }
}
