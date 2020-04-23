<?php

namespace App\DataFixtures;

use App\Entity\Quiz;
use App\Entity\User;
use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType as DateTimeType;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        // $product = new Product();
        // $manager->persist($product);

        $cat_arr = [
            'Harry Potter' => [
                'Dans la partie d’échec Harry Potter prend la place de :' => [
                    'Un fou' => true,
                    'Une tour' => false,
                    'Un pion' => false,
                ]
            ],
            'Sigles Français',
            'Définitions de mots',
            'Les spécialités culinaires',
            'Séries TV : Les Simpson - partie 1',
            'Séries TV : Les Simpson - partie 2',
            'Séries TV : Stargate SG1',
            'Séries TV : NCIS',
            'Jeux de société',
            'Programmation',
            'Sigles Informatiques',
        ];
        // for ($i = 0; $i < count($cat_arr); $i++) {
        foreach ($cat_arr as $cat_name => $quiz_arr) {
            $category = new Category();
            if ( !is_array($quiz_arr) ) {
                $category->setName($quiz_arr);
            }  else {
                $category->setName($cat_name);
            }

            if (isset($quiz_arr) && is_array($quiz_arr)) {
                foreach ($quiz_arr as $question => $answers) {
                    $quiz = new Quiz();
                    $quiz->setName($question)
                        ->setData($answers)
                        ->setCategory($category);
                    $manager->persist($quiz);
                }
            }
            $manager->persist($category);
        }

        $users_arr = [
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
