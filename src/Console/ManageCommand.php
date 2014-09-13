<?php

namespace Mihaeu\MovieManager\Console;

use Mihaeu\MovieManager\Config;
use Mihaeu\MovieManager\Ini\Reader;
use Mihaeu\MovieManager\MovieFinder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class ManageCommand extends Command
{
    public function configure()
    {
        $this
            ->setName('manage')
            ->setDescription('Manage your movie collection.')
            ->addArgument(
                'path',
                InputArgument::REQUIRED,
                'Path to your movie folder.'
            )
            ->addOption(
                'show-all',
                null,
                InputOption::VALUE_NONE,
                'Shows all movies instead of only bad ones.'
            )
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $config = new Config();
        $finder = new MovieFinder();
        $movieFiles = $finder->findMoviesInDir($input->getArgument('path'), $config->get('allowed-movie-formats'));

        if (!$input->getOption('show-all')) {
            $movieFiles = $this->filterBadMovies($movieFiles);
        }

        $table = $this->getHelper('table');
        $table
            ->setHeaders(['Name', 'Format ', 'Folder ', 'Info   ', 'Screeny', 'Poster '])
            ->setRows($this->formatMoviesForTable($movieFiles))
        ;
        $table->render($output);

        $this->manageMoviesInteractively($movieFiles, $input, $output);
    }

    /**
     * Filters the movies so that only movies which are not properly parsed will be left.
     *
     * @param array $movieFiles
     *
     * @return array
     */
    public function filterBadMovies(array $movieFiles)
    {
        return array_filter($movieFiles, function ($movie) {
            return !$movie['format']
            || !$movie['folder']
            || !$movie['link']
            || !$movie['screenshot']
            || !$movie['poster'];
        });
    }

    /**
     * Formats a movie for pretty printing in a symfony console table.
     *
     * @param array $movieFiles
     *
     * @return array
     */
    public function formatMoviesForTable(array $movieFiles)
    {
        return array_map(function (array $movie) {
            return [
                substr($movie['name'], 0, 40),
                $movie['format']        ? '<fg=black;bg=green>   ✔   </fg=black;bg=green>' : '<fg=black;bg=red>   ✘   </fg=black;bg=red>',
                $movie['folder']        ? '<fg=black;bg=green>   ✔   </fg=black;bg=green>' : '<fg=black;bg=red>   ✘   </fg=black;bg=red>',
                $movie['link']          ? '<fg=black;bg=green>   ✔   </fg=black;bg=green>' : '<fg=black;bg=red>   ✘   </fg=black;bg=red>',
                $movie['screenshot']    ? '<fg=black;bg=green>   ✔   </fg=black;bg=green>' : '<fg=black;bg=red>   ✘   </fg=black;bg=red>',
                $movie['poster']        ? '<fg=black;bg=green>   ✔   </fg=black;bg=green>' : '<fg=black;bg=red>   ✘   </fg=black;bg=red>'
            ];
        }, $movieFiles);
    }

    /**
     * @param array $movieFiles
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    public function manageMoviesInteractively(array $movieFiles, InputInterface $input, OutputInterface $output)
    {
        /** @var QuestionHelper $dialog */
        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            'Process movie?',
            ['y' => 'yes', 'n' => 'no', 'q' => 'quit'],
            'y'
        );

        $index = 0;
        foreach ($movieFiles as $movie) {
            $output->writeln(sprintf("\n<info>[%d/%d] %s</info>", ++$index, count($movieFiles), $movie['name']));
            $answer = $helper->ask($input, $output, $question);
            if ('no' === $answer) {
                continue;
            }

            if ('quit' === $answer) {
                return;
            }

            if ($movie['link']) {
                $infoFile = $movie['path'].DIRECTORY_SEPARATOR.basename($movie['path']).' - IMDb.url';
                $movieInfo = Reader::read($infoFile);
            }
        }
    }
}