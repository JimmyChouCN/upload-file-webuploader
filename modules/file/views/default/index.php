<?php
use yii\helpers\Html;
use yii\helpers\Url;
use backend\components\GridView;
use mdm\admin\components\Helper;
use backend\assets\AppAsset;

$this->title = '文件管理';
$this->params['breadcrumbs'][] = $this->title;
?>
<!--引入CSS-->
<?= Html::cssFile('@web/css/webuploader/webuploader.css') ?>
<?= Html::cssFile('@web/css/file.css') ?>
<script type="text/javascript">
    var requestFileListUrl = '<?= Url::to(['file-list']);?>';
    var searchFileUrl = '<?= Url::to(['search']);?>';
    var uploadUrl = "<?= Url::to(['upload']) ?>";
    var deleteThisFileUrl = "<?= Url::to(['delete']) ?>";
    var createFolderUrl = "<?= Url::to(['create-folder']) ?>";
    var checkResumeUrl = "<?= Url::to(['check-resume']) ?>";
    var deleteAllUrl = "<?= Url::to(['delete-all']) ?>";
    var setShowSwitchUrl = "<?= Url::to(['set-show-switch']) ?>";
    var downloadOneFileUrl = "<?= Url::to(['download-one-file']) ?>";
    var renameUrl = "<?= Url::to(['rename']) ?>";
</script>
<!--引入JS-->
<?php
AppAsset::register($this);
AppAsset::addScript($this, Yii::$app->request->baseUrl.'/js/webuploader/webuploader.js');
AppAsset::addScript($this, Yii::$app->request->baseUrl.'/js/webuploader_upload_all.js');
?>
<!-- 顶部操作按钮 -->
<div class="default-dom-button">
    <div class="list-grid-switch grid-switched-on">
        <a node-type="list-switch" style="display: none;" class="list-switch" href="javascript:void(0)">
            <span class="fa fa-bars"></span>
        </a>
        <a node-type="grid-switch" class="grid-switch" href="javascript:void(0)" style="display: none;">
            <span class="fa fa-th-large"></span>
        </a>
    </div>
    <!-- 搜索 -->
    <div class="bar-search">
        <div class="form-box" node-type="form-box">
            <form node-type="search-form" class="search-form clearfix" action="javascript: void(0)" method="get">
                <input node-type="search-query click-ele" data-key="SEARCH_QUERY" autocomplete="off" class="search-query" name="q" value="" spellchecking="off" type="text">
                <span node-type="search-clear" class="input-clear fa fa-times-circle" style="display: none;"></span>
                <span node-type="click-ele" data-key="SEARCH_BUTTON" class="search-button">
                    <span class="fa fa-search"></span>
                </span>
                <span node-type="search-placeholder" class="input-placeholder" style="display: block;">搜索您的文件</span>
            </form>
        </div>
    </div>
    <div class="bar" style="white-space: nowrap; position: relative;">
        <div id="btnContainer">
            <div class="upload_btn" style="z-index: 999;"></div>
            <div class="upload_btn_menu" style="width:84px;">
                <div class="choose_files_btn"></div>
                <div class="choose_folder_btn"></div>
            </div>
        </div>
        <?= Html::a('<i class="fa fa-folder"></i> 新建文件夹', 'javascript:void(0)', ['id'=>'create-folder', 'class' => 'btn btn-info', 'style'=>'height:32px;']) ?>
        <div class="list-tools" style="position: absolute;top: 0px;padding-top: 11px;line-height: normal;display: none;visibility: visible;width: 629px;padding-left: 217px;">
            <?= Html::a('<i class="fa fa-download"></i> 批量下载', 'javascript:void(0)', ['id'=>'download-files', 'class' => 'btn btn-info', 'style'=>'height:32px;']) ?>
            <?= Html::a('<i class="glyphicon glyphicon-trash"></i> 批量删除', 'javascript:void(0)', ['class' => 'btn btn-danger gridview', 'style'=>'height:32px;']) ?>
        </div>
        <input type="hidden" id="current-position-id" value="<?= $page_num?>">
    </div>
</div>

<!-- 文件列表 -->
<div class="file-index"></div>

<script type="text/javascript">
$(document).ready(function(){
    var object = window.getHash();
    $.ajax({
        url: requestFileListUrl,
        dataType: 'html',
        type: 'GET',
        data: {
            id: object.id,
            sort: window.localStorage.listsort,
            keyword: object.keyword,
        },
        beforeSend: function(xhr){
            xhr.setRequestHeader('X-PJAX', true);
        },
        success: function(data){
            $('.file-index').html(data);
            window.chooseViewType();
            if(window.location.hash == '' || window.location.hash == 'undifiend'){
                window.location.hash = 'show_type=' + window.localStorage.chooseviewtype;
            } else {
                window.location.hash = window.replaceHash(window.location.hash, {'show_type': window.localStorage.chooseviewtype});
            }
            // history.replaceState(null, $(data).filter('title').text(), window.replaceUrl(window.location.href, {'show_type': window.localStorage.chooseviewtype}));
        }
    });
});
</script>

<!-- 文件上传信息框 -->
<div id="uploader" class="wu-example dialog dialog-web-uploader dialog-blue h5-uploader" style="width: 633px;top: auto;bottom: 0px;left: auto;right: 30px;display: none;visibility: visible;z-index: 42;    font-size: 12px;">
    <div class="dialog-header">
        <h3><span class="dialog-header-title"><em class="select-text"></em></span></h3>
        <div class="dialog-control">
            <span class="dialog-icon dialog-close fa fa-times"><span class="sicon">×</span></span>
            <span class="dialog-icon dialog-min fa fa-minus"><span class="sicon">-</span></span>
        </div>
    </div>
    <div class="dialog-min-header" style="display: none;">
        <div class="header-progress"></div>
        <h3><span class="dialog-header-title"><em class="select-text">上传完成</em></span></h3>
        <div class="dialog-control">
            <span class="dialog-icon dialog-close fa fa-times"><span class="sicon">×</span></span>
            <span class="dialog-icon dialog-back fa fa-square-o"><span class="sicon">□</span></span>
        </div>
    </div>
    <!--用来存放文件信息-->
    <div class="dialog-body" style="display:block;">
        <div class="uploader-list-wrapper">
            <div class="uploader-list-header">
                <div class="file-name">文件(夹)名</div>
                <div class="file-size">大小</div>
                <div class="file-path">上传目录</div>
                <div class="file-status">状态</div>
                <div class="file-operate">操作</div>
            </div>
            <div id="thelist" class="uploader-list">
                <ul class="container" id="uploaderList">
                </ul>
            </div>
        </div>
    </div>
</div>