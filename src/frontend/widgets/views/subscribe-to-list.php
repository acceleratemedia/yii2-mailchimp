<?php

/* @var $subscribeToListForm \bvb\mailchimp\frontend\models\SubscribeToListForm */
/* @var $moduleId string */
/* @var $apiModuleId string */
/* @var $label string */
/* @var $hint string */

use bvb\mailchimp\frontend\MailchimpModule;
use kartik\form\ActiveForm;
use yii\helpers\Html;
use yii\helpers\Url;

$form = ActiveForm::begin();
    $emailField = $form->field($subscribeToListForm, 'email');

    // --- Apply any label/hint passed into the widget
    if($label === false){
        $emailField->label(false);
    }
    if(is_string($label)){
        $emailField->label($label);
    }
    if($hint === false){
        $emailField->hint(false);
    }
    if(is_string($hint)){
        $emailField->hint($hint);
    }

    echo $emailField->input('email', ['placeholder' => 'Enter Email Address']);
    echo Html::activeHiddenInput($subscribeToListForm, 'listId'); ?>
    <div id="<?= $form->getId(); ?>-success-message" class="success-message-container"></div>
    <?php echo Html::submitButton('Subscribe', ['class' => 'btn btn-primary']);
ActiveForm::end();

$emailInputId = Html::getInputId($subscribeToListForm, 'email');
$listIdInputId = Html::getInputId($subscribeToListForm, 'listId');
$subscribeToListUrl = Url::to(['/'.$moduleId.'/'.$apiModuleId.'/v1/lists/subscribe']);
$csrfParam = Yii::$app->request->csrfParam;
$csrfToken = Yii::$app->request->csrfToken;
$readyJs = <<<JAVASCRIPT
$("#{$form->getId()}").on("beforeSubmit", function(e){
    fetch("{$subscribeToListUrl}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                email: $("#{$emailInputId}").val(),
                listId: $("#{$listIdInputId}").val(),
                $csrfParam: "{$csrfToken}"
            })
        })
        .then(response => Promise.all([response.ok, response.json()]))
        .then(([responseOk, body])  => {
            if(!responseOk || !body.success){
                if(body.message){
                    $('#{$form->getId()}').yiiActiveForm('updateAttribute', '{$emailInputId}', [body.message]);
                } else {
                    $('#{$form->getId()}').yiiActiveForm('updateAttribute', '{$emailInputId}', ["Unknown error"]);
                }
            } else {
                $('#{$form->getId()}-success-message').html("Sign-up successful");
            }
        });
    return false;
});
JAVASCRIPT;
$this->registerJs($readyJs);