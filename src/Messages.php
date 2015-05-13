<?php
function DebugLog($msg)
{ 
	// open file
	$fd = fopen("tmp/propunter.log", "a");
	// write string
	fwrite($fd, "[" . date("m/d/Y H:i") . "] " . $msg . "\n");
	// close file
	fclose($fd);
}
$messageList = array();

function ReceiveMessage($message, $param)
{
	global $messageList;
	if(!array_key_exists($message,$messageList))
	{
		printf("Unknown message %s.\n", $message);
		return;
	}
	$test = new $messageList[$message];
	$test->Handle($param);
}

function PickMySQL($needToWrite)
{
	//return "localhost";
	if($needToWrite)
	{
		return "int-mysql-master.00b1208e-4997-496a-8a4e-ef5ca1a2b8c2.scalr.ws";
	}
	else
	{
		return "int-mysql-slave.00b1208e-4997-496a-8a4e-ef5ca1a2b8c2.scalr.ws";
	}
}

class GameMessage
{
	var $type;

	function Handle($param)
	{
	}
}

function ExtractParameters($fromArray, $listOfKeys)
{
	$newArray = array();
	foreach($listOfKeys as $key)
	{
		$newArray[$key] = $fromArray[$key];
	}
	return $newArray;
}

//login
class LoginMessage extends GameMessage
{
	function Handle($param)
	{

		$mysql = 'localhost';
		mysql_connect($mysql, "ptgi", "igtp23") or die(mysql_error());
		mysql_select_db("traintoproclaim") or die(mysql_error());

	 	$user      = $_REQUEST["user"];
		$password  = $_REQUEST["pwd"];

	        $query = "SELECT * FROM user WHERE name='$user' AND password='$password'"; 
		DebugLog($query);
		$result = mysql_query($query);
		$row = mysql_fetch_assoc($result);
		$cnt = mysql_num_rows($result);
		
		 if($cnt>0)
                {
			 $id=$row['id'];
			 
			$query = "SELECT c.name FROM
				  country c,region r,city ci,suburb s,user u
                                  WHERE
                                  c.id=r.idcountry AND r.id=ci.idregion AND ci.id=s.idcity AND s.id=u.idsuburb AND u.id='$id'";
 
			DebugLog($query);
			$result = mysql_query($query);
			$row = mysql_fetch_assoc($result);
			$cnt = mysql_num_rows($result);
			
			 if($cnt>0)
        	        {			 	
			 	$countryname           = $row['name'];
 		                $response['Country']  = "$countryname";
 			}	

			$query1 = "SELECT count(totalloved) count FROM
				  result
                                  WHERE iduser='$id'";
 
			DebugLog($query1);
			$result1 = mysql_query($query1);
			$row1    = mysql_fetch_assoc($result1);
			$cnt1    = mysql_num_rows($result1);
			
			 if($cnt1>0)
        	        {			 	
			 	$count1                 = $row1['count'];
 			}	
			if($count1) $response['Count']      = "$count1";
			else	$response['Count']      = "0";
		        $response['Response'] = 'success';
		         echo (json_encode($response));
		         return;
                }
                else
                {
		         $response['Response'] = 'error';
		         echo (json_encode($response));
		         return;
                }
	}
}
$messageList['Login'] = "LoginMessage";

class SendmailMessage extends GameMessage
{
	function Handle($param)
	{

		$mysql = 'localhost';
		mysql_connect($mysql, "ptgi", "igtp23") or die(mysql_error());
		mysql_select_db("traintoproclaim") or die(mysql_error());

		$email     = $_REQUEST["email"];
		$subject   = $_REQUEST["sub"];
		$name      = $_REQUEST["name"];
		$ccemail   = $_REQUEST["ccemail"];
		
		// Check for existing email
		$sql = "SELECT id FROM email_sent WHERE email='$email' AND subject='$subject'";
		$result = mysql_query($sql);
		if ($result !== FALSE) {
			if (mysql_num_rows($result) > 0) {
				DebugLog("BUG: Duplicate email attempt to $email subject ($subject)");
				return;
			}
		} else {
			DebugLog(mysql_error());
		}
		
		$resultXML  = '<?xml version="1.0" encoding="UTF-8" ?> ';
		$resultXML .= "<Response>";	
		
		$from ='responses@traintoproclaim.com';
		$bcc  ='responses@traintoproclaim.com';	
		$xheaders  = "";
		$xheaders .= "From: <$from>\n";
		$xheaders .= "Bcc: <$bcc>\n";
		if ($ccemail != '') $xheaders .= "Cc: <$ccemail>\n";
		$xheaders .= "X-Sender: <$from>\n";
		$xheaders .= "X-Mailer: PHP\n";
		$xheaders .= "X-Priority: 1\n"; 
		$xheaders .= "Content-Type:text/html; charset=\"iso-8859-1\"\n";
		$xheaders .= "Reply-To: <$from>\r\n";
		$xheaders .= "Return-Path: <$from>\r\n";

		$to  = $email;
		
		if($subject=='1')
		{
		$subject = $name.' '.'- give it some thought';
		$msg="<html>
				<body>
			<h3>Dear {$name},</h3>
			 <p>Thanks for viewing the presentation on the Bible, I hope you found it interesting and informative. I would encourage you to give this some more thought, eternity is a long time, the ramifications of this are huge! </p>
			<p> Please go to <a href='http://answersaboutlife.com' target='_blank'>answersaboutlife.com</a> and check out both the video and the answers to questions such as 'Does God exist?', 'What about other religions?' and 'Is the Bible true?'.  
<a href='http://www.answersaboutlife.com/images/stories/books/ebook.pdf'> Click here </a> to view or download a free e-book for more info.  If you have any questions or want to make contact then please reply to this email and we can be in touch with you, otherwise this is a one off email and you will not receive anymore emails from us </p>
		<br><p>Kind Regards</p>
		<p>Team from Train To Proclaim</p>	
			</body>
			</html>";
		}
		
		if($subject=='2')
		{
		$subject = $name.' '.'- time to explore it further';
		$msg="<html>
				<body>
			<h3>Dear {$name},</h3>
			 <p>Thanks for viewing the presentation on the Bible, I hope you found it interesting and informative. It is great that you want to give this some more thought and explore it further, eternity is a long time, the ramifications of this are huge!</p>
			<p>Please go to <a href='http://answersaboutlife.com' target='_blank'>answersaboutlife.com</a> and check out both the video and the answers to questions such as 'Does God exist?', 'What about other religions?' and 'Is the Bible true?'. 
<a href='http://www.answersaboutlife.com/images/stories/books/ebook.pdf'> Click here</a> to view or download a free e-book for more info.  If you have any questions or want to make contact then please reply to this email and we can be in touch with you, otherwise this is a one off email and you will not receive anymore emails from us. </p>
		<br><p>Kind Regards</p>
		<p>Team from Train To Proclaim</p>	
			</body>
			</html>";
		}

		if($subject=='3')
		{
		$subject = $name.' '.'- you have made the best decision of your life!';
		$msg="<html>
				<body>
			<h3>Dear {$name},</h3>
			 <p>Good on you for making the commitment to follow Jesus, it certainly is the best decision you can ever make in your life!  God is so good and as you talk to Him and get to know Him personally you will sense His amazing love for you and His interest in your life.  As you read the Bible you will see how He wants you to live and will discover His plan for your life.  And as you connect with other Christians in church you will learn so much from others about what it means to follow Jesus.</p>
			<p> <a href='http://www.answersaboutlife.com/images/stories/books/ebook.pdf'> Click here </a> to view or download a free e-book with more info.  Need help with finding a good church or need to talk some more with someone?  We would love to help you in any way we can and would love to hear from you and stay in touch.  If you would like this too then please reply to this email, otherwise this is a one off email and you will not receive anymore emails from us.  </p>
			<p>Would you like others to see this presentation?  Please direct them to answersaboutlife.com and encourage them to view the video presentation.  And if you would like an App on your phone or tablet then go to the App Store and search for 'G7' or 'Gospel in 7'.  There is also more free resources for you at our website <a href='http://www.traintoproclaim.com' target='_blank'> www.traintoproclaim.com</a>.</p>
		<br><p>God bless you and hope to hear from you soon!</p>
		<p>Team from Train To Proclaim</p>	
			</body>
			</html>";
		}

		if($subject=='4')
		{
		$subject = $name.' '.'- you are almost there';
		$msg="<html>
				<body>
			<h3>Dear {$name},</h3>
			 <p>Thanks for viewing the presentation on the Bible, I hope you found it interesting and informative. It is great that you want to surrender to Jesus. You are almost there, but there was something that you were struggling to agree with and need to think through.  I would encourage you to give this some more thought, eternity is a long time, the ramifications of this are huge! </p>
			<p> <a href='http://www.answersaboutlife.com/images/stories/books/ebook.pdf'>
Click here </a> to view or download a free e-book with some more info on the seven heart attitudes that you need to agree on in order to surrender your life to Jesus. Please go to <a href='http://answersaboutlife.com' target='_blank'> answersaboutlife.com </a> and check out both the video and any answers to questions you may have </p>
			 <p>Need help with finding a good church or need to talk some more with someone?  We would love to help you in any way we can and would love to hear from you and stay in touch.  If you would like this too then please reply to this email, otherwise this is a one off email and you will not receive anymore emails from us. </p>	
		<br><p>God bless you and hope to hear from you soon!</p>
		<p>Team from Train To Proclaim</p>	
			</body>
			</html>";
		}

		if($subject=='5')
		{
		$subject = $name.' '.'- here is the info you requested';
		$msg="<html>
				<body>
			<h3>Dear {$name},</h3>
			 <p>Thanks for viewing the presentation on the Bible, called Gospel in 7 or G7. It is great that you want to want to know more about the resources available to share Jesus with others.  Doing this appropriately can be a challenge and we all want others to come to know Jesus but don't want to put them off by anything we do or say.  </p>
			<p>The G7 is a fantastic tool for presenting the Gospel in a non-threatening way, communicating quickly and clearly the need for salvation.  There is no jargon, is easy to understand and is interesting to view.  Please view our website at <a href='http://www.traintoproclaim.com' target='_blank'>www.traintoproclaim.com </a> for free evangelism resources you can download and use and if you would like an App on your phone or tablet then go to the App Store and search for 'G7' or 'Gospel in 7'.</p>
			<p>If we can help in any way in training you or your church in evangelism then please reply to this email, otherwise this is a one off email and you will not receive anymore emails from us. </p>
		<br><p>God bless you and hope to hear from you soon!</p>
		<p>Team from Train To Proclaim</p>	
			</body>
			</html>";
		}
		if(mail( $to,$subject, $msg,$xheaders))
		{
			$response['Response'] = 'Success';
		        echo (json_encode($response));
		        return;
		}
		else
		{
//			$response['Response'] = 'Success';
			$response['Response'] = 'Error';
			echo (json_encode($response));
			return;
		}
		
		// Insert a record into the database
		$ip = $_SERVER['REMOTE_ADDR'];
		$client = $_SERVER['HTTP_USER_AGENT'];
		$sql = "INSERT INTO email_sent (dtc, email, subject, ip, client) VALUES ('NOW()', '$email', '$subject', '$ip', '$client')";
		$result = mysql_query($sql);
		if ($result !== TRUE) {
			DebugLog(mysql_error());
		}

	}
}
$messageList['Sendmail'] = "SendmailMessage";

//Insert user count country vise in result table
class CountrycountMessage extends GameMessage
{
	function Handle($param)
	{

		$mysql = 'localhost';
		mysql_connect($mysql, "ptgi", "igtp23") or die(mysql_error());
		mysql_select_db("traintoproclaim") or die(mysql_error());

		$username         = $_REQUEST["user_name"]; 
		$country_name1    = $_REQUEST["country_name1"];
      		$country_name2    = $_REQUEST["country_name2"];
		$country_name3    = $_REQUEST["country_name3"];
		
		$country_nos1     = $_REQUEST["country_nos1"];
      		$country_nos2     = $_REQUEST["country_nos2"];
		$country_nos3     = $_REQUEST["country_nos3"];
		
		$currdate = date("y-m-d H:i:s");
	 	$query = "SELECT * FROM user WHERE name='$username'"; 
		DebugLog($query);
		$result = mysql_query($query);
		$row = mysql_fetch_assoc($result);
		$cnt = mysql_num_rows($result);
		if($cnt>0)
                {
		  $userid =$row['id'];	

	        $query1 = "SELECT * FROM country WHERE name =".'"'.$country_name1.'"';
		DebugLog($query1);
		$result1 = mysql_query($query1);
		$row1 = mysql_fetch_assoc($result1);
		$cnt1 = mysql_num_rows($result1);
		if($cnt1>0 && $country_nos1>0)
                {
	         $countryid1 = $row1['id'];	
                 $insertSQL1 = "INSERT INTO result (idcountry,iduser,totalloved,createdby,dayloved) VALUES ('$countryid1', '$userid',
                                          '$country_nos1','$userid','$currdate')";
		 mysql_query($insertSQL1); 
		 $num1       = mysql_affected_rows();	  
		}	
	    
                $query2 = "SELECT * FROM country WHERE name =".'"'.$country_name2.'"';
		DebugLog($query2);
		$result2 = mysql_query($query2);
		$row2 = mysql_fetch_assoc($result2);
		$cnt2 = mysql_num_rows($result2);
		if($cnt2>0 && $country_nos2>0)
                {
		  $countryid2 =$row2['id'];	
                  $insertSQL2 ="INSERT INTO result (idcountry,iduser,totalloved,createdby,dayloved) VALUES ('$countryid2', 						     '$userid','$country_nos2','$userid','$currdate')";
		  mysql_query($insertSQL2); 
		  $num2       = mysql_affected_rows();	    
		}	

 	        $query3 = "SELECT * FROM country WHERE name =".'"'.$country_name3.'"';
		DebugLog($query3);
		$result3 = mysql_query($query3);
		$row3 = mysql_fetch_assoc($result3);
		$cnt3 = mysql_num_rows($result3);
		if($cnt3>0 && $country_nos3>0)
                {
		    $countryid3 =$row3['id'];	
                    $insertSQL3 = "INSERT INTO result (idcountry,iduser,totalloved,createdby,dayloved) VALUES ('$countryid3', 						      '$userid','$country_nos3','$userid','$currdate')";
		    mysql_query($insertSQL3); 
		    $num3       = mysql_affected_rows();	    
		}	
		}

			$query11 = "SELECT sum(totalloved) count FROM
				  result
                                  WHERE iduser='$userid'";
 
			DebugLog($query11);
			$result11 = mysql_query($query11);
			$row11    = mysql_fetch_assoc($result11);
			$cnt11    = mysql_num_rows($result11);
			
			 if($cnt11>0)
        	        {			 	
			 	$count11                 = $row11['count'];
 			}	
			if($count11) $response['Count']      = "$count11";
			else 	$response['Count']      = "0";


		if(($num1 =='1' ) || ($num2 =='1') || ($num3 =='1'))	
		{
			$response['Response'] = 'Success';
		        echo (json_encode($response));
		        return;
		}
		else
		{
			$response['Response'] = 'Error';
		        echo (json_encode($response));
		        return;
		}


	}
}
$messageList['Countrycount'] = "CountrycountMessage";

//Totalloved
class TotalcountMessage extends GameMessage
{
	function Handle($param)
	{

		$mysql = 'localhost';
		mysql_connect($mysql, "ptgi", "igtp23") or die(mysql_error());
		mysql_select_db("traintoproclaim") or die(mysql_error());

	 	$user      = $_REQUEST["user"];

	        $query = "SELECT * FROM user WHERE name='$user'"; 
		DebugLog($query);
		$result = mysql_query($query);
		$row = mysql_fetch_assoc($result);
		$cnt = mysql_num_rows($result);
		
		 if($cnt>0)
                {
		        $id=$row['id'];
			 
			$query = "SELECT sum(totalloved) count FROM
				  result
                                  WHERE iduser='$id'";
 
			DebugLog($query);
			$result = mysql_query($query);
			$row = mysql_fetch_assoc($result);
			$cnt = mysql_num_rows($result);
			
			 if($cnt>0)
        	        {			 	
			 	$count                 = $row['count'];
 		                $response['Response']  = "$count";
 			}	

		         echo (json_encode($response));
		         return;
                }
                else
                {
		         $response['Response'] = '0';
		         echo (json_encode($response));
		         return;
                }
	}
}
$messageList['Totalcount'] = "TotalcountMessage";


?>
