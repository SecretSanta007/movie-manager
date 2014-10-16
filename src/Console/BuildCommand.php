<?php

namespace Mihaeu\MovieManager\Console;

use Mihaeu\MovieManager\Builder\Html;

use Mihaeu\MovieManager\Config;
use Mihaeu\MovieManager\Factory\FileSetFactory;
use Mihaeu\MovieManager\Factory\MovieFactory;
use Mihaeu\MovieManager\IO\Filesystem;
use Mihaeu\MovieManager\IO\Ini;
use Mihaeu\MovieManager\MovieDatabase\IMDb;
use Mihaeu\MovieManager\MovieDatabase\OMDb;
use Mihaeu\MovieManager\MovieDatabase\TMDb;
use Mihaeu\MovieManager\MovieFinder;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class BuildCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setName('build')
            ->setDescription('Builds a nice collection file in HTML.')
            ->addArgument(
                'path',
                InputArgument::REQUIRED,
                'Path to your movie folder.'
            )
            ->addArgument(
                'save',
                InputArgument::OPTIONAL,
                'Save the result to a file.',
                'php://output'
            )
            ->addOption(
                'limit',
                null,
                InputOption::VALUE_REQUIRED,
                'Limit the number of movies.',
                -1
            )
            ->addOption(
                'no-posters',
                null,
                InputOption::VALUE_NONE,
                'Limit the number of movies.'
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $movieFactory = new MovieFactory(null, null, null, new Ini(new Filesystem()));
        $buildWithPosters = !$input->getOption('no-posters');
        $builder = new Html($movieFactory, $buildWithPosters);

        $path = realpath($input->getArgument('path'));
        $config = new Config();
        $movieFinder = new MovieFinder(new FileSetFactory($path), $config->get('allowed-movie-formats'));
        $movies = $movieFinder->findMoviesInDir($path);
        file_put_contents(
            $input->getArgument('save'),
            $builder->build($movies)
        );
    }
}
