<?php

namespace mdagostino\MultipleChoiceExams;

use mdagostino\MultipleChoiceExams\Exam\ExamWithTimeController;

class ExamControllerTest extends \PHPUnit_Framework_TestCase {

  protected $exam;

  protected $questions = array();

  protected $controller;

  public function tearDown() {
    \Mockery::close();
  }

  public function setUp() {
    $this->questions = array();
    for ($i = 0; $i < 10; $i++) {
      $question = \Mockery::mock('mdagostino\MultipleChoiceExams\Question\QuestionInterface');
      $this->questions[] = $question;
    }

    $this->examTimer = \Mockery::mock('mdagostino\MultipleChoiceExams\Timer\ExamTimerInterface');

    $this->examTimer
    ->shouldReceive('start')->once()
    ->shouldReceive('stillHasTime')->andReturn(TRUE);

    $this->exam = \Mockery::mock('mdagostino\MultipleChoiceExams\Exam\ExamInterface');
    $this->exam
    ->shouldReceive('getQuestion')
    ->andReturnUsing(function($argument) {
        return $this->questions[$argument];
    })
    ->shouldReceive('isApproved')->andReturn(TRUE)
    ->shouldReceive('getCurrentQuestion')->andReturn(\Mockery::mock('mdagostino\MultipleChoiceExams\Question\QuestionInterface'))
    ->shouldReceive('totalQuestions')->andReturn(count($this->questions));
  }

  public function testExamControlerCreation() {
    $controller = new ExamWithTimeController($this->exam);
    $controller->setTimer($this->examTimer);
    $controller->startExam();
    $this->assertEquals($controller->getCurrentQuestionCount(), 1, "The first question is numbered with 1");
    $this->assertEquals($controller->getQuestionCount(), 10, "The are 10 questions in the current exam");
    $this->assertEquals($controller->getExam(), $this->exam);
  }


  public function testExamControllerQuestionNavigation() {
    $controller = new ExamWithTimeController($this->exam);
    $controller->setTimer($this->examTimer);
    $controller->startExam();
    $this->assertEquals($controller->getCurrentQuestionCount(), 1, "The first question is numbered with 1");

    $controller->moveToNextQuestion();
    $this->assertEquals($controller->getCurrentQuestionCount(), 2, "The second question is numbered with 1");

    $controller->moveToPreviousQuestion();
    $this->assertEquals($controller->getCurrentQuestionCount(), 1, "The first question is numbered with 1");

    $controller->moveToLastQuestion();
    $this->assertEquals($controller->getCurrentQuestionCount(), 10, "The last question is numbered with 10");

    $controller->moveToFirstQuestion();
    $this->assertEquals($controller->getCurrentQuestionCount(), 1, "The first question is numbered with 1");

    // Try out of range movements
    $controller->moveToLastQuestion();
    $controller->moveToNextQuestion();
    $this->assertEquals($controller->getCurrentQuestionCount(), 10, "The last question is numbered with 10");

    // Try negative movement
    $controller->moveToFirstQuestion();
    $controller->moveToPreviousQuestion();
    $this->assertEquals($controller->getCurrentQuestionCount(), 1, "The first question is numbered with 1");
  }

  public function testGetCurrentQuestion() {
    $controller = new ExamWithTimeController($this->exam);
    $controller->setTimer($this->examTimer);
    $controller->startExam();
    $this->assertEquals($controller->getCurrentQuestion(), $this->questions[0]);

    $controller->moveToNextQuestion();
    $this->assertEquals($controller->getCurrentQuestion(), $this->questions[1]);

    $controller->moveToLastQuestion();
    $this->assertEquals($controller->getCurrentQuestion(), end($this->questions));
  }

  public function testReviewQuestionsLater() {
    $tag = 'review_later';

    $first_question = \Mockery::mock('mdagostino\MultipleChoiceExams\Question\QuestionInterface');
    $first_question_info = \Mockery::mock('mdagostino\MultipleChoiceExams\Question\QuestionInfoInterface');
    $first_question_info->shouldReceive('tag')->once()->with($tag);
    $first_question_info->shouldReceive('hasTag')->twice()->andReturn(TRUE, FALSE);
    $first_question_info->shouldReceive('untag')->once()->with($tag);
    $first_question->shouldReceive('getInfo')->andReturn($first_question_info);

    $second_question = \Mockery::mock('mdagostino\MultipleChoiceExams\Question\QuestionInterface');
    $second_question_info = \Mockery::mock('mdagostino\MultipleChoiceExams\Question\QuestionInfoInterface');
    $second_question_info->shouldReceive('tag')->never();
    $second_question_info->shouldReceive('hasTag')->twice()->andReturn(FALSE, FALSE);
    $second_question->shouldReceive('getInfo')->andReturn($second_question_info);

    $third_question = \Mockery::mock('mdagostino\MultipleChoiceExams\Question\QuestionInterface');
    $third_question_info = \Mockery::mock('mdagostino\MultipleChoiceExams\Question\QuestionInfoInterface');
    $third_question_info->shouldReceive('tag')->once()->with($tag);
    $third_question_info->shouldReceive('untag')->never()->with($tag);
    $third_question_info->shouldReceive('hasTag')->twice()->andReturn(TRUE, TRUE);
    $third_question->shouldReceive('getInfo')->andReturn($third_question_info);

    $this->questions = array(
      $first_question,
      $second_question,
      $third_question,
    );

    $this->exam = \Mockery::mock('mdagostino\MultipleChoiceExams\Exam\ExamInterface');
    $this->exam
    ->shouldReceive('getQuestion')
    ->andReturnUsing(function($argument) {
        return $this->questions[$argument];
    })
    ->shouldReceive('getQuestions')->andReturn($this->questions)
    ->shouldReceive('getCurrentQuestion')->andReturnUsing(function($argument) {
      $this->questions[$this->controller->getCurrentQuestionCount()];
    })
    ->shouldReceive('totalQuestions')->andReturn(3);

    $this->controller = new ExamWithTimeController($this->exam);
    $this->controller->setTimer($this->examTimer);

    $this->controller->startExam();

    // Mark the first question to be reviewed later
    $this->controller->tagCurrentQuestion($tag);
    $this->assertEquals($this->controller->getCurrentQuestion(), $first_question);

    $this->controller->moveToNextQuestion();
    $this->assertEquals($this->controller->getCurrentQuestion(), $second_question);

    // Mark the third question to not be reviewd later
    $this->controller->moveToNextQuestion();
    $this->assertEquals($this->controller->getCurrentQuestion(), $third_question);
    $this->controller->tagCurrentQuestion($tag);

    $this->assertEquals($this->controller->getQuestionsTagged($tag), array($first_question, $third_question));

    $this->controller->moveToFirstQuestion();
    $this->assertEquals($this->controller->getCurrentQuestion(), $first_question);
    $this->controller->untagCurrentQuestion($tag);
    $this->assertEquals($this->controller->getQuestionsTagged($tag), array($third_question));

  }

  public function testFinalizeExam() {
    $controller = new ExamWithTimeController($this->exam);
    $controller->setTimer($this->examTimer);

    $controller->startExam();
    $controller->finalizeExam();
  }


}

