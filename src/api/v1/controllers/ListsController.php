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
            $message =  $e->getMessage();
            Yii::error('There was a problem adding a user to a list: '.$e->getMessage());            
        } catch(\GuzzleHttp\Exception\ClientException $e){
            $errorContents = Json::decode($e->getResponse()->getBody()->getContents());
            if(isset($errorContents['title']) && $errorContents['title'] == 'Member Exists'){
                // --- Here we need to check if the user unsubscribed. If they are 
                // --- trying to resubscribe we want to give them a better response
                // --- than they are just already subscribed
                $message = 'This email address is already subscribed to this list.';
                try {
                    $response = ApiHelper::getSingleton()->getClient()->lists->getListMember($listId, $email);
                    if($response->status == 'unsubscribed'){
                        $message = 'You have already unsubscribed from this list. In order to re-subscribe ';
                        if(isset(Yii::$app->params['mailchimp']['resubscribeFormUrl'])){
                            $message .= '<a href="'.Yii::$app->params['mailchimp']['resubscribeFormUrl'].'" target="_blank">use our signup form</a>.';
                        } else {
                            $message .= 'please contact us.';
                        }
                    }
                } catch(\Throwable $t){
                    // --- Just let this go with the 'already subscribed' message
                    Yii::error(
                        'There was an error checking list member data after they seemed to attempt to resubscribe: '.$t->getMessage()."\n".
                        '$errorContents: '.print_r($t,true)
                    );
                }
            } else {
                Yii::error(
                    'There was a problem adding a user to a list: '.$e->getMessage()."\n".
                    '$errorContents: '.print_r($errorContents,true)
                );
                $message = 'Error connecting to service.';
            }
        }

        return [
            'success' => $success,
            'message' => $message,
        ];
    }
}
