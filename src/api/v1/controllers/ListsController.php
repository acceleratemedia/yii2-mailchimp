<?php

namespace bvb\mailchimp\api\v1\controllers;

use bvb\mailchimp\common\helpers\ApiHelper;
use Yii;
use yii\helpers\Json;
use yii\web\Controller;

/**
 * RestrictedFileController gives access to restricted files in the backend
 * and implements AdminAccess to only allow admins to access them
 */
class ListsController extends Controller
{
    /**
     * Attempts to subscribe the provided email to the provided list
     * @return mixed
     */
    public function actionSubscribe()
    {
        $success = false;
        $message = '';

        $listId = Yii::$app->request->post('listId');
        $email = Yii::$app->request->post('email');

        try {
            $response = ApiHelper::getSingleton()->getClient()->lists->addListMember($listId, [
                'email_address' => $email,
                'status' => "subscribed",
            ]);
            $success = true;
        } catch (\MailchimpMarketing\ApiException $e) {
            Yii::error($e);
            $message =  $e->getMessage();
            
        } catch(\GuzzleHttp\Exception\ClientException $e){
            $errorContents = Json::decode($e->getResponse()->getBody()->getContents());
            if(isset($errorContents['title']) && $errorContents['title'] == 'Member Exists'){
                $message = 'This email address is already subscribed to this list.';
            } else {
                Yii::error($e);
                $message = 'Error connecting to service.';
            }
        }

        return [
            'success' => $success,
            'message' => $message,
        ];
    }
}
