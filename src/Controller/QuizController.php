<?php

namespace App\Controller;

use DateTime;
use App\Entity\Quiz;
use App\Entity\Answer;
use App\Form\PlayType;
use App\Form\QuizType;
use App\Entity\Category;
use App\Entity\Question;
use App\Repository\QuizRepository;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/quiz")
 * @(repositoryClass="Blogger\BlogBundle\Repository\BlogRepository")
 */
class QuizController extends AbstractController
{
    /**
     * @Route("/", name="quiz_index", methods={"GET"})
     */
    public function index(QuizRepository $quizRepository): Response
    {
        return $this->render('quiz/index.html.twig', [
            'quizzes' => $quizRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="quiz_new", methods={"GET","POST"})
     */
    // public function new(Request $request, ObjectManager $entityManager): Response
    public function new(Request $request): Response
    {
        //Passer un dernier param pour se servir de findAll dans QuizType
        $getRepo =  $this->getDoctrine()->getRepository(Category::class);
        $quiz = new Quiz();
        for ($i = 0; $i < 10; $i++) {
            $questions{
                $i} = new Question();
            $questions{
                $i}->setName('Question-Generated-In-QuizController-' . ($i + 1));
            for ($j = 1; $j < 4; $j++) {
                $answer{
                    $j} = new Answer();
                $answer{
                    $j}->setName('Question-' . ($i + 1) . '-Answer-' . $j);
                if ($j == 1) {
                    $answer{
                        $j}->setIsCorrect(true);
                } else {
                    $answer{
                        $j}->setIsCorrect(false);
                }
                $questions{
                    $i}->addAnswer($answer{
                    $j});
            }
            $quiz->addQuestion($questions{
                $i});
        }
        $quiz->setName("Akira");
        $quiz->setData("Tetsuo, un adolescent...");

        // $question->setQuizId($quiz);
        $form = $this->createForm(QuizType::class, $quiz, [
            'get_category_repo' => $getRepo,
            'quiz' => $quiz
        ]);
        //===============================================================

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($quiz);
            // dd($quiz);
            $entityManager->flush();

            return $this->redirectToRoute('quiz_index');
        }

        // dump($form->createView());
        // exit();


        return $this->render('quiz/new.html.twig', [
            'quiz' => $quiz,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/edit", name="quiz_edit", methods={"GET","POST"})
     * 
     */
    public function edit(Request $request, Quiz $quiz): Response
    {
        // dd($quiz);
        //Passer un dernier param pour se servir de findAll dans QuizType
        $getRepo =  $this->getDoctrine()->getRepository(Category::class);
        $quiz_temp = new Quiz();


        $questions =  $this->getDoctrine()->getRepository(Question::class)->findByQuiz($quiz->getId());
        foreach ($questions as $question) {
            $answers =  $this->getDoctrine()->getRepository(Answer::class)->findByQuestion($question->getId());
            foreach ($answers as $anwser) {
                $question->addAnswer($anwser);
            }
            $quiz_temp->addQuestion($question);
            $quiz->addQuestion($question);
        }

        $form = $this->createForm(QuizType::class, $quiz_temp, [
            'get_category_repo' => $getRepo,
            'quiz' => $quiz,
        ]);
        // dd($form);
        //===============================================================

        // $form = $this->createForm(QuizType::class, $quiz);

        // dd($form);
        // dd($quiz->getQuestions());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // $this->getDoctrine()->getManager()->flush();
            // dd($quiz_temp);
            $entityManager = $this->getDoctrine()->getManager();
            //questions modifiés se trouvent bien dans le $quiz de persist
            $entityManager->persist($quiz);
            // $entityManager->merge($quiz);
            $em = $this->getDoctrine()->getManager();

            //Quiz Name Must Be Unique
            if (
                !empty($product = $em->getRepository(Quiz::class)->findOneByName($quiz_temp->getName()))
                && strtolower($product->getName()) == strtolower($quiz_temp->getName())
                && strtolower($product->getName()) !== strtolower($quiz->getName())
            ) {
                $this->addFlash('danger', 'The quiz name is already taken. Please choose another.');
            } else {
                if ($quiz_temp->getName() !== $quiz->getName() && $quiz_temp != null) {
                    $quiz->setName($quiz_temp->getName());
                }
                if ($quiz_temp->getData() !== $quiz->getData() && $quiz_temp != null) {
                    $quiz->setData($quiz_temp->getData());
                }
                if ($quiz_temp->getCategory() !== $quiz->getCategory() && $quiz_temp != null) {
                    $quiz->setcategory($quiz_temp->getCategory());
                }
                foreach ($quiz->getQuestions() as $question) {
                    $question->setQuizId($quiz);
                    $entityManager->persist($question);
                }
                $quiz->setUpdatedAt(new \DateTime('now'));
                $quiz->createSlug($quiz->getName());
                // dd($quiz);
                $entityManager->flush();
                return $this->redirectToRoute('quiz_index');
            }
        }
        return $this->render('quiz/edit.html.twig', [
            'quiz' => $quiz,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="quiz_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Quiz $quiz): Response
    {
        if ($this->isCsrfTokenValid('delete' . $quiz->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            // dd($quiz);
            $entityManager->remove($quiz);
            $entityManager->flush();
        }

        return $this->redirectToRoute('quiz_index');
    }

    /**
     * @Route("/{id}/play", name="quiz_play",methods={"GET","POST"})
     */
    public function play(Quiz $quiz, Request $request): Response
    {
        $id = $quiz->getId();
        $cache_name = 'quiz.game.' . $id;
        $cache = new FilesystemAdapter();
        // $cache->deleteItem($cache_name);   dd();
        $productsCount = $cache->getItem($cache_name);
        $answers_from_user = [];
        if ($productsCount->get() != null) {
            $answers_from_user = $productsCount->get()['data'];
        }
        $qst_key = 0;
        if ($productsCount->get() != null) {
            $qst_key = array_search(false, $productsCount->get()['data']);
        }
        //Getting questions
        $questions =  $this->getDoctrine()->getRepository(Question::class)->findByQuiz($quiz);
        foreach ($questions as $key => $question) {
            $cache_questions[$key] = false; //Generate question array for cache
            $question->setQuizId($quiz);
            $answers =  $this->getDoctrine()->getRepository(Answer::class)->findByQuestion($question);
            foreach ($answers as $answer) {
                $question->addAnswer($answer);
            }
        }
        if (!$cache->getItem($cache_name)->isHit()) { //create game if it doesnt exist
            echo 'New Game';
            echo '<br>';
            echo 'Registrating quiz data to begin';
            echo '<br>';
            $productsCount = $cache->getItem($cache_name);
            $productsCount->set([
                'quiz_id' => $quiz->getId(),
                'data' => $cache_questions,
            ]);
            $cache->save($productsCount);
        } else { //continue game until all questions are answered == true
            echo 'Game exists<br>';
            echo 'Continue with question:';
            echo '<br>';
            //continue game
            // $cache->deleteItem('quiz.game');
        }



        //test display
        // $productsCount = $cache->getItem('quiz.game');
        // $qst_key = array_search(false, $productsCount->get()['data']);
        // dd($productsCount->get());
        // dd($qst_key);
        // dd($questions[0]);

        // dd($answers_from_user);
        //Answer validation
        // dd($answers_from_user, !($qst_key = array_search(false, $productsCount->get()['data'])));
        if (
            strtolower($request->server->get("REQUEST_METHOD")) == 'get'
        ) {
            if (
                count($answers_from_user) > 0 
                && isset($answers_from_user[0])
                && $answers_from_user[0] !=  false
                && array_search(false, $productsCount->get()['data']) == false
            ) {
                echo 'this is get';
                echo '<br>';
                $answers_from_user = $productsCount->get()['data'];
                return $this->getScore($questions, $answers_from_user);
            }
        }
        if (strtolower($request->server->get("REQUEST_METHOD")) == 'post') {
            if (isset($request->request->all()['retake']) && $request->request->all()['retake'] == 'retake') {
                $cache->deleteItem($cache_name);
                return $this->redirectToRoute('quiz_play', [
                    'id' => $id
                ]);
            }

            echo 'this is post';
            echo '<br>';
            if (count($request->request->all()) == 0) {
                // dd($request->request->all());
                $this->addFlash('danger', 'You must submit an answer.');
            } else if (count($request->request->all()) > 1) {
                // dd($request->request->all());
                $this->addFlash('danger', 'You must not submit more than one answer.');
            } else {
                // dd();
                // dd($cache_questions);
                $cache_questions = $productsCount->get()['data'];
                // dd($productsCount);
                $cache_questions[$qst_key] = array_key_first($request->request->all());
                $productsCount->set([
                    'quiz_id' => $quiz->getId(),
                    'data' => $cache_questions,
                ]);
                $cache->save($productsCount);
                // dd($productsCount->get()['data']);
                if (
                    !($qst_key = array_search(false, $productsCount->get()['data']))
                    && count($answers_from_user) > 0
                ) {
                    //show score and retake button
                    $answers_from_user = $productsCount->get()['data'];
                    return $this->getScore($questions, $answers_from_user);
                    // dd($productsCount->get()['data']);
                    // dd(array_search(false, $productsCount->get()['data']));

                    //store game inside cache => Done
                    // Display historic of games played with score...

                    //  Store in historic if connected
                }
            }
        }

        // dd(

        //     $questions[0]->getAnswers()[0]
        // );

        //  builderform doesnt work with my checkbox parameters
        // $form = $this->createForm(PlayType::class, $questions[$qst_key], [
        //     'question' => $questions[$qst_key]
        // ]);
        //automatically display first question


        // dd($qst_key);
        return $this->render('quiz/play.html.twig', [
            'quiz' => $quiz,
            'questions' => $questions,
            'qst_key' => $qst_key,
            // 'form' => $form->createView(),
        ]);

        // $productsCount = $cache->getItem('quiz.game');
        // dd($productsCount->get());

        // $cache->deleteItem('quiz.game');

        // return $this->render('quiz/play.html.twig', [
        //     'quiz' => $quiz,
        //     'questions' => $questions,
        // ]);
    }


    private function getScore($questions, $answers_from_user)
    {
        // dd(array_search(false, $answers_from_user));
        echo 'quiz already played';
        $score = 0;
        foreach ($questions as $key => $question) {
            foreach ($question->getAnswers() as $answer) {
                // echo(preg_replace('/\_/', " ", $answers_from_user[$key]));
                // echo '<br>';
                // echo $answer->getName();
                // echo '<br>';
                // echo($answer->getIsCorrect());
                // echo '<br>';
                // dd($question->getAnswers(), $answers_from_user);
                if (preg_replace('/\_/', " ", $answers_from_user[$key]) == $answer->getName() && $answer->getIsCorrect()) {
                    $score++;
                }
            }
        }
        $score_str = $score . '/' . count($answers_from_user);
        //bouton: retake and erase old one

        return $this->render('quiz/play.html.twig', [
            'quiz' => $questions[0]->getQuizId(),
            'questions' => $questions,
            'qst_key' => 0,
            'quiz_done' => true,
            'score_str' => $score_str,
        ]);
    }

    /**
     * @Route("/{id}", name="quiz_show", methods={"GET"})
     */
    public function show(Quiz $quiz): Response
    {
        return $this->render('quiz/show.html.twig', [
            'quiz' => $quiz,
        ]);
    }
}
