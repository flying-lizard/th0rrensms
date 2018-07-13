<?php
session_start();
require_once "vendor/autoload.php";

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Setup config
$configFile = __DIR__ . '/config.json';

if(is_readable($configFile) === false)
{
	echo "config.json not defined\n";
	exit(1);
}

$config =
[
	'transmission' =>
	[
		'rpcUrl' => 'http://localhost:9091/transmission/rpc'
	],

	'ngrok' =>
	[
		'listTunnelsApiEndpoint' => 'http://localhost:4040/api/tunnels'
	],

	'twillioSubmitedBaseNgrokUrlLockFile' => 'lastNgrokUrl'
];

$config = array_merge($config, json_decode(file_get_contents($configFile), true));

// Enforce whitelist when a request is being made.
if(isset($_REQUEST['From']) === true && empty($config['whitelistedNumbers']) === false && in_array($_REQUEST['From'], $config['whitelistedNumbers']) === false)
{
	echo "You are not authorized";
	exit(1);
}


spl_autoload_register(function($class)
{
	$include = __DIR__ . '/includes/' .  str_replace("\\", "/", $class) . '.php';

	return include $include;
});
