<?php
/** @var $subscribeToListForm \bvb\mailchimp\frontend\models\SubscribeToListForm */
/** @var $moduleId string */
/** @var $apiModuleId string */
/** @var $label string */
/** @var $hint string */
/** @var $buttonOptions array */

use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

$form = ActiveForm::begin();

// --- This may look dumb as fuck, because it is. Setting `errorOptions`
// --- here overrides and container configurations or the original class
// --- configuration for that attribute. So, to prevent apps from losing
// --- their configuration we need to get the default options by first
// --- instantiating the field, then merge those defaults with the encode
// --- option. Unfortunately using ActiveField::error() doesn't allow
// --- for changing the `encode` since the JS that is registered is done
// --- when the field is instantiated. So, we literally need to instantiate
// --- it twice to get the defaults, the merge in our desired changes with
// --- those defaults and create it again. Dumb as fuck but okay.
$emailField = $form->field($subscribeToListForm, 'email');
$defaultErrorOptions = $emailField->errorOptions;
$emailField = $form->field($subscribeToListForm, 'email', ['errorOptions' => ArrayHelper::merge($defaultErrorOptions, ['encode' => false])]);


// --- Apply any label/hint passed into the widget
if($label === false) {
    $emailField->label(false);
}
if(is_string($label)) {
    $emailField->label($label);
}
if($hint === false) {
    $emailField->hint(false);
}
if(is_string($hint)) {
    $emailField->hint($hint);
}

echo $emailField->input('email', ['placeholder' => 'Enter Email Address']);
echo Html::activeHiddenInput($subscribeToListForm, 'listId');
?>
    <div id="<?= $form->getId(); ?>-success-message" class="success-message-container"></div>
    <?php echo Html::submitButton('Subscribe', $buttonOptions);
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
                email: $(this)[0].querySelector('[type=email').value,
                listId: $(this)[0].querySelector('[name*=listId').value,
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
        }).catch(err => {
            console.error(err);
            $('#{$form->getId()}').yiiActiveForm('updateAttribute', '{$emailInputId}', ["Server error"]);
        });
    return false;
});
JAVASCRIPT;
$this->registerJs($readyJs);
