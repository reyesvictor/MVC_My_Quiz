<?php

namespace App\DataFixtures;

use App\Entity\Quiz;
use App\Entity\User;
use App\Entity\Answer;
use App\Entity\Category;
use App\Entity\Question;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType as DateTimeType;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        // $product = new Product();
        // $manager->persist($product);

        $arr = [

            //refaire tout
            //dabord une cateogie FILM
            //dans film on met un quiz harry potter
            //dans ce quiz on met plusieurs questions
            //dans ces questions on met plusieurs reponses
            //donc il faut que je crée une entity question
            //avec une relation onetomany Question->Quiz
            //et une entity reponse avec un tinyint
            //relation onetomany Answer->Question
            //ou tout foutre dans un tableau MDR

            //Categorie => [
            //     Quiz => [
            //         Question => [
            //             reponses => true/false
            //         ]
            //     ]
            // ]
            'Films' => [
                'Harry Potter' => [
                    'Dans la partie d’échec Harry Potter prend la place de :' => [
                        'Un fou' => true,
                        'Une tour' => false,
                        'Un pion' => false,
                    ],
                    'Quel est le mot de passe du bureau de Dumbledore ?' => [
                        'Chocogrenouille' => false,
                        'Sorbet Citron' => true,
                        'Dragées Surprise' => false,
                    ]
                ],
            ],
            'Sigles' => [
                'Sigles Français' => [
                    'Que signifie CROUS ?' => [
                        "Centre de Restauration et d'Organisation Universitaire et Secondaire" => false,
                        "Comité Régional pour l'Organisation Universitaire et Scolaire" => false,
                        "Centre Régional des Oeuvres Universitaires et Scolaires" => true,
                    ]
                ]
            ]
            // 'Définitions de mots',
            // 'Les spécialités culinaires',
            // 'Séries TV : Les Simpson - partie 1',
            // 'Séries TV : Les Simpson - partie 2',
            // 'Séries TV : Stargate SG1',
            // 'Séries TV : NCIS',
            // 'Jeux de société',
            // 'Programmation',
            // 'Sigles Informatiques',
        ];
        // for ($i = 0; $i < count($cat_arr); $i++) {
        foreach ($arr as $cat_name => $quiz) {
            $category_obj = new Category();
            if (!is_array($quiz)) {
                $category_obj->setName($quiz);
            } else {
                $category_obj->setName($cat_name);
            }

            if (isset($quiz) && is_array($quiz)) {
                foreach ($quiz as $name => $data) {
                    $quiz_obj = new Quiz();
                    $quiz_obj->setName($name)
                        ->setData('Description faite dans AppFixtures')
                        ->setCategory($category_obj);
                    foreach ($data as $question_name => $answers) {
                        $question_obj = new Question();
                        $question_obj->setName($question_name)
                            ->setQuizId($quiz_obj);
                        foreach ($answers as $answer => $value) {
                            $answer_obj = new Answer();
                            $answer_obj->setName($answer)
                                ->setQuestionId($question_obj)
                                ->setIsCorrect($value);
                            $manager->persist($answer_obj);
                        }
                        $manager->persist($question_obj);
                    }
                    $manager->persist($quiz_obj);
                }
            }
            $manager->persist($category_obj);
        }

        $users_arr = [
            'Anonymous',
            'Martin Aubry',
            'Edouard Balladur',
            'Robert Badinter',
            'Jean-Marc Ayrault',
            'Benoit Hamon',
        ];

        for ($i = 0; $i < count($users_arr); $i++) {
            $user = new User();
            $user->setName($users_arr[$i])
                ->setEmail(str_replace(' ',  '@', strtolower($users_arr[$i])))
                ->setPassword('root');
            $manager->persist($user);
        }


        // Dans la partie d’échec Harry Potter prend la place de :;Un fou;Une tour;Un pion

        // Quel est le mot de passe du bureau de Dumbledore ?;Sorbet Citron;Chocogrenouille;Dragées Surprise
        // Quel chiffre est écrit à l'avant du Poudlard Express ?;5972;4732;6849
        // Avec qui Harry est-il interdit de jouer à vie au Quidditch par Ombrage ?;George Weasley;Fred Weasley;Drago Malefoy
        // Sur quelle(s) main(s) Harry s'écrit-il "je ne dois pas dire de mensonge" pendant ses retenues avec Ombrage ?;La droite;La gauche;Les deux
        // Everard et Dilys sont :;Deux directeurs de Poudlard;Deux amants célèbres de Poudlard;Deux préfets en chef
        // Quel est le prénom du professeur Gobe-Planche ?;Wilhelmina;Libellia;Carlotta
        // Quel est le nom de jeune fille de Molly Weasley ?;Prewett;Foist;Jugson
        // Lequel de ces Mangemorts n'était pas présent lors de l'invasion au ministère ?;Rowle;Crabbe;Goyle
        // En quelle année sont morts les parents de Harry Potter ?;1981;1982;1983

        // Quel est le mot de passe du bureau de Dumbledore ?;Sorbet Citron;Chocogrenouille;Dragées Surprise

        // $quiz = new Quiz();
        // $quiz->
        $manager->flush();
    }
}
