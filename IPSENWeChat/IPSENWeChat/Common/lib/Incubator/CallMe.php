<?php

class CallMe
{
	private $init;
	
	public function CallMe($init){
		$this->init = $init;
		
	}
	
	public function showMeMore(){
		echo $this->init;
		
	}
	
}
