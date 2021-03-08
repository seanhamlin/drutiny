<?php

namespace Drutiny\Console\Command;


use Drutiny\PolicyFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 *
 */
class AuditListCommand extends DrutinyBaseCommand
{
    protected $policyFactory;

    public function __construct(PolicyFactory $policyFactory)
    {
        $this->policyFactory = $policyFactory;
        parent::__construct();
    }

  /**
   * @inheritdoc
   */
    protected function configure()
    {
        $this
        ->setName('audit:list')
        ->setDescription('Show all php audit classes available.')
        ;
    }

  /**
   * @inheritdoc
   */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getTargetFactory()->create('@none');

        $finder = new Finder();
        $finder->directories()
        ->in(DRUTINY_LIB)
        ->name('Audit');

        $files = new Finder();
        $files->files()->name('*.php');
        foreach ($finder as $dir) {
            if (strpos($dir->getRealPath(), '/tests/') !== false) {
                continue;
            }
            $files->in($dir->getRealPath());
        }

        $list = [];
        foreach ($files as $file) {
            include_once $file->getRealPath();
        }

        $audits = array_filter(get_declared_classes(), function ($class) {
            $reflect = new \ReflectionClass($class);
            if ($reflect->isAbstract()) {
                return false;
            }
            return $reflect->isSubclassOf('\Drutiny\Audit');
        });

        sort($audits);
        $policy_list = $this->policyFactory->getPolicyList(true);

        $stats = [];
        foreach ($audits as $audit) {
          try {
            $instance = $this->getContainer()->get($audit);
          }
          catch (ServiceNotFoundException $e) {
            $this->getLogger()->error($e->getMessage());
            continue;
          }

          $deprecated = $instance->isDeprecated() ? ' <fg=yellow>(deprecated)</>' : '';
          $stats[] = [$audit.$deprecated, count(array_filter($policy_list, function ($policy) use ($audit) {
            return $audit == $policy['class'];
          }))];
        }

        $io = new SymfonyStyle($input, $output);
        $io->title('Drutiny Audit Classes');
        // $io->listing($audits);
        $io->table(['Audit', 'Policy utilisation'], $stats);
        return 0;
    }
}
