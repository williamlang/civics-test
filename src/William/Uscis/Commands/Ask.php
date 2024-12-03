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

#[AsCommand(name: 'app:ask')]
class Ask extends Command {

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

        $random = mt_rand(0, sizeof($yaml['questions']) - 1);

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
        foreach ($asking['answers'] as $correctAnswer) {
            foreach ($answers as $enteredAnswer) {
                if ($correctAnswer == $enteredAnswer) {
                    $correct++;
                }
            }
        }

        if ($correct == $count) {
            $output->writeln("Correct!");
            return Command::SUCCESS;
        } else {
            $output->writeln("Not correct, answers: " . join(", ", $asking['answers']));
            return Command::FAILURE;
        }
    }

}
