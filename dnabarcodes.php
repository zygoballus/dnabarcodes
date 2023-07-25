<?php
// This script is dual licensed under the MIT License and the CC0 License.
error_reporting( E_ALL );
ini_set( 'display_errors', 1 );
ini_set( 'max_execution_time', 900 );

include 'conf.php';

$useragent = 'DNAbarcodes/1.0';
$inatapi = 'https://api.inaturalist.org/v1/';
$errors = [];
$updated = false;

function make_curl_request( $url = null, $token = null, $postData ) {
	global $useragent, $errors;
	$curl = curl_init();
    if ( $curl && $url ) {
    	$curlheaders = array(
    		'Cache-Control: no-cache',
    		'Content-Type: application/json',
    		'Content-Length: ' . strlen( $postData ),
    		'Accept: application/json'
    	);
    	if ( $token ) {
    		$curlheaders[] = "Authorization: Bearer " . $token;
    	}
        curl_setopt( $curl, CURLOPT_URL, $url );
        curl_setopt( $curl, CURLOPT_USERAGENT, $useragent );
        curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $curl, CURLOPT_HTTPHEADER, $curlheaders );
        curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, 'POST' );
		curl_setopt( $curl, CURLOPT_POSTFIELDS, $postData );
        $out = curl_exec( $curl );
		if ( $out ) {
        	$object = json_decode( $out );
        	if ( $object ) {
        		return json_decode( json_encode( $object ), true );
        	} else {
        		$errors[] = 'API request failed. ' . curl_error( $curl );
        		return null;
        	}
        } else {
        	$errors[] = 'API request failed. ' . curl_error( $curl );
        	return null;
        }
    } else {
    	$errors[] = 'Curl initialization failed. ' . curl_error( $curl );
        return null;
    }
}

function iNat_auth_request( $app_id, $app_secret, $username, $password, $url = 'https://www.inaturalist.org/oauth/token' ) {
	global $useragent, $errors;
    $curl = curl_init();
    $payload = array( 'client_id' => $app_id, 'client_secret' => $app_secret, 'grant_type' => "password", 'username' => $username, 'password' => $password );
    if ( $curl ) {
        curl_setopt( $curl, CURLOPT_URL, $url );
        curl_setopt( $curl, CURLOPT_USERAGENT, $useragent );
        curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $curl, CURLOPT_POST, true );
        curl_setopt( $curl, CURLOPT_POSTFIELDS, $payload );
        $out = curl_exec( $curl );
        if ( $out ) {
        	$object = json_decode( $out );
        	if ( $object ) {
        		return json_decode( json_encode( $object ), true );
        	} else {
        		$errors[] = 'API request failed. ' . curl_error( $curl );
        		return null;
        	}
        } else {
        	$errors[] = 'API request failed. ' . curl_error( $curl );
        	return null;
        }
    } else {
    	$errors[] = 'Curl initialization failed. ' . curl_error( $curl );
        return null;
    }
}

// See if form was submitted.
if ( $_POST ) {
	if ( isset( $_POST['observation'] )
		&& is_numeric( $_POST['observation'] )
		&& $_POST['gene']
		&& isset( $_POST['barcode'] )
		&& strlen( $_POST['barcode'] ) > 10
	) {
		$postData['observation_field_value'] = [];
		$postData['observation_field_value']['observation_id'] = intval( $_POST['observation'] );
		$postData['observation_field_value']['value'] = $_POST['barcode'];
		switch ( $_POST['gene'] ) {
			case 'COI':
				$postData['observation_field_value']['observation_field_id'] = 824;
				break;
			case 'ITS':
				$postData['observation_field_value']['observation_field_id'] = 2330;
				break;
			case '18S':
				$postData['observation_field_value']['observation_field_id'] = 14900;
				break;
		}
		$postData = json_encode( $postData );
  		$response = iNat_auth_request( $app_id, $app_secret, $username, $password );
		if ( $response ) {
			$token = $response['access_token'];
			$url = $inatapi . 'observation_field_values';
			$response = make_curl_request( $url, $token, $postData );
			if ( $response ) {
				$updated = true;
			}
		}
	}
}
?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Language" content="en-us">
	<title>iNatDNAbarcode</title>

<style type="text/css">
body {
	font-family: "Trebuchet MS", Verdana, sans-serif;
	color:#777777;
	background: #FFFFFF;
	}
#content {
	margin: 2em;
	}
#success {
	margin: 1em 0;
	font-weight: bold;
	}
#errors {
	margin: 1em 0;
	color: #FF6666;
	font-weight: bold;
	}
</style>
<script src="./jquery.min.js"></script>
</head>
<body>
<div id="content">
<?php
if ( $updated ) {
	print( '<p id="success">Update successful!</p>' );
}
if ( $errors ) {
	print( '<p id="errors">' );
	print( 'Errors:<br/>' );
	foreach ( $errors as $error ) {
		print( $error . '<br/>' );
	}
	print( '</p>' );
}
?>
<form id="barcodeform" action="dnabarcodes.php" method="post">
<p>
	iNaturalist observation: <input type="text" id="observation" name="observation" /><br/>
	Gene region: <select name="gene" id="gene">
		<option value="">&nbsp;</option>
		<option value="COI">COI</option>
		<option value="ITS">ITS</option>
		<option value="18S">18S</option>
	</select><br/>
	DNA barcode:<br/><textarea rows="5" cols="50" name="barcode"></textarea>
</p>
<input class="submitbtn" type="submit" />
</form>
</body>
</html>
