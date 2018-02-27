<?php
namespace Drutiny\Item;

trait ContentSeverityTrait {

  /**
   * @bool Severity.
   */
  protected $severity = 0;

  public function setSeverity($sev = 0)
  {
    switch ($sev) {
      case self::SEVERITY_NONE:
      case 'none':
        $this->severity = Item::SEVERITY_NONE;
        break;

      case self::SEVERITY_LOW:
      case 'low':
        $this->severity = Item::SEVERITY_LOW;
        break;

      case self::SEVERITY_NORMAL:
      case 'normal':
        $this->severity = Item::SEVERITY_NORMAL;
        break;

      case self::SEVERITY_HIGH:
      case 'high':
        $this->severity = Item::SEVERITY_HIGH;
        break;

      case self::SEVERITY_CRITICAL:
      case 'critical':
        $this->severity = Item::SEVERITY_CRITICAL;
        break;

      default:
        throw new \Exception("Unknown severity level: $sev.");
    }
  }

  public function getSeverity()
  {
    return $this->severity;
  }

  public function getSeverityName()
  {

    switch (TRUE) {
      case $this->severity === self::SEVERITY_NONE:
      case $this->severity === 'none':
        return 'none';

      case $this->severity === self::SEVERITY_LOW:
      case $this->severity === 'low':
        return 'low';

      case $this->severity === self::SEVERITY_NORMAL:
      case $this->severity === 'normal':
        return 'normal';

      case $this->severity === self::SEVERITY_HIGH:
      case $this->severity === 'high':
        return 'high';

      case $this->severity === self::SEVERITY_CRITICAL:
      case $this->severity === 'critical':
        return 'critical';

      default:
        throw new \Exception("Unknown severity level: $sev.");
    }
  }
}
 ?>
