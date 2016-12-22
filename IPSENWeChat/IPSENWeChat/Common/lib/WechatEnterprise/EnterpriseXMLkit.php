<?php

class EnterpriseXMLkit
{
	public function replyText($receivedXML, $text){
		$replyXML['Content'] = $text;
		
		$assembledXML = $this->assembleXML($receivedXML, $replyXML, 'text');
		return $assembledXML;
	}
	
	public function replyNews($receivedXML, $news){
		$replyXML['ArticleCount'] = count($news);
		$replyXML['Articles'] = $news;
		
		$assembledXML = $this->assembleXML($receivedXML, $replyXML, 'news');
		return $assembledXML;
	}
	
	private function assembleXML($receivedXML, $replyXML, $replyType){
		$replyXML['ToUserName'] = $receivedXML['FromUserName'];
		$replyXML['FromUserName'] = $receivedXML['ToUserName'];
		$replyXML['CreateTime'] = NOW_TIME;
		$replyXML['MsgType'] = $replyType;
		
		$xml = new \SimpleXMLElement('<xml></xml>');
		$this->_data2xml($xml, $replyXML);
		$str = $xml->asXML();
		
		return $str;
	}
	
	/* 组装xml数据 */
	public function _data2xml($xml, $data, $item = 'item') {
		foreach ( $data as $key => $value ) {
			is_numeric ( $key ) && ($key = $item);
			if (is_array ( $value ) || is_object ( $value )) {
				$child = $xml->addChild ( $key );
				$this->_data2xml ( $child, $value, $item );
			} else {
				if (is_numeric ( $value )) {
					$child = $xml->addChild ( $key, $value );
				} else {
					$child = $xml->addChild ( $key );
					$node = dom_import_simplexml ( $child );
					$node->appendChild ( $node->ownerDocument->createCDATASection ( $value ) );
				}
			}
		}
	}
	
	
	
	
	
	// public function replyNews($articles) {
		// $msg ['ArticleCount'] = count ( $articles );
		// $msg ['Articles'] = $articles;
		// $this->_replyData ( $msg, 'news' );
	// }
	/* 发送回复消息到微信平台 */
	public function _replyData($msg) {
		// $msg ['ToUserName'] = $this->data ['FromUserName'];
		// $msg ['FromUserName'] = $this->data ['ToUserName'];
		// $msg ['CreateTime'] = NOW_TIME;
		// $msg ['MsgType'] = $msgType;
		
		$xml = new \SimpleXMLElement ( '<xml></xml>' );
		$this->_data2xml ( $xml, $msg );
		$str = $xml->asXML ();
		
		// 记录日志
		//addWeixinLog ( $str, '_replyData' );
		return $str;
	}
	
}

