<?php

/**
 * Created by PhpStorm.
 * User: lixiang
 * Date: 16/7/20
 * Time: 17:12
 */

namespace imxiangli\select2;

use yii\helpers\Html;
use yii\helpers\StringHelper;
use yii\helpers\Url;
use yii\web\JsExpression;
use yii\web\View;
use yii\widgets\InputWidget;

class Select2Widget extends InputWidget
{
	public $serverUrl = null;
	public $selectedItem = [];
	public $itemsName = 'items';
	public $nameField = 'name';
	public $searchKeywordName = 'keyword';
	public $pageName = 'page';
	public $pageCountField = 'page_count';
	public $cache = true;
	public $loading = '正在加载...';
	public $placeholder = '请选择';
	public $placeholderId = null;
	public $data = null;
	public $minimumInputLength = 0;
	public $static = false;
	public $language = 'zh-CN';
	public $width = '95px';

	/** @var JsExpression */
	public $eventSelect = null;
	/** @var JsExpression */
	public $eventOpening = null;

	public function run()
	{
		$this->registerClientScript();
		if ($this->hasModel()) {
			return Html::activeDropDownList($this->model, $this->attribute, $this->selectedItem, $this->options);
		} else {
			return Html::dropDownList($this->name, $this->value, $this->selectedItem, $this->options);
		}
	}

	protected function registerClientScript()
	{
		Select2Asset::register($this->view)->js[] = 'js/i18n/' . $this->language . '.js';
		$placeholder = null;
		if (null !== $this->placeholder) {
			$placeholder = "placeholder: '{$this->placeholder}',";
			if (null !== $this->placeholderId) {
				$placeholderId = $this->placeholderId;

				if (null !== $this->placeholderId && StringHelper::countWords($this->placeholderId) <= 0) {
					$placeholderId = "''";
				}
				$placeholder = "placeholder: {id: {$placeholderId}, text: '{$this->placeholder}'},";
			}
		}

		$eventJsSelect = '';
		if ($this->eventSelect instanceof JsExpression) {
			$eventJsSelect = $this->eventSelect->expression;
		}
        $eventJsOpening = '';
        if ($this->eventOpening instanceof JsExpression) {
            $eventJsOpening = $this->eventOpening->expression;
        }

		if ($this->static) {
			$script = "$(function(){
					$('#{$this->options['id']}').select2({
						{$placeholder}
						language: 'zh-CN',
						width: '{$this->width}'
					}).on('select2:select', function(env){
						{$eventJsSelect}
					});
				});";
		} else {
			$data = '';
			if ($this->serverUrl !== null) {
				$data = "ajax: {
					url: function(){return getServerUrl()},
					dataType: 'json',
					delay: 250,
					data: function (params) {
					  return {
						{$this->searchKeywordName}: params.term, // search term
						{$this->pageName}: params.page
					  };
					},
					processResults: function (data, params) {
					  params.page = params.page || 1;
					  var list = data.{$this->itemsName};
					  if(params.page <= 1)
					  {
						list.unshift({{$this->nameField}: '{$this->placeholder}', id: '{$this->placeholderId}'});
					  }
					  return {
						results: list,
						pagination: {
						  more: params.page < data.{$this->pageCountField}
						}
					  };
					},
					cache: " . ($this->cache ? 'true' : 'false') . "
				  },";
			} else if (!is_array($this->data)) {
				$data = 'data: ' . json_encode($this->data) . ',';
			}
			$script = "$(function(){
			var serverUrl = '" . Url::to($this->serverUrl) . "';
			function getServerUrl(){
			    return serverUrl;
			}
			
			$('#{$this->options['id']}').select2({
				language: 'zh-CN',
				{$placeholder}
			 	{$data}
				escapeMarkup: function (markup) { return markup; },
			 	minimumInputLength: {$this->minimumInputLength},
				width: '{$this->width}',
				templateResult: function (repo) {
					if (repo.loading) return '{$this->loading}';
					var markup = repo.{$this->nameField};
					return markup;
				},
				templateSelection: function (repo) {
				  	return repo.{$this->nameField} || repo.text;
				}
			}).on('select2:select', function(env){
				{$eventJsSelect}
			}).on('select2:opening', function(env){
			    {$eventJsOpening}
			});
		});";
		}
		$this->view->registerJs($script, View::POS_READY);
	}
}
