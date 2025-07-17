<?php

function search($term, $limit=null) {
  global $collection;
  $max = 100;
  if (strlen($limit)) {
    $max = 200;
  }
  $url = '/public/v1/1033/100/1/search/bibs/keyword/kw?bibsperpage=' . $max . '&q=';
  $method = 'GET';
  $query = rawurlencode($term);
  if (strlen($limit) > 0) {
    $query = $query . '&limit=' . $limit;
  } else {
    $query = $query . '&limit=' . rawurlencode($collection);
  }
  $result = requestPolaris($url, $method, $query);
  return $result;
}

function getInfo($info) {
  $url = '/public/v1/1033/100/1/' . $info;
  $method = 'GET';
  $result = requestPolaris($url, $method);
  return $result;
}

function authenticateStaff($domain, $user, $pass) {
  $url = '/protected/v1/1033/100/1/authenticator/staff';
  $method = 'POST';
  $body = json_encode(array( 'Domain' => $domain, 'Username' => $user, 'Password' => $pass ));
  $result = requestPolaris($url, $method, $body);
  return $result;
}

function requestPolaris($url, $method, $request=null, $headers=[], $secret=null) {
  global $base, $apiid, $apikey;
  $api = $base;
  $accessID = $apiid;
  $accessKey = $apikey;
  $url = $api . $url;

  if ($method == 'GET') {
    $url = $url . $request;
  }

  $date = gmdate('r');
  $concat = $method . $url . $date . $secret;
  $signature = base64_encode(hash_hmac('sha1', $concat, $accessKey, true));

  array_push($headers, "PolarisDate: " . $date);
  array_push($headers, "Authorization: PWS " . $accessID . ":" . $signature);
  array_push($headers, "Accept: application/json");

  $result = null;
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
  curl_setopt($curl, CURLOPT_TIMEOUT, 15);
  curl_setopt($curl, CURLOPT_URL, $url);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

  if ($method == 'POST') {
    array_push($headers, "Content-Type: application/json","Content-Length: " . strlen($request));
    curl_setopt($curl, CURLOPT_POST, TRUE);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
  }

  curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

  $result = curl_exec($curl);
  $code = curl_getinfo($curl , CURLINFO_RESPONSE_CODE);
  curl_close($curl);
  if ($code != 200) {
    $result = '{"PAPIErrorCode":' . $code . '}';
  }
  return json_decode($result);
}

function placeHold($item,$card) {
  global $domain, $staffuser, $staffpass, $orgid, $workstationid, $userid;

  $data = authenticateStaff($domain, $staffuser, $staffpass);

  if (!isset($data->AccessToken) || !isset($data->AccessSecret)) {
     return "error2";
  }

  $token = $data->AccessToken;
  $secret = $data->AccessSecret;

  $url = '/public/v1/1033/100/1/patron/';

  $headers = array();
  $headers[] = 'X-PAPI-AccessToken: ' . $token;

  $data = requestPolaris($url, 'GET', $card, $headers, $secret);

  if (!isset($data->PatronID)) {
    return "error3";
  }

  $id = $data->PatronID;

  $url = '/public/v1/1033/100/1/holdrequest';

  $body = json_encode(array( 'PatronID' => $id, 'BibID' => $item, 'PickupOrgID' => $orgid, 'WorkstationID' => $workstationid, 'UserID' => userid, 'RequestingOrgID' => $orgid ));

  $data = requestPolaris($url, 'POST', $body);

  if (isset($data->StatusType) && $data->StatusType == 1 && $data->StatusValue == 6) {
    return "Error: hold already exists";
  }

  if (isset($data->StatusType) && $data->StatusType == 2 && $data->StatusValue) {
    return $data->StatusValue;
  }
  if (isset($data->StatusType) && $data->StatusType == 5) {
    return "error5";
  }
  return "error4";
}

?>