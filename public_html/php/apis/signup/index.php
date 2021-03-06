<?php

require_once dirname(__DIR__, 2) . "/classes/autoload.php";
require_once dirname(__DIR__, 2) . "/lib/xsrf.php";
require_once dirname(__DIR__, 2) . "/lib/mail.php";
require_once("/etc/apache2/capstone-mysql/encrypted-config.php");

use Edu\Cnm\DevConnect\Profile;

/**
 * API for the sign up process
 *
 * @author Devon Beets <dbeetzz@gmail.com>
 **/

//verify the session, start if not active
if(session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}

//prepare the default message
$reply = new stdClass();
$reply->status = 200;
$reply->data = null;
try {
	//grab the MySQL connection
	$pdo = connectToEncryptedMySQL("/etc/apache2/capstone-mysql/devconnect.ini");

	//determine which HTTP method was used
	$method = array_key_exists("HTTP_X_HTTP_METHOD", $_SERVER) ? $_SERVER["HTTP_X_HTTP_METHOD"] : $_SERVER["REQUEST_METHOD"];

	//perform the actual post
	if($method === "POST") {

		verifyXsrf();
		$requestContent = file_get_contents("php://input");
		$requestObject = json_decode($requestContent);

		//sanitize and trim the inputs
		$profileName = filter_input(INPUT_POST, "profileName", FILTER_SANITIZE_STRING);
		$profileAccountType = filter_input(INPUT_POST, "profileAccountType", FILTER_SANITIZE_STRING);
		$profileEmail = filter_input(INPUT_POST, "profileEmail", FILTER_SANITIZE_STRING);
		$password = filter_input(INPUT_POST, "password", FILTER_SANITIZE_STRING);
		$confirmPassword = filter_input(INPUT_POST, "confirmPassword", FILTER_SANITIZE_STRING);

		//check that the user fields that are required have been sent and filled out correctly
		if(empty($requestObject->profileName) === true) {
			throw(new \InvalidArgumentException("Please fill in a name."));
		} elseif(empty($requestObject->profileAccountType) === true) {
			throw(new \InvalidArgumentException("Please select an account type."));
		} elseif(empty($requestObject->profileEmail) === true) {
			throw(new \InvalidArgumentException("Please fill in an email."));
		} elseif(empty($requestObject->password) === true) {
			throw(new \InvalidArgumentException("Please fill in a password."));
		} elseif(empty($requestObject->confirmPassword) === true) {
			throw(new \InvalidArgumentException("Please confirm the password"));
		} elseif($requestObject->password !== $requestObject->confirmPassword) {
			throw(new \InvalidArgumentException("Password does not match"));
		}


		if($requestObject->profileAccountType === "O") {
			$emailContent = "<p>Thank you for signing up with DevConnect! We will be reviewing your request pending approval and email activation.</p>" . PHP_EOL;
		} elseif($requestObject->profileAccountType === "D") {
			$emailContent = "<p>Thank you for signing up with DevConnect! Please click the link to activate your account, thank you!</p>" . PHP_EOL;
		}

		$profileApproved = true;
		if($requestObject->profileAccountType === "O") {
			$profileApproved = false;
		}
		$profileApprovedById = null;
		$profileApprovedDateTime = null;


		//create a new user, password salt and hash, and activation token
		$profileActivationToken = bin2hex(random_bytes(16));
		$salt = bin2hex(random_bytes(32));
		$hash = hash_pbkdf2("sha512", $requestObject->password, $salt, 262144);


		//create a new profile for the user
		$profile = new Profile(null, $requestObject->profileAccountType, $profileActivationToken, $profileApproved, $profileApprovedById, $profileApprovedDateTime, "I'm too lazy to create a profile! XD", $requestObject->profileEmail, null, $hash, "I don't know where I am!", $requestObject->profileName, $salt);
		$profile->insert($pdo);

		// grab profile from database and put into a session
		$profile = Profile::getProfileByProfileId($pdo, $profile->getProfileId());
		$_SESSION["profile"] = $profile;

		//building the activation link that can travel to another server and still work. This is the link that will be clicked to confirm the account.
		// FIXME: make sure URL is /public_html/activation/$activation
		$basePath = dirname($_SERVER["SCRIPT_NAME"], 4);
		$urlglue = $basePath . "/activation/$profileActivationToken";
		$confirmLink = "https://" . $_SERVER["SERVER_NAME"] . $urlglue;
		$message = $emailContent . "<p>Y U NO CLICK LINK: <a href=\"$confirmLink\">$confirmLink<a></p>";

		$response = mailGunner("DevConnect", "gsandoval49@cnm.edu", $requestObject->profileName,
			$requestObject->profileEmail, "Thank you for joining DevConnect! :)", $message);
		$reply->message = "Sign up was successful. Please check your email for account activation information.";
	} else {
		throw(new \InvalidArgumentException("Invalid HTTP request."));
	}

} catch(\Exception $exception) {
	$reply->status = $exception->getCode();
	$reply->message = $exception->getMessage();
} catch(\TypeError $typeError) {
	$reply->status = $typeError->getCode();
	$reply->message = $typeError->getMessage();
}
header("Content-type: application/json");
echo json_encode($reply);