<?php

namespace Drutiny\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Yaml\Yaml;

/**
 * Helper for building checks.
 */
class CheckGenerateCommand extends Command {

  /**
   * @inheritdoc
   */
  protected function configure() {
    $this
      ->setName('check:generate')
      ->setDescription('Create a check');
  }

  /**
   * @inheritdoc
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $helper = $this->getHelper('question');

    // Title.
    $question = new Question('What is the title of your check? ', 'My Custom Check');
    $title = $helper->ask($input, $output, $question);

    // Name.
    $question = new Question('Please provide a machine name for your check? ', strtolower(preg_replace('/[^a-z0-9]/', '', $title)));
    $name = $helper->ask($input, $output, $question);

    $yaml['title'] = $title;
    $class = str_replace(' ', '', ucwords(strtolower($title)));
    $yaml['class'] = 'Drutiny\Check\\' . $class;
    $yaml['description'] = 'Description of what the check is and why you would use it.';
    $yaml['remediation'] = 'Recommendations on how to remediation go here.';
    $yaml['success'] = "Text for the successful report of the check.\n Use {{foo}} to render output.";
    $yaml['failure'] = "Text for the failed report of the check.\n Use {{foo}} to render output.";
    $yaml['parameters']['foo'] = [
      'type' => 'string',
      'description' => 'What the parameter is and why how it is used to configure the check.',
      'default' => 'bar',
    ];
    $yaml['tags'] = ['Custom'];

    $question = new ConfirmationQuestion("Does this check support auto-remediation? (y/n) ");
    $remediable = $helper->ask($input, $output, $question);

    $check_yaml_filepath = 'src/Check/' . $name . '.yml';
    file_put_contents($check_yaml_filepath, Yaml::dump($yaml, 4));
    $output->writeln("<info>Created $check_yaml_filepath</info>");

    $check_php = $remediable ? $this->getRemediableCheckTemplate($title, $class) : $this->getCheckTemplate($title, $class);
    $check_php_filepath = 'src/Check/' . $class . '.php';
    file_put_contents($check_php_filepath, $check_php);
    $output->writeln("<info>Created $check_php_filepath</info>");
  }

  /**
   *
   */
  public function getRemediableCheckTemplate($title, $class) {
    return '<?php

    namespace Drutiny\Check;

    use Drutiny\Sandbox\Sandbox;

    /**
     * ' . $title . '
     */
    class ' . $class . ' extends Check implements RemediableInterface {

      /**
       * @inheritDoc
       */
      public function check(Sandbox $sandbox) {
        // TODO: Write check.
        return FALSE;
      }

      /**
       * @inheritDoc
       */
      public function remediate(Sandbox $sandbox) {
        // TODO: Remediate site.
        return $this->check($sandbox);
      }
    }
    ';
  }

  /**
   *
   */
  public function getCheckTemplate($title, $class) {
    return '<?php

    namespace Drutiny\Check;

    use Drutiny\Sandbox\Sandbox;

    /**
     * ' . $title . '
     */
    class ' . $class . ' extends Check {

      /**
       * @inheritDoc
       */
      public function check(Sandbox $sandbox) {
        // TODO: Write check.
        return FALSE;
      }
    }
    ';
  }

}
