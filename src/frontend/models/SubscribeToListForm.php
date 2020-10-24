<?php

namespace bvb\mailchimp\frontend\models;

use Yii;
use yii\base\Model;

/**
 * SubscribeToListForm is the model representing the intake of a user's email
 * address to subscribe them to an email list
 */
class SubscribeToListForm extends Model
{
    /**
     * Email address to subscribe to the list
     * @var string
     */
    public $email;

    /**
     * ID of the audience/list being subscribed to
     * @var string
     */
    public $listId;

    /**
     * Set the email as the one for the logged in user if there is one
     * {@inheritdoc}
     */
    public function init()
    {
        if(!Yii::$app->user->isGuest){
            $this->email = Yii::$app->user->identity->email;
        }
        return parent::init();
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['email', 'listId'], 'required'],
            [['email'], 'email'],
        ];
    }
}