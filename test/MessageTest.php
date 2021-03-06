<?php
namespace Edu\Cnm\DevConnect\Test;

use Edu\Cnm\DevConnect\{Message, Profile};

// grab the project test parameters
require_once("DevConnectTest.php");

// grab the class under scrutiny
require_once(dirname(__DIR__) . "/public_html/php/classes/autoload.php");

/**
 * Full PHPUnit test for the Message class
 *
 * This is a complete PHPUnit test for the Message class. It is complete because *ALL* MySQL and PDO enabled methods
 * are tested for both valid and invalid inputs.
 *
 * @see Message
 * @author Devon Beets <dbeetzz@gmail.com>
 **/

class MessageTest extends DevConnectTest {
	/**
	 * content of the Message
	 * @var string $VALID_MESSAGECONTENT
	 **/
	protected $VALID_MESSAGECONTENT = "PHPUnit message content test great success";
	/**
	 * content of the updated Message
	 * @var string $VALID_MESSAGECONTENT2
	 **/
	protected $VALID_MESSAGECONTENT2 = "PHPUnit message content test still great success";
	/**
	 * timestamp of the Message; starts as null and is assigned later
	 * @var \DateTime $VALID_MESSAGEDATE
	 **/
	protected $VALID_MESSAGEDATE = null;
	/**
	 * mailgun id of the Message
	 * @var string $VALID_MAILGUNID
	 **/
	protected $VALID_MAILGUNID = "1337";
	/**
	 * mailgun id of the updated Message
	 * @var string $VALID_MAILGUNID2
	 **/
	protected $VALID_MAILGUNID2 = "5W4G";
	/**
	 * content of the Message subject
	 * @var string $VALID_MESSAGESUBJECT
	 **/
	protected $VALID_MESSAGESUBJECT = "PHPUnit message subject test great success";
	/**
	 * Profile that created the Message, this is for foreign key relations
	 * @var Profile messageSentProfileId
	 **/
	protected $messageSentProfileId = null;

	/**
	 * Profile that received the Message, this is for foreign key relations
	 * @var Profile messageReceiveProfileId
	 **/
	protected $messageReceiveProfileId = null;

	/**
	 * create dependent objects first before running each test
	 **/
	public final function setUp() {
		//run the default setUp() method first
		parent::setUp();

		//create and insert a Profile to send the test Message
		$this->messageSentProfileId = new Profile(null, "Q", "12345678901234567890123456789012", true, 1, null, "Hi, I'm Markimoo!", "foo@bar.com", "4018725372539424208555279506880426447359803448671421461653568500", "12345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678", "Los Angeles", "Mark Fischbach", "1234567890123456789012345678901234567890123456789012345678901234");
		$this->messageSentProfileId->insert($this->getPDO());

		//create and insert a Profile to receive the test Message
		$this->messageReceiveProfileId = new Profile(null, "T", "12345678901234567890123456789012", true, 2, null, "Hi, I'm Irelia!", "bar@foo.com", "4018725372539424208555279506880426447359803448671421461653568500", "12345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678", "Ionia", "Irelia Ionia", "1234567890123456789012345678901234567890123456789012345678901234");
		$this->messageReceiveProfileId->insert($this->getPDO());

		//calculate the date using the time the unit test was set up
		$this->VALID_MESSAGEDATE = new \DateTime();
	}

	/**
	 * test inserting a valid Message and verifying that actual MySQL data matches
	 **/
	public function testInsertValidMessage() {
		//count the number of rows and save it for later
		$numRows = $this->getConnection()->getRowCount("message");

		//create a new message and insert into MySQL
		$message = new Message(null, $this->messageReceiveProfileId->getProfileId(), $this->messageSentProfileId->getProfileId(), $this->VALID_MESSAGECONTENT, $this->VALID_MESSAGEDATE, $this->VALID_MAILGUNID, $this->VALID_MESSAGESUBJECT);
		$message->insert($this->getPDO());

		//grab the data from MySQL and enforce that the fields match our expectations
		$pdoMessage = Message::getMessageByMessageId($this->getPDO(), $message->getMessageId());
		$this->assertEquals($numRows + 1, $this->getConnection()->getRowCount("message"));
		$this->assertEquals($pdoMessage->getMessageReceiveProfileId(), $this->messageReceiveProfileId->getProfileId());
		$this->assertEquals($pdoMessage->getMessageSentProfileId(), $this->messageSentProfileId->getProfileId());
		$this->assertEquals($pdoMessage->getMessageContent(), $this->VALID_MESSAGECONTENT);
		$this->assertEquals($pdoMessage->getMessageDateTime(), $this->VALID_MESSAGEDATE);
		$this->assertEquals($pdoMessage->getMessageMailgunId(), $this->VALID_MAILGUNID);
		$this->assertEquals($pdoMessage->getMessageSubject(), $this->VALID_MESSAGESUBJECT);
	}

	/**
	 * test inserting a Message that already exists
	 * @expectedException \PDOException
	 **/
	public function testInsertInvalidMessage() {
		//create a Message with a non null message id and watch it fail
		$message = new Message(DevConnectTest::INVALID_KEY, $this->messageReceiveProfileId->getProfileId(), $this->messageSentProfileId->getProfileId(), $this->VALID_MESSAGECONTENT, $this->VALID_MESSAGEDATE, $this->VALID_MAILGUNID, $this->VALID_MESSAGESUBJECT);
		$message->insert($this->getPDO());
	}

	/**
	 * test inserting a Message, editing it, and then updating it
	 **/
	public function testUpdateValidMessage() {
		//count the number of rows and save it for later
		$numRows = $this->getConnection()->getRowCount("message");

		//create a new Message and insert it into MySQL
		$message = new Message(null, $this->messageReceiveProfileId->getProfileId(), $this->messageSentProfileId->getProfileId(), $this->VALID_MESSAGECONTENT, $this->VALID_MESSAGEDATE, $this->VALID_MAILGUNID, $this->VALID_MESSAGESUBJECT);
		$message->insert($this->getPDO());

		//edit the Message and update it in MySQL
		$message->setMessageContent($this->VALID_MESSAGECONTENT2);
		$message->update($this->getPDO());

		//grab the data from MySQL and enforce that it matches our expectations
		$pdoMessage = Message::getMessageByMessageId($this->getPDO(), $message->getMessageId());
		$this->assertEquals($numRows + 1, $this->getConnection()->getRowCount("message"));
		$this->assertEquals($pdoMessage->getMessageReceiveProfileId(), $this->messageReceiveProfileId->getProfileId());
		$this->assertEquals($pdoMessage->getMessageSentProfileId(), $this->messageSentProfileId->getProfileId());
		$this->assertEquals($pdoMessage->getMessageContent(), $this->VALID_MESSAGECONTENT2);
		$this->assertEquals($pdoMessage->getMessageDateTime(), $this->VALID_MESSAGEDATE);
		$this->assertEquals($pdoMessage->getMessageMailgunId(), $this->VALID_MAILGUNID);
		$this->assertEquals($pdoMessage->getMessageSubject(), $this->VALID_MESSAGESUBJECT);
	}

	/**
	 * test updating a Message that does not exist
	 *
	 * @expectedException \PDOException
	 **/
	public function testUpdateInvalidMessage() {
		//create a Message and try to update it without actually updating it and watch it fail
		$message = new Message(null, $this->messageReceiveProfileId->getProfileId(), $this->messageSentProfileId->getProfileId(), $this->VALID_MESSAGECONTENT, $this->VALID_MESSAGEDATE, $this->VALID_MAILGUNID, $this->VALID_MESSAGESUBJECT);
		$message->update($this->getPDO());
	}

	/**
	 * test inserting a valid Message and then deleting it
	 **/
	public function testDeleteValidMessage () {
		//count the number of rows and save it for later
		$numRows = $this->getConnection()->getRowCount("message");

		//create a new Message and insert it into MySQL
		$message = new Message(null, $this->messageReceiveProfileId->getProfileId(), $this->messageSentProfileId->getProfileId(), $this->VALID_MESSAGECONTENT, $this->VALID_MESSAGEDATE, $this->VALID_MAILGUNID, $this->VALID_MESSAGESUBJECT);
		$message->insert($this->getPDO());

		//delete the message from MySQL
		$this->assertEquals($numRows + 1, $this->getConnection()->getRowCount("message"));
		$message->delete($this->getPDO());

		//grab the data from MySQL and enforce the message does not exist
		$pdoMessage = Message::getMessageByMessageId($this->getPDO(), $message->getMessageId());
		$this->assertNull($pdoMessage);
		$this->assertEquals($numRows, $this->getConnection()->getRowCount("message"));
	}

	/**
	 * test deleting a Message that does not exist
	 *
	 * @expectedException \PDOException
	 **/
	public function testDeleteInvalidMessage() {
		//create a Message and try to delete it without inserting it
		$message = new Message(null, $this->messageReceiveProfileId->getProfileId(), $this->messageSentProfileId->getProfileId(), $this->VALID_MESSAGECONTENT, $this->VALID_MESSAGEDATE, $this->VALID_MAILGUNID, $this->VALID_MESSAGESUBJECT);
		$message->delete($this->getPDO());
	}

	/**
	 * test grabbing a Message by sent profile id
	 **/
	public function testGetValidMessageByMessageSentProfileId() {
		//count the number of rows and save it for later
		$numRows = $this->getConnection()->getRowCount("message");

		//create a new Message and insert it into MySQL
		$message = new Message(null, $this->messageReceiveProfileId->getProfileId(), $this->messageSentProfileId->getProfileId(), $this->VALID_MESSAGECONTENT, $this->VALID_MESSAGEDATE, $this->VALID_MAILGUNID, $this->VALID_MESSAGESUBJECT);
		$message->insert($this->getPDO());

		//grab the data from MySQL and enforce that the fields match our expectations
		$results = Message::getMessageByMessageSentProfileId($this->getPDO(), $message->getMessageSentProfileId());
		$this->assertEquals($numRows + 1, $this->getConnection()->getRowCount("message"));
		$this->assertCount(1, $results);
		$this->assertContainsOnlyInstancesOf("Edu\\Cnm\\DevConnect\\Message", $results);

		//grab the result from the array and validate it
		$pdoMessage =$results[0];
		$this->assertEquals($pdoMessage->getMessageReceiveProfileId(), $this->messageReceiveProfileId->getProfileId());
		$this->assertEquals($pdoMessage->getMessageSentProfileId(), $this->messageSentProfileId->getProfileId());
		$this->assertEquals($pdoMessage->getMessageContent(), $this->VALID_MESSAGECONTENT);
		$this->assertEquals($pdoMessage->getMessageDateTime(), $this->VALID_MESSAGEDATE);
		$this->assertEquals($pdoMessage->getMessageMailgunId(), $this->VALID_MAILGUNID);
		$this->assertEquals($pdoMessage->getMessageSubject(), $this->VALID_MESSAGESUBJECT);
	}

	/**
	 * test grabbing a Message by a sent profile id that does not exist
	 **/
	public function testGetInvalidMessageByMessageSentProfileId() {
		//grab a message by searching for a sent profile id that does not exist
		$message = Message::getMessageByMessageSentProfileId($this->getPDO(), 12);
		$this->assertCount(0, $message);
	}

	/**
	 * test grabbing a Message by received profile id
	 **/
	public function testGetValidMessageByMessageReceiveProfileId() {
		//count the number of rows and save it for later
		$numRows = $this->getConnection()->getRowCount("message");

		//create a new Message and insert it into MySQL
		$message = new Message(null, $this->messageReceiveProfileId->getProfileId(), $this->messageSentProfileId->getProfileId(), $this->VALID_MESSAGECONTENT, $this->VALID_MESSAGEDATE, $this->VALID_MAILGUNID, $this->VALID_MESSAGESUBJECT);
		$message->insert($this->getPDO());

		//grab the data from MySQL and enforce that the fields match our expectations
		$results = Message::getMessageByMessageReceiveProfileId($this->getPDO(), $message->getMessageReceiveProfileId());
		$this->assertEquals($numRows + 1, $this->getConnection()->getRowCount("message"));
		$this->assertCount(1, $results);
		$this->assertContainsOnlyInstancesOf("Edu\\Cnm\\DevConnect\\Message", $results);

		//grab the result from the array and validate it
		$pdoMessage =$results[0];
		$this->assertEquals($pdoMessage->getMessageReceiveProfileId(), $this->messageReceiveProfileId->getProfileId());
		$this->assertEquals($pdoMessage->getMessageSentProfileId(), $this->messageSentProfileId->getProfileId());
		$this->assertEquals($pdoMessage->getMessageContent(), $this->VALID_MESSAGECONTENT);
		$this->assertEquals($pdoMessage->getMessageDateTime(), $this->VALID_MESSAGEDATE);
		$this->assertEquals($pdoMessage->getMessageMailgunId(), $this->VALID_MAILGUNID);
		$this->assertEquals($pdoMessage->getMessageSubject(), $this->VALID_MESSAGESUBJECT);
	}

	/**
	 * test grabbing a Message by a receive profile id that does not exist
	 **/
	public function testGetInvalidMessageByMessageReceiveProfileId() {
		//grab a message by searching for a receive profile id that does not exist
		$message = Message::getMessageByMessageReceiveProfileId($this->getPDO(), 24);
		$this->assertCount(0, $message);
	}

	/**
	 * test grabbing a Message by message subject
	 **/
	public function testGetValidMessageByMessageSubject() {
		//count the number of rows and save it for later
		$numRows = $this->getConnection()->getRowCount("message");

		//create a new Message and insert it into MySQL
		$message = new Message(null, $this->messageReceiveProfileId->getProfileId(), $this->messageSentProfileId->getProfileId(), $this->VALID_MESSAGECONTENT, $this->VALID_MESSAGEDATE, $this->VALID_MAILGUNID, $this->VALID_MESSAGESUBJECT);
		$message->insert($this->getPDO());

		//grab the data from MySQL and enforce that the fields match our expectations
		$results = Message::getMessageByMessageSubject($this->getPDO(), $message->getMessageSubject());
		$this->assertEquals($numRows + 1, $this->getConnection()->getRowCount("message"));
		$this->assertCount(1, $results);
		$this->assertContainsOnlyInstancesOf("Edu\\Cnm\\DevConnect\\Message", $results);

		//grab the result from the array and validate it
		$pdoMessage =$results[0];
		$this->assertEquals($pdoMessage->getMessageReceiveProfileId(), $this->messageReceiveProfileId->getProfileId());
		$this->assertEquals($pdoMessage->getMessageSentProfileId(), $this->messageSentProfileId->getProfileId());
		$this->assertEquals($pdoMessage->getMessageContent(), $this->VALID_MESSAGECONTENT);
		$this->assertEquals($pdoMessage->getMessageDateTime(), $this->VALID_MESSAGEDATE);
		$this->assertEquals($pdoMessage->getMessageMailgunId(), $this->VALID_MAILGUNID);
		$this->assertEquals($pdoMessage->getMessageSubject(), $this->VALID_MESSAGESUBJECT);
	}

	/**
	 * test grabbing a Message by a message subject that does not exist
	 **/
	public function testGetInvalidMessageByMessageSubject() {
		//grab a message by searching for a message subject that does not exist
		$message = Message::getMessageByMessageSubject($this->getPDO(), "you will find nada");
		$this->assertCount(0, $message);
	}
}

