<?php

namespace Archibald;

use GuzzleHttp\Client;

class Request
{
	private $body;
	private $channel;
	private $user;

	private $requestGifs = 'http://replygif.net/api/gifs';
	private $requestTags = 'http://replygif.net/api/tags';
	private $apiKey = '39YAprx5Yi';

	private $client;

	public function __construct($request)
	{
		$this->body = $request['body'];
		$this->channel = $request['channel'];
		$this->user = $request['user'];

		$this->client = new Client();

		$this->parseRequestType();
	}

	public function parseRequestType()
	{
		switch ($this->body) {
			case 'shaq':
				$this->body = 'I love you!';
				$shaq = $this->staticRequest('shaq');
				break;

			case 'kannste':
			case 'kannsteschonsomachen':
			case 'kannstemachen':
			case 'kacke':
				$kannste = $this->staticRequest('kannste');
				break;

			case 'tags':
				$tags = $this->searchTags($this->body);
                $tagList = $this->getTagList($tags);
				break;

            case 'random';
                $tags = $this->searchRandomGif();
                break;

			case '';
				echo 'Please provide a tag! e.g. `/archie wow`';
				break;

			default:
				$search = $this->searchGif($this->body);
				break;
		}
	}

	private function staticRequest($request)
	{
		$responseBody = '';

		switch ($request) {
			case 'shaq':
				$responseBody = 'http://replygif.net/i/1106.gif';
				break;
			case 'kannste':
				$responseBody = 'http://i.imgur.com/D6iqV0b.png';
				break;
		}

		$this->postResponse($responseBody);
	}

    public function searchRandomGif()
    {
        $tags = $this->searchTags('');
        $randomTag = $tags[array_rand($tags)];

        return $this->searchGif($randomTag->title);
    }

	public function searchGif($requestString)
	{
		try {
			$response = $this->client->get(
				$this->requestGifs, [
					'query' => [
						'api-key' => $this->apiKey,
						'tag' => $requestString
					]
				]
			);
		}
		catch (RequestException $e) {
			echo $e->getRequest();
			if ($e->hasResponse()) {
				$this->postResponse($e->getResponse());
			}
		}
		$responseBody = $response->getBody();
		$message = $this->randomGif($responseBody);

		if (false !== $message) {
		  $this->postResponse($message);
		}
		else {
			echo 'No GIFs found with tag *' . $this->body . '*';
		}
	}

	public function searchTags($requestString)
	{
		try {
			$response = $this->client->get(
				$this->requestTags, [
					'query' => [
						'api-key' => $this->apiKey,
						'reaction' => 1
					]
				]
			);
		}
		catch (RequestException $e) {
			echo $e->getRequest();
			if ($e->hasResponse()) {
				$this->postResponse($e->getResponse());
			}
		}

		$responseBody = $response->getBody();

        return json_decode($responseBody);
	}

	public function randomGif($responseBody)
	{
		$gifs = json_decode($responseBody);

		$size = count($gifs);
		$randomIndex = rand(0, $size-1);

		if ($size < 1) {
			return false;
		}
		return $gifs[$randomIndex]->file;
	}

	public function getTagList($tags)
	{
		$tagList = '';

		foreach ($tags as $tag) {
			$tagList .= $tag->title . " (" . $tag->count . ")\t";
		}

		/**
		 * The Tag List is echoed by slackbot,
		 * so other don’t see it
		 */
		echo $tagList;
	}

	public function postResponse($message)
	{
		$finalMessage = $this->user . ": <" . $message . "|" . $this->body . ">";
		$channel = $this->channel;

		$data = array(
			'payload' => json_encode(array(
				'username' => 'Archibald',
				'icon_emoji' => ':hatched_chick:',
				'channel' => $channel,
				'text' => $finalMessage
			))
		);

		$request = $this->client->post(WEBHOOK_URL, array(
			'body' => $data
		));
	}
}
