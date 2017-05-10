<?php

namespace Drutiny\Check\D7;

use Drutiny\Check\D8\User1 as D8User1;
use Drutiny\Sandbox\Sandbox;
use Drutiny\Check\RemediableInterface;

/**
 * User #1
 */
class User1 extends D8User1 implements RemediableInterface {

  public function remediate(Sandbox $sandbox)
  {
    $output = $sandbox->drush()->evaluate(function ($status, $email) {
      $user = user_load(1);
      $user->status = $status;
      $user->pass = user_password(32);
      $user->mail = $email;
      $user->name = user_password();
      return user_save($user);
    }, [
      'status' => (int) (bool) $sandbox->getParameter('status'),
      'email' => $sandbox->getParameter('email')
    ]);

    return $this->check($sandbox);
  }

}
