<?php
/**
 * APNotification
 * Allows to send a Push Notification to
 * a specific iPhone
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
 * @since   2010/01/14
 * @package APNP
 */

class APNotification extends APNSBase{

  /**
   * Sets the URLs for the two different
   * run enviroments development and production
   *
   * @var array
   */
  protected static $_ENVIROMENTS = array(
                   'development' => 'ssl://gateway.sandbox.push.apple.com:2195',
                   'production' => 'ssl://gateway.push.apple.com:2195');

  /**
   * Unique iPhone Device Token
   * identifies a specific iPhone
   *
   * @var string
   */
  protected $_deviceToken;

  /**
   * message to be send to the iPhone
   *
   * @var string
   */
  protected $_message = '';

  /**
   * Number, that should be displayed
   * in the badge of the receiving app
   * on Push Notification
   * leave blank for no badge
   *
   * @var int
   */
  protected $_badge;

  /**
   * Sound to be played on receival of Push Notification
   * leave blank for standard sound
   *
   * @var string
   */
  protected $_sound;

  /**
   * Stores the properties, that should be send to the
   * remote iPhone Application
   */
  protected $_properties = array();
  
  /**
   * Initializes a Push Notification with an
   * enviroment.
   * Valid values: development, production
   *
   * @param string $environment (production or development)
   */
  public function  __construct($environment = "development") {
    parent::__construct($environment);
  }

  /*
   * Getter and Setter
   */

  /**
   * Set deviceToken
   *
   * @param string $deviceToken
   * @return void
   */
  public function setDeviceToken($deviceToken)
  {
    $this->_deviceToken = $deviceToken;
  }

  /**
   * Sets Push Notification message (mandatory)
   *
   * @param string $message
   */
  public function setMessage($message)
  {
    # Doesn't allow empty messages
    if($this->_checkMessageValue($message) === false)
      throw new Exception('You need to add a message for your Push Notification.');

    $this->_message = $message;

  }

  /**
   * Sets Push Notification sound (obligative)
   *
   * @param string $sound
   */
  public function setSound($sound)
  {
    $this->_sound = $sound;
  }

  /**
   * Sets number to be displayed in the badge
   * on the app icon.
   *
   * @param int $badge
   */
  public function setBadge($badge)
  {
    # Only accepts integer values
    if(is_int($badge) === false)
      throw new Exception('The badge has to be an integer value.');

    $this->_badge = $badge;
  }

  /**
   * Adds a Property which should be send to the
   * remote iPhone Application
   *
   * @param string $key name of the property
   * @param array/string $value can be an array or string
   */
  public function addProperty($key, $value)
  {
    # is the key empty or a space?
    if(trim($key) === "" || $key === "aps")
      throw new Exception("The key of your property needs a valid name, can't be a space or empty or named aps");
    # is there already a property named $key
    if(isset($this->_properties[$key]) === true)
      throw new Exception("You already added a property with the name: ".$key);

    # add property to property array
    $this->_properties[$key] = $value;
  }

  /**
   * Checks wether the value of the message
   * is valid
   *
   * @param string $message
   * @return bool
   * @todo check wether there are any apple restrictions for the message
   */
  protected function _checkMessageValue($message)
  {
    if(trim($message) !== '')
      return true;
    else
      return false;
  }

  /**
   * Returns the properties
   * @return array $this->_properties
   */
  private function __getProperties()
  {
    return $this->_properties;
  }

  /**
   * Creates the Payload Body for the APNS
   *
   * @return string JSON Codierter Push Notification body
   */
  private function __getNotificationPayload()
  {
    # Add message to the payload
    if($this->_checkMessageValue($this->_message) === false)
      throw new Exception('You need to add a message for your Push Notification.');

    $notificationBody['aps'] = array('alert' => $this->_message);

    # Add badge if set
    if($this->_badge !== NULL && is_int($this->_badge))
      $notificationBody['aps']['badge'] = $this->_badge;

    # Add sound if set
    if($this->_sound !== NULL && is_string($this->_sound))
      $notificationBody['aps']['sound'] = $this->_sound;

    # Merges the Properties and the aps dictonary
    if($this->_properties !== NULL && is_array($this->_properties))
      $notificationBody = array_merge($notificationBody, $this->__getProperties());

    return json_encode($notificationBody);
  }

  /**
   * Constructs the Push Notification cf. Apple specification
   *
   * @return byte string
   */
  private function __getNotification() {
    $notification =
      chr(0) .
      pack("n",32) . pack('H*', str_replace(' ', '', $this->_deviceToken)) .
      pack("n",strlen($this->__getNotificationPayload())) . $this->__getNotificationPayload();

    return $notification;
  }

  /**
   * DEBUG Output;
   */
  public function debug()
  {
    echo $this->__getNotificationPayload();
  }

  /**
   * @todo Implement Method, that saves to Database
   */
  public function save()
  {

  }


  /**
   * Sends the Push Notification via Socket Connection
   * to APNS.
   *
   * @return void
   */
  public function send()
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
            APNotification::$_ENVIROMENTS[$this->_enviroment],
            $errorNumber,
            $errorString,
            60,
            STREAM_CLIENT_CONNECT,
            $streamContext);

    # Connection failed?
    if ($errorString)
      throw new Exception('Can\'t connect to Apple Push Notification Service: '.$errorString);
    
    //echo $this->__getNotification();

    # Send Push Notification
    fwrite($fp, $this->__getNotification());

    # Close Connection
    fclose($fp);

  } // End send()

} // End APNotification

?>