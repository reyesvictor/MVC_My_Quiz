<?php

namespace App\Form;

use App\Entity\Quiz;
use App\Entity\Category;
use App\Form\QuestionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class QuizType extends AbstractType
{

    /**
     * Get configuration for form inpout.
     *
     * @param string $label
     * @param string $placeholder
     * @return array
     */
    private function getConfiguration($value, $placeholder)
    {
        return [
            'attr' => [
                'value' => $value,
                // 'label' => $label,
                'placeholder' => $placeholder,
            ]
        ];
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['get_category_repo'] != '') {

            $getRepo = $options['get_category_repo'];
            $categoryList = $getRepo->findAll();
            // dd($categoryList);
            foreach ($categoryList as $key => $value) {
                // dd($value);
                $choices[$value->getName()] = $value->getId();
                // $choices[$value->getName()] = new Category();
            }
        }

        // dd($choices);
        global $quiz;
        $quiz =  $options['quiz'];
        //New Quiz
        if ($quiz !== 'quiz') {
            $name_value = $quiz->getName();
            $data_value = $quiz->getData();
        } else {
            $name_value = "";
            $data_value = "";
        }
        $builder
            ->add('name', TextType::class, $this->getConfiguration($name_value, 'Title here'))
            ->add('data', TextType::class, $this->getConfiguration($data_value, 'Description here'))
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'getName',
                'preferred_choices' => function (?Category $category) {
                    global $quiz;
                        if ($quiz->getCategory()->getName() !== $category->getName()) {
                        return false;
                    }
                },
            ])
            ->add('questions', CollectionType::class, [
                'entry_type' => QuestionType::class,
                'label' => 'Questions List',
                // 'label_format' => 'form.questions.%name%',
                'entry_options' => [

                    // 'questions' => $options['quiz']->getQuestions(),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Quiz::class,
            'quiz' => 'quiz',
            'get_category_repo' => '',
        ]);
        // $resolver->setRequired('get_category_repo');
    }
}
