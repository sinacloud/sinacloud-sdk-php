<?php
namespace sinacloud\sae;

/**
 * 新浪云 MailCloud 客户端
 *
 * @copyright Copyright (c) 2017, SINA, All rights reserved.
 *
 * ```php
 * <?php
 * use sinacloud\sae\MailCloud as MailCloud;
 * use sinacloud\sae\Mail as Mail;
 *
 * **类初始化**
 *
 * $m = new MailCloud($AccessKey, $SecretKey);
 *
 * **新建一封邮件**
 *
 * $mail = new Mail('sender@example.com', 'sender', 'recivier@example.com', 'test mailcloud', 'test');
 *
 * **添加附件**
 *
 * $mail->attachments = array(array('name'=>'test.jpg', 'path'=>'/tmp/test.jpg'))
 *
 * **发送**
 *
 * $m->sendMail($mail);
 * ?>
 * ```
 */

define('DEFAULT_MAILCLOUD_ENDPOINT', 'https://mailcloud.api.sinacloud.com.cn');


class MailCloud {
    private $__accessKey;
    private $__secretKey;
    private $__endpoint;

    /**
     * 构造函数
     *
     * @param string $accessKey API Accesskey
     * @param string $secretKey API Secretkey
     * @param string $endpoint 新浪云 MailCloud 的 endpoint
     * @return void
     */
    public function __construct($accessKey, $secretKey, $endpoint=DEFAULT_MAILCLOUD_ENDPOINT) {
        $this->__accessKey = $accessKey;
        $this->__secretKey = $secretKey;
        $this->__endpoint = $endpoint;
    }

    /**
     * 发送邮件
     *
     * @param Mail mail 邮件
     * @return array 任务ID
     */
    public function sendMail($mail) {
        if (!($mail->from && $mail->to && $mail->subject && $mail->body)) {
            throw new MailCloudException("from,to,subject,body is required", 1);
        }
        if (!(filter_var($mail->from, FILTER_VALIDATE_EMAIL) && filter_var($mail->to, FILTER_VALIDATE_EMAIL))) {
            throw new MailCloudException("from,to is not valid", 1);
        }
        $data = array();
        $data['from'] = $mail->from;
        $data['from_name'] = $mail->from_name;
        $data['to'] = $mail->to;
        $data['subject'] = $mail->subject;
        $data['body'] = $mail->body;
        $data['body_type'] = $mail->body_type;
        if (is_array($mail->attachments)) {
            foreach ($mail->attachments as $att) {
                if($att['name'] && file_exists($att['path'])) {
                    $data[$att['name']] = '@'.$att['path'];
                }
            }
        }
        $ret = $this->_call('/mail/send', $data);
        if (!$ret) {
            throw new MailCloudException('api failed', 2);
        }
        if ($ret['code'] != 0) {
            throw new MailCloudException($ret['message'], $ret['code']);
        }
        return $ret['data'];
    }

    private function _call($uri, $data=false) {
        $method = "GET";
        $ch = curl_init();
        $url = sprintf('%s%s', $this->__endpoint, $uri);
        curl_setopt($ch, CURLOPT_URL, $url);

        if ($data) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            $method = "POST";
        }

        $headers = $this->_get_signed_header($method, $uri);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $txt = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        if ($info['http_code'] != 200) {
            return false;
        }
        return json_decode($txt, true);
    }

    private function _get_signed_header($method, $uri)
    {
        $a = array();
        $a[] = $method;
        $a[] = $uri;
        $timeline = time();
        $b = array('x-sae-accesskey' => $this->__accessKey, 'x-sae-timestamp' => $timeline);
        ksort($b);
        foreach ($b as $key => $value) {
            $a[] = sprintf("%s:%s", $key, $value);
        }
        $str = implode("\n", $a);
        $s = hash_hmac('sha256', $str, $this->__secretKey, true);
        $b64_s = base64_encode($s);
        $headers = array();
        $headers[] = sprintf('x-sae-accesskey:%s', $this->__accessKey);
        $headers[] = sprintf('x-sae-timestamp:%s', $timeline);
        $headers[] = sprintf('Authorization: SAEV1_HMAC_SHA256 %s', $b64_s);
        return $headers;
    }
}

/**
 *
 * @author sinacloud
 *
 * @param $from        string 发件人
 * @param $from_name   string 发件人名称
 * @param $to          string 收件人地址
 * @param $subject     string 邮件标题
 * @param $body        string 邮件正文
 * @param $body_type   string 邮件正文类型(html, plain)
 * @param $attachments array 邮件附件array("name"=>'xx.jpg', "path"=>'./attach.jpg')
 */
/**
 * MailCloud 异常类
 */
class Mail {
    /**
     * 发件人
     *
     * @var string
     * @access public
     */
    public $from;

    /**
     * 发件人名称
     *
     * @var string
     * @access public
     */
    public $from_name;

    /**
     * 收件人
     *
     * @var string
     * @access public
     */
    public $to;

    /**
     * 主题
     *
     * @var string
     * @access public
     */
    public $subject;

    /**
     * 正文
     *
     * @var string
     * @access public
     */
    public $body;

    /**
     * 正文类型('plain', 'html')
     *
     * @var string
     * @access public
     */
    public $body_type;

    /**
     * 附件
     *
     * @var array
     * @access public
     */
    public $attachments;

    /**
     * 构造函数
     *
     * @param $from        string 发件人
     * @param $from_name   string 发件人名称
     * @param $to          string 收件人地址
     * @param $subject     string 邮件标题
     * @param $body        string 邮件正文
     * @param $body_type   string 邮件正文类型(html, plain)
     * @param $attachments array 邮件附件array("name"=>'xx.jpg', "path"=>'./attach.jpg')
     */
    public function __construct($from=null, $from_name=null, $to=null, $subject=null, $body=null, $body_type='html', $attachments=null) {
        $this->from = $from;
        $this->from_name = $from_name;
        $this->to = $to;
        $this->subject = $subject;
        $this->body = $body;
        $this->body_type = $body_type;
        $this->attachments = $attachments;
    }
}

/**
 * MailCloud 异常类
 */
class MailCloudException extends \Exception {
    /**
     * 构造函数
     *
     * @param string $message 异常信息
     * @param int $code 异常码
     */
    function __construct($message, $code=0) {
        parent::__construct($message, $code);
    }
}

