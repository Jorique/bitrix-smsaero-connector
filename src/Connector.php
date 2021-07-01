<?php

namespace Jorique\BitrixSmsAeroConnector;

use Bitrix\Main\Application;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;
use Bitrix\MessageService\Sender\BaseConfigurable;
use Bitrix\MessageService\Sender\Result\MessageStatus;
use Bitrix\MessageService\Sender\Result\SendMessage;

class Connector extends BaseConfigurable
{
    public static function isSupported()
    {
        return true;
    }

    public static function onGetSmsSenders()
    {
        return [new static];
    }

    public function getId()
    {
        return 'smsaero';
    }

    public function getName()
    {
        return "SMSAero";
    }

    public function getShortName()
    {
        return 'SMSAero';
    }

    /**
     * Флаг того, настроен ли шлюз и готов к работе
     * @return false
     */
    public function isRegistered()
    {
        $login = $this->getOption('login');
        $apiKey = $this->getOption('api_key');

        return $login && $apiKey;
    }

    public function register(array $fields)
    {
        return [];
    }

    public function getOwnerInfo()
    {
        return [];
    }

    public function getExternalManageUrl()
    {
        return 'https://domain.tld';
    }

    public function getMessageStatus(array $messageFields)
    {
        $res = new MessageStatus;

        return $res;
    }

    public function getFromList()
    {
        $result = $this->callExternalMethod('sign/list', []);

        if ($result->isSuccess()) {
            $from = [];
            $resultData = $result->getData();
            foreach ($resultData['data'] as $sender) {
                if (is_array($sender)) {
                    $from[] = [
                        'id' => $sender['name'],
                        'name' => $sender['name'],
                    ];
                }
            }

            return $from;
        }

        return [];
    }

    public function sendMessage(array $messageFields)
    {
        $result = new SendMessage;

        $apiResult = $this->callExternalMethod('sign/list', []);
        $resultData = $apiResult->getData();

        if (!$apiResult->isSuccess()) {
            if (!$resultData['success']) {
                $result->setStatus(MessageService\MessageStatus::DEFERRED);
                $result->addError(new Error($this->getErrorMessage(0)));
            } else {
                $result->addErrors($apiResult->getErrors());
            }
        } else {
            $sign = $resultData['data'][0]['name'];
            $params = array(
                'number' => $messageFields['MESSAGE_TO'],
                'text' => $messageFields['MESSAGE_BODY'],
                'sign' => $sign
            );

            $apiResult = $this->callExternalMethod('sms/send', $params);
            $resultData = $apiResult->getData();

            if (!$apiResult->isSuccess()) {
                if (!$resultData['success']) {
                    $result->setStatus(MessageService\MessageStatus::DEFERRED);
                    $result->addError(new Error($this->getErrorMessage(0)));
                } else {
                    $result->addErrors($apiResult->getErrors());
                }
            } else {
                $smsData = $resultData['data'];

                if (isset($smsData['id'])) {
                    $result->setExternalId($smsData['id']);
                }
                $result->setAccepted();
            }
        }

        return $result;
    }

    private function callExternalMethod($method, $params)
    {
        $url = 'https://gate.smsaero.ru/v2/'.$method;
        $login = $this->getOption('login');
        $apiKey = $this->getOption('api_key');

        $httpClient = new HttpClient(array(
            "socketTimeout" => 10,
            "streamTimeout" => 30,
            "waitResponse" => true,
        ));
        $httpClient->setCharset('UTF-8');
        $httpClient->setHeader('User-Agent', 'Bitrix24');
        $httpClient->setHeader('Accept', 'application/json');
        $httpClient->setAuthorization($login, $apiKey);

        $isUtf = Application::getInstance()->isUtfMode();

        if (!$isUtf)
        {
            $params = \Bitrix\Main\Text\Encoding::convertEncoding($params, SITE_CHARSET, 'UTF-8');
        }

        $result = new Result();
        $answer = array();

        if ($httpClient->query(HttpClient::HTTP_POST, $url, $params) && $httpClient->getStatus() == '200')
        {
            $answer = $this->parseExternalAnswer($httpClient->getResult());
        }

        if (!$answer['success'])
        {
            $result->addError(new Error($this->getErrorMessage(0, $answer)));
        }
        $result->setData($answer);

        return $result;
    }

    private function parseExternalAnswer($httpResult)
    {
        try
        {
            $answer = Json::decode($httpResult);
        }
        catch (\Bitrix\Main\ArgumentException $e)
        {
            $data = explode(PHP_EOL, $httpResult);
            $code = (int)array_shift($data);
            $answer = $data;
            $answer['status_code'] = $code;
            $answer['status'] = $code === 100 ? 'OK' : 'ERROR';
        }

        if (!is_array($answer) && is_numeric($answer))
        {
            $answer = array(
                'status' => $answer === 100 ? 'OK' : 'ERROR',
                'status_code' => $answer
            );
        }

        return $answer;
    }

    private function getErrorMessage($errorCode, $answer = null)
    {
        return 'Неизвестная ошибка';
    }
}