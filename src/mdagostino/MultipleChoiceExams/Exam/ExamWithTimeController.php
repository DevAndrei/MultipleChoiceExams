<?php

namespace mdagostino\MultipleChoiceExams\Exam;

use mdagostino\MultipleChoiceExams\Exception\ExpiredTimeException;
use mdagostino\MultipleChoiceExams\Timer\ExamTimerInterface;

class ExamWithTimeController extends AbstractExamController implements ExamControllerInterface {

  protected $timer;

  /**
   * Defines a period of time between the .
  */
  public function setTimer(ExamTimerInterface $timer) {
    $this->timer = $timer;
    return $this;
  }

  public function startExam() {
    parent::startExam();
    $this->timer->start();
  }

  public function answerCurrentQuestion(array $answer) {
    if ($this->timer->stillHasTime() == FALSE) {
      $this->finalizeExam();
      throw new ExpiredTimeException("There is no left time to complete the exam.");
    }

    $this->getExam()->answerQuestion($this->getCurrentQuestionIndex() - 1, $answer);
  }

}
