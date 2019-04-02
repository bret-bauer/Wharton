<?php
$man=0;
if(isset($_GET['man'])) $man=1;
date_default_timezone_set("America/Chicago");
chdir('/www/rpf/html/wharton');
set_time_limit(3600*24);   // 24 hours
// ini_set('memory_limit', (1024*1024)*8);
$seg_cnt=0;  $seek=true; $e_mess="These episodes just in<br><br>";
$new_stuff=0;
$list_data=file_get_contents("bestof.txt");
$z=array();
$z['cookiefile']="/www/rpf/html/wharton/cookie.txt";
$z['refer']="https://businessradio.wharton.upenn.edu/bestof/";
if($man) echo "Manual mode running...<br>";
// grab landing page
//$list_data=fetch("https://shows.pippa.io/wbr-highlights",$z);
$list_data=file_get_contents("https://shows.pippa.io/wbr-highlights/episodes");
file_put_contents("big_list.txt",$list_data);
sleep(3);
$seek=1;
$max_episodes=14;
$hits=0;
$list_data=file_get_contents("big_list.txt");
while($seek) {
	$seek=false;
	$pos=strpos($list_data,'class="ant-card-body"');
	if($pos !=false) {
		$seg_cnt++;
		$seek=true;
		
		if($seg_cnt > 1) {
		
		$show_cnt=$seg_cnt-1;
		echo $show_cnt.") ";
		// get segment
		$segment=GrabIt($list_data,'class="ant-card-body"','LISTEN NOW',2500,true);
		// get url for episode
		$url=GrabIt($segment,'href="','"',500,true);
		// get episode title
		$title=GrabIt($segment,'visit episode ','"',500,true);
		$title=str_replace("visit episode ","",$title);

		$tdate=GrabIt($segment,'<time dateTime="','"',100,true);
		$tdate=substr($tdate,0,10);
		$pdate=$tdate;
		
		$mp3=str_replace("autoplay=1","autoplay=0",$url);
		echo "$title - $pdate - $mp3<br>";

		$mp3_page="https://player.pippa.io/wbr-highlights".$mp3."?theme=default&cover=1&latest=1&autoplay=0";
		$page_data=file_get_contents($mp3_page);
		$mp3_link=GrabIt($page_data,'og:video" content="','?ref=',200,true);
			
		// only valid records
		if(strlen($mp3) > 10) {
			$hits++;
			// load the MP3 and save
			$title=str_replace(" ","_",$title);
			$file_name=ValChars($title."_".$pdate).".mp3";
			$file_name=str_replace("__","_",$file_name);
			if(file_exists($file_name)) {
				echo "SKIPPED $file_name<br>";
			}
			else
			{
				$try_times=1;
				$try=true;
				while($try) {
					$mp3_data=fetch($mp3_link,$z);
					$sl=strlen($mp3_data);
					if($sl > 1000) {
						$try=false;
						break;
					}
					$try_times++;
					echo " TRY $try_times ";
					if($try_times > 0) {
						echo "TOO MANY TRIES - ABORTING";
						die();
					}
					sleep(4);
				}
				if($sl > 1000) {
					file_put_contents($file_name,$mp3_data);
					echo "saved $file_name  MP3 file - $sl<br>";
					$e_mess.=$file_name."<br>";
					$new_stuff++;
				}
				else
				{
					echo "ZERO BYTES $file_name  MP3 file - $sl<br>";
				}
				$mp3_data=null;
				flush();
				ob_flush();
				sleep(rand(3,11));   // delay to keep from blacklisting
			}
			}
		}
		// chop string a bit smaller each time
		$list_data=substr($list_data,$pos+40,9999999);
		echo "<br>";
		if($hits > $max_episodes) { $seek=0; echo '<br>END! Hit max segment limit<br><br>'; }
	}
	else
	{
		echo 'END! Ran out of segments<br><br>';
	}
}

if(! $new_stuff) $e_mess.="Nothing new found.";

$headers = "From: RPF Server <do-not-reply@allytitle.com>\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
if(! $man) {
	if($new_stuff) echo "<br>To David email: ".mail('davidbrooks@usecapital.com','New Wharton Business School Episodes',$e_mess,$headers);
}
die("sent mail");


function ToK($number) {
    if($number >= 1024) {
       return intval($number/1024) . "k"; 
    }
    else {
        return $number;
    }
}

function ValChars($stf)
{
$valid_chars="00123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_".CHR(34);
$ans="";
$lgth=strlen($stf);
for ($cnt = 0; $cnt < $lgth; $cnt++) {
	$char = substr($stf, $cnt, 1);
	if($char=="0") {
		$ans.="0";
	}
	else
	{
		if(strpos($valid_chars,$char) != false) { $ans.=$char; }
	}
}
return $ans;
}

function fetch( $url, $z=null ) {
	$timeout=90;
        $ch =  curl_init();
        // $useragent = isset($z['useragent']) ? $z['useragent'] : 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:10.0.2) Gecko/20100101 Firefox/10.0.2';

	$ua="Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/44.0.2403.89 Safari/537.36";
	// curl_setopt($ch,CURLOPT_USERAGENT,$ua);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Connection: keep-alive',
		'Cache-Control: max-age=0',
		'Upgrade-Insecure-Requests: 1',
		'User-Agent: Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.99 Safari/537.36',
		'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
		'Accept-Encoding: gzip, deflate, br',
		'Accept-Language: en-US,en;q=0.9',
	));
        // curl_setopt( $ch, CURLOPT_CAINFO, 'server.crt' );
	// curl_setopt( $ch, CURLOPT_CAPATH, '/xampp/apache/conf/' );
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
	if( isset($z['post']) ) curl_setopt( $ch, CURLOPT_POST, $z['post'] );
        if( isset($z['post_fields']) )   curl_setopt( $ch, CURLOPT_POSTFIELDS, $z['post_fields'] );
        if( isset($z['refer']) )   curl_setopt( $ch, CURLOPT_REFERER, $z['refer'] );
        // curl_setopt( $ch, CURLOPT_USERAGENT, $useragent );
        curl_setopt( $ch, CURLOPT_COOKIEJAR,  $z['cookiefile'] );
        curl_setopt( $ch, CURLOPT_COOKIEFILE, $z['cookiefile'] );
	curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER ,false); 
	curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $timeout );
	curl_setopt( $ch, CURLOPT_TIMEOUT, $timeout );
	curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
		    
	// curl_setopt($ch, CURLOPT_VERBOSE, true);
	// $verbose = fopen('php://temp', 'rw+');
	// curl_setopt($ch, CURLOPT_STDERR, $verbose); 
	    
	$result = curl_exec( $ch );
	$response = curl_getinfo( $ch );
	    
	// rewind($verbose);
	// $verboseLog = stream_get_contents($verbose);
	// echo "Verbose information:\n<pre>", htmlspecialchars($verboseLog), "</pre>\n"; 
        curl_close( $ch );
			
        return $result;
    }
   
function GrabIt($data,$key,$stopper,$max=100,$raw=false) {
$ans="";
$pos=strpos($data,$key);
if($pos > 0) {
	$snip=substr($data,$pos+strlen($key),$max);
	$end=strpos($snip,$stopper);
	$ans=substr($snip,0,$end);
	$ans=trim( preg_replace( '/\s+/', ' ', $ans ) );  // remove crlf
	if(! $raw) {
		$ans=strip_tags($ans);
		$ans=str_replace(chr(34).">","",$ans);
	}
	// $ans = preg_replace('/[^a-zA-Z0-9\s\-=+\|!@#$%^&*()`~\[\]{};:\'",<.>\/?]/', '', $ans);
}
return($ans);
}   

?>