<?php
/* Time to complete the additon of the Pi
We need to:
1) Get all of the post data
2) check that the email is not already in the database. This should be possible from where we try and add it to the database and it should kick out.
3) if the addition was successful send verification email.
3b) or redirect to generic error page with failed addition
*/

function randblock() {
	$randno = rand(0,1000);
	$hex = dechex($randno);
	$length = strlen($hex);

	if ($length < 4) {
		$max = 4;
		$max = $max - strlen($hex);
	
		if($max == 1) {
			$hex .= "0";
		}
		elseif($max == 2) {
			$hex = "0" . $hex;
			$hex .= "0";
		}
	
		elseif($max == 3) {
			$hex = "0" . $hex;
			$hex .= "00";
		}
	}
	return $hex;
}

//Connect
$m = new MongoClient("mongodb://ams1.ryanteck.org.uk:27017,ny1.ryanteck.org.uk:27017/?replicaSet=rtk1");
$db = $m->selectDB("rastrack");
$piscollection = $db->pis;
include("inc/header.php");
include("inc/solvemedialib.php");
$privkey="2LlsUypgLG6PDS8Tocc8ky3He2oaYXmw";
$hashkey="49BvH90yDPy3biLS0S7t9J4pT7OfVzTS";

//Username
if(isset($_POST['Username'])) {
	$name = $_POST['Username'];
}
else {
	rastrackHeader("Add Your Pi",2);
	echo "<h3>There was no username inputted on the last page. Please press back and correct this</h3>";
	die(include("inc/footer.php"));
}

$solvemedia_response = solvemedia_check_answer($privkey,
					$_SERVER["REMOTE_ADDR"],
					$_POST["adcopy_challenge"],
					$_POST["adcopy_response"],
					$hashkey);
if (!$solvemedia_response->is_valid) {
	rastrackHeader("Add Your Pi",2);
	echo "<h3>Captcha threw an error. This was: </h3>";
	print "Error: ".$solvemedia_response->error;
	die(include("inc/footer.php"));
}

if(isset($_POST['twitter'])) {
	$twitter = $_POST['twitter'];
}
else { $twitter = "";}

if(isset($_POST['Location'])) {
	$location = $_POST['Location'];
}
else {
	rastrackHeader("Add Your Pi",2);
	echo "<h3>There was no location inputted on the last page. Please press back and correct this</h3>";
	die(include("inc/footer.php"));
}

if(isset($_POST['dateOfArrival'])) {
	$date = $_POST['dateOfArrival'];
}
else {
	rastrackHeader("Add Your Pi",2);
	echo "<h3>There was no date inputted on the last page. Please press back and correct this</h3>";
	die(include("inc/footer.php"));
}
if(isset($_POST['email'])) {
	$email = $_POST['email'];
}
else {
	rastrackHeader("Add Your Pi",2);
	echo "<h3>There was no email inputted on the last page. Please press back and correct this</h3>";
	die(include("inc/footer.php"));
}

if(isset($_POST['newsletter'])) {
	if($_POST['newsletter'] == "on") {
		$newsletter = 1;	
	}
}
else {
$newsletter = 0;
}

$emailkey = randblock().randblock().randblock().randblock();
$keyexpires = strtotime("+48 hours");
$latlon = explode (",",$location);



try {
	$piscollection->insert(array("username" =>$name,"_id"=>$email,"emailkey"=>$emailkey,"keyexpires"=>$keyexpires,"newsletter"=>$newsletter,"location"=>array("type"=>'Point',"coordinates"=>array(floatval($latlon[1]),floatval($latlon[0]))), 
	"twitter"=>$twitter,"date"=>$date),array("w" => 1));
	//var_dump($mongoinsert);
}

catch(MongoCursorException $e) {
	$code = $e->getCode();
	if($code==11000) {
		rastrackHeader("Add Your Pi",2);
		echo "<h3>This email is already in the database. Please use another one or change your details.</h3>";
		die(include("inc/footer.php"));
	}
	else {
		rastrackHeader("Add Your Pi",2);
		echo "<h3>There was an error. If you continue to have trouble send the following information to us using the contact page.</h3>";
		echo "<div class='panel panel-danger'>";
		var_dump($e);
		echo "</div>";
		die(include("inc/footer.php"));
	}
}

//Email



$to = $email;
$subject = "Raspberry Pi Map Verification";
$from = "www@ryanteck.org.uk";
$headers = "From:" . $from;
$message = "Dear ".$name."

 

You can confirm your email by going to http://rastrack.co.uk/verify.php?key=".$emailkey."&email=".str_replace('+', '%2B',$email)." . If you find you have any details incorrect then you can change the information by going to http://rastrack.co.uk/changekey.php?key=".$emailkey."&email=".rawurlencode($email)." (Note the change your details link will expire after 48 hours for security reasons, if you neeed to change after click the change details link on the website).

Thank you once again for adding your Raspberry Pi to Rastrack
Ryan Walmsley
Creator of Rastrack.co.uk";
mail($to,$subject,$message,$headers);
//Everything should have gone well
rastrackHeader("Add Your Pi",2);
echo "<h3>Your Pi is added!</h3><h5>A verification and information email has been sent to the email provided.";
die(include("inc/footer.php"));


?>
