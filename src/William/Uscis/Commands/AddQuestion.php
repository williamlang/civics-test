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

#[AsCommand(name: 'app:add-question')]
class AddQuestion extends Command {

    protected function configure(): void {
        $this
            // the command description shown when running "php bin/console list"
            ->setDescription('Adds a question');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $fileName = __DIR__ . '/../../../../questions.yml';
        $yaml = Yaml::parseFile($fileName);
        if (empty($yaml)) {
            return Command::FAILURE;
        }

        /** @var HelperQuestion $helper */
        $helper = $this->getHelper('question');

        $question = new Question("Please enter the question: ");
        $questionValue = $helper->ask($input, $output, $question);

        $question = new Question("What is the answer? Finish with 'x' ");

        $answers = [];
        $answerValue = null;
        while ($answerValue != 'x') {
            $answerValue = $helper->ask($input, $output, $question);

            if ($answerValue != 'x') {
                $answers[] = $answerValue;
            }

            if (empty($answerValue)) {
                $answerValue = 'x';
            }
        }

        $question = new Question("How many answers are required? ");
        $countValue = $helper->ask($input, $output, $question);

        $questionCount = sizeof($yaml['questions']);
        $addedQuestion = [
            'id' => $questionCount + 1,
            'question' => $questionValue,
            'answers' => $answers,
        ];

        if (!empty($countValue) && $countValue > 1) {
            $addedQuestion['count'] = intval($countValue);
        }

        $yaml['questions'][] = $addedQuestion;
        
        file_put_contents($fileName, Yaml::dump($yaml));

        return Command::SUCCESS;
    }

}
