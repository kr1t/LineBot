<?php
namespace Krit;

class LineBot
{
    private $token;
    private $secretId;

    private $replyToken;
    private $uid = [];

    private $messages = [];

    private $userClass = "App\\User";
    private $userModel;

    public function __construct($token = null, $secretId = null)
    {
        $this->setConfig($token, $secretId);
        $this->userModel = new $this->userClass;
    }

    public function __call($name, $arg)
    {
        call_user_func($name, $arg);
    }

    public function setConfig($token = null, $secretId = null)
    {
        $this->token = $token;
        $this->secretId = $secretId;
        return $this;
    }

    public function setUserClass($uclass)
    {
        $this->userClass = $uclass;
        $this->userModel = new $this->userClass;
        return $this;
    }

    public function setReplyToken($replyToken)
    {
        $this->replyToken = $replyToken;
        return $this;
    }

    public function setUser($uid)
    {
        foreach (array($uid) as $key => $id) {
            $this->uid[] = $id;
        }
        return $this;
    }

    public function addCarousel($title, $contents = [])
    {

        $this->messages[] = [
            "type" => "flex",
            "altText" => $title,
            "contents" => array(
                'type' => 'carousel',
                'contents' => $contents,
            ),
        ];

        return $this;
    }

    public function addBubble($title, $content)
    {

        $this->messages[] = [
            "type" => "flex",
            "altText" => $title,
            "contents" => $content,
        ];

        return $this;

    }

    public function addText($text)
    {

        foreach ((array) $text as $key => $text) {
            $this->messages[] = [
                "type" => "text",
                "text" => "{$text}",
            ];
        }

        return $this;
    }

    public function addImage($image)
    {

        foreach ((array) $image as $key => $image_url) {
            $this->messages[] = [
                "type" => "image",
                "originalContentUrl" => "{$image_url}",
                "previewImageUrl" => "{$image_url}",
            ];
        }
        return $this;
    }

    public function addLocation($title, $address, $lat, $long)
    {

        $this->messages[] = [
            "type" => "location",
            "title" => $title,
            "address" => $address,
            "lat" => $lat,
            "long" => $long,
        ];

        return $this;
    }

    public function addImageURI($title, $image_url, $uri, $rX = 29, $rY = 24)
    {

        $this->addBubble(
            $title,
            [
                'type' => 'bubble',
                'hero' => [
                    'type' => 'image',
                    'url' => $image_url,
                    'size' => 'full',
                    'aspectRatio' => "{$rX}:{$rY}",
                    'aspectMode' => 'cover',
                    'action' => [
                        'type' => 'uri',
                        'uri' => $uri,
                    ],
                ],
            ],
        );

        return $this;
    }

    public function addImageScanQR($title, $image_url, $rX = 29, $rY = 24)
    {

        $this->addBubble(
            $title,
            [
                'type' => 'bubble',
                'hero' => [
                    'type' => 'image',
                    'url' => $image_url,
                    'size' => 'full',
                    'aspectRatio' => "{$rX}:{$rY}",
                    'aspectMode' => 'cover',
                    'action' => [
                        'type' => 'uri',
                        'uri' => 'https://line.me/R/nv/QRCodeReader',
                    ],
                ],
            ],
        );

        return $this;
    }

    public function addImageText($title, $image_url, $text, $rX = 29, $rY = 24)
    {

        $this->addBubble(
            $title,
            [
                'type' => 'bubble',
                'hero' => [
                    'type' => 'image',
                    'url' => $image_url,
                    'size' => 'full',
                    'aspectRatio' => "{$rX}:{$rY}",
                    'aspectMode' => 'cover',
                    'action' => [
                        'type' => 'message',
                        'text' => $text,
                    ],
                ],
            ],
        );

        return $this;
    }

    public function push()
    {
        $uid = $this->uid;
        $messages = $this->messages;

        if (!count($uid)) {
            return ["need" => "::setUser()"];
        } else if (count($uid) == 1) {
            $data = [
                "to" => $uid[0],
                "messages" => $messages,
            ];
            $response = $this->sendPushMessage($data);
        } else {
            $data = [
                "to" => $uid,
                "messages" => $messages,
            ];
            $response = $this->sendMultiCastMessage($data);
        }

        return $response;
    }

    public function reply()
    {
        $replyToken = $this->replyToken;
        $messages = $this->messages;

        if (!$replyToken) {
            return ["need" => "::setReplyToken()"];
        }

        $data = [
            "replyToken" => $replyToken,
            "messages" => $messages,
        ];

        return $this->sendReplyMessage($data);
    }

    public function toJSON()
    {

        $data = [
            "to" => $this->uid,
            "replyToken" => $this->replyToken,
            "messages" => $this->messages,
        ];

        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    public function getUser($column = 'line_user_id')
    {
        $uid = $this->uid;

        if (!count($uid)) {
            return ["need" => "::setUser()"];
        }

        $userModel = $this->userModel;
        $user = $userModel->where($column, $uid[0])->first();
        return $user;
    }

    public function getUsers($column = 'line_user_id')
    {
        $uid = $this->uid;

        if (!count($uid)) {
            return ["need" => "::setUser(['1','2'])"];
        }

        $userModel = $this->userModel;
        $users = $userModel->where(function ($q) use ($column, $uid) {
            foreach ($uid as $uid) {
                $q->orWhere($column, 'like', $uid);
            }
        })->get();
        return $users;
    }

    //////////////////////// HTTP ////////////////////////
    private $lineAPIBaseUrl = "https://api.line.me/v2/bot/message";

    private function curlHttpPost($urlName, $post_body = null)
    {
        $url = "{$this->lineAPIBaseUrl}/{$urlName}";
        $accssToken = $this->token;
        $header = array('Content-Type: application/json', 'Authorization: Bearer ' . $accssToken);

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        if ($post_body) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $post_body);
        }
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        $result = curl_exec($curl);
        curl_close($curl);
        return $result;
    }
    private function curlHttpGet($urlName)
    {

        $url = "{$this->lineAPIBaseUrl}/{$urlName}";
        $accssToken = $this->token;
        $header = array('Content-Type: application/json', 'Authorization: Bearer ' . $accssToken);

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => $header,
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;

    }

    private function sendPushMessage($data)
    {

        try {
            $post_body = json_encode($data, JSON_UNESCAPED_UNICODE);
            return $this->curlHttpPost('push', $post_body);

        } catch (\Exception $e) {
            return $e;
        }
    }

    private function sendMultiCastMessage($data)
    {

        try {
            $post_body = json_encode($data, JSON_UNESCAPED_UNICODE);
            return $this->curlHttpPost('multicast', $post_body);

        } catch (\Exception $e) {
            return $e;
        }
    }

    public function sendReplyMessage($data)
    {
        try {
            $post_body = json_encode($data, JSON_UNESCAPED_UNICODE);
            return $this->curlHttpPost('reply', $post_body);

        } catch (\Exception $e) {
            return $e;
        }
    }

    public function getQuota()
    {
        try {
            return $this->curlHttpGet('quota');
        } catch (\Exception $e) {
            return $e;
        }
    }

    public function getQuotaConsumption()
    {
        try {
            return $this->curlHttpGet('quota/consumption');
        } catch (\Exception $e) {
            return $e;
        }
    }

}
