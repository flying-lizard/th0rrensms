<?php
require 'bootstrap.php';

use Martial\Transmission\API\Argument\Torrent\Get;
use Twilio\Twiml;

// All purpose dump var for inspection in the logs
$dump = '';

// Message to be sent back
$message = '';
$transmission = new Transmission($config['transmission']['rpcUrl']);

// Explode, first word is the command
$arguments = explode(' ', substr(trim($_REQUEST['Body']), 0, 1024));

// Make the command and arguments case insensitive.
$arguments[0] = strtolower($arguments[0]);
$arguments[1] = strtolower(@$arguments[1]);

// Check for magnet link
if(substr($_REQUEST['Body'], 0, 7) === 'magnet:' || substr($_REQUEST['Body'], 0, 4) === 'http')
{
	// iza magnet, start
	try
	{
		$torrent = $transmission->torrentAdd($_REQUEST['Body']);
		$message = "#{$torrent['id']} {$torrent['name']} added to the queue";
	}

	catch(Martial\Transmission\API\DuplicateTorrentException $e)
	{
		$message = "Torrent #{$e->getTorrentId()} {$e->getTorrentName()} already queued";
	}
}

else
{
	switch($arguments[0])
	{
		case 'free':
			$df     = floor((int) $transmission->free()['size-bytes'] / 1073741824);
			$message = "{$df}GB free";
		break;

		// Select default torrent
		case 'select':
			if(ctype_digit($arguments[1]) === true)
			{
				$_SESSION['CURRENT_TORRENT_ID'] = $arguments[1];

				$message = "Torrent {$arguments[1]} is now set as default";
			}

			else
			{
				$message = "Provided id is invalid\nUse 'select #'";
			}
		break;

		// List all active torrents
		case 'list':
			$torrents = $transmission->torrentGet();

			foreach($torrents as $torrent)
			{
				// Filter out completed torrents
				if($torrent[Get::STATUS] === 4 || $arguments[1] === 'all')
				{
					$torrentName = str_replace('.', ' ', $torrent[Get::NAME]);
					$message .= "#{$torrent[Get::ID]}: {$torrentName}\n";
				}
			}

			if(empty($message) === true)
			{
				$message = 'No active download';
			}

			else
			{
				$message = "🌊\n" . $message;
			}
		break;

		case 'status':
			$torrentId = $transmission->getSessionCurrentTorrentId();

			// If id passed as argument use it
			if(ctype_digit($arguments[1]) === true)
			{
				$torrentId = (int) $arguments[1];
			}

			else if($torrentId === 0)
			{
				$message = "Use \"select <id>\" to choose default torrent first ";
				break;
			}

			$torrent = $transmission->torrentGet([ $torrentId ]);

			if(empty($torrent) === true)
			{
				$message = "This torrent no longuer exists, use list";
			}

			else
			{
				$torrent  = $torrent[0];
				$peers    = '';
				$status   = Transmission::TORRENT_STATUSES[$torrent[Get::STATUS]];
				$percent  = floor($torrent[Get::PERCENT_DONE] * 100);
				$rateDown = floor($torrent[Get::RATE_DOWNLOAD] / 1000);

				if($torrent[Get::STATUS] === 4)
				{
					$peers = "from {$torrent[Get::PEERS_SENDING_TO_US]} out of {$torrent[Get::PEERS_CONNECTED]} peers";
				}

				$message  = "{$status} {$peers}, {$percent}% completed at {$rateDown}KB/s of \"{$torrent[Get::NAME]}\"";
			}
		break;

		// Can't use start or stop because of spam laws
		case 'play':
			$torrentId = $transmission->getSessionCurrentTorrentId();

			// If id passed as argument use it
			if(ctype_digit($arguments[1]) === true)
			{
				$torrentId = (int) $arguments[1];
			}

			$transmission->torrentStart([ $torrentId ]);

			$pausedTorrent = $transmission->torrentGet([ $torrentId ])[0];
			$message       = "Start #{$pausedTorrent['id']} {$pausedTorrent['name']}";
		break;

		case 'pause':
			$torrentId = $transmission->getSessionCurrentTorrentId();

			// If id passed as argument use it
			if(ctype_digit($arguments[1]) === true)
			{
				$torrentId = (int) $arguments[1];
			}

			$transmission->torrentStop([ $torrentId ]);

			$pausedTorrent = $transmission->torrentGet([ $torrentId ])[0];
			$message       = "Paused #{$pausedTorrent['id']} {$pausedTorrent['name']}";
		break;

		case 'halp':
			$message = "Send a magnet link or torrent url to start it
			select <id>: select id as default torrent
			list [all]: list all active downloads with ids
			status [id]: torrent status, current if id not provided
			play [id]: start torrent, current id if not provided
			pause [id]: pause torrent, current id if not provided
			free: display free GBs
			halp: this halp";
		break;

		// Only start will be seen, they'll get intercepted by the sms provider or something like that
		case 'start':
		case 'stop':
			$message = 'omg i was missing you so much!!';
		break;

		default:
			$message = 'Command not understood, send halp for halp';
	}
}

$response = new Twiml();
$response->message(trim($message));

echo $response;

// Dirt poor logging ;)
$rq    = print_r($_REQUEST, true);
$rs    = print_r($response, true);
$rss   = $response;
$dump  = print_r($dump, true);
$debug = "
================================================================================
DUMP: {$dump}
REQUEST: {$rq}
RESPONSE: {$rs}
RESPONSE STRING: {$rss}

--------------------------------------------------------------------------------
";

file_put_contents('twilio.log', $debug, FILE_APPEND);
