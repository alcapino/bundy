<?php
require_once __DIR__ . '/vendor/autoload.php';
$file = "/home/pv/log/suplog.log";
file_put_contents($file, "start!", FILE_APPEND);

define('APPLICATION_NAME', 'Google Sheets API PHP Quickstart');
define('CREDENTIALS_PATH', '~/.credentials/sheets.googleapis.com-php-quickstart.json');
define('CLIENT_SECRET_PATH', __DIR__ . '/etc/client_secret.json');
// If modifying these scopes, delete your previously saved credentials
// at ~/.credentials/sheets.googleapis.com-php-quickstart.json
define('SCOPES', implode(' ', array(
  Google_Service_Sheets::SPREADSHEETS)
));

if (php_sapi_name() != 'cli') {
  throw new Exception('This application must be run on the command line.');
}

/**
 * Returns an authorized API client.
 * @return Google_Client the authorized client object
 */
function getClient() {
  $client = new Google_Client();
  //$api_key = 'AIzaSyA4ruZs7NjExXXmHxppui48yBt2gNVLbO0';
  //$client->setDeveloperKey($api_key);
  $client->setApplicationName(APPLICATION_NAME);
  $client->setScopes(SCOPES);
  $client->setAuthConfig(CLIENT_SECRET_PATH);
  $client->setAccessType('offline');

  // Load previously authorized credentials from a file.
  $credentialsPath = expandHomeDirectory(CREDENTIALS_PATH);
  if (file_exists($credentialsPath)) {
    $accessToken = json_decode(file_get_contents($credentialsPath), true);
  } else {
    // Request authorization from the user.
    $authUrl = $client->createAuthUrl();
    printf("Open the following link in your browser:\n%s\n", $authUrl);
    print 'Enter verification code: ';
    $authCode = trim(fgets(STDIN));

    // Exchange authorization code for an access token.
    $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

    // Store the credentials to disk.
    if(!file_exists(dirname($credentialsPath))) {
      mkdir(dirname($credentialsPath), 0700, true);
    }
    file_put_contents($credentialsPath, json_encode($accessToken));
    printf("Credentials saved to %s\n", $credentialsPath);
  }
  $client->setAccessToken($accessToken);

  // Refresh the token if it's expired.
  if ($client->isAccessTokenExpired()) {
    $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
    file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
  }
  return $client;
}

/**
 * Expands the home directory alias '~' to the full path.
 * @param string $path the path to expand.
 * @return string the expanded path.
 */
function expandHomeDirectory($path) {
  $homeDirectory = getenv('HOME');
  if (empty($homeDirectory)) {
    $homeDirectory = getenv('HOMEDRIVE') . getenv('HOMEPATH');
  }
  return str_replace('~', realpath($homeDirectory), $path);
}

// Get the API client and construct the service object.
$client = getClient();
$service = new Google_Service_Sheets($client);

// Prints the names and majors of students in a sample spreadsheet:
// https://docs.google.com/spreadsheets/d/1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms/edit
//$spreadsheetId = '1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms';
//echo __DIR__ . '/etc/bundy.conf';die();
$sheetdata = json_decode( file_get_contents(__DIR__ . '/etc/bundy.conf') );
//die();
$spreadsheetId = $sheetdata->spreadsheetID;
$range = $sheetdata->range;
$range_data = $service->spreadsheets_values->get($spreadsheetId, $range);
$values = $range_data->getValues();
$lastrow = $values == "" ? 1 : count($values) + 1;
$rangeOUT = $sheetdata->OUTrange . $lastrow;
//print_r($values != "");
//print_r(isset(end($values)[2]) ? "set" : "unset");

if ( $values == "" || isset(end($values)[2]) ) {   // check if last row has OUT
  echo "in";
    $valueInputOption = "RAW";
    $values = [[ date("D YMd"), date("G:i:s") ]];
    $body = new Google_Service_Sheets_ValueRange([
      'values' => $values
    ]);
    $params = [
      'valueInputOption' => $valueInputOption,
    ];
    $result = $service->spreadsheets_values->append($spreadsheetId, $range, $body, $params);

} else {
  echo "out";
    $valueInputOption = "RAW";
    $values = [[ date("G:i:s") ]];
    $body = new Google_Service_Sheets_ValueRange([
      'values' => $values
    ]);
    $params = [
      'valueInputOption' => $valueInputOption,
    ];
    $result = $service->spreadsheets_values->append($spreadsheetId, $rangeOUT, $body, $params);

}

die();

