<?php
/**
 * Update ngrok SMS webhook if it changed.
 *
 *
 */

require 'bootstrap.php';

$lastNgrokUrl = '';
$twillioSubmitedBaseNgrokUrlLockFile = __DIR__ . '/' . $config['twillioSubmitedBaseNgrokUrlLockFile'];

// Get current ngrok url
$json = @file_get_contents($config['ngrok']['listTunnelsApiEndpoint']);

// Check if there's something, ngrok is probably not running
if(empty($json))
{
	echo "error: ngrok api not reachable at {$config['ngrok']['listTunnelsApiEndpoint']}, is it even running?\n";
	exit(2);
}

$tunnels  = json_decode($json);
$ngrokUrl = findTunnel($tunnels);

if(is_readable($twillioSubmitedBaseNgrokUrlLockFile))
{
	// Get last set ngrok url
	$lastNgrokUrl = file_get_contents($twillioSubmitedBaseNgrokUrlLockFile);
}

// Update Twilio webhook if ngrok url changed
if(empty($ngrokUrl) === false && $lastNgrokUrl !== $ngrokUrl)
{
	$twilio     = new Twilio\Rest\Client($config['twillio']['accountSid'], $config['twillio']['authToken']);
	$webhookUrl = "{$ngrokUrl}/{$config['basePath']}/reply.php";

	// Send new webhook over to Twilio
	$incoming_phone_number = $twilio->incomingPhoneNumbers($config['twillio']['phoneNumberSid'])
		->update([
			'accountSid' => $config['twillio']['accountSid'],
			'smsUrl'     => $webhookUrl
		]);

	// Update last base ngrok url
	file_put_contents($twillioSubmitedBaseNgrokUrlLockFile, $ngrokUrl);

	echo "updated sms webook: {$webhookUrl}\n";
}

else
{
	echo "not updating sms webhook because it did not changed\n";
}

exit(0);

/**
 * Find HTTPS tunnel, fallback to HTTP. Takes ngrok /api/tunnels endpoint result.
 *
 */
function findTunnel(stdClass $tunnels)
{
	// Default to whatever is the first
	$url = $tunnels->tunnels[0]->public_url;

	// Search for HTTPS tunnels
	foreach($tunnels->tunnels as $tunnel)
	{
		if($tunnel->proto === 'https')
		{
			$url = $tunnel->public_url;

			break;
		}
	}

	return $url;
}
