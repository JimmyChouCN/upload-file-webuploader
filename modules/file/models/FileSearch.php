<?php

namespace backend\modules\file\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\modules\file\models\File;

/**
 * RegionSearch represents the model behind the search form about `backend\models\Region`.
 */
class FileSearch extends File
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'parent_id', 'is_folder', 'user_id'], 'integer'],
            [['file_name', 'real_file_size', 'create_time'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params, $pid=0)
    {
        if($pid == -1){
            $query = File::find()->where(['and', ['not like', 'file_ext', Yii::$app->request->get('keyword')], ['like', 'file_name', Yii::$app->request->get('keyword')], ['is_delete'=>0]]);
        } else {
            $query = File::find()->where(['parent_id'=>$pid, 'is_delete'=>0]);
        }

        // add conditions that should always apply here
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => false,
                'pageSizeParam' => false,
                // 'route' => false,
                'validatePage' => false,
            ],
        ]);

        $dataProvider->setSort([
            'defaultOrder' => ['create_time' => SORT_DESC],
            'attributes' => [
                'real_file_size' => [
                    'asc' => ['is_folder' => SORT_DESC, 'real_file_size' => SORT_ASC],
                    'desc' => ['is_folder' => SORT_DESC, 'real_file_size' => SORT_DESC],
                ],
                'file_name' => [
                    'asc' => ['is_folder' => SORT_DESC, 'file_name' => SORT_ASC],
                    'desc' => ['is_folder' => SORT_DESC, 'file_name' => SORT_DESC],
                ],
                'create_time' => [
                    'asc' => ['is_folder' => SORT_DESC, 'create_time' => SORT_ASC],
                    'desc' => ['is_folder' => SORT_DESC, 'create_time' => SORT_DESC],
                ]
            ]
        ]);
        
        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'is_folder' => $this->is_folder,
            'user_id' => $this->user_id,
        ]);

        $query->andFilterWhere(['like', 'file_name', $this->file_name]);

        return $dataProvider;
    }
}
