<?php

namespace Drutiny\Command;

use Drutiny\Profile\ProfileController;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drutiny\Check\Registry;
use Drutiny\Profile\Profile;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 *
 */
class ProfileCreateCommand extends Command {

  /**
   * @inheritdoc
   */
  protected function configure() {
    $this
      ->setName('profile:generate')
      ->setDescription('Create a profile of checks.');
  }

  /**
   * @inheritdoc
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $profile = new Profile();

    $helper = $this->getHelper('question');
    $question = new Question('Title: ', $profile->getTitle());
    $question->setValidator(function ($value) {
      if (trim($value) == '') {
        throw new \Exception('Title cannot be empty');
      }
        return $value;
    });

    $profile->setTitle($helper->ask($input, $output, $question));

    $question = new Question('Machine name: ', $profile->getTitle());
    $question->setValidator(function ($value) {
      if (trim($value) == '') {
        throw new \Exception('Machine name cannot be empty');
      }
        return $value;
    });

    $profile->setMachineName($helper->ask($input, $output, $question));

    foreach (Registry::load() as $check => $filepath) {
      $question = new ConfirmationQuestion("Would you like to use $check? (y/n) ", TRUE);
      if ($helper->ask($input, $output, $question)) {
        $profile->addCheck($check);
      }
    }

    $dir = ProfileController::save($profile);
    $output->writeln('<info>Profile written to ' . $dir . '</info>');
  }

}
