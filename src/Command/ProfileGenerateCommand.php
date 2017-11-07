<?php

namespace Drutiny\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Drutiny\Registry;
use Drutiny\ProfileInformation;
use Symfony\Component\Yaml\Yaml;

/**
 *
 */
class ProfileGenerateCommand extends Command {

  /**
   * @inheritdoc
   */
  protected function configure() {
    $this
      ->setName('profile:generate')
      ->setDescription('Create a profile');
  }

  /**
   * @inheritdoc
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $helper = $this->getHelper('question');
    $profile_data = [];

    // Title.
    $question = new Question('Please provide a title for your profile: ', 'My Custom Profile');
    $profile_data['title'] = $helper->ask($input, $output, $question);

    // Name.
    $question = new Question('Please provide a machine name for your profile: ', strtolower(preg_replace('/[^a-z0-9]/', '', $profile_data['title'])));
    $name = $helper->ask($input, $output, $question);

    // Checks.
    $checks = array_keys((new Registry())->policies());
    $question = new Question("Add policies you would like added to this profile:\n > ");
    do {
      $question->setAutocompleterValues($checks);
      $value = $helper->ask($input, $output, $question);
      if (!empty($value)) {
        $profile_data['policies'][$value] = [];
        $i = array_search($value, $checks);
        unset($checks[$i]);
      }
      $question = new Question("Add another policy or <enter> to finish: ");
    } while (!empty($value));

    // Validate profile.
    try {
      $profile = new ProfileInformation($profile_data);
      $filepath = 'profiles/' . $name . '.profile.yml';
      if (!is_dir('profiles')) {
        mkdir('profiles');
      }
      file_put_contents($filepath, Yaml::dump($profile_data));
      $output->writeln("<info>Written profile to $filepath</info>");
    }
    catch (\Exception $e) {
      $output->writeln('<error>' . $e->getMessage() . '</error>');
    }
  }

}
