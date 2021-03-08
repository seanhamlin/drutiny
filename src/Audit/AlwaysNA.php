<?php

namespace Drutiny\Audit;

use Drutiny\Audit;
use Drutiny\Audit\AuditValidationException;
use Drutiny\Sandbox\Sandbox;

/**
 * An audit that always not applicable.
 */
class AlwaysNA extends Audit
{

    public function audit(Sandbox $sandbox)
    {
        throw new AuditValidationException("This policy is not applicable.");
    }
}
