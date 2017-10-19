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
class AuditGenerateCommand extends Command {

  /**
   * @inheritdoc
   */
  protected function configure() {
    $this
      ->setName('audit:generate')
      ->setDescription('Create an Audit class');
  }

  /**
   * @inheritdoc
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $helper = $this->getHelper('question');

    // Title.
    $question = new Question('What is the title of your audit? ', 'My Custom Audit');
    $title = $helper->ask($input, $output, $question);

    // Name.
    $question = new Question('Please provide a machine name for your audit? ', strtolower(preg_replace('/[^a-z0-9]/', '', $title)));
    $name = $helper->ask($input, $output, $question);

    $yaml['title'] = $title;
    $class = str_replace(' ', '', ucwords(strtolower($title)));
    $yaml['class'] = 'Drutiny\Audit\\' . $class;
    $yaml['description'] = 'Description of what the audit is and why you would use it.';
    $yaml['remediation'] = 'Recommendations on how to remediation go here.';
    $yaml['success'] = "Text for the successful report of the audit.\n Use {{foo}} to render output.";
    $yaml['failure'] = "Text for the failed report of the audit.\n Use {{foo}} to render output.";
    $yaml['parameters']['foo'] = [
      'type' => 'string',
      'description' => 'What the parameter is and why how it is used to configure the audit.',
      'default' => 'bar',
    ];
    $yaml['tags'] = ['Custom'];

    $question = new ConfirmationQuestion("Does this audit support auto-remediation? (y/n) ");
    $remediable = $helper->ask($input, $output, $question);

    $check_yaml_filepath = 'src/Audit/' . $name . '.yml';
    file_put_contents($check_yaml_filepath, Yaml::dump($yaml, 4));
    $output->writeln("<info>Created $check_yaml_filepath</info>");

    $check_php = $remediable ? $this->getRemediableCheckTemplate($title, $class) : $this->getCheckTemplate($title, $class);
    $check_php_filepath = 'src/Audit/' . $class . '.php';
    file_put_contents($check_php_filepath, $check_php);
    $output->writeln("<info>Created $check_php_filepath</info>");
  }

  /**
   *
   */
  public function getRemediableCheckTemplate($title, $class) {
    return '<?php

    namespace Drutiny\Audit;

    use Drutiny\Sandbox\Sandbox;

    /**
     * ' . $title . '
     */
    class ' . $class . ' extends Audit implements RemediableInterface {

      /**
       * @inheritDoc
       */
      public function audit(Sandbox $sandbox) {
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

    namespace Drutiny\Audit;

    use Drutiny\Sandbox\Sandbox;

    /**
     * ' . $title . '
     */
    class ' . $class . ' extends Audit {

      /**
       * @inheritDoc
       */
      public function audit(Sandbox $sandbox) {
        // TODO: Write check.
        return FALSE;
      }
    }
    ';
  }

}
