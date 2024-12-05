<?php

namespace William\Uscis;

use Symfony\Component\Yaml\Yaml;

class Helper {

    const QUESTIONS_FILE = __DIR__ . '/../../../questions.yml';
    const COUNTS_FILE = __DIR__ . '/../../../counts.yml';
    const RESULTS_FILE = __DIR__ . '/../../../results.yml';

    public static function loadQuestions() : array {
        $questions = Yaml::parseFile(self::QUESTIONS_FILE);
        return $questions;
    }

    public static function loadCounts() : array {
        $counts = Yaml::parseFile(self::COUNTS_FILE);
        return $counts;
    }

    public static function loadResults() : array {
        $results = Yaml::parseFile(self::RESULTS_FILE);
        return $results;
    }

    public static function saveQuestions($array) : void {
        file_put_contents(self::QUESTIONS_FILE, Yaml::dump($array));
    }

    public static function saveCounts($array) : void {
        file_put_contents(self::COUNTS_FILE, Yaml::dump($array));
    }

    public static function saveResults($array) : void {
        file_put_contents(self::RESULTS_FILE, Yaml::dump($array));
    }

    public static function sortQuestionsByCount() : callable {
        return function ($a, $b) {
            if ($a['count'] == $b['count']) {
                return 0;
            }
            return ($a['count'] < $b['count']) ? -1 : 1;
        };
    }

    public static function sortCountsByPerc() : callable {
        return function($a, $b) {
            if ($a['count'] > 0 && $b['count'] > 0) {
                $aPerc = $a['correct'] / $a['count'];
                $bPerc = $b['correct'] / $b['count'];

                if ($aPerc == $bPerc) {
                    return 0;
                }

                return ($aPerc < $bPerc) ? -1 : 1;
            } else {
                return 0;
            }
        };
    }

    // https://en.wikibooks.org/wiki/Algorithm_Implementation/Strings/Longest_common_substring#PHP
    public static function get_longest_common_subsequence($string_1, $string_2) {
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
