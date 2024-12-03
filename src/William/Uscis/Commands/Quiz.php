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

#[AsCommand(name: 'app:quiz')]
class Quiz extends Command {

    protected function configure(): void {
        $this
            // the command description shown when running "php bin/console list"
            ->setDescription('Asks a question');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $fileName = __DIR__ . '/../../../../questions.yml';
        $yaml = Yaml::parseFile($fileName);
        if (empty($yaml)) {
            return Command::FAILURE;            
        }

        /** @var HelperQuestion $helper */
        $helper = $this->getHelper('question');

        $questionCount = 0;
        $correct = 0;
        $questionsAsked = [];
        while ($questionCount < 10) {
            $questionCount++;
            $output->writeln("Question $questionCount: ");
            $output->writeln("========================================");
            $random = mt_rand(0, sizeof($yaml['questions']) - 1);

            while (in_array($random, $questionsAsked)) {
                $random = mt_rand(0, sizeof($yaml['questions']) - 1);
            }

            $asking = $yaml['questions'][$random];
            $questionsAsked[] = $random;
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
            $foundAnswer = false;
            foreach ($asking['answers'] as $correctAnswer) {
                foreach ($answers as $enteredAnswer) {
                    if (
                        strtolower($correctAnswer) == strtolower($enteredAnswer) ||
                        $this->compareString($correctAnswer, $enteredAnswer) > 75
                    ) {
                        $foundCount++;
                        $foundAnswer = true;
                    }
                }
            }

            if (!$foundAnswer) {
                $output->writeln("/ = \ = / = \ / = \ = /");
                $output->writeln("answers: " . join(", ", $asking['answers']));
                $output->writeln("/ = \ = / = \ / = \ = /");
            } else {
                $correct++;
            }

            $output->writeln("========================================");
            $output->writeln("\n");
        }        


        $output->writeln($correct . " / " . $questionCount . " = " . round($correct / $questionCount, 2) * 100 . "%");

        if ($correct / $questionCount > 0.6) {
            $output->writeln("Pass!");
            return Command::SUCCESS;
        } else {
            $output->writeln("Fail!");
            return Command::FAILURE;
        }
    }


    private function compareString($stringOne, $stringTwo) : int {
        $max = max(strlen($stringOne), strlen($stringTwo));
        $correct = 0;

        for ($i = 0; $i < $max; $i++) {
            if (!empty($stringOne[$i]) && !empty($stringTwo[$i])) {
                if ($stringOne[$i] == $stringTwo[$i]) {
                    $correct++;
                }
            }
        }

        return round($correct / $max, 2) * 100;
    }
}
