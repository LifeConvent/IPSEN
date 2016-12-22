<?php

class XMLkit
{
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
	
	
	//数组转XML
    function arrayToXml($arr){ 
		$xml = "<xml>"; 
		$xml.= $this->arrayToXmlContent($arr);
		$xml.="</xml>"; 
		return $xml; 
	}
	
	function arrayToXmlContent($arr){
		foreach ($arr as $key=>$val){ 
			if(is_array($val)){ 
				$xml.="<".$key.">".$this->arrayToXmlContent($val)."</".$key.">"; 
			}
			else{ 
				if (is_numeric($val)){
					$xml.="<".$key.">".$val."</".$key.">";
				}else{
					$xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
				}
			} 
		} 
		
		return $xml; 
	}

    //将XML转为array
    function xmlToArray($xml)
    {    
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);        
        return $values;
    }
	
}

