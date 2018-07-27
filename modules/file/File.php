<?php

namespace backend\modules\file;

/**
 * 文件管理模块
 * 1、多文件上传---如果在文件夹内上传，根据上传的位置，把文件设置在那个文件夹下面
 * 2、文件夹上传---如果在文件夹内上传，根据上传的位置，把文件夹和该下的文件设置在那个文件夹下面
 * 数据库设计：
 * 1、文件表---（自增ID，文件名，所属文件夹ID，是否是文件夹，文件大小，）
 * 2、用户表
 * 3、
 */
/**
 * file module definition class
 */
class File extends \yii\base\Module
{
    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'backend\modules\file\controllers';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        // custom initialization code goes here
    }
}
