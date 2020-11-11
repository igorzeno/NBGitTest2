<?php

namespace frontend\models;
use yii;

class Test 
{
    public static function getList(){
        $sql = 'SELECT * FROM test';
        return Yii::$app->db->createCommand($sql)->queryAll();
    }
}