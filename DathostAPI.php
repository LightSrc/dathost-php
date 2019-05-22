<?php

class DathostAPI {
    private $username;
    private $password;
    public $errors = array();
    public $warnings = array();
    public $status_code = 0;
    public $verify_ssl = true;
    public $result = false;
    public $apiURL = 'https://dathost.net/api/0.1/';

    public function __construct($email='', $password='') {
        $this->username = $email;
        $this->password = $password;
    }

    public function makeCall($path, $params=[], $method, $curlheaders=[]) {

    // Clear the public vars
    $this->errors = [];
    $this->status_code = 0;
    $this->result = false;

    $call_url = $path;
    $curl_handle=curl_init();
    // Common settings
    curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl_handle, CURLOPT_USERPWD, $this->username . ":" . $this->password);

    if (!$this->verify_ssl) {
      // WARNING: this would prevent curl from detecting a 'man in the middle' attack
      curl_setopt ($curl_handle, CURLOPT_SSL_VERIFYHOST, 0);
      curl_setopt ($curl_handle, CURLOPT_SSL_VERIFYPEER, 0);
    }

    // Determine REST verb and set up params
    switch( strtolower($method) ) {
      case "post":
        $fields = http_build_query($params);
        if(empty($curlheaders)) {
            $curlheaders[] = 'Content-Length: ' . strlen($fields);
        }
        curl_setopt($curl_handle, CURLOPT_POST, 1);
        curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $fields);
        break;

      case 'put':
        $fields = http_build_query($params, '', '&');
        if(empty($curlheaders)) {
            $curlheaders[] = 'Content-Length: ' . strlen($fields);
        }
        curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $fields);
        break;

      case 'delete':
        $fields = http_build_query($params, '', '&');
        if(empty($curlheaders)) {
            $curlheaders[] = 'Content-Length: ' . strlen($fields);
        }
        curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, "DELETE");
        break;

      case "get":
      default:
        $call_url .= "?".http_build_query($params, "", "&");
    }
    curl_setopt($curl_handle, CURLOPT_HTTPHEADER, $curlheaders);
    curl_setopt($curl_handle, CURLOPT_URL, $call_url);

    $curl_result = curl_exec($curl_handle);
    $info = curl_getinfo($curl_handle);
    $this->status_code = (int) $info['http_code'];
    $return = false;
    if ($curl_result === false) {
      // CURL Failed
      $this->errors[] = curl_error($curl_handle);
    } else {
      switch ($this->status_code) {

        case 400: // Validation errors
          $return = $this->result = $curl_result;
          break;
        case 405: // Validation errors
          $return = $this->result = $curl_result;
          break;
        case 404: // Not found/Not in scope of account
          $return = $this->result = $curl_result;
          if(!empty($return->error)) {
            foreach($return->error as $error) {
              $this->errors[] = $error;
            }
          }
          $return = false;
          break;

        case 500: // Oh snap!
          $return = $this->result = false;
          $this->errors[] = "Server returned HTTP 500";
          break;

        case 200:
          $return = $this->result = $curl_result;
          // Check if the result set is nil/empty
          if (empty($return)) {
            $this->errors[] = "Result set empty";
            $return = false;
          }
          break;

        default:
          $this->errors[] = "Server returned unexpected HTTP Code ($this->status_code)";
          $return = false;
      }
    }

    curl_close($curl_handle);
    return $return;
    //return array($return, $this->errors, $call_url); // for debugging
    }

    public function getAccountInfo($params=[]) {
      return $this->makeCall($this->apiURL . 'account', $params, 'get');
    }

    public function getGameServers($params=[]) {
      return $this->makeCall($this->apiURL . 'game-servers', $params, 'get');
    }

    public function createGameServer($params=[]) {
      return $this->makeCall($this->apiURL . 'game-servers', $params, 'post');
    }

    public function deleteGameServer($serverID) {
      return $this->makeCall($this->apiURL . 'game-servers/' . $serverID, [], 'delete');
    }

    public function getGameServerInfo($serverID) {
      return $this->makeCall($this->apiURL . 'game-servers/' . $serverID, [], 'get');
    }

    public function updateGameServerInfo($serverID, $params=[]) {
        return $this->makeCall($this->apiURL . 'game-servers/' . $serverID, $params, 'put');
    }

    public function getGameServerConsoleLogs($serverID, $params=[]) {
        return $this->makeCall($this->apiURL . 'game-servers/' . $serverID . '/console', $params, 'get');
    }

    public function sendTextToGameServerConsole($serverID, $params=[]) {
        return $this->makeCall($this->apiURL . 'game-servers/' . $serverID . '/console', $params, 'post');
    }

    public function duplicateGameServer($serverID) {
        return $this->makeCall($this->apiURL . 'game-servers/' . $serverID . '/duplicate', [], 'post');
    }

    public function listFilesOnGameServer($serverID, $params=[]) {
        return $this->makeCall($this->apiURL . 'game-servers/' . $serverID . '/files', $params, 'get');
    }

    public function deleteFileOrPathFromGameServer($serverID, $path) {
        return $this->makeCall($this->apiURL . 'game-servers/' . $serverID . '/' . $path, [], 'delete');
    }

    public function downloadFileFromGameServer($serverID, $path, $params=[]) {
        return $this->makeCall($this->apiURL . 'game-servers/' . $serverID . '/files/' . $path, $params, 'get');
    }

    public function uploadFileToGameServer($serverID, $path, $params=[]) {
        return $this->makeCall($this->apiURL . 'game-servers/' . $serverID. '/files/' . $path, $params, 'post');
    }

    public function moveFileInGameServer($serverID, $path, $params=[]) {
        return $this->makeCall($this->apiURL . 'game-servers/' . $serverID . '/files/' . $path, $params, 'put');
    }

    public function getGameServerMetrics($serverID) {
        return $this->makeCall($this->apiURL . 'game-servers/' . $serverID . '/metrics', [], 'get');
    }

    public function regenerateGameServerPasswordFTP($serverID) {
        return $this->makeCall($this->apiURL. 'game-servers/' . $serverID . '/regenerate-ftp-password', [], 'post');
    }

    public function startGameServer($serverID, $params=[]) {
        return $this->makeCall($this->apiURL. 'game-servers/' . $serverID . '/start', $params, 'post');
    }

    public function stopGameServer($serverID, $params=[]) {
        return $this->makeCall($this->apiURL. 'game-servers/' . $serverID . '/stop', $params, 'post');
    }

    public function syncFilesBetweenLocalCacheAndGameServer($serverID) {
        return $this->makeCall($this->apiURL . 'game-servers/' . $serverID . '/sync-files', [], 'post');
    }

    public function unzipFileOnGameServer($serverID, $path, $params=[]) {
        return $this->makeCall($this->apiURL . 'game-servers/' . $serverID . '/unzip/' . $path, $params, 'post');
    }
}
