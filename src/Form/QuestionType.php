<?php

namespace App\Form;

use App\Entity\Question;
use App\Form\AnswerType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class QuestionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        // dd($options['questions']->getName());

        $builder
            ->add('name', TextType::class)
            ->add('answers', CollectionType::class,[
                'entry_type' => AnswerType::class,
                'label' => 'Answers',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Question::class,
            // 'questions' => '',
        ]);
    }
}
