<?php

require_once dirname(__DIR__, 2) . "../classes/autoload.php"; /**this don't work still**/
require_once dirname(__DIR__, 2) . "/lib/xsrf.php";
require_once("/etc/apache2/capstone-mysql/encrypted-config.php");

use Edu\Cnm\DevConnect\Image;

/**
 * API for the Image class
 *
 * @author Devon Beets <dbeetzz@gmail.com> based on code by Derek Mauldin
 **/

// verify the session, start if not active
if(session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}

// prepare an empty reply
$reply = new stdClass();
$reply->status = 200;
$reply->data = null;

try {
	// grab the MySQL connection
	$pdo = connectToEncryptedMySQL("etc/apache2/capstone-mysql/dev-connect.ini");

	// determine which HTTP method was used
	$method = array_key_exists("HTTP_X_HTTP_METHOD", $_SERVER) ? $_SERVER["HTTP_X_HTTP_METHOD"] : $_SERVER["REQUEST_METHOD"];

	// sanitize input
	$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
	$imagePath = filter_input(INPUT_GET, "imagePath", FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);

	// make sure the id is valid for methods that require it
	if(($method === "DELETE" || $method === "PUT") && (empty($id) === true || $id < 0)) {
		throw (new InvalidArgumentException("id cannot be empty or negative", 405));
	}

	// handle GET request, if id is present, return that image
	if($method === "GET") {
		// set XSRF cookie
		setXsrfCookie();

		// get a specific image and update reply
		if(empty($id) === false) {
			$image = Image::getImageByImageId($pdo, $id);
			if($image !== null) {
				$reply->data = $image;
			}
		} elseif(empty($imagePath) === false) {
			$images = Image::getImageByImagePath($pdo, $imagePath);
			if($images !== null) {
				$reply->data = $images;
			}
		}
	} elseif($method === "PUT" || $method === "POST") {

		verifyXsrf();
		$requestContent = file_get_contents("php://input");
		$requestObject = json_decode($requestContent);

		// make sure image content is available
		if(empty($requestObject->imagePath) === true) {
			throw(new \InvalidArgumentException("No content for the image",405));
		}

		// perform the actual put or post
		if($method === "PUT") {

			// retrieve the image to update
			$image = Image::getImageByImageId($pdo, $id);
			if($image === null) {
				throw(new \RuntimeException("Image does not exist", 404));
			}

			// put the image path into the image and update
			$image->setImagePath($requestObject->imagePath);
			$image->update($pdo);

			// update reply
			$reply->message = "Image updated OK";

		} elseif($method === "POST") {

			// create a new image and insert it into the database
			$image = new Image(null, $requestObject->imagePath, $requestObject->imageType);
			$image->insert($pdo);

			// update reply
			$reply->message = "Image created OK";
		}
	} elseif($method === "DELETE") {
		verifyXsrf();

		// retrieve the image to be deleted
		$image = Image::getImageByImageId($pdo, $id);
		if($image === null) {
			throw(new \RuntimeException("Image does not exist", 404));
		}

		// delete the image
		$image->delete($pdo);

		//update reply
		$reply->message = "Image deleted OK";
	} else {
		throw (new \InvalidArgumentException("Invalid HTTP method request"));
	}

	// update reply with exception information
} catch(Exception $exception) {
	$reply->status = $exception->getCode();
	$reply->message = $exception->getMessage();
	$reply->trace = $exception->getTraceAsString();
} catch (TypeError $typeError) {
	$reply->status = $typeError->getCode();
	$reply->message = $exception->getMessage();
}

header("Content type: application/json");
if($reply->data === null) {
	unset($reply->data);
}

// encode and return the reply to the front end caller
echo json_encode($reply);