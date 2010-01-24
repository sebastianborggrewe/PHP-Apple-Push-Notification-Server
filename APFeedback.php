<?php
/**
 * APFeedback
 * Allows to request a Feedback Message from Apple
 * in order to tell you wether the user still
 * uses your application.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * 
 * @author  Sebastian Borggrewe <me@sebastianborggrewe.de>
 * @since   2010/01/24
 * @package APNP
 */
class APFeedback extends APNSBase{
  /**
   * Sets the URLs for the two different
   * run enviroments test and production
   *
   * @var array
   */
  protected static $_ENVIROMENTS = array(
                      'development' => 'ssl://feedback.sandbox.push.apple.com:2196',
                      'production' => 'ssl://feedback.push.apple.com:2196'
                   );

  /**
   * Initializes a Feedback with an
   * enviroment.
   * Valid values: development, production
   *
   * @param string $enviroment (production or developement)
   */
  public function  __construct($enviroment = "development") {
    parent::__construct($enviroment);
  }

 /**
  * Get Information about last usage of the Application
  * for every iPhone Device which is registered to the
  * Push Service.
  * 
  * @return Array Feedback
  */
  public function receive()
  {
    if($this->_checkPrivateKey($this->_privateKey) === false)
      throw new Exception('You need to set the path to the private key file.');

    # Initialize stream context
    $streamContext = stream_context_create();

    # Load private key
    stream_context_set_option($streamContext, 'ssl', 'local_cert', $this->_privateKey);

    # Get private key passphrase if any
    if($this->_privateKeyPassphrase !== '')
      stream_context_set_option($streamContext, 'ssl', 'passphrase', $this->_privateKeyPassphrase);

    # Connect to the Apple Push Notification Service
    $fp = stream_socket_client(
            APFeedback::$_ENVIROMENTS[$this->_enviroment],
            $errorNumber,
            $errorString,
            60,
            STREAM_CLIENT_CONNECT,
            $streamContext);

    # Connection failed?
    if ($errorString)
      throw new Exception('Can\'t connect to Apple Push Feedback Service: '.$errorString);

    while ($data = fread($fp, 38)) {
      $feedback[] = unpack("N1timestamp/n1length/H*devtoken", $data);
    }

    # Return Feedback
    return $feedback;

    # Close Connection
    fclose($fp);

  } // End receive()
} // End APFeedback
?>
