<?php

namespace App\Form;

use App\Entity\Answer;
use App\Entity\Question;
use App\Form\AnswerType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class PlayType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $question = $builder->getAttributes()['data_collector/passed_options']['data'];
        // $question = $options['question'];
        // $arr = [];
        // foreach ($question->getAnswers() as $key => $answer) {
        //     $arr[$key] = $question->getAnswers();
        // }

        // dd( $question->getAnswers()[0]);
        // dd($arr);
        // dd($question->getAnswers());
        $builder
            ->add('name', TextType::class, [
                'disabled' => true,
            ])
            // ->add('answers', CollectionType::class,[
            //     'entry_type' => AnswerType::class,
            //     'label' => 'Answers',
            //     // 'disabled' => true,
            // ])
            ->add('answers', EntityType::class, [
                'class' => CheckboxType::class,
                'choices' => [
                    '1' => true, 
                    '2' => false, 
                    '3' => false, 
                ],
            ])
            // ->add('answers', EntityType::class, [
            //     'class' => Question::class,
            //     // 'choices'=> $question->getAnswers()[0],
            //     // 'choices'=> $arr,
            //     'choices' => $question->getAnswers(),
            // ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Question::class,
            'question' => '',
        ]);
    }
}
