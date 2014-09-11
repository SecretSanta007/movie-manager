<?php

namespace Mihaeu\MovieManager\Console;

use Mihaeu\MovieManager\Ini\Reader;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends Command
{
    /**
     * @var array|Callback[]
     */
    private $filters;

    public function configure()
    {
        $this
            ->setName('list')
            ->setDescription('Lists all the (correctly formatted) movies in a directory.')
            ->addArgument(
                'path',
                InputArgument::REQUIRED,
                'Path to your movie folder.'
            )
            ->addOption(
                'print0',
                null,
                InputOption::VALUE_NONE,
                'Prints the movies with a null character instead of new lines (e.g. for xargs -0).'
            )
            ->addOption(
                'year-from',
                null,
                InputOption::VALUE_REQUIRED,
                'List only movies from a certain year (e.g. -yf 2000 list movies between 2000-2014).'
            )
            ->addOption(
                'year-to',
                null,
                InputOption::VALUE_REQUIRED,
                'List only movies up to a certain year (e.g. -yt 2000 list movies between 1900-2000).'
            )
            ->addOption(
                'rating',
                null,
                InputOption::VALUE_REQUIRED,
                'List only movies with an IMDb rating equal or higher then this rating.'
            )
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $path = $input->getArgument('path');
        $delimiter = $input->getOption('print0') ? "\0" : PHP_EOL;

        $this->setUpFilters($input);

        $movies = [];
        $movieFolders = array_diff(scandir($path), ['.', '..']);
        foreach ($movieFolders as $movieFolder) {
            $linkFile = "$path/$movieFolder/$movieFolder - IMDb.url";
            $movieInfo = Reader::read($linkFile);
            if (false === $movieInfo) {
                continue;
            }

            if (!isset($movieInfo['info'])) {
                continue;
            }

            if ($this->passesFilters($input, $movieInfo['info'])) {
                $movies[] = realpath($path).DIRECTORY_SEPARATOR.$movieFolder;
            }
        }
        echo implode($delimiter, $movies);
    }

    public function setUpFilters(InputInterface $input)
    {
        if ($input->getOption('year-from')) {
            $this->filters['year-from'] = function (array $movieInfo, $year) {
                return isset($movieInfo['release_date']) && $movieInfo['release_date'] >= $year;
            };
        }
        if ($input->getOption('year-to')) {
            $this->filters['year-to'] = function (array $movieInfo, $year) {
                return isset($movieInfo['release_date']) && $movieInfo['release_date'] <= $year;
            };
        }
        if ($input->getOption('rating')) {
            $this->filters['rating'] = function (array $movieInfo, $rating) {
                return isset($movieInfo['imdb_rating']) && $movieInfo['imdb_rating'] >= $rating;
            };
        }
    }

    public function passesFilters(InputInterface $input, $movieInfo)
    {
        if ($input->getOption('year-from')
            && isset($movieInfo['release_date'])
            && $movieInfo['release_date'] >= $input->getOption('year-from')) {
            return true;
        }
        if ($input->getOption('year-to')
            && isset($movieInfo['release_date'])
            && $movieInfo['release_date'] <= $input->getOption('year-to')) {
            return true;
        }
        if ($input->getOption('rating')
            && (
            (isset($movieInfo['imdb_rating']) && floatval($movieInfo['imdb_rating']) >= floatval($input->getOption('rating')))
            || (isset($movieInfo['popularity']) && floatval($movieInfo['popularity']) >= floatval($input->getOption('rating')))
            )) {
            @print($movieInfo['original_title'].'-'.$movieInfo['popularity'].$movieInfo['imdb_rating'].PHP_EOL);
            return true;
        }
    }
}