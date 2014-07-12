<?php

namespace Mihaeu\MovieManager\Console;

use Mihaeu\MovieManager\Builder\Html;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class BuildCommand extends Command
{
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
                'Save the result to a file.'
            )
            ->addOption(
                'limit',
                -1,
                InputOption::VALUE_REQUIRED,
                'Limit the number of movies.'
            )
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $builder = new Html();

        $path = realpath($input->getArgument('path'));
        if (is_writable(dirname($input->getArgument('save')))) {
            $save = realpath($input->getArgument('save'));
            if (!is_writable(dirname($save))) {
                throw new \InvalidArgumentException(dirname($save).' is not writable.');
            }
            file_put_contents($save, $builder->build($path, $input->getOption('limit')));
        } else {
            echo $builder->build($path, $input->getOption('limit'));
        }
    }
}