<?php
/**
* 
*/
class Mail_MailInfo
{
	public $username;
	public $password;
	public $host;
	public $port;
	public $SMTPSecure = "tls";
	public $SMTPAuth = true;

	public $from;
	public $fromName;
	public $replyTo;
	public $replyToName;

	public $to = array();
	public $cc = array();
	public $bcc = array();

	public $subject = "";

	public $msgHTML = "";
	public $altBody = "";

	public $attachment = '';

	public $SMTPDebug = 0;
	public $debugoutput = 'html';


	public function getPHPMail(){
		$mail = new Mail_PHPMailer();
		//Tell PHPMailer to use SMTP
		$mail->isSMTP();

		//Enable SMTP debugging
		// 0 = off (for production use)
		// 1 = client messages
		// 2 = client and server messages
		$mail->SMTPDebug = $this->SMTPDebug;

		//Ask for HTML-friendly debug output
		$mail->Debugoutput = $this->debugoutput;

		//Set the hostname of the mail server
		$mail->Host = $this->host;

		//Set the SMTP port number - 587 for authenticated TLS, a.k.a. RFC4409 SMTP submission
		$mail->Port = $this->port;

		//Set the encryption system to use - ssl (deprecated) or tls
		$mail->SMTPSecure = $this->SMTPSecure;

		//Whether to use SMTP authentication
		$mail->SMTPAuth = $this->SMTPAuth;

		//Username to use for SMTP authentication - use full email address for gmail
		$mail->Username = $this->username;

		//Password to use for SMTP authentication
		$mail->Password = $this->password;

		//Set who the message is to be sent from
		if (is_array($this->from)) {
			foreach ($this->from as $key => $value) {
				$mail->setFrom($key, $value);
			}
		}

		//Set an alternative reply-to address
		if (is_array($this->replyTo)) {
			foreach ($this->replyTo as $key => $value) {
				$mail->addReplyTo($key, $value);
			}
		}

		//Set who the message is to be sent to
		if (is_array($this->to)) {
			foreach ($this->to as $key => $value) {
				$mail->addAddress($key, $value);
			}
		}

		if (is_array($this->cc)) {
			foreach ($this->cc as $key => $value) {
				$mail->addCC($key, $value);
			}
		}

		if (is_array($this->bcc)) {
			foreach ($this->bcc as $key => $value) {
				$mail->addBCC($key, $value);
			}
		}

		//Set the subject line
		$mail->Subject = $this->subject;

		//Read an HTML message body from an external file, convert referenced images to embedded,
		//convert HTML into a basic plain-text alternative body
		$mail->msgHTML($this->msgHTML);

		//Replace the plain text body with one created manually
		$mail->AltBody = $this->altBody;

		//Attach an image file
		$mail->addAttachment($this->attachment);

		//send the message, check for errors
		return $mail;
	}


}

?>