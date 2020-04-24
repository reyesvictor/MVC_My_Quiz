<?php
namespace App\Form;

use App\Entity\Quiz;
use App\Entity\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class QuizType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $getRepo = $options['get_category_repo'];
        $categoryList = $getRepo->findAll();
        foreach ($categoryList as $key => $value) {
            $choices[$value->getName()] = $value->getId();
        }
        
        $builder
            ->add('name')
            ->add('data')
            ->add('category', ChoiceType::class, [
                'choices'  => $choices,
            ],);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Quiz::class,
        ]);
        $resolver->setRequired('get_category_repo');
    }
}
