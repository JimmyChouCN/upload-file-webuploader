<?php

namespace backend\modules\file\controllers;

use Yii;
use yii\web\Controller;
use backend\modules\file\models\File;
use backend\modules\file\models\FileSearch;
use backend\modules\file\components\FileUploadFun;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
// use backend\models\BackendLog;

/**
 * Default controller for the `file` module
 */
class DefaultController extends Controller
{
    public $enableCsrfValidation = false; // 不启用验证
    public $md5File = [];

    public function beforeAction($action){
        if (parent::beforeAction($action)) {
            $this->md5File = @file(Yii::getAlias('@common') . DIRECTORY_SEPARATOR . 'caches/md5list2.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);        
            $this->md5File = $this->md5File ? $this->md5File : [];
            return true;
        } else {
            return false;
        }
    }

    /**
     * Renders the index view for the module
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('index',[
            'folder_id' => $this->getSessionPositionID(),
        ]);
    }

    public function actionDelete($id){
        $model = File::findOne($id);
        $model->is_delete = 1;
        if($model->save(false)){
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * [actionDeleteAll description]
     * @param  [type] $ids [description]
     * @return [type]      [description]
     */
    public function actionDeleteAll() {
        $ids = Yii::$app->request->post('ids');
        if(!empty($ids)){
            if(File::updateAll(['is_delete'=>1], ['in', 'id', $ids])){
                // BackendLog::saveLog(Yii::$app->controller->id, Yii::$app->controller->action->id, rtrim(implode(',', $ids)));
                return 1;
            } else {
                return 0;
            }
        } else {
            return 0;
        }
    }

    /**
     * 新建文件夹
     * @return [type] [description]
     */
    public function actionCreateFolder() {
        if(Yii::$app->request->isPost) {
            $datas = Yii::$app->request->post();
            $model = new File();
            $model->is_folder = 1;
            $model->user_id = Yii::$app->user->identity->id;
            $model->file_size = "-";
            $model->file_ext = "folder";
            $model->file_type = "folder";
            $model->parent_id = $this->getSessionPositionID();
            $model->file_name = $this->checkExistsName($datas['file_name'], 1);
            $model->pch = $datas['pch'];
            if($model->save()){
                return 1;
            } else {
                return 0;
            }
        }
    }

    /**
     * 检查是否重名
     * 1、检查该文件是否存在
     * 2、查询该文件重名尾号到多少
     * 3、自增1
     * @param  [string]  $file_name [description]
     * @param  integer $is_folder [description]
     * @param  string  $file_ext  [description]
     * @return [type]             [description]
     */
    private function checkExistsName($file_name, $is_folder = 0, $file_ext = '') {
        $parent_id = $this->getSessionPositionID();
        $is_file_exit = File::find()->select('file_name')->where(['parent_id'=>$parent_id, 'file_name'=>$file_name, 'is_folder'=>$is_folder])->count();
        if(empty($is_folder) || $is_folder == 0) {
            $file_name = str_replace('.' . $file_ext, '', $file_name);
        }
        if($is_file_exit) {
            $files = File::find()->select('file_name')->where(['parent_id'=>$parent_id, 'is_folder'=>$is_folder])->andWhere(['like', 'file_name', $file_name . '(%', false])->asArray()->all();
            $rows = [];
            if(empty($is_folder) || $is_folder == 0) {
                $pattern = '/(?<='.addcslashes($file_name, '[],&*$#@!^_-+=~`?/<>;:.() ').'\()\d*(?=\)$)/';
            } else {
                $pattern = '/(?<='.$file_name.'\()\d*(?=\)$)/';
            }
            foreach ($files as $file) {
                if(empty($is_folder) || $is_folder == 0){
                    $new_name = str_replace('.' . $file_ext, '', $file['file_name']);
                } else {
                    $new_name = $file['file_name'];
                }
                preg_match($pattern, $new_name, $match);
                if(empty($match[0])) {
                    continue;
                }
                $rows[] = $match[0];
            }
            if ($rows) {
                $new_index = max($rows)+1;
                if($is_folder){
                    $new_file_name = $file_name . "($new_index)";
                } else {
                    $new_file_name = $file_name . "($new_index)" . '.' . $file_ext;
                }
            } else {
                if($is_folder){
                    $new_file_name = $file_name . '(1)';
                } else {
                    $new_file_name = $file_name . "(1)" . '.' . $file_ext;
                }
            }
        } else {
            if($is_folder){
                $new_file_name = $file_name;
            } else {
                $new_file_name = $file_name . '.' . $file_ext;
            }
        }
        return $new_file_name;
    }

    /**
     * 显示文件列表
     * @param  integer $id   [description]
     * @param  integer $type [description]
     * @return [type]        [description]
     */
    public function actionFileList($id = 0, $type = 0) {
        if($type==2) {
            $id = $this->getSessionPositionID();
        }
        $searchModel = new FileSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, $id);
        $nav_title = '<a href="javascript:jumpToFolder(0);" title="全部文件" data-deep="0">全部文件</a>
            <span class="historylistmanager-separator-gt">&gt;</span>';
        $file = File::find()->select('parent_id')->where(['id'=>$id])->asArray()->one();
        $pre_id = $file['parent_id'];
        if(!empty($id) && isset($id) && $id>=0){
            $title_datas = $this->getFileTitle($id);
            ArrayHelper::multisort($title_datas, 'id');
            $i = 1;
            $count = count($title_datas);
            if($count>0){
                foreach ($title_datas as $key => $value) {
                    if($i<$count){
                        $nav_title .= '<a href="javascript:jumpToFolder('.$value['id'].');" title="'.$value['file_name'].'" data-deep="1">'.$value['file_name'].'</a><span class="historylistmanager-separator-gt">&gt;</span>';
                    } else {
                        $nav_title .= '<span title="'.$value['file_name'].'">'.$value['file_name'].'</span>';
                    }
                    $i++;
                }
            } else {
                $nav_title = '';
            }
        } else {
            if(Yii::$app->request->get('keyword')){
                $nav_title .= '<span title="搜索：'.Yii::$app->request->get('keyword').'">搜索："'.Yii::$app->request->get('keyword').'"</span>';
            } else {
                $nav_title = '';
            }
            $pre_id = 0;
        }
        
        Yii::$app->session->set('file_upload_position_id', $id);
        if(array_key_exists('HTTP_X_PJAX', $_SERVER) && $_SERVER['HTTP_X_PJAX'] === 'true'){
            return $this->renderPartial('_fileList', [
                'searchModel' => $searchModel,
                'dataProvider' => $dataProvider,
                'title' => $nav_title,
                'id' => $id,
                'pre_id' =>$pre_id,
            ]);
        } else {
            return $this->render('index', [
                'id' => $id,
                'sort' => Yii::$app->request->queryParams['sort'],
                'keyword' => Yii::$app->request->queryParams['keyword'],
            ]);
        }
    }

    public function actionSearch(){
        $searchModel = new FileSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, -1);
        $nav_title = '<a href="javascript:jumpToFolder(0);" title="全部文件" data-deep="0">全部文件</a>
            <span class="historylistmanager-separator-gt">&gt;</span>';
       
        if(Yii::$app->request->get('keyword')){
            $nav_title .= '<span title="搜索：'.Yii::$app->request->get('keyword').'">搜索："'.Yii::$app->request->get('keyword').'"</span>';
        } else {
            $nav_title = '';
        }
        $pre_id = 0;
        
        if(array_key_exists('HTTP_X_PJAX', $_SERVER) && $_SERVER['HTTP_X_PJAX'] === 'true'){
            return $this->renderPartial('_fileList', [
                'searchModel' => $searchModel,
                'dataProvider' => $dataProvider,
                'title' => $nav_title,
                'pre_id' =>$pre_id,
            ]);
        } else {
            return $this->render('index');
        }
    }

    /**
     * 获取文件头部导航数据
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function getFileTitle($id){
        $folder_datas = [];
        $file = File::find()->select('id, file_name, parent_id')->where(['id' => $id, 'is_folder'=>1])->asArray()->one();
        if($file['parent_id'] != 0 ){
            $folder_datas[$file['parent_id']] = ['id'=>$file['id'], 'file_name'=>$file['file_name'], 'parent_id'=>$file['parent_id']];
            $next_file = $this->getFileTitle($file['parent_id']);
            if(!empty($next_file)){
                foreach ($next_file as $key => $value) {
                    $folder_datas[$value['parent_id']] = ['id'=>$value['id'], 'file_name'=>$value['file_name'], 'parent_id'=>$value['parent_id']];
                }
            }
        } else {
            $folder_datas[0] = ['id'=>$file['id'], 'file_name'=>$file['file_name'], 'parent_id'=>0];
        }
        return $folder_datas;
    }

    /**
     * 获取当前位置
     * @return [type] [description]
     */
    private function getSessionPositionID(){
        return empty(Yii::$app->session->get('file_upload_position_id')) ? 0 : Yii::$app->session->get('file_upload_position_id');
    }

    private function unicode2utf8($str) {
        if(!$str){
            return $str;
        }
        $decode = json_decode($str);
        if($decode){
            return $decode;
        }
        $str = '["' . $str . '"]';
        $decode = json_decode($str);
        if(count($decode) == 1){
            return $decode[0];
        }
        return $str;
    }

    public function actionCheckResume(){
        
        
        if(!empty($_POST['status'])){
            if($_POST['status'] == 'chunkCheck'){//用于断点续传，验证指定分块是否已经存在，避免重复上传
                $chunkIndex = $_POST['chunkIndex'];
                $target = Yii::getAlias('@common'). DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'files_tmp'. DIRECTORY_SEPARATOR . iconv("UTF-8", "GBK", $this->unicode2utf8('"' . $_POST["file_name"] . '"')) . "_{$chunkIndex}.part";
                if(file_exists($target) && filesize($target) == $_POST['size']){
                    return ('{"ifExist":1}');
                }
                return ('{"ifExist":0}');

            } elseif($_POST['status'] == 'md5Check') { // 秒传验证
                $file_datas = Yii::$app->request->post();
                $upload_result = [];
                if (!empty($file_datas["md5"]) && array_search($file_datas["md5"], $this->md5File ) !== FALSE ) { // 判断MD5
                    $saveDir = Yii::$app->user->identity->username . '_' . Yii::$app->user->identity->id . DIRECTORY_SEPARATOR . date('Y-m', time()) . DIRECTORY_SEPARATOR . date('d', time());
                    $file_path = File::find()->select('file_path')->where(['file_name' => $file_datas['name'], 'file_md5' => $file_datas["md5"], 'is_folder' => 0])->asArray()->one();
                    if(empty($file_datas['path'])){
                        $fileName = $this->checkExistsName($file_datas['name'], 0, $file_datas['ext']); 
                        if($fileName != $file_datas['name']) {
                            $org_file = Yii::getAlias("@common") . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . iconv('UTF-8','GB2312', $file_path['file_path']);
                            $new_file = Yii::getAlias("@common") . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'files'  . DIRECTORY_SEPARATOR  . $saveDir;
                            $this->mkDirs($new_file);
                            $new_file = $new_file . DIRECTORY_SEPARATOR . iconv('UTF-8','GB2312', $fileName);
                            $file_datas['name'] = $fileName;
                            @copy($org_file, $new_file);
                        }
                        $saveDir = $saveDir . DIRECTORY_SEPARATOR . $file_datas['name'];
                    } else {
                        if(!empty($file_path)){
                            $saveDir = $file_path['file_path'];
                        } else {
                            $saveDir = $saveDir . DIRECTORY_SEPARATOR . $file_datas['name'];
                        }
                    }
                    $upload_result['result']['path'] = $saveDir/* . DIRECTORY_SEPARATOR . $file_datas['name']*/;
                    $upload_result['result']['name'] = $file_datas['name'];
                    $this->saveFile($file_datas, $upload_result, $file_datas["md5"]); // 存在直接入库
                    return ('{"ifExist": 1}');
                }
                Yii::$app->session->set('file_md5_num', $file_datas["md5"]);
                return ('{"ifExist":0}');
            }
        }
    }

    private function mkDirs($dir){
        if(!is_dir($dir)){
            if(!$this->mkDirs(dirname($dir))){
                return false;
            }
            if(!mkdir($dir,0777)){
                return false;
            }
        }
        return true;
    }

    /**
     * 上传文件操作
     * @return [type] [description]
     */
    public function actionUpload() {
        $file_datas = $_POST;

        // 是否是文件夹上传
        if(!empty($file_datas['path'])) {
            $fileName = $file_datas['name'];
        } else {
            $fileName = $this->checkExistsName($file_datas['name'], 0, $file_datas['ext']);
        }
        // 上传文件操作
        $is_upload_success = new FileUploadFun($file_datas['chunk'], $file_datas['chunks'], $fileName);
        $upload_result = $is_upload_success->init();
        if($upload_result['result']['done'] == 1) {
            array_push($this->md5File, Yii::$app->session->get('file_md5_num'));
            $this->md5File = array_unique($this->md5File);
            file_put_contents(Yii::getAlias('@common').DIRECTORY_SEPARATOR.'caches/md5list2.txt', join($this->md5File, "\n"));
            $this->saveFile($file_datas, $upload_result, Yii::$app->session->get('file_md5_num'));
        }
    }

    /**
     * 入库操作
     * @param  [array] $file_datas [description]
     * @return [type]             [description]
     */
    private function saveFile($file_datas = [], $upload_result = [], $file_md5 = ''){
        $get_parent_id = $file_datas['current_position_id'];
        if(!empty($file_datas)) {
            if(!empty($file_datas['path'])) {
                $file_folder_paths = str_replace('/' . $file_datas['name'], '', $file_datas['path']);
                $folder_name_datas = explode('/', $file_folder_paths);
                $folder_parent_id = 0;
                if(!empty($folder_name_datas)) {
                    foreach ($folder_name_datas as $key => $value) {
                        if($key == 0){
                            $count = File::find()->select('id')->where([/*'file_name'=>$value, */'parent_id'=>$get_parent_id, 'is_folder'=>1, 'pch'=>$file_datas['pch']])->asArray()->one();
                            if(empty($count)){
                                $model = new File();
                                $model->file_name = $this->checkExistsName($value, 1);
                                $model->parent_id = $get_parent_id;
                                $model->is_folder = 1;
                                $model->user_id = Yii::$app->user->identity->id;
                                $model->file_size = "-";
                                $model->file_ext = "folder";
                                $model->file_type = "folder";
                                $model->pch = $file_datas['pch'];
                                $model->save();
                                $folder_parent_id = $model->id;
                            } else {
                                $folder_parent_id = $count['id'];
                            }
                        } else {
                            $count = File::find()->select('id')->where(['file_name'=>$value, 'parent_id'=>$folder_parent_id, 'is_folder'=>1, 'pch'=>$file_datas['pch']])->asArray()->one();
                            if(empty($count)){
                                $model = new File();
                                $model->file_name = $value;
                                $model->parent_id = $folder_parent_id;
                                $model->is_folder = 1;
                                $model->user_id = Yii::$app->user->identity->id;
                                $model->file_size = "-";
                                $model->file_ext = "folder";
                                $model->file_type = "folder";
                                $model->pch = $file_datas['pch'];
                                $model->save();
                                $folder_parent_id = $model->id;
                            } else {
                                $folder_parent_id = $count['id'];
                            }
                        }
                    }
                }
                $model = new File();
                $model->file_name = $file_datas['name'];
                $model->parent_id = $folder_parent_id;
                $model->is_folder = 0;
                $model->user_id = Yii::$app->user->identity->id;
                $model->file_size = $file_datas['size'];
                $model->real_file_size = $file_datas['real_size'];
                $model->file_path = $upload_result['result']['path'];
                $model->file_ext = $file_datas['ext'];
                $model->file_type = $file_datas['type'];
                $model->pch = $file_datas['pch'];
                $model->file_md5 = $file_md5;
                $model->save();
            } else {
                $model = new File();
                $model->file_name = $upload_result['result']['name'] /*$file_datas['name']*/;
                $model->parent_id = $get_parent_id;
                $model->is_folder = 0;
                $model->user_id = Yii::$app->user->identity->id;
                $model->file_size = $file_datas['size'];
                $model->real_file_size = $file_datas['real_size'];
                $model->file_path = $upload_result['result']['path'];
                $model->file_ext = $file_datas['ext'];
                $model->file_type = $file_datas['type'];
                $model->pch = $file_datas['pch'];
                $model->file_md5 = $file_md5;
                $model->save();
            }
        }
    }

    /**
     * 文件下载
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function actionDownloadOneFile($id) {
        $model = File::findOne($id);
        $file = Yii::getAlias("@common") . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . iconv('UTF-8','GBK', $model->file_path);
        if(!file_exists($file)) {
            die('文件不存在');
            exit;
        }
        //header("Cache-Control: public"); 
        //header("Content-Description: File Transfer"); 
        header("Content-type: application/octet-stream");
        header("Content-Transfer-Encoding: binary"); //告诉浏览器，这是二进制文件
        header('Content-Length: '. filesize($file)); //告诉浏览器，文件大小
        header('Content-disposition: attachment; filename="'.$model->file_name.'"'/*basename($model->file_path)*/); //文件名     
        @readfile($file);
    }

    /**
     * 重命名
     * @return [type] [description]
     */
    public function actionRename(){
        if(Yii::$app->request->isPost) {
            $datas = Yii::$app->request->post();
            $model = File::findOne($datas['id']);
            if($datas['type']==1){
                $model->file_name = $this->checkExistsName($datas['file_name'], 1);
            } else {
                $model->file_name = $this->checkExistsName($datas['file_name'], 0, pathinfo($datas['file_name'], PATHINFO_EXTENSION));
            }
            if($model->save(false)){
                return 1;
            } else {
                return 0;
            }
        }
    }

    public function actionCopy(){

    }

    public function actionMove(){

    }

    // 构建文件夹和文件
    public function actionCreatedirfile(){
        ob_start();  
        ob_clean();
       // 判断目录存在删除
        if(file_exists($this->downloadPath)){
            $this->removeDir($this->downloadPath);
        }
        
       // 创建下载根目录 
        if(!file_exists($this->downloadPath)){
            mkdir($this->downloadPath);
        }

        if(!empty($_POST['fileId'])){
            $fileIds = explode(',', $_POST['fileId']);
            foreach ($fileIds as $key => $value) {
                $model = File::findOne(['id'=>$value]);
                if($model->file_type==1){
                   // $path = $this->downloadPath.mb_convert_encoding($model->file_name,'gb2312','utf8');// 必須轉碼，解決中文亂碼錯誤
                    $path = $this->downloadPath.iconv("utf-8","GBK//IGNORE",$model->file_name);
                    //$path = $this->downloadPath.$model->file_name;
                    if(!file_exists($path)){
                        mkdir($path);
                    }
                    $this->list_dir($model->id,$path);
                } else {
                    if(file_exists($model->file_path)){
                        //copy($model->file_path, $this->downloadPath.$model->file_name);
                        copy($model->file_path, $this->downloadPath.iconv("utf-8","GBK//IGNORE",$model->file_name));
                        //copy($model->file_path, $this->downloadPath.mb_convert_encoding($model->file_name,'gb2312','utf8'));
                    }
                }
            }
            // 压缩文件操作
            $filename = $this->zipfile($this->downloadPath);
            echo json_encode(['flag'=>1,'url'=>Yii::$app->urlManager->createUrl(['library/download','filename'=>$filename])]);
            exit;
        } 
        echo json_encode(['flag'=>0]);
        exit;
    }
    // 删除文件夹操作
    public function removeDir($dirName) 
    { 
        if(!is_dir($dirName)) 
        { 
            return false; 
        } 
        $handle = @opendir($dirName); 
        while(($file = @readdir($handle)) !== false) 
        { 
            if($file != '.' && $file != '..') 
            { 
                $dir = $dirName . '/' . $file; 
                is_dir($dir) ? $this->removeDir($dir) : @unlink($dir); 
            } 
        } 
        closedir($handle);  
        rmdir($dirName) ; 
    } 

    // 递归构建文件夹和文件
    public function list_dir($id,$path){
        if(!empty($id)){
            $model = File::find()->where(['belong_to'=>$id])->asArray()->all(); 
            foreach ($model as $key=>$value) {            
                if($value['file_type']==1){
                    //$path = $path.'/'.$value['file_name'];
                    $path = $path.'/'.iconv("utf-8","GBK//IGNORE",$value['file_name']);
                    //$path = $path.'/'.mb_convert_encoding($value['file_name'],'gb2312','utf8');
                    if(!file_exists($path)){
                        mkdir($path);
                    }
                    $this->list_dir($value['id'],$path);
                } else {
                    if(file_exists($value['file_path'])){
                        //copy($value['file_path'], $path.'/'.$value['file_name']);
                        copy($value['file_path'], $path.'/'.iconv("utf-8","GBK//IGNORE",$value['file_name']));
                        //copy($value['file_path'], $path.'/'.mb_convert_encoding($value['file_name'],'gb2312','utf8'));
                    }
                }
            }
        }
    }

    /**
     * 压缩方法
     * @param  [string] $path [文件夹路径]
     * @return [type]       [description]
     */
    public function zipfile($path){

        if(!file_exists($this->downloadfilesPath)){
            mkdir($this->downloadfilesPath);
        }
        $file = 'cic_downloadFile'.time().'.zip';
        $filename =$this->downloadfilesPath.$file;

        $pathInfo = pathInfo($path);
        $parentPath = $pathInfo['dirname'];
        $dirName = $pathInfo['basename']; 

        $zip = new \ZipArchive;
        $res = $zip->open($filename,\ZipArchive::CREATE);
        if($res===TRUE){         
            //$zip->addEmptyDir($dirName);        
            $this->addFileToZip($path, $zip, strlen("$parentPath.$dirName/")); //调用方法，对要打包的根目录进行操作，并将ZipArchive的对象传递给方法
            $zip->close();
        }
        return $file;
    }

    // 递归把文件和文件夹加入压缩对象中
    public function addFileToZip($path,&$zip,$exclusiveLength){
        $handler=opendir($path); //打开当前文件夹由$path指定。
        while(($filename=readdir($handler))!==false){
            if($filename != "." && $filename != ".."){//    文件夹文件名字为'.'和‘..’，不要对他们进行操作
                $filePath = "$path$filename";
                $localPath = substr($filePath, $exclusiveLength);
                if(is_dir($filePath)){// 如果读取的某个对象是文件夹，则递归
                    $zip->addEmptyDir($localPath);
                    $this->addFileToZip($filePath.'/', $zip, $exclusiveLength);
                }else{ //将文件加入zip对象
                    $zip->addFile($filePath, $localPath);
                }
            }
        }
        @closedir($handler);
    }

    // 压缩包下载
    public function actionDownload(){
        if(!empty($_GET['filename'])){
            if(!file_exists($this->downloadfilesPath.$_GET['filename'])){
                die('文件不存在');
                exit;
            }
            //header("Cache-Control: public"); 
            //header("Content-Description: File Transfer"); 
            header('Content-disposition: attachment; filename='.basename($this->downloadfilesPath.$_GET['filename'])); //文件名   
            header('Content-Type: application/zip'); //zip格式的   
            //header("Content-Transfer-Encoding: binary"); //告诉浏览器，这是二进制文件    
            header('Content-Length: '. filesize($this->downloadfilesPath.$_GET['filename'])); //告诉浏览器，文件大小   
            @readfile($this->downloadfilesPath.$_GET['filename']);
        }
    }
    

}
