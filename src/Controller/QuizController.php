<?php

namespace App\Controller;

use DateTime;
use App\Entity\Quiz;
use App\Entity\User;
use App\Entity\Answer;
use App\Form\PlayType;
use App\Form\QuizType;
use App\Entity\Category;
use App\Entity\Historic;
use App\Entity\Question;
use App\Controller\HomeController;
use App\Controller\UserController;
use App\Repository\QuizRepository;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
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
    public function index(QuizRepository $quizRepository, Request $request): Response
    {
        HomeController::countVisitors($request, $this->getUser());
        $historics = $this->getDoctrine()->getRepository(Historic::class)->findAll();
        $historics = array_reverse($historics); //montrer le plus récent en premier

        return $this->render('quiz/index.html.twig', [
            'quizzes' => $quizRepository->findAll(),
            'historics' => $historics,
        ]);
    }

    /**
     * @Route("/new", name="quiz_new", methods={"GET","POST"})
     * @Security("is_granted('ROLE_USER')", message="You can't create a quiz. Connect first !") 
     * 
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


        if ($this->getUser() !== null) {
            $user = $this->getUser();
        } else {
            $user = $this->getDoctrine()->getRepository(User::class)->getByName('Anonymous')[0];
        }
        $quiz->setAuthor($user);

        // $question->setQuizId($quiz);
        $form = $this->createForm(QuizType::class, $quiz, [
            'get_category_repo' => $getRepo,
            'quiz' => $quiz
        ]);
        //===============================================================

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->answersAreNotValid($request)) {
                return $this->render('quiz/new.html.twig', [
                    'quiz' => $quiz,
                    'form' => $form->createView(),
                ]);
            }
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
     * @Security("is_granted('ROLE_USER')", message="You can't create a quiz. Connect first !") 
     * 
     */
    public function edit(Request $request, Quiz $quiz): Response
    {
        if (!$this->userIsSameAsAuthor($quiz)) {
            return $this->redirectToRoute('homepage');
        }

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

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // dd($request->request->get('quiz')['questions']);
            //only one option possible, for now...
            if ($this->answersAreNotValid($request)) {
                return $this->render('quiz/edit.html.twig', [
                    'quiz' => $quiz,
                    'form' => $form->createView(),
                ]);
            }
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

    private function answersAreNotValid($request)
    {
        $nbr = 0;
        foreach ($request->request->get('quiz')['questions'] as $key => $question) {
            $i = 0;
            foreach ($question['answers'] as $data) {
                if (array_key_exists("is_correct", $data)) {
                    $i++;
                }
            }
            if ($i > 1) {
                $nbr = $key + 1;
                $this->addFlash("danger", "Only one answer can be selected as correct for the question n° {$nbr} : \"{$question['name']}\"");
            } else if ($i == 0) {
                $nbr = $key + 1;
                $this->addFlash("danger", "You must select a correct answer for the question n° {$nbr} : \"{$question['name']}\"");
            }
        }
        if ($nbr > 0) {
            return true;
        }
    }

    /**
     * @Route("/{id}", name="quiz_delete", methods={"DELETE"})
     * @Security("is_granted('ROLE_USER')", message="You can't delete a quiz that isnt yours !") 
     */
    public function delete(Request $request, Quiz $quiz): Response
    {
        if (!$this->userIsSameAsAuthor($quiz)) {
            return $this->redirectToRoute('homepage');
        }

        if ($this->isCsrfTokenValid('delete' . $quiz->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            // dd($quiz);
            $cache = new FilesystemAdapter();
            for ($user_id = 0; $user_id < 1000; $user_id++) {
                $cache->deleteItem('quiz.game.' . $quiz->getId() . '.' . $user_id);
            }
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
        if ($this->getUser() != null) {
            $user_id = $this->getuser()->getId();
        } else {
            $user_id = 0; //anon 
        }

        // $cache_name = 'quiz.gacame.' . $id;
        $cache_name = 'quiz.game.' . $id .  '.' . $user_id;
        $cache = new FilesystemAdapter();
        $productsCount = $cache->getItem($cache_name);
        $answers_from_user = [];
        $qst_key = 0;
        $questions =  $this->getDoctrine()->getRepository(Question::class)->findByQuiz($quiz);
        if ($productsCount->get() != null) {
            $answers_from_user = $productsCount->get()['data'];
            $qst_key = array_search(false, $productsCount->get()['data']);

            //if replay button erase previous game from cache
            if (strtolower($request->server->get("REQUEST_METHOD")) == 'post') {
                if (isset($request->request->all()['retake']) && $request->request->all()['retake'] == 'retake') {
                    $cache->deleteItem($cache_name);
                    return $this->redirectToRoute('quiz_play', [
                        'id' => $id
                    ]);
                }
            }

            //probleme historic ne passe pas a true
            if ($productsCount->get()['historic_stored']) {
                if ($this->userPlayedTheGame($productsCount)) {
                    return $this->showQuizHasBeenPlayed($questions, $user_id, $productsCount);
                }
            }
        }

        //Getting questions
        foreach ($questions as $key => $question) {
            $cache_questions[$key] = false; //Generate question array for cache
            $question->setQuizId($quiz);
            $answers =  $this->getDoctrine()->getRepository(Answer::class)->findByQuestion($question);
            foreach ($answers as $answer) {
                $question->addAnswer($answer);
            }
        }
        if (!$cache->getItem($cache_name)->isHit()) { //create game if it doesnt exist
            $productsCount = $cache->getItem($cache_name);
            $productsCount->set([
                'quiz_id' => $quiz->getId(),
                'user_id' => $user_id,
                'data' => $cache_questions,
                'historic_stored' => false,
                'score' => false,
            ]);
            $cache->save($productsCount);
        }

        //If method get and if game already played show score and replay button
        if (strtolower($request->server->get("REQUEST_METHOD")) == 'get') {
            if (
                count($answers_from_user) > 0
                && isset($answers_from_user[0])
                && $answers_from_user[0] !=  false
                && array_search(false, $productsCount->get()['data']) == false
                //ajouter une condition ici pour verifier si cest le meme user qui joue ou pas. sinon rediriger vers game start
            ) {
                if ($this->userPlayedTheGame($productsCount)) {
                    return $this->showQuizHasBeenPlayed($questions, $user_id, $productsCount);
                }
            }
        }

        if (strtolower($request->server->get("REQUEST_METHOD")) == 'post') {
            //If method post and if game already played show score and replay button
            if (isset($request->request->all()['retake']) && $request->request->all()['retake'] == 'retake') {
                $cache->deleteItem($cache_name);
                return $this->redirectToRoute('quiz_play', [
                    'id' => $id
                ]);
            }

            if (count($request->request->all()) == 0) {
                // dd($request->request->all());
                $this->addFlash('danger', 'You must submit an answer.');
            } else if (count($request->request->all()) > 1) {
                // dd($request->request->all());
                $this->addFlash('danger', 'You must not submit more than one answer.');
            } else {
                $correct = 0;
                for ($i = 0; $i < 3; $i++) {
                    $correct_answer = $questions[$qst_key]->getAnswers()[$i];
                    if ($correct_answer->getIsCorrect()) {
                        if (
                            preg_replace('/\ |\_/', '', strtolower($correct_answer->getName())) ==
                            preg_replace('/\ |\_/', '', strtolower(array_key_first($request->request->all())))
                        ) {
                            $this->addFlash("success", $correct_answer->getName() . " was the correct answer !");
                            $correct++;
                        }
                    }
                }
                if ($correct == 0) {
                    $this->addFlash("danger", "Wrong answer ! The corret answer was " . $correct_answer->getName());
                }
                $cache_questions = $productsCount->get()['data'];
                // dd($productsCount);
                $cache_questions[$qst_key] = array_key_first($request->request->all());
                $productsCount->set([
                    'quiz_id' => $quiz->getId(),
                    'user_id' => $user_id,
                    'data' => $cache_questions,
                    'historic_stored' => false,
                    'score' => false,
                ]);
                $cache->save($productsCount);
                // dd($productsCount->get()['data']);
                if (
                    !($qst_key = array_search(false, $productsCount->get()['data']))
                    && count($answers_from_user) > 0
                ) {
                    //show score and retake button
                    $answers_from_user = $productsCount->get()['data'];
                    $this->saveToHistoric($productsCount, $quiz, $this->getScore($questions, $answers_from_user));
                    // return $this->getScore($questions, $answers_from_user);
                    if ($this->userPlayedTheGame($productsCount)) {
                        return $this->showQuizHasBeenPlayed($questions, $user_id, $productsCount);
                    }
                }
            }
        }
        //Show game if user hasn't played it before
        return $this->render('quiz/play.html.twig', [
            'quiz' => $quiz,
            'questions' => $questions,
            'qst_key' => $qst_key,
        ]);
    }

    private function saveToHistoric($cache, Quiz $quiz, $score)
    {
        //Define a user to store in historic
        $user =  $this->getDoctrine()->getRepository(User::class)->findByName('Anonymous')[0];
        if ($this->getUser() !== null) {
            $user = $this->getUser();
        }

        //Storing to historic database
        $h = new Historic();
        $h->setUserId($user);
        $h->setQuizId($quiz);
        $h->setScore($score);
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($h);
        $entityManager->flush();

        // setting cache parameter
        $cache->set([
            'quiz_id' => $cache->get()['quiz_id'],
            'user_id' => $cache->get()['user_id'],
            'data' => $cache->get()['data'],
            'historic_stored' => true,
            'score' => $score,
        ]);
        $ch = new FilesystemAdapter();
        $ch->save($cache);
    }


    private function userPlayedTheGame($cache)
    {
        if (
            $this->getUser() != null && $this->getUser()->getId() == $cache->get()['user_id'] ||
            $this->getUser() == null && $cache->get()['user_id'] == 0
        ) {
            return true;
        }
    }


    private function showQuizHasBeenPlayed($questions, $user_id, $productsCount)
    {
        return $this->render('quiz/play.html.twig', [
            'quiz' => $questions[0]->getQuizId(),
            'user_id' => $user_id,
            'questions' => $questions,
            'qst_key' => 0,
            'quiz_done' => true,
            'score_str' => $productsCount->get()['score'],
        ]);
    }

    private function getScore($questions, $answers_from_user)
    {
        $score = 0;
        foreach ($questions as $key => $question) {
            foreach ($question->getAnswers() as $answer) {
                // dd(preg_replace('/\_/', " ", $answers_from_user[$key]), $answer->getName());
                if (preg_replace('/\_/', " ", $answers_from_user[$key]) == $answer->getName() && $answer->getIsCorrect()) {
                    $score++;
                }
            }
        }
        $score_str = $score . '/' . count($answers_from_user);
        return $score_str;
    }



    /**
     * Show Personnal Historic
     * 
     * @Route("/historic", name="quiz_myhistoric", methods={"GET","POST"})
     */
    public function myHistoric(): Response
    {
        if ($this->getUser() !== null) {
            $user = $this->getUser()->getId();
            $name = $this->getUser()->getName();
        } else {
            $user = $this->getDoctrine()->getRepository(User::class)->findByName('Anonymous')[0]->getId();
            $name = $this->getDoctrine()->getRepository(User::class)->findByName('Anonymous')[0]->getName();
        }
        $historics = $this->getDoctrine()->getRepository(Historic::class)->findByUser($user);
        $historics = array_reverse($historics); //montrer le plus récent en premier
        // dd($this->getDoctrine()->getRepository(User::class)->findByName('Anonymous')[0]);
        // $user = $this->getUser();
        // if ($this->getUser() == null) {
        //     $user = new User();
        // }
        return $this->render('quiz/my_historic.html.twig', [
            'historics' => $historics,
            'name' => $name
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


    private function userIsSameAsAuthor(Quiz $quiz)
    {
        if ($quiz->getAuthor() == $this->getUser() || in_array('ROLE_ADMIN', $this->getUser()->getRoles())) {
            return true;
        }
        $this->addFlash("warning", "You can't access a quiz of another user. No hacking allowed here !");
    }
}
