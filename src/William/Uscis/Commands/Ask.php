<?php

namespace William\Uscis\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Yaml\Yaml;
use William\Uscis\Helper;

#[AsCommand(name: 'app:ask')]
class Ask extends Command {

    const WRONG = 'wrong';
    const LOW = 'low';

    protected function configure(): void {
        $this
            // the command description shown when running "php bin/console list"
            ->setDescription('Asks a question')
            ->addArgument('mode', InputArgument::OPTIONAL, 'Two modes [wrong] for least answered correctly and [low] for low ask count.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $yaml = Helper::loadQuestions();
        $countYaml = Helper::loadCounts();

        $mode = $input->getArgument('mode');

        if ($mode == self::WRONG) {
            uasort($countYaml['questions'], Helper::sortCountsByPerc());
            $random = array_key_first($countYaml['questions']);
        } else if ($mode == self::LOW) {
            uasort($countYaml['questions'], Helper::sortQuestionsByCount());
            $random = array_key_first($countYaml['questions']);
        } else {
            $random = mt_rand(0, sizeof($yaml['questions']) - 1);
        }

        $asking = $yaml['questions'][$random];
        $output->writeln($asking['question']);
        $count = !empty($asking['count']) ? $asking['count'] : 1;
        if ($count > 1) {
            $output->writeln("There are " . $asking['count'] . " answers required.");
        }

        /** @var HelperQuestion $helper */
        $helper = $this->getHelper('question');

        $answers = [];
        while (sizeof($answers) < $count) {
            $question = new Question("Answer: ");
            $answerValue = $helper->ask($input, $output, $question);
            $answers[] = $answerValue;
        }

        $correct = 0;
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
                    $correct++;
                    break;
                }
            }
        }

        $countYaml['questions'][$random]['count']++;

        if ($correct == $count) {
            $output->writeln("Correct!");
            $countYaml['questions'][$random]['correct']++;
            Helper::saveCounts($countYaml);

            return Command::SUCCESS;
        } else {
            $output->writeln("Not correct, answers: " . join(", ", $asking['answers']));
            return Command::FAILURE;
        }
    }

}
