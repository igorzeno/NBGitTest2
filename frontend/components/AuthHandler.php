<?php

namespace frontend\components;

use frontend\models\Auth;
use frontend\models\User;
use Yii;
use yii\authclient\ClientInterface;
use yii\helpers\ArrayHelper;

/**
 * AuthHandler handles successful authentification via Yii auth component
 */
class AuthHandler
{
    /**
     * @var ClientInterface
     */
    private $client;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    public function handle()
    {
        if(!Yii::$app->user->isGuest){
            return; 
        }
        
        $attributes = $this->client->getUserAttributes();
        
        $auth = $this->findAuth($attributes);
        if($auth){
            $user = $auth->user;
          // return \Yii::$app->user->identity->username; die;
            return Yii::$app->user->login($user);
        }
        if($user = $this->createAccount($attributes)){
            return Yii::$app->user->login($user);
        }
    }
    private function findAuth($attributes){
        $id = ArrayHelper::getValue($attributes, 'id');
        $params = [
            'source' => $this->client->getId(),
            'source_id' => (string)$id,
        ];
        return Auth::find()->where($params)->one();
    }
    
    private function createAccount($attributes){
//                echo '<pre>';
//        var_dump($attributes);
//        echo '</pre>';die;
        $id = ArrayHelper::getValue($attributes, 'id');
        $email = ArrayHelper::getValue($attributes, 'email');
        $name = ArrayHelper::getValue($attributes, 'first_name');
        
        if(User::find()->where(['email' => $email])->exists()){
            return;
        }
        $user = $this->createUser($email,$name);
        $transaction = User::getDb()->beginTransaction();
        if($user->save()){
            $auth = $this->createAuth($user->id, $id);
            if($auth->save()){
                $transaction->commit();
                return $user;
            }
        }
    }
    
    private function createUser($email, $name){
        return new User([
            'username' => $name,
            'email' => $email,
            'auth_key' => Yii::$app->security->generateRandomString(),
            'password_hash' => Yii::$app->security->generatePasswordHash(Yii::$app->security->generateRandomString()),
          //  'password_reset_token' => Yii::$app->security->generatePasswordResetToken(),
            'status' => 10,
            'created_at' => $time = time(),
            'updated_at' => $time,
        ]);  
    }
    
    private function createAuth($userId, $sourceId){
        return new Auth([
            'user_id' => $userId,
            'source' => $this->client->getId(),
            'source_id' => (string) $sourceId,
        ]);        
    }
}      