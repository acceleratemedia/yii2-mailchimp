<?php

namespace bvb\mailchimp\console\controllers;

use bvb\mailchimp\common\helpers\ApiHelper;
use DateTime;
use Yii;
use yii\base\InvalidConfigException;

/**
 * ListController is for console commands for List objects in Mailchimp 
 */
class ListController extends \yii\console\Controller
{
    /**
     * @var array Closure that is run and returns an array with key-value pairs
     * with key being the name of the merge field in Mailchimp and value being
     * the value. This function is passed the $user found in the system with the
     * same email address.
     * ```php
     * function($user){
     *     // --- Figure out the value of the field
     *     return [
     *         'FNAME' => $user->first_name,
     *         'LNAME' => $user->last_name,
     *         'member' => $user->isMember,
     *         ...
     *     ];
     * }
     * ```
     * This array should be set in the configuration for the module
     * https://mailchimp.com/help/manage-audience-signup-form-fields/#Add_and_delete_fields_in_the_audience_settings
     */
    public $memberSyncFunction;

    /**
     * Syncs merge fields data in Mailchimp with the data desired by the application
     * @param string $listId ID of the list we are syncing members on in Mailchimp
     * @param integer $limit How many members to process/sync
     * @param int $minSecondsBetweenSyncs The minimum number of seconds to wait
     * between before syncing a user again. This depends on the LASTSYNC merge
     * field being set up in Mailchimp
     * @return integer
     */
    public function actionMembersSync($listId, $limit = 1000, $minSecondsBetweenSyncs = 604800)
    {
        if(empty($this->memberSyncFunction)){
            throw new InvalidConfigException('$memberSyncFunction must be set on ListController to return merge field values');
        }

        Yii::$app->reporting->startReport(['title' => 'Syncing Mailchimp Contacts']);

        Yii::$app->reporting->addInfo('Starting to sync members with arguments $listId='.$listId.' amd $limit='.$limit);

        $response = ApiHelper::getSingleton()->getClient()->lists->getList($listId);

        $page = $numProcessed = $numWithNoUserAccount = $numSyncedRecently = 0;
        $pageSize = 100;
        Yii::$app->reporting->addInfo('Found list '.$response->name.' with '.$response->stats->member_count.' members. Aiming to process '.$limit.' total members by querying for '.$pageSize.' at a time');

        $syncDateSubmitFormat = (new DateTime)->format('m/d/Y');
        $syncDateResponseFormat = (new DateTime)->format('Y-m-d');

        do{
            Yii::$app->reporting->startGroup();
            $offset = $page * $pageSize;

            try{
                Yii::$app->reporting->addInfo('Requesting from API for list members with $pageSize='.$pageSize.' and $offset='.$offset);

                $response = ApiHelper::getSingleton()->getClient()->lists->getListMembersInfo(
                    $listId, 
                    $fields = null,
                    $excludeFields = null, 
                    $pageSize,
                    $offset
                );

                if(empty($response->members))                {
                    Yii::$app->reporting->addNotice('No members returned from API request, quitting.');
                    Yii::$app->reporting->endGroup();
                    break;
                }

                Yii::$app->reporting->addInfo('API call returned '.count($response->members).' members to sync.');

                foreach($response->members as $member){
                    Yii::$app->reporting->startGroup();
                    Yii::$app->reporting->addInfo('Starting to sync information for member with email '.$member->email_address);
                    
                    // --- If we have no user, skip
                    $user = Yii::$app->user->identityClass::findOne(['email' => $member->email_address]);
                    if(
                        // --- If a member was recently synced, skip them
                        isset($member->merge_fields->LASTSYNC) &&
                        !empty($member->merge_fields->LASTSYNC) && 
                        time() - strtotime($member->merge_fields->LASTSYNC) < $minSecondsBetweenSyncs
                    ){
                        Yii::$app->reporting->addNotice('Skipping sync for this member since they were synced '.$member->merge_fields->LASTSYNC);
                        $numSyncedRecently++;
                    } else {
                        // --- Make note if no user account
                        if(empty($user)){ 
                            Yii::$app->reporting->addNotice('No user found in the system for this email');
                            $numWithNoUserAccount++;
                        }

                        // --- if we have a user and they weren't synced recently, sync them
                        $mergeFields = call_user_func($this->memberSyncFunction, $user);
                        try{
                            $response = ApiHelper::getSingleton()->getClient()->lists->updateListMember(
                                $listId,
                                $member->id,
                                ['merge_fields' => array_merge($mergeFields, ['LASTSYNC' => $syncDateSubmitFormat])]
                            );

                            if($response->merge_fields->LASTSYNC == $syncDateResponseFormat){
                                Yii::$app->reporting->addSuccess('Member synced successfully');
                                $numProcessed++;
                            } else {
                                Yii::$app->reporting->addSuccess('Member not synced based on response sync time: '.print_r($respone,true));
                            }
                        } catch (\Exception $e){
                            Yii::$app->reporting->addError($e->getMessage());            
                        }
                    }
                    Yii::$app->reporting->endGroup();
                }
            } catch(\Exception $e){
                Yii::$app->reporting->addError($e->getMessage());
            }

            Yii::$app->reporting->endGroup();
            $page++;
        } while($numProcessed < $limit);

        Yii::$app->reporting->addSummary($numWithNoUserAccount.' records from mailchimp had no user account in this application');
        Yii::$app->reporting->addSummary($numSyncedRecently.' records were skipped because they were synced recently');
        Yii::$app->reporting->addSummary($numProcessed.' records successfully synced');

        return \yii\console\ExitCode::OK;
    }
}