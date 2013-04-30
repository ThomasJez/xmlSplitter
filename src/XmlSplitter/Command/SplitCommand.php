<?php

namespace XmlSplitter\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use XmlSplitter\XmlSplitter;

class SplitCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('split')
            ->setDescription('split an XML File')
            ->addArgument(
                'file',
                InputArgument::REQUIRED,
                'the file name of the xml'
            )
            ->addArgument(
                'tag',
                InputArgument::REQUIRED,
                'on which tag you want to split'
            )
            ->addOption(
                'name-by-tag-value',
                'ntv',
                InputOption::VALUE_OPTIONAL,
                'which tag value should be used to name the splitted file'
            )
            ->addOption(
                'name-by-attribute-value',
                'nav',
                InputOption::VALUE_OPTIONAL,
                'which attribute value should be used to name the splitted file'
            )
            ->addOption(
                'output-folder',
                'of',
                InputOption::VALUE_OPTIONAL,
                'absolute path to a folder you want to store the splitted xml files'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $reader = new \XMLReader();
        $xmlSplitter = new XmlSplitter($reader, $input->getArgument('file'));
        $xmlSplitter->setNameByTag($input->getOption('name-by-tag-value'));
        $xmlSplitter->setNameByAttribute($input->getOption('name-by-attribute-value'));
        if (!is_null($input->getOption('output-folder'))) {
            $xmlSplitter->setOutputFolder($input->getOption('output-folder'));
        }
        $xmlSplitter->split($input->getArgument('tag'));

    }
}
