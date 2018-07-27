<?php
use yii\helpers\Html;
use yii\helpers\Url;
use backend\modules\file\models\File;
use backend\components\GridView;
use mdm\admin\components\Helper;
use yii\widgets\LinkPager;
?>
<!-- 头部导航信息 -->
<div node-type="module-history-list" class="module-history-list">
    <span class="history-list-dir">全部文件</span>
    <ul class="historylistmanager-history" node-type="historylistmanager-history" <?php if(!empty($title)):?>style="display: block;"<?php else:?>style="display: none;"<?php endif;?>>
        <li><a data-deep="-1" href="javascript:jumpToFolder(<?= $pre_id?>);">返回上一级</a><span class="historylistmanager-separator">|</span></li>
        <li node-type="historylistmanager-history-list">
            <?= $title?>
        </li>
    </ul>
</div>

<?= GridView::widget([
    'options' => ['id' => 'list-view', 'style' => 'display:none;'],
    'dataProvider' => $dataProvider,
    'columns' => [
        [
            'class' => 'yii\grid\CheckboxColumn',
            'name' => 'id',
        ],
        [
            'attribute' => 'file_name',
            'format' => 'raw',
            'value' => function($model) {
                return '<div class="fileicon '. File::get_file_icon($model->file_ext) .'"></div>
                        <div class="file-name">
                            <div class="text">'. Html::a($model->file_name, (!empty($model->is_folder) ? 'javascript:jumpToFolder('. $model->id .');' : 'javascript:void(0);'), ['class'=>(empty($model->is_folder)?'filename':'foldername'), 'title'=>$model->file_name]) .'</div>                          
                        </div>';
            }
        ],
        [
            'attribute' => 'real_file_size',
            'value' => function($model){
                return $model->file_size;
            }
        ],
        [
            'attribute' => 'create_time',
            'format' => ['date', 'php:Y-m-d H:i:s'],
        ],
        [
            'label' => '所在目录',
            'attribute' => 'file_path',
            'format' => 'raw',
            'enableSorting' => false,
            'value' => function($model){
                return File::getFileLocation($model->parent_id);
            },
            'visible' => !empty(Yii::$app->request->get('keyword')),  
        ],
        [
            'class' => 'yii\grid\ActionColumn',
            'template' => Helper::filterActionColumn('{download} {rename} {copy} {remove} {delete}'),
            'visibleButtons' => [
                'download' => function ($model, $key, $index) {
                    return $model->is_folder === 0;
                }
            ],
            'buttons' => [
                'download' => function($url, $model, $key){
                    return Html::a('<span class="glyphicon glyphicon-download-alt"></span>', ['download-one-file', 'id'=>$model->id]/*'javascript:downloadThisFile('.$model->id.')'*/, ['title' => '下载'] ) ;
                },
                'rename' => function($url, $model, $key){
                    return Html::a('<span class="glyphicon glyphicon-pencil"></span>', 'javascript:void(0);', ['title' => '重命名', 'data-id' => $model->id, 'folder-type' => $model->is_folder, 'class'=>'rename'] ) ;
                },
                // 'copy' => function($url, $model, $key){
                //     return Html::a('<span class="glyphicon glyphicon-file"></span>', 'javascript:copyThisFile('.$model->id.')', ['title' => '复制到'] ) ;
                // },
                // 'remove' => function($url, $model, $key){
                //     return Html::a('<span class="glyphicon glyphicon-transfer"></span>', 'javascript:removeThisFile('.$model->id.')', ['title' => '移动到'] ) ;
                // },
                'delete' => function($url, $model, $key){
                    return Html::a('<span class="glyphicon glyphicon-trash"></span>', 'javascript:deleteThisFile('.$model->id.')', ['title' => '删除'] ) ;
                }
            ],
        ],
    ],
]); ?>

<div id="grid-view" style="display: none;">
    <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th><input type="checkbox" class="select-on-check-all" name="id_all" value="1"><span>全选</span></th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($dataProvider->getModels())):?>
                <tr data-key="<?= $file->id?>" style="display:inline-block;">
                    <td>
                        <div class="empty">没有找到数据。</div>
                    </td>
                </tr>
            <?php else:?>
                <?php foreach($dataProvider->getModels() as $file):?>
                <tr data-key="<?= $file->id?>" style="float: left;">
                    <td>
                        <div class="grid-view-item open-enable" _position="<?= $file->parent_id?>" _installed="1" style="display: block;">
                            <?php if(in_array($file->file_ext, ['jpg','png','jpeg','gif'])):?>
                                <div class="fileicon" title=""><img class="thumb" src="<?= Yii::getAlias('@root') . '/common/uploads/files' . DIRECTORY_SEPARATOR . $file->file_path?>" style="visibility: visible; left: 0px; top: 0px;width: 67px;"></div>
                            <?php else:?>
                                <div class="fileicon <?= File::get_file_icon($file->file_ext, 2)?>" title="" <?= (!empty($file->is_folder) ? 'onclick="javascript:jumpToFolder('. $file->id .');"' : '')?>><img class="thumb" style="visibility: hidden;"></div>
                            <?php endif;?>
                            <div class="file-name"><a node-type="name" class="filename" href="javascript:void(0);" title="<?= $file->file_name?>"><?= $file->file_name?></a></div>
                            <span node-type="checkbox" class="checkbox">
                                <span class="fa fa-check-circle"></span>
                                <input type="checkbox" name="id[]" style="display:none;" value="<?= $file->id?>">
                            </span>
                        </div>
                    </td>
                </tr>
                <?php endforeach;?>
            <?php endif;?>
        </tbody>
    </table>
    <!-- <div class="summary">第<b>1-20</b>条，共<b>86</b>条数据.</div> -->
    <?= LinkPager::widget([
        'pagination'=>$dataProvider->pagination,
        // 'firstPageLabel' => '首页',
        // 'lastPageLabel' => '尾页',
        // 'hideOnSinglePage' => false,
    ]);?>
</div>
