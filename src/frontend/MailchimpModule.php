<?php

namespace bvb\mailchimp\frontend;

use bvb\mailchimp\api\ApiModule;
use bvb\mailchimp\api\v1\V1Module;;
use yii\base\Module;
use yii\helpers\ArrayHelper;

class MailchimpModule extends Module
{
    /**
     * Suggested default ID to use for this module when configuring it in
     * the application
     * @var string
     */
    const DEFAULT_ID = 'mailchimp';

    /**
     * Setup the frontend module to have the API module as a submodule
     * so that we can hit endpoints
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        $this->setModules(ArrayHelper::merge([
            ApiModule::DEFAULT_ID => [
                'class' => ApiModule::class,
                'modules' => [
                    'v1' => [
                        'class' => V1Module::class
                    ]
                ]
            ],
        ], $this->modules));
    }
}