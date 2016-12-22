<?php
/**
 * Created by PhpStorm.
 * User: lawrance
 * Date: 16/10/20
 * Time: 下午3:15
 */


namespace Home\Controller;

use Think\Controller;
use Think\Model;

// 导入微信企业号类库
import('Common.lib.WechatEnterprise.WXBizMsgCrypt', APP_PATH, '.php');

// 导入XML类库
import('Common.lib.WechatEnterprise.EnterpriseXMLkit', APP_PATH, '.php');

// 导入CURL类库
import('Common.lib.CURL.CURL', APP_PATH, '.php');

// 导入LogMaster类库
import('Common.lib.LogMaster.LogMaster', APP_PATH, '.php');
//traceHttp();

define("encodingAesKey", "e3eeMwjjqNd1NJEt38yNwQ20qOZemPbWyDopnDGZaw1");
define("token", "IPSEN");
define("corpId", "wx72d997ae150ccf6f");
define("corpsecret", "Ia0CtpywryfU5VGNAlk23s8ctM-99v7we2HZmkVuh_szDz3dinL9aQ9gqslBTZk2");

define("logFile", "wechat_log");

class AccessController extends Controller
{

    public $sVerifyMsgSig;
    public $sVerifyTimeStamp;
    public $sVerifyNonce;
    public $sVerifyEchoStr;

    public function access()
    {
        if ($_GET['echostr']) {
            $this->valid();
        } else {
            $this->responseMsg(); //如果没有echostr，则返回消息
        }
    }

    public function valid()
    {
        $logMaster = new \LogMaster();

        $sVerifyMsgSig = I('get.msg_signature');
        $sVerifyTimeStamp = I('get.timestamp');
        $sVerifyNonce = I('get.nonce');
        $sVerifyEchoStr = I('get.echostr');

        $logMaster->tellMe(logFile, "msg_signature:{$sVerifyMsgSig}");
        $logMaster->tellMe(logFile, "timestamp:{$sVerifyTimeStamp}");
        $logMaster->tellMe(logFile, "nonce:{$sVerifyNonce}");
        $logMaster->tellMe(logFile, "echostr:{$sVerifyEchoStr}");

        // 需要返回的明文
        $sEchoStr = "";

        $wxcpt = new \WXBizMsgCrypt(token, encodingAesKey, corpId);
        $errCode = $wxcpt->VerifyURL($sVerifyMsgSig, $sVerifyTimeStamp, $sVerifyNonce, $sVerifyEchoStr, $sEchoStr);
        if ($errCode == 0) {
            //
            // 验证URL成功，将sEchoStr返回
            // HttpUtils.SetResponce($sEchoStr);
            $logMaster->tellMe(logFile, "返回\n" . $sEchoStr);
            print($sEchoStr);

        } else {
            $logMaster->tellMe(logFile, "错误码\n" . $errCode);
            print("ERR: " . $errCode . "\n\n");
        }
    }

    public function responseMsg()
    {
        $sVerifyMsgSig = I('get.msg_signature');
        $sVerifyTimeStamp = I('get.timestamp');
        $sVerifyNonce = I('get.nonce');
//        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
//        取得XML数据包信息
        $sReqData = file_get_contents("php://input");
        $wxcpt = new \WXBizMsgCrypt(token, encodingAesKey, corpId);
        //decrypt
        $sMsg = "";  //解析之后的明文
        $errCode = $wxcpt->DecryptMsg($sVerifyMsgSig, $sVerifyTimeStamp, $sVerifyNonce, $sReqData, $sMsg);
        if ($errCode == 0) {
            if (!empty($sMsg)) {
                $postObj = simplexml_load_string($sMsg, 'SimpleXMLElement', LIBXML_NOCDATA);
                if (trim($postObj->MsgType) == "text") {
                    $this->responseText($postObj);
                } else if (trim($postObj->MsgType) == "event") {
                    $this->responseEvent($postObj);
                }
            }
        } else {
            exit($errCode);
        }

    }

    public function responseText($postObj)
    {
        switch ($postObj->Content) {
            case "1":
                $this->returnText($postObj, $postObj->FromUserName);
                break;
            default :
                $mycontent = "您的输入无效，本应用仅用来推送设备信息，如有其他需求，请移步至相应应用下！";
                $this->returnText($postObj, $mycontent);
                break;
        }

    }

    public function responseEvent($postObj)
    {
        if ($postObj->Event == "click") {
            switch ($postObj->EventKey) {
                case 'my_equipment':
//                    $this->returnText($postObj, '帮助页面跳转～待开发');
                    $this->returnText($postObj, $this->read($postObj->FromUserName));
                    break;
                case 'help':
                    $this->returnText($postObj, '帮助页面跳转～待开发');
                    break;
            }
        }
    }

    public function returnText($postObj, $content)
    {
        $wxcpt = new \WXBizMsgCrypt(token, encodingAesKey, corpId);
        $mytpl = new Mytpl;
        $text_mes = array("ToUserName" => $postObj->FromUserName, "FromUserName" => corpId, "CreateTime" => $this->sVerifyTimeStamp, "Content" => $content);
        $sRespData = $mytpl->text_tpl($text_mes);
        $sEncryptMsg = ""; //xml格式的密文
        $errCode = $wxcpt->EncryptMsg($sRespData, $this->sVerifyTimeStamp, $this->sVerifyNonce, $sEncryptMsg);
        if ($errCode == 0) {
            exit($sEncryptMsg);
        } else {
            exit($errCode);
        }
    }

    public function read($userid = null)
    {
        $result = file_get_contents("Public/file/" . $userid . ".txt");
        return $result;
    }

    /**
     * @function 处理微发来的消息时间
     * @param $object
     * @return string
     */
    function catchEvent($object)
    {
        //对象为空时要向微信返回空字符串
        if (empty($object))
            echo '';
        switch ($object->MsgType) {
            case 'text': {
                echo $this->transmitText($object, "文本消息");
                exit();
                break;
            }
            case 'event': {
                switch ($object->Event) {
                    case 'subscribe': {
                        echo $this->transmitText($object, "关注");
                        exit();
                        break;
                    }
                    case 'CLICK': {
                        echo $this->transmitText($object, "点击");
                        exit();
                        break;
                    }
                }
                break;
            }
            default:
                echo '';
                break;
        }
    }

    /**
     * @function 回复图文消息
     * @param $object
     * @param $newsArray
     * @return string
     */
    function transmitNews($object, $newsArray)
    {
        if (!is_array($newsArray)) {
            return '';
        }
        $itemTpl = "<item>
                        <Title><![CDATA[%s]]></Title>
                        <Description><![CDATA[%s]]></Description>
                        <PicUrl><![CDATA[%s]]></PicUrl>
                        <Url><![CDATA[%s]]></Url>
                </item>";
        $item_str = "";
        foreach ($newsArray as $item) {
            $item_str .= sprintf($itemTpl, $item['Title'], $item['Description'], $item['PicUrl'], $item['Url']);
        }
        $xmlTpl = "<xml>
                    <ToUserName><![CDATA[%s]]></ToUserName>
                    <FromUserName><![CDATA[%s]]></FromUserName>
                    <CreateTime>%s</CreateTime>
                    <MsgType><![CDATA[news]]></MsgType>
                    <ArticleCount>%s</ArticleCount>
                    <Articles>
                        $item_str
                    </Articles>
               </xml>";

        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time(), count($newsArray));
        return $result;
    }

    /**
     * @function 回复文字消息
     * @param $object
     * @param $contentStr
     * @return string
     */
    function transmitText($object, $contentStr)
    {
        $textTpl = "<xml>
							<ToUserName><![CDATA[%s]]></ToUserName>
							<FromUserName><![CDATA[%s]]></FromUserName>
							<CreateTime>%s</CreateTime>
							<MsgType><![CDATA[%s]]></MsgType>
							<Content><![CDATA[%s]]></Content>
							</xml>";
        return sprintf($textTpl, $object->FromUserName, $object->ToUserName, time(), 'text', $contentStr);
    }

    function traceHttp()
    {
        logger("REMOTE_ADDR:" . $_SERVER["REMOTE_ADDR"] . ((strpos($_SERVER["REMOTE_ADDR"], "101.226")) ? "From WeiXin" : "Unkonow IP"));
        logger("QUERY_STRING:" . $_SERVER["QUERY_STRING"]);
    }

    function logger($content)
    {
        file_put_contents("log.html", date('Y-m-d H:i:s  ') . $content . "<br>", FILE_APPEND);
    }

}

class Mytpl
{
    public function text_tpl($text_mes)
    {
        $str = "<xml>
                <ToUserName><![CDATA[" . $text_mes['ToUserName'] . "]]></ToUserName>
                <FromUserName><![CDATA[" . $text_mes['FromUserName'] . "]]></FromUserName>
                <CreateTime>" . $text_mes['CreateTime'] . "</CreateTime>
                <MsgType><![CDATA[text]]></MsgType>
                <Content><![CDATA[" . $text_mes['Content'] . "]]></Content>
            </xml>";
        return $str;
    }

    public function news_tpl($mes, $news_mes)
    {
        $allNews = "";
        foreach ($news_mes as $news) {
            $allNews .= "<item>
                           <Title><![CDATA[" . $news['Title'] . "]]></Title>
                           <Description><![CDATA[" . $news['Description'] . "]]></Description>
                           <PicUrl><![CDATA[" . $news['PicUrl'] . "]]></PicUrl>
                           <Url><![CDATA[" . $news['Url'] . "]]></Url>
                       </item>";
        }

        $str = "<xml>
               <ToUserName><![CDATA[" . $mes['ToUserName'] . "]]></ToUserName>
               <FromUserName><![CDATA[" . $mes['FromUserName'] . "]]></FromUserName>
               <CreateTime>" . $mes['CreateTime'] . "</CreateTime>
               <MsgType><![CDATA[news]]></MsgType>
               <ArticleCount>" . count($news_mes) . "</ArticleCount>
               <Articles>
                   " . $allNews . "
               </Articles>
            </xml>";
        return $str;
    }
}
