<?php

namespace bvb\mailchimp\api;

use Yii;
use yii\base\Module;
use yii\web\ForbiddenHttpException;

/**
 * MailChimpModule has controllers and endpoints that are intended to be used by the
 * application via AJAX calls to make further calls to Mailchimp's API and return
 * that data for use in views
 */
class ApiModule extends Module
{
    /**
     * Default ID used to configure this module within the greater Stripe module
     * @var string
     */
    const DEFAULT_ID = 'api';

    /**
     * All requests coming in to the API module should be parsed as JSON and all
     * responses should go out as JSON
     * {@inheritdoc}
     */
    public function init()
    {
        Yii::$app->request->parsers = ['application/json' => \yii\web\JsonParser::class];
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return parent::init();
    }

    /**
     * Deny any requests without a referrer or where the source IP isn't the
     * server's IP
     * {@inheritdoc}
     */
    public function beforeAction($action)
    {
        if(
            !Yii::$app->request->getReferrer() ||
            Yii::$app->request->getUserIp() != Yii::$app->request->getRemoteIp()
        ){
            throw new ForbiddenHttpException();
        }
        return parent::beforeAction($action);
    }
}