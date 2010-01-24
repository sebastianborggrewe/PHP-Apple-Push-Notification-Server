<?php
/**
 * Usage Examples
 *
 * @author  Sebastian Borggrewe <me@sebastianborggrewe.de>
 * @since   2010/01/24
 * @package APNP
 */

error_reporting(E_ALL | E_STRICT);
include 'APNSBase.php';
include 'APNotification.php';
include 'APFeedback.php';

try{

  # Notification Example
  $notification = new APNotification('development');
  $notification->setDeviceToken("xxxxxxxx xxxxxxxx xxxxxxxx xxxxxxxx xxxxxxxx xxxxxxxx xxxxxxxx xxxxxxxx");
  $notification->setMessage("Test Push");
  $notification->setBadge(1);
  $notification->setPrivateKey('certificate/apns-dev.pem');
  $notification->setPrivateKeyPassphrase('test');
  $notification->send();

  # Feedback Example
  $feedback = new APFeedback('development');
  $feedback->setPrivateKey('certificate/apns-dev.pem');
  $feedback->setPrivateKeyPassphrase('test');
  $feedback->receive();

}catch(Exception $e){
  echo $e->getLine().': '.$e->getMessage();
}
?>
