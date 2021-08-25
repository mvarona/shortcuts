<?php
declare(strict_types=1);
require("./vendor/autoload.php");
include("./src/DeepLTranslator.php");
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

final class DeepLTranslatorTest extends TestCase
{

	protected $client;

	protected function setUp(): void
	{
		$this->client = new GuzzleHttp\Client([
			'base_uri' => 'http://localhost/xampp%20PHP/src/'
		]);
	}

	public function testReturn400WhenBadRequest(): void
	{
		try {
			$response = $this->client->post('DeepLTranslator.php', [
				'form_params' => [
					'invalidParamName1' => "A",
					'invalidParamName2' => "B"
				]
			]);

		} catch (RequestException $e) {
			if ($e->hasResponse()){
				$this->assertEquals(400, $e->getResponse()->getStatusCode());
			}   
		}
	}

	public function testReturnCorrectTranslation(): void
	{
		try {
			$response = $this->client->post('DeepLTranslator.php', [
				'form_params' => [
					'text' => "Hi",
					'target_language' => "ES"
				]
			]);
			
			$result = json_decode($response->getBody()->getContents(), true);
			$metadata = array();
			$metadata["detected_language"] = "EN";
			$expectedResponse = array();
			$expectedResponse["translation"] = "Hola";
			$expectedResponse["metadata"] = $metadata;
			$expectedResponse["error"] = "";
			
			$this->assertSame($result, $expectedResponse);

		} catch (RequestException $e) {
			$this->assertTrue(false);
		}
	}

}