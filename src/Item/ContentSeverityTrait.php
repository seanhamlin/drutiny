<?php
namespace Drutiny\Item;

trait ContentSeverityTrait {

  /**
   * @bool Severity.
   */
  protected $severity = 0;

  public function setSeverity($sev = 0)
  {
    switch (TRUE) {
      case $sev === self::SEVERITY_NONE:
      case $sev === 'none':
        $this->severity = Item::SEVERITY_NONE;
        break;

      case $sev === self::SEVERITY_LOW:
      case $sev === 'low':
        $this->severity = Item::SEVERITY_LOW;
        break;

      case $sev === self::SEVERITY_NORMAL:
      case $sev === 'normal':
        $this->severity = Item::SEVERITY_NORMAL;
        break;

      case $sev === self::SEVERITY_HIGH:
      case $sev === 'high':
        $this->severity = Item::SEVERITY_HIGH;
        break;

      case $sev === self::SEVERITY_CRITICAL:
      case $sev === 'critical':
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
