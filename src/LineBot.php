<?php
namespace ArmKrit;
class LineBot {
    private $token;
    private $secretId;

    private $replyToken;
    private $uid;
    private $str = NULL;

    private $messages = [];

    function __construct($token, $secretId) {
        $this->token = $token;
        $this->secretId = $secretId;
    }

    public function __call($name,$arg){
        call_user_func($name,$arg);
    }

    function setUser($replyToken, $uid) {
        $this->replyToken = $replyToken;
        $this->uid = $uid;
        return $this;
    }

    public function addText($text){
       $this->messages[] = [
                    "type" => "text",
                    "text" => "{$text}"
        ];
        return $this;
    }

    public function addCarousel($name, $contents = []){

        $this->messages[] =  [
                "type" => "flex",
                "altText" => $name,
                "contents" => array(
                    'type' => 'carousel',
                    'contents' => $contents,
                ),
        ];

        return $this;

    }

    public function addTexts($tests = []){

        foreach($tests as $text){
            $this->messages[] = [
                "type" => "text",
                "text" => "{$text}"
            ];
        }

        return $this;
    }

    public function push(){

        $data = [
            "to" => $this->uid,
            "messages" => $this->messages
        ];

        return $this->sendPushMessage($data);
    }

    public function reply(){

        $data = [
            "replyToken" => $this->replyToken,
            "messages" => $this->messages

        ];

        return $this->sendReplyMessage($data);
    }

     public function getJson(){

        $data = [
            "to" => $this->uid,
            "replyToken" => $this->replyToken,
            "messages" => $this->messages
        ];

        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }


     public function getUID(){
        return $this->uid;
    }


    private function sendPushMessage($data)
    {
      try {
        $post_body = json_encode($data, JSON_UNESCAPED_UNICODE);

        $url = 'https://api.line.me/v2/bot/message/push';
        $accssToken = $this->token;
        $post_header = array('Content-Type: application/json', 'Authorization: Bearer ' . $accssToken);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $post_header);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_body);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
        } catch (\Exception $e) {
            return $e;
        }
    }

        public function sendReplyMessage($data)
    {
        try {
            $post_body = json_encode($data, JSON_UNESCAPED_UNICODE);
            $url = 'https://api.line.me/v2/bot/message/reply';
            $accssToken = $this->token;
            $post_header = array('Content-Type: application/json', 'Authorization: Bearer ' . $accssToken);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $post_header);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_body);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            $result = curl_exec($ch);
            curl_close($ch);

            return $result ? $result : 'nodata';
        } catch (\Exception $e) {
            return $e;
        }
    }

}
