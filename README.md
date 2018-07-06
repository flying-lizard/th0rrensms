th0rrensms

Manage your Transmission torrents... BY SMS!

You can start torrents by pasting the magnet link or torrent url and it'll start automatically.

Get status on the speed and peers.

Even gives you how much free space left you have!!

Install
- Install Transmission with a reachable rpc url
- Put th0rrensms somewhere in a publicly reachable webserver configured with PHP
- run composer update in the th0rrensms directory
- Copy config.json-sample to config.json and setup Twilio account information and Transmission rpc url
- Go in your Twillio account, under Phone Numbers / Manage Numbers / Active Numbers / Your phone number and set the publicly reachable url to reply.php as a messaging webhook.

If you can't afford the luxury of Twillio contacting your home server directly
you can use ngrok to tunnel the traffic. Just install it and use the ngrok
endpoint with the proper path in the messaging webhook.
