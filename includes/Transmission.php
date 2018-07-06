<?php
use GuzzleHttp\Client;
use Martial\Transmission\API\Argument\Torrent\Add;
use Martial\Transmission\API\Argument\Torrent\Get;
use Martial\Transmission\API\CSRFException;
use Martial\Transmission\API\RpcClient;
use Martial\Transmission\API\TorrentIdList;
use Martial\Transmission\API\TransmissionAPI;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

// Just a MartialGeek/transmission-api wrapper
class Transmission
{
	protected $api;
	protected $sessionId;

	const TORRENT_STATUSES =
	[
		'Stopped', 'Check waiting', 'Checking', 'Download waiting', 'Downloading', 'Seed waiting', 'Seeding'
	];

	public function __construct(string $rpcUri, string $rpcUsername = '', string $rpcPassword = '')
	{
		$guzzle    = new Client(['base_uri' => $rpcUri]);
		$this->api = new RpcClient($guzzle, $rpcUsername, $rpcPassword);

		// Fetching a new session ID
		try
		{
		    $this->api->sessionGet($this->sessionId);
		}

		catch(CSRFException $e)
		{
		    $this->sessionId = $e->getSessionId();
		}
	}

	public function torrentGet(array $ids = [ ])
	{
	    return $this->api->torrentGet($this->sessionId, new TorrentIdList($ids),
		[
	        Get::ID,
	        Get::NAME,
	        Get::STATUS,
	        Get::ETA,
	        Get::PERCENT_DONE,
	        Get::RATE_DOWNLOAD,
			Get::SECONDS_DOWNLOADING,
			Get::SECONDS_DOWNLOADING,
			Get::ETA_IDLE,
			Get::IS_STALLED,
			Get::PEERS_CONNECTED,
			Get::PEERS_SENDING_TO_US,
	    ]);
	}

	public function torrentStart(array $ids)
	{
		return $this->api->torrentStart($this->sessionId, new TorrentIdList($ids));
	}

	public function torrentStop(array $ids)
	{
		return $this->api->torrentStop($this->sessionId, new TorrentIdList(
		    $ids
		));
	}

	// Adding a new torrent to the download queue
	public function torrentAdd($newTorrentFile)
	{
		if(substr($_REQUEST['Body'], 0, 7) === 'magnet:')
		{
			return $this->api->torrentAdd($this->sessionId,
			[
				Add::FILENAME => $newTorrentFile
			]);
		}

		// If torrent url, download and base64 encode it
		if(substr($_REQUEST['Body'], 0, 4) === 'http')
		{
			return $this->api->torrentAdd($this->sessionId,
			[
				Add::METAINFO => base64_encode(file_get_contents($newTorrentFile))
			]);
		}

		return false;
	}

	public function getSessionCurrentTorrentId()
	{
		if(empty($_SESSION['CURRENT_TORRENT_ID']) === false && ctype_digit($_SESSION['CURRENT_TORRENT_ID']) === true)
		{
			return (int) $_SESSION['CURRENT_TORRENT_ID'];
		}

		return 0;
	}

	public function free()
	{
		return $this->api->freeSpace($this->sessionId, '/');
	}
}
