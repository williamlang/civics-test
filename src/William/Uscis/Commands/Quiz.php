<?php

namespace William\Uscis\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Yaml\Yaml;
use William\Uscis\Helper;

#[AsCommand(name: 'app:quiz')]
class Quiz extends Command {

    const QUESTION_COUNT = 10;

    protected function configure(): void {
        $this
            // the command description shown when running "php bin/console list"
            ->setDescription('Asks a question');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $yaml = Helper::loadQuestions();
        $countYaml = Helper::loadCounts();
        $resultsYaml = Helper::loadResults();

        $questionCopy = $countYaml['questions'];
        
        uasort($questionCopy, Helper::sortQuestionsByCount());
        $lowestQuestion = array_key_first($questionCopy);

        uasort($questionCopy, Helper::sortCountsByPerc());
        $worstQuestion = array_key_first($questionCopy);

        /** @var HelperQuestion $helper */
        $helper = $this->getHelper('question');

        $questionsToAsk = [];
        if (is_numeric($worstQuestion)) {
            $questionsToAsk[] = $worstQuestion;
        }

        if (is_numeric($lowestQuestion)) {
            $questionsToAsk[] = $lowestQuestion;
        }

        $questionsToAsk = array_unique($questionsToAsk);

        while (sizeof($questionsToAsk) < self::QUESTION_COUNT) {
            $random = mt_rand(0, sizeof($yaml['questions']) - 1);

            if (!in_array($random, $questionsToAsk)) {
                $questionsToAsk[] = $random;
            }
        }

        $correct = 0;
        $result = [];
        foreach ($questionsToAsk as $i => $questionId) {
            $output->writeln("Question " . $i + 1 ." : ");
            $output->writeln("========================================");

            $asking = $yaml['questions'][$questionId];
            $output->writeln($asking['question']);

            $count = !empty($asking['count']) ? $asking['count'] : 1;
            if ($count > 1) {
                $output->writeln("There are " . $asking['count'] . " answers required.");
            }

            $answers = [];
            while (sizeof($answers) < $count) {
                $question = new Question("Answer: ");
                $answerValue = $helper->ask($input, $output, $question);

                $answers[] = $answerValue;
            }

            $foundCount = 0;
            foreach ($answers as $enteredAnswer) {
                foreach ($asking['answers'] as $correctAnswer) {           
                    $perc = 0;
                    if (!is_numeric($correctAnswer)) {
                        $longest_string = Helper::get_longest_common_subsequence($correctAnswer, $enteredAnswer);
                        $perc = round(strlen($longest_string) / strlen($correctAnswer), 2) * 100;
                    }                   
                    
                    if (
                        strtolower($correctAnswer) == strtolower($enteredAnswer) ||
                        $perc >= 60
                    ) {
                        $foundCount++;
                        break;
                    }
                }
            }

            if (empty($countYaml['questions'][$questionId])) {
                $countYaml['questions'][$questionId] = [
                    'id' => $questionId,
                    'count' => 0,
                    'correct' => 0
                ];
            }

            $countYaml['questions'][$questionId]['count']++;
            $result['questions'][] = $questionId;

            if ($foundCount == $count) {
                $correct++;
                $countYaml['questions'][$questionId]['correct']++;
            } else {
                $output->writeln("/ = \ = / = \ / = \ = /");
                $output->writeln("answers: " . join(", ", $asking['answers']));
                $output->writeln("/ = \ = / = \ / = \ = /");
            }

            $output->writeln("========================================");
            $output->writeln("\n");
        }        

        Helper::saveCounts($countYaml);

        $output->writeln($correct . " / " . self::QUESTION_COUNT . " = " . round($correct / self::QUESTION_COUNT, 2) * 100 . "%");
        $result['correct'] = $correct;
        $resultsYaml['results'][] = $result;

        Helper::saveResults($resultsYaml);

        if ($correct / self::QUESTION_COUNT >= 0.6) {
            $output->writeln("Pass!");
            return Command::SUCCESS;
        } else {
            $output->writeln("Fail!");
            return Command::FAILURE;
        }
    }
}
