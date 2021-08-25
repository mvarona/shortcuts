<?php
declare(strict_types=1);
require("DotEnv.php");
error_reporting(E_ERROR | E_PARSE);
define("HTTP_CODE_BAD_REQUEST", 400);
use DevCoder\DotEnv;

final class TranslateResponse implements JsonSerializable
{
	private $translation;
	private $metadata;
	private $error;

	public function __construct(string $translation, array $metadata, string $error)
	{
		$this->translation = $translation;
		$this->metadata = $metadata;
		$this->error = $error;
	}

	public function jsonSerialize()
    {
    	$result = array();
    	$result["translation"] = $this->translation;
    	$result["metadata"] = $this->metadata;
    	$result["error"] = $this->error;
        return $result;
    }
}

interface Translator 
{
	public function __construct(string $text, string $target_language);
	public function translate(): TranslateResponse;
}

final class DeepLTranslator implements Translator
{
	private $text;
	private $target_language;
	private $translation;
	private $metadata;

	public function __construct(string $text, string $target_language)
	{
		$this->text = $text;
		$this->target_language = $target_language;
	}

	public function translate(): TranslateResponse
	{
		$url = "https://api-free.deepl.com/v2/translate";
		$ch = curl_init($url);

		$data = array(
			'auth_key' => getenv('DEEPL_AUTH_KEY'),
			'text' => $this->text,
			'target_lang' => $this->target_language
		);
		$data_string = http_build_query($data);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/x-www-form-urlencoded'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($ch);
		if (curl_errno($ch)) {
		    echo 'Error:' . curl_error($ch);
		}
		curl_close($ch);

		if (!$result){
			$error = new TranslateResponse("", array(), "Error: Connection failed");
			return $error;
		}

		$translatedWords = json_decode($result, true);
		$translatedText = $translatedWords['translations'][0]['text'];
		$detectedLanguage = $translatedWords['translations'][0]['detected_source_language']; 

		$response = new TranslateResponse($translatedText, 
			array("detected_language" => $detectedLanguage),
			"");
		return $response;
	}
}

function checkArguments()
{
	if (isset($_POST['text']) && isset($_POST['target_language'])){
		return true;
	}
	return false;
}

function returnJSONResponse($response)
{
	header('Content-Type: application/json');
	echo($response);
}

function main()
{
	(new DotEnv(__DIR__ . '/../.env'))->load();

	if (checkArguments()){
		$deepLTranslator = new DeepLTranslator(
			$_POST['text'],
			$_POST['target_language']);
		$result = json_encode($deepLTranslator->translate());
		returnJSONResponse($result);
	} else {
		http_response_code(HTTP_CODE_BAD_REQUEST);
	}
}

main();

?>