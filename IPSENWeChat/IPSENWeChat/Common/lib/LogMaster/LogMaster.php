<?php

class LogMaster
{

	function tellMe($fileName, $content)
	{
		$location = __DIR__."/Log/{$fileName}.log";
		$logFile = fopen("{$location}", "a") or die("Unable to open {$location}!");
		fwrite($logFile, $content."\n");
		date_default_timezone_set("Asia/Shanghai");
		fwrite($logFile, date("h:i:sa")."\n\n");
		fclose($myfile);
	}
	
}