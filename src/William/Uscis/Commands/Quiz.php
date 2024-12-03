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
            foreach ($answers as $enteredAnswer) {
                foreach ($asking['answers'] as $correctAnswer) {                
                    $longest_string = $this->get_longest_common_subsequence($correctAnswer, $enteredAnswer);
                    $perc = round(strlen($longest_string) / strlen($correctAnswer), 2) * 100;
                    if (
                        strtolower($correctAnswer) == strtolower($enteredAnswer) ||
                        $perc >= 75
                    ) {
                        $foundCount++;
                        echo "foundCount++";
                        break;
                    }
                }
            }

            if ($foundCount == $count) {
                $correct++;
            } else {
                $output->writeln("/ = \ = / = \ / = \ = /");
                $output->writeln("answers: " . join(", ", $asking['answers']));
                $output->writeln("/ = \ = / = \ / = \ = /");

            }

            $output->writeln("========================================");
            $output->writeln("\n");
        }        


        $output->writeln($correct . " / " . $questionCount . " = " . round($correct / $questionCount, 2) * 100 . "%");

        if ($correct / $questionCount >= 0.6) {
            $output->writeln("Pass!");
            return Command::SUCCESS;
        } else {
            $output->writeln("Fail!");
            return Command::FAILURE;
        }
    }

    // https://en.wikibooks.org/wiki/Algorithm_Implementation/Strings/Longest_common_substring#PHP
    private function get_longest_common_subsequence($string_1, $string_2) {
        $string_1_length = strlen($string_1);
        $string_2_length = strlen($string_2);
        $return          = '';
        
        if ($string_1_length === 0 || $string_2_length === 0)
        {
            // No similarities
            return $return;
        }
        
        $longest_common_subsequence = array();
        
        // Initialize the CSL array to assume there are no similarities
        $longest_common_subsequence = array_fill(0, $string_1_length, array_fill(0, $string_2_length, 0));
        
        $largest_size = 0;
        
        for ($i = 0; $i < $string_1_length; $i++)
        {
            for ($j = 0; $j < $string_2_length; $j++)
            {
                // Check every combination of characters
                if ($string_1[$i] === $string_2[$j])
                {
                    // These are the same in both strings
                    if ($i === 0 || $j === 0)
                    {
                        // It's the first character, so it's clearly only 1 character long
                        $longest_common_subsequence[$i][$j] = 1;
                    }
                    else
                    {
                        // It's one character longer than the string from the previous character
                        $longest_common_subsequence[$i][$j] = $longest_common_subsequence[$i - 1][$j - 1] + 1;
                    }
                    
                    if ($longest_common_subsequence[$i][$j] > $largest_size)
                    {
                        // Remember this as the largest
                        $largest_size = $longest_common_subsequence[$i][$j];
                        // Wipe any previous results
                        $return       = '';
                        // And then fall through to remember this new value
                    }
                    
                    if ($longest_common_subsequence[$i][$j] === $largest_size)
                    {
                        // Remember the largest string(s)
                        $return = substr($string_1, $i - $largest_size + 1, $largest_size);
                    }
                }
                // Else, $CSL should be set to 0, which it was already initialized to
            }
        }
        
        // Return the list of matches
        return $return;
    }
}
