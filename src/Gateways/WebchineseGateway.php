<?php

/*
 * This file is part of the overtrue/easy-sms.
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Overtrue\EasySms\Gateways;

use Overtrue\EasySms\Contracts\MessageInterface;
use Overtrue\EasySms\Contracts\PhoneNumberInterface;
use Overtrue\EasySms\Exceptions\GatewayErrorException;
use Overtrue\EasySms\Support\Config;
use Overtrue\EasySms\Traits\HasHttpRequest;

/**
 * Class WebchineseGateway.
 *
 * @see http://www.smschinese.cn/api.shtml
 * @author hiscaler <hiscaler@gmail.com>
 */
class WebchineseGateway extends Gateway
{

    use HasHttpRequest;

    const ENDPOINT_TEMPLATE = 'http://%s.api.smschinese.cn/?Uid=%s&Key=%s&smsMob=%s&smsText=%s';

    /**
     * 错误信息
     *
     * @return array
     */
    private static function errors()
    {
        return array(
            '-1' => '没有该用户账户',
            '-2' => '接口密钥不正确',
            '-21' => 'MD5接口密钥加密不正确',
            '-3' => '短信数量不足',
            '-11' => '该用户被禁用',
            '-14' => '短信内容出现非法字符',
            '-4' => '手机号格式不正确',
            '-41' => '手机号码为空',
            '-42' => '短信内容为空',
            '-51' => '短信签名格式不正确',
            '-6' => 'IP限制',
        );
    }

    /**
     * @param \Overtrue\EasySms\Contracts\PhoneNumberInterface $to
     * @param \Overtrue\EasySms\Contracts\MessageInterface $message
     * @param \Overtrue\EasySms\Support\Config $config
     *
     * @return array
     *
     * @throws \Overtrue\EasySms\Exceptions\GatewayErrorException ;
     */
    public function send(PhoneNumberInterface $to, MessageInterface $message, Config $config)
    {
        $endpoint = $this->buildEndpoint($to, $message, $config);

        /* @var $result string|array */
        $result = $this->request('get', $endpoint);

        if ($result === false) {
            throw new GatewayErrorException('连接短信发送服务器出错。', 500);
        } elseif ($result < 0) {
            $errors = self::errors();
            throw new GatewayErrorException(isset($errors[$result]) ? $errors[$result] : '未知错误', 400);
        }

        return $result;
    }

    /**
     * Build endpoint url.
     *
     * @param PhoneNumberInterface $to
     * @param MessageInterface $message
     * @param \Overtrue\EasySms\Support\Config $config
     *
     * @return string
     */
    protected function buildEndpoint(PhoneNumberInterface $to, MessageInterface $message, Config $config)
    {
        $charset = $config->get('charset', 'utf8');
        if (!in_array($charset, ['utf8', 'gbk'])) {
            $charset = 'utf8';
        }

        return sprintf(self::ENDPOINT_TEMPLATE, $charset, $config->get('uid'), $config->get('key'), $to->getNumber(), $message->getContent());
    }

}
