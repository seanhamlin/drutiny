<?php

namespace Drutiny\Annotation;

/**
 * @Annotation
 */
class CheckInfo
{
  public $title;
  public $description;
  public $remediation;
  public $success;
  public $failure;
  public $exception;
  public $not_available;
  public $warning;
  public $notice;
  public $supports_remediation = FALSE;
  public $testing = FALSE;
}
