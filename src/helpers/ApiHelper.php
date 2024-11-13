<?php

namespace bvb\mailchimp\helpers;

use MailchimpMarketing\ApiClient;
use Yii;
use yii\base\InvalidConfigException;

/**
 * ApiHelper wraps MailChimps PHP marketing API allowing use of application
 * parameters to set things like default lists, keys, etc
 */
class ApiHelper extends \yii\base\BaseObject
{
    /**
     * Implement the singleton function since each class extending from this
     * by default only ever needs a single instance
     */
    use \yiiutils\Singleton;

    /**
     * API key gotten in MailChimp's account area
     * @var string
     */
    public $apiKey;

    /**
     * Server prefix is found in the URL when you are logged into your account
     * @var string
     */
    public $server;

    /**
     * Client class from MailChimp's SDK to do operations with
     * @var MailchimpMarketing\ApiClient
     */
    protected $_client;

    /**
     * Initialize the client with the keys in applicaton config
     * @return void
     */
    public function init()
    {
        if(empty($this->apiKey)) {
            if(!isset(Yii::$app->params['mailchimp']['apiKey'])) {
                throw new InvalidConfigException('To use the Mailchimp API an `apiKey` needs to be provided to the helper class or an application parameter needs to be set under [\'mailchimp\'][\'apiKey\']');
            }
            $this->apiKey = Yii::$app->params['mailchimp']['apiKey'];
        }
        if(empty($this->server)) {
            if(!isset(Yii::$app->params['mailchimp']['server'])) {
                throw new InvalidConfigException('To use the Mailchimp API an `server` needs to be provided to the helper class or an application parameter needs to be set under [\'mailchimp\'][\'server\']');
            }
            $this->server = Yii::$app->params['mailchimp']['server'];
        }

        $this->_client = new ApiClient();
        $this->_client->setConfig([
            'apiKey' => $this->apiKey,
            'server' => $this->server
        ]);

        parent::init();
    }

    /**
     * Getter for the client
     * @var MailchimpMarketing\ApiClient
     */
    public function getClient()
    {
        return $this->_client;
    }
}
