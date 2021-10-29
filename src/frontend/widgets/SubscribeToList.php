<?php

namespace bvb\mailchimp\frontend\widgets;

use bvb\mailchimp\api\ApiModule;
use bvb\mailchimp\frontend\MailchimpModule;
use bvb\mailchimp\frontend\models\SubscribeToListForm;
use yii\base\Widget;
use yii\helpers\ArrayHelper;;

/**
 * SubscribeToList displays a form with a slot for a user's email to subscribe
 * them to a specified list
 */
class SubscribeToList extends Widget
{
    /**
     * ID of the list/audience in MailChimp that a user will sign up for by
     * supplying their email address
     * @var string
     */
    public $listId;

    /**
     * The model representing the intake of a user's email address to subscribe
     * them to a list
     * @var \bvb\mailchimp\frontend\models\SubscribeToListForm
     */
    public $subscribeToListForm;

    /**
     * The ID of the MailchimpModule that is needed to determine the URL endpoint
     * used to add an email address to a list
     * @var string
     */
    public $moduleId = MailchimpModule::DEFAULT_ID;

    /**
     * The ID of the API module that is needed to determine the URL endpoint
     * used to add an email address to a list
     * @var string
     */
    public $apiModuleId = ApiModule::DEFAULT_ID;

    /**
     * Label to be used to render the email input. Defaults to the value on the
     * model
     * @var string
     */
    public $label;

    /**
     * Hint to be used to render the email input. Defaults to the value on the
     * model
     * @var string
     */
    public $hint;

    /**
     * @var array Passed into Html::submitButton()
     */
    public $buttonOptions = [];

    /**
     * @var array Default values passed into Html::submitButton()
     */
    static $defaultButtonOptions = [
        'class' => ['btn btn-primary']
    ];

    /**
     * Displays the form
     * {@inheritdoc}
     */ 
    public function run()
    {
        return $this->render('subscribe-to-list', [
            'apiModuleId' => $this->apiModuleId,
            'hint' => $this->hint,
            'label' => $this->label,
            'moduleId' => $this->moduleId,
            'subscribeToListForm' => $this->getSubscribeToListForm(),
            'buttonOptions' => ArrayHelper::merge(self::$defaultButtonOptions, $this->buttonOptions)
        ]);
    }

    /**
     * Getter for [[$subscribeToListForm]] and will create a new instance if 
     * none is supplied during widget initialization
     * @var \bvb\mailchimp\frontend\models\SubscribeToListForm
     */
    public function getSubscribeToListForm()
    {
        if(empty($this->subscribeToListForm)){
            $this->subscribeToListForm = new SubscribeToListForm([
                'listId' => $this->listId
            ]);
        }
        return $this->subscribeToListForm;
    }
}