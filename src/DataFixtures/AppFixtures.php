<?php

namespace App\DataFixtures;

use DateTime;
use App\Entity\Quiz;
use App\Entity\Role;
use App\Entity\User;
use App\Entity\Answer;
use App\Entity\Category;
use App\Entity\Historic;
use App\Entity\Question;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType as DateTimeType;

class AppFixtures extends Fixture
{

    private $content = [

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

        // Dans la partie d’échec Harry Potter prend la place de :;Un fou;Une tour;Un pion
        // Quel est le mot de passe du bureau de Dumbledore ?;Sorbet Citron;Chocogrenouille;Dragées Surprise

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
                ],
                "Quel chiffre est écrit à l'avant du Poudlard Express ?" => [
                    '5972' => true,
                    '4732' => false,
                    '6849' => false,
                ],
                "Avec qui Harry est-il interdit de jouer à vie au Quidditch par Ombrage ?" => [
                    'George Weasley' => true,
                    'Fred Weasley' => false,
                    'Drago Malefoy' => false,
                ],
                "Sur quelle(s) main(s) Harry s\'écrit-il \"je ne dois pas dire de mensonge\" pendant ses retenues avec Ombrage ?" => [
                    'La droite' => true,
                    'La gauche' => false,
                    'Les deux' => false,
                ],
                "Everard et Dilys sont :" => [
                    'Deux directeurs de Poudlard' => true,
                    'Deux amants célèbres de Poudlard' => false,
                    'Deux préfets en chef' => false,
                ],
                "Quel est le prénom du professeur Gobe-Planche ?" => [
                    'Wilhelmina' => true,
                    'Libellia' => false,
                    'Carlotta' => false,
                ],
                "Quel est le nom de jeune fille de Molly Weasley ?" => [
                    'Prewett' => true,
                    'Foist' => false,
                    'Jugson' => false,
                ],
                "Lequel de ces Mangemorts n'était pas présent lors de l'invasion au ministère ?" => [
                    'Rowle' => true,
                    'Crabbe' => false,
                    'Goyle' => false,
                ],
                "En quelle année sont morts les parents de Harry Potter ?" => [
                    '1981' => true,
                    '1982' => false,
                    '1983' => false,
                ],
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
    private $encoder;

    public function __construct(UserPasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    public function load(ObjectManager $manager)
    {
        $users_arr = [
            'admin',
            'deletedUser',
            'Anonymous',
            'Martine Aubry',
            'Edouard Balladur',
            'Robert Badinter',
            'Nadine Morano',
            'Jean-Marc Ayrault',
            'Benoit Hamon',
            'Christine Boutin',
            'Catherine deMedicis',
            'Bouddha',
        ];

        for ($i = 0; $i < count($users_arr); $i++) {
            $user = new User();
            $pwd_hashed = $this->encoder->encodePassword($user, 'root');
            if ($i == 0 || $i == 1 || $i == 2) {
                if ($i == 0) {
                    $adminRole = new Role();
                    $adminRole->setTitle('ROLE_ADMIN');
                    $manager->persist($adminRole);

                    $user->setIsAdmin(1)
                        ->addUserRoles($adminRole);
                    $this->registerQuizzes($user, $manager);
                }
            }
            $user->setEmailIsVerified(1);
            if ($users_arr[$i] == "Catherine deMedicis") {
                $user->setEmailVerifiedAt(new \DateTime('1558-01-01'))
                    ->setLastConnectedAt(new \DateTime('1560-01-01'));
            } else if ($users_arr[$i] == "Bouddha") {
                $user->setEmailVerifiedAt(new \DateTime('0001-01-01'))
                    ->setLastConnectedAt(new \DateTime('0001-02-01'));
            } else {
                $user->setEmailVerifiedAt(new \DateTime('now'));
            }

            if (preg_match('/\ /', $users_arr[$i])) {
                $user->setEmail(str_replace(' ',  '@', strtolower($users_arr[$i])) . '.fr');
            } else {
                $user->setEmail($users_arr[$i] . '@' . preg_replace('/\_/', '', $users_arr[$i]) . '.fr');
            }

            $user->setName($users_arr[$i])
                ->setPassword($pwd_hashed);
            $manager->persist($user);
        }

        $manager->flush();


        //erase old cache
        $cache = new FilesystemAdapter();
        for ($i = 0; $i < 1000; $i++) {
            //delete all quiz of all users
            for ($user = 0; $user < 1000; $user++) {
                //$i = id of quiz
                $productsCount = $cache->getItem('quiz.game.' . $i . '.' . $user);
                if ($productsCount->isHit()) {
                    $cache->deleteItem('quiz.game.' . $i . '.' . $user);
                }
            }

            //$i = id of user
            $vKey = $cache->getItem('key.verification.' . $i);
            if ($vKey->isHit()) {
                $cache->deleteItem('key.verification.' . $i);
            }
        }
        //delete visitors count
        $visitors = $cache->getItem('visitors');
        if ($visitors->isHit()) {
            $cache->deleteItem('visitors');
        }
    }

    private function registerQuizzes(User $user, ObjectManager $manager)
    {
        foreach ($this->content as $cat_name => $quiz) {
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
                        ->setCategory($category_obj)
                        ->setAuthor($user);
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
                    for ($i = 1; $i <=   11; $i++) {
                        $h = new Historic();
                        $score = ($i - 1) . '/10';
                        $h->setUserId($user);
                        $h->setQuizId($quiz_obj);
                        $h->setScore($score);
                        $lastMonth = date("Y-m-d", strtotime("-$i weeks"));
                        $h->setCreatedAt(new DateTime($lastMonth));
                        $manager->persist($h);
                    }
                    for ($i = 0; $i <= 7; $i++) {
                        $h = new Historic();
                        $score = ($i + 1) . '/10';
                        $h->setUserId($user);
                        $h->setQuizId($quiz_obj);
                        $h->setScore($score);
                        $lastDays = date("Y-m-d", strtotime("-$i days"));
                        $h->setCreatedAt(new DateTime($lastDays));
                        $manager->persist($h);
                    }
                }
            }
            $manager->persist($category_obj);
        }
    }
}
