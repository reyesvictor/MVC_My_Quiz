<?php

namespace App\Form;

use App\Entity\Quiz;
use App\Entity\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class QuizType extends AbstractType
{

    /**
     * Get configuration for form inpout.
     *
     * @param string $label
     * @param string $placeholder
     * @return array
     */
    private function getConfiguration($label, $placeholder)
    {
        return [
            'attr' => [
                // 'value' => 'Jean',
                // 'label' => $label,
                'placeholder' => $placeholder,
            ]
        ];
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $getRepo = $options['get_category_repo'];
        $categoryList = $getRepo->findAll();
        // dd($categoryList);
        foreach ($categoryList as $key => $value) {
            // dd($value);
            $choices[$value->getName()] = $value->getId();
            // $choices[$value->getName()] = new Category();
        }

        // dd($choices);

        //New Quiz
        $builder
            ->add('name', TextType::class, $this->getConfiguration('Titre', 'Votre titre ici'))
            ->add('data')
            // ->add('category', ChoiceType::class, [
            //     // 'class' => Category::class
            //     'choices'  => $choices,
            //     // 'choices'  => [
            //     //     new Category(2),
            //     //     new Category(3),
            //     //     new Category(4),

            //     // ]
            // ],);
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Quiz::class,
        ]);
        $resolver->setRequired('get_category_repo');
    }
}
