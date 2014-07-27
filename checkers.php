<?php
	/*
	 * Checkers uptime monitor created by Josh (AKA gravypod) Katz (7/26/14 US)
	 */
	echo("Loading Configs\n");
	require_once("./config.php");
	
	date_default_timezone_set(TIMEZONE);
	
	require_once('./libs/PHPMailerAutoload.php');
	
	$emails = loadJson(EMAILS); // Who do we send emails to?
	
	global $date;
	$date = date("l jS F \@ g:i a", time()); // Format a date of the check.
	
	$plugins = glob("./plugins/*.php");
	$state = loadState();
	
	foreach ($plugins as $path) { // Find everything in plugins dir.
		
		include_once($path); // Include and parse plugin
		
		$baseName =  basename($path, ".php"); // Get file name
		$returned = call_user_func($baseName); // Get info returned from a plugin
		$reported = isset($state[$baseName]); // Should we report to the incident?
		
		if ($returned === false) {
			
			echo("Service $baseName is ONLINE\n");
			
			if ($reported) {
				$offline = $state[$baseName];
				send("Service $baseName back online: ($offline) -> ($date)", $emails); // How long was the outage.
			}
			
			unset($state[$baseName]);
			
		} else {
			
			echo("Service $baseName is OFFLINE\n");
			
			if (!$reported) {
				send($returned, $emails); // inform about outage
			}
			
			$state[$baseName] = $date;
			
		}
		
	}
	
	saveState($state);
	
	function loadState() { // Load online / offline states
		return loadJson("state.json");
	}
	
	function saveState($state) {
		
		file_put_contents("state.json", json_encode($state)); // Save the states to a json file
		
	}
	
	function loadJson($file) { // Load a JSON file.
		
		if (!file_exists($file)) {
			return array();
		}
		
		return json_decode(file_get_contents($file), true);
		
	}
	
	/*
	 * Crazy PHP mail function. Credits to http://ctrlq.org/code/19589-send-mail-php
	 */
	function send($message, $addresses) {
		$mail = new PHPMailer;
		$mail->isSMTP();                                      // Set mailer to use SMTP
		$mail->Host = HOST;                                   // Specify main and backup server
		$mail->SMTPAuth = true;                               // Enable SMTP authentication
		$mail->Username = USERNAME;                           // SMTP username
		$mail->Password = PASSWORD;                           // SMTP password
		$mail->SMTPSecure = 'tls';                            // Enable encryption, 'ssl' also accepted
		$mail->Port = 587;                                   //Set the SMTP port number - 587 for authenticated TLS
		$mail->setFrom(FROM_EMAIL, FROM_NAME);                //Set who the message is to be sent from
		$mail->addReplyTo(RELY_TO, REPLY_NAME);               //Set an alternative reply-to address
		$mail->WordWrap = 50;                                 // Set word wrap to 50 characters
		$mail->isHTML(true);                                  // Set email format to HTML
		$mail->Subject = SUBJECT;
		$mail->Body = $message;
		$mail->AltBody = $message;
		foreach ($addresses as $a) {
			echo("Sending to $a\n");
			$mail->addAddress($a);  // Add a recipient
		}
		if(!$mail->send()) {
			echo 'Message could not be sent.';
			echo 'Mailer Error: ' . $mail->ErrorInfo;
			exit;
		}
	}
	
?>