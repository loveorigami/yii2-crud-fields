<?php
namespace execut\crudFields;


use yii\db\ActiveQuery;

abstract class Plugin
{
    /**
     * @var Behavior
     */
    public $owner = null;
    public function getFields() {
        return [];
    }

    /**
     * Example:
     * [
     *    [
     *      'class' => Page::class,
     *      'name' => 'pagesPage',
     *      'link' => [
     *          'id' => 'pages_page_id',
     *      ],
     *      'multiple' => false
     *    ]
     * ]
     *
     * @return array
     */
    public function getRelations() {
        return [];
    }

    public function rules() {
        return [];
    }

    public function initDataProvider($dataProvider) {
    }

    public function applyScopes(ActiveQuery $q) {
    }
}