<?php

namespace backend\modules\file\models;

use Yii;
use yii\helpers\Html;
/**
 * This is the model class for table "{{%file}}".
 *
 * @property string $id
 * @property string $file_name
 * @property integer $parent_id
 * @property integer $is_folder
 * @property integer $user_id
 * @property string $file_size
 */
class File extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%file}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['parent_id', 'is_folder', 'user_id', 'sys_property', 'create_time', 'update_time', 'is_delete', 'status', 'real_file_size'], 'integer'],
            [['file_name', 'file_size', 'file_path', 'file_ext', 'file_type', 'pch'], 'string', 'max' => 255],
            [['parent_id', 'sys_property', 'is_folder', 'is_delete', 'status', 'real_file_size'], 'default', 'value'=>0],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => '编号',
            'file_name' => '文件名',
            'parent_id' => 'Parent ID',
            'is_folder' => 'Is Folder',
            'user_id' => 'User ID',
            'file_size' => '文件大小',
            'create_time' => '上传时间',
            'real_file_size' => '文件大小',
        ];
    }

    public function beforeSave($insert){
        if(parent::beforeSave($insert)){
            if($this->isNewRecord){
                $this->create_time = time();
                $this->update_time = time();
            } else{
                $this->update_time = time();
            }
            return true;
        } else {
            return false;
        }
    }

    public static function get_file_icon($ext, $show_switch=1){
        $type_name = '';
        if($show_switch==1){
            $size_type = 'small';
            $size_short_type = 's';
        } else {
            $size_type = 'large';
            $size_short_type = 'l';
        }
        switch(true){
            case in_array($ext, ['php', 'css', 'js', 'java', 'sql']):
                $type_name = 'fileicon-'.$size_type.'-code';
                break;
            case in_array($ext, ['wav','wmv','mkv','mp4','rmvb','avi','flv','swf.mpeg4','mpeg2','3gp','mpga','qt','rm','wmz','wmd','wvx','wmx','wm','mpg','mpeg','mov','asf','m4v']):
                $type_name = 'fileicon-'.$size_type.'-video';
                break;
            case in_array($ext, ['mp3','wav','aac.wma','ra','ram','mp2','ogg','aif','mpega','amr','mid','midi','m4a']):
                $type_name = 'fileicon-'.$size_type.'-mp3';
                break;
            case in_array($ext, ['docx','doc']):
                $type_name = 'fileicon-'.$size_type.'-doc';
                break;
            case in_array($ext, ['xlsx','xls']):
                $type_name = 'fileicon-'.$size_type.'-xls';
                break;
            case in_array($ext, ['ppt']):
                $type_name = 'fileicon-'.$size_type.'-ppt';
                break;
            case in_array($ext, ['pdf']):
                $type_name = 'fileicon-'.$size_type.'-pdf';
                break;
            case in_array($ext, ['zip']):
                $type_name = 'fileicon-'.$size_type.'-zip';
                break;
            case in_array($ext, ['rar']):
                $type_name = 'fileicon-'.$size_type.'-rar';
                break;
            case in_array($ext, ['jpeg','png','jpg','gif']):
                $type_name = 'fileicon-'.$size_type.'-pic';
                break;
            case in_array($ext, ['txt']):
                $type_name = 'fileicon-'.$size_type.'-txt';
                break;
            case in_array($ext, ['folder']):
                $type_name = 'dir-'.$size_type;
                break;
            case in_array($ext, ['psd','ai']):
                $type_name = 'fileicon-sys-'.$size_short_type.'-psd';
                break;
            case in_array($ext, ['exe']):
                $type_name = 'fileicon-sys-'.$size_short_type.'-exe';
                break;
            case in_array($ext, ['html']):
                $type_name = 'fileicon-sys-'.$size_short_type.'-web';
                break;
            case in_array($ext, ['swf']):
                $type_name = 'fileicon-sys-'.$size_short_type.'-swf';
                break;
            default:
                $type_name = 'default-'.$size_type;
                break;
        }
        return $type_name;
    }

    public function getFileLocation($pid){
        $model = File::find()->select('id, file_name, is_folder')->where(['id' => $pid, 'is_delete' => 0])->asArray()->one();
        if($pid==0){
            $model['file_name'] = '全部文件';
            $model['id'] = 0;
        }
        return Html::a($model['file_name'], 'javascript:jumpToFolder(' . $model['id'] . ');', ['class' => 'path', 'title' => $model['file_name']]);
    }
}
