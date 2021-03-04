<?php

namespace Drutiny\PolicySource;

use Drutiny\Policy;
use Drutiny\Policy\UnavailablePolicyException;
use Drutiny\LanguageManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Finder\Finder;
use Psr\Log\LoggerInterface;

class PolicyStorage implements PolicySourceInterface
{
    protected PolicySourceInterface $source;
    protected LanguageManager $languageManager;
    protected string $list;
    protected LoggerInterface $logger;

    public function __construct(PolicySourceInterface $source, ContainerInterface $container, LanguageManager $languageManager)
    {
      $this->source = $source;
      $this->store = strtr('%dir/sources/%lang/%source', [
        '%dir' => $container->getParameter('policy.library.fs'),
        '%lang' => $languageManager->getCurrentLanguage(),
        '%source' => str_replace('\\', '', get_class($source))
      ]);
      $this->languageManager = $languageManager;

      is_dir($this->store) || mkdir($this->store, 0744, true);

      $this->list = $this->store . '/list.json';

      $this->logger = $container->get('Psr\Log\LoggerInterface');
    }

    /**
     *{@inheritdoc}
     */
    public function getName()
    {
      return $this->source->getName();
    }

    /**
     * Sync contents from source.
     */
    public function refresh()
    {
      file_exists($this->list) && unlink($this->list);

      foreach ($this->getList($this->languageManager) as $definition) {
        $filename = $this->policyLocation($definition);
        file_exists($filename) && unlink($filename);
        try {
          yield $this->load($definition);
        }
        catch (UnavailablePolicyException $e) {
          $this->logger->error($e->getMessage());
        }
      }
    }

    private function policyLocation(array $definition)
    {
      return $this->store . '/' . $definition['uuid'] . '.policy.json';
    }

    /**
     *{@inheritdoc}
     */
    public function getList(LanguageManager $languageManager)
    {
      if (file_exists($this->list)) {
        return json_decode(file_get_contents($this->list), true);
      }
      $contents = $this->source->getList($languageManager);
      file_put_contents($this->list, json_encode($contents));
      return $contents;
    }

    /**
     *{@inheritdoc}
     */
    public function load(array $definition)
    {

      if (file_exists($this->policyLocation($definition))) {
        $properties = json_decode(file_get_contents($this->policyLocation($definition)), true);
        $policy = new Policy();
        $policy->setProperties($properties);
        return $policy;
      }

      $policy = $this->source->load($definition);
      file_put_contents($this->policyLocation($definition), json_encode($policy->export()));
      return $policy;
    }

    /**
     *{@inheritdoc}
     */
    public function getWeight()
    {
      return $this->source->getWeight();
    }
}
