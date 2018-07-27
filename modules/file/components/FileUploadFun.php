<?php
namespace backend\modules\file\components;

use Yii;
use backend\modules\file\models\File;

class FileUploadFun {
    private $uploadDir; //上传目录
    private $targetDir;  //PHP文件临时目录
    private $cleanupTmpDir = true;
    private $maxFileAge = 5 * 3600;
    private $chunk = 0; //第几个文件块
    private $chunks = 1; //文件块总数
    private $fileName; //文件名
    private $filePath;
    private $uploadPath;
    private $org_name;
    private $saveDir;

    public function __construct($chunk, $chunks, $fileName){
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        @set_time_limit(5 * 60);
        $this->saveDir = Yii::$app->user->identity->username . '_' . Yii::$app->user->identity->id . DIRECTORY_SEPARATOR . date('Y-m', time()) . DIRECTORY_SEPARATOR . date('d', time());
        $this->uploadDir = Yii::getAlias("@common") . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . $this->saveDir;
        $this->targetDir = Yii::getAlias('@files_uploads_tmp') . DIRECTORY_SEPARATOR . Yii::$app->user->identity->username . '_' . Yii::$app->user->identity->id;
        $this->chunk =  empty($chunk) ? 0 : $chunk;
        $this->chunks =  $chunks;
        $this->org_name = $fileName;
        $this->fileName =  iconv("UTF-8", "GBK", $this->unicode2utf8('"' . $fileName . '"'));
        $this->filePath = $this->targetDir . DIRECTORY_SEPARATOR . $this->fileName;
        $this->uploadPath = $this->uploadDir . DIRECTORY_SEPARATOR . $this->fileName;
    }

    public function init(){
        $this->touchDir();
        $this->removeOldTmpFile();
        return $this->openTmpFile();
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

    //建立上传文件夹
    private function touchDir(){
        //$this->mkDirs($this->uploadDir);
        if(!file_exists($this->uploadDir)){
            @mkdir($this->uploadDir, 0777, true);
        }
        if(!file_exists($this->targetDir)){
            @mkdir($this->targetDir, 0777, true);
        }
    }

    private function unicode2utf8($str) {
        if(!$str) {
            return $str;
        }
        $decode = json_decode($str);
        if($decode) {
            return $decode;
        }
        $str = '["' . $str . '"]';
        $decode = json_decode($str);
        if(count($decode) == 1) {
            return $decode[0];
        }
        return $str;
    }

    // Remove old temp files
    private function removeOldTmpFile() {
        if ($this->cleanupTmpDir) {
            $dir = '';
            if (!is_dir($this->targetDir) || !$dir = opendir($this->targetDir)) {
                return ['jsonrpc' => "2.0", 'error' => ['code' => 100, 'message' => 'Failed to open temp directory.']];
                // return ('{"jsonrpc" : "2.0", "error" : {"code": 100, "message": "Failed to open temp directory."}, "id" : "id"}');
            }
            while(false !== ($file = readdir($dir))) {
                $tmpfilePath = $this->targetDir . DIRECTORY_SEPARATOR . $file;
                // If temp file is current file proceed to the next
                if ($tmpfilePath == "{$this->filePath}_{$this->chunk}.part" || $tmpfilePath == "{$this->filePath}_{$this->chunk}.parttmp") {
                    continue;
                }
                // Remove temp file if it is older than the max age and is not the current file
                if (preg_match('/\.(part|parttmp)$/', $file) && (@filemtime($tmpfilePath) < time() - $this->maxFileAge)) {
                    @unlink($tmpfilePath);
                }
            }
            closedir($dir);
        }
    }

    // Open temp file
    private function openTmpFile() {
        if (!$out = @fopen("{$this->filePath}_{$this->chunk}.parttmp", "wb")) {
            return (['jsonrpc' => "2.0", 'error' => ['code' => 102, 'message' => 'Failed to open output stream.']]);
        }
        if (!empty($_FILES)) {
            if ($_FILES["file"]["error"] || !is_uploaded_file($_FILES["file"]["tmp_name"])) {
                return (['jsonrpc' => "2.0", 'error' => ['code' => 103, 'message' => 'Failed to move uploaded file.']]);
            }
            // Read binary input stream and append it to temp file
            if (!$in = @fopen($_FILES["file"]["tmp_name"], "rb")) {
                return (['jsonrpc' => "2.0", 'error' => ['code' => 101, 'message' => 'Failed to open input stream.']]);
            }
        } else {
            if (!$in = @fopen("php://input", "rb")) {
                return (['jsonrpc' => "2.0", 'error' => ['code' => 101, 'message' => 'Failed to open input stream.']]);
            }
        }
        while ($buff = fread($in, 4096)) {
            fwrite($out, $buff);
        }
        @fclose($out);
        @fclose($in);
        rename("{$this->filePath}_{$this->chunk}.parttmp", "{$this->filePath}_{$this->chunk}.part");
        $index = 0;
        $done = true;
        for( $index = 0; $index < $this->chunks; $index++ ) {
            if ( !file_exists("{$this->filePath}_{$index}.part") ) {
                $done = false;
                break;
            }
        }
        if ( $done ) {
            if (!$out = @fopen($this->uploadPath, "wb")) {
                return (['jsonrpc' => "2.0", 'error' => ['code' => 102, 'message' => 'Failed to open output stream.']]);
            }
            if ( flock($out, LOCK_EX) ) {
                for( $index = 0; $index <= $this->chunks; $index++ ) {
                    if (!$in = @fopen("{$this->filePath}_{$index}.part", "rb")) {
                        break;
                    }
                    while ($buff = fread($in, 4096)) {
                        fwrite($out, $buff);
                    }
                    @fclose($in);
                    @unlink("{$this->filePath}_{$index}.part");
                }
                flock($out, LOCK_UN);
            }
            @fclose($out);
        }
        // Return Success JSON-RPC response
        return (['jsonrpc' => "2.0", 'result' => ['done' => $done ? 1 : 0, 'name' => $this->org_name, 'path' => $this->saveDir . DIRECTORY_SEPARATOR . $this->org_name]]);
    }

}