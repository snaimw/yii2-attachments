<?php

namespace nemmo\attachments\components;

use kartik\file\FileInput;
use nemmo\attachments\models\UploadForm;
use nemmo\attachments\ModuleTrait;
use yii\base\InvalidConfigException;
use yii\bootstrap\Widget;
use yii\helpers\FileHelper;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * Created by PhpStorm.
 * User: Алимжан
 * Date: 13.02.2015
 * Time: 21:18
 */
class AttachmentsInput extends Widget
{
    use ModuleTrait;

    public $id = 'file-input';

    public $model;

    public $attribute = 'main';

    public $pluginOptions = [];

    public $options = [];

    public function init()
    {
        parent::init(); // TODO: Change the autogenerated stub

        if (empty($this->model)) {
            throw new InvalidConfigException("Property {model} cannot be blank");
        }

        FileHelper::removeDirectory($this->getModule()->getUserDirPath($this->attribute)); // Delete all uploaded files in past

        $this->pluginOptions = array_replace($this->pluginOptions, [
            'uploadUrl' => Url::toRoute('/attachments/file/upload'),
            'initialPreview' => $this->model->isNewRecord ? [] : $this->model->getInitialPreview($this->attribute),
            'initialPreviewConfig' => $this->model->isNewRecord ? [] : $this->model->getInitialPreviewConfig($this->attribute),
            'uploadAsync' => false
        ]);

        $this->options = array_replace($this->options, [
            'id' => $this->id,
            //'multiple' => true
        ]);
        $pref = '_' . $this->attribute;
        $js = <<<JS
var fileInput$pref = $('#file-input$pref');
var form$pref = fileInput$pref.closest('form');
var filesUploaded$pref = false;
var filesToUpload$pref = 0;
var uploadButtonClicked$pref = false;
//var formSubmit = false;
form$pref.on('beforeSubmit', function(event) { // form submit event
    console.log('submit');
    if (!filesUploaded$pref && filesToUpload$pref) {
        console.log('upload');
        $('#file-input$pref').fileinput('upload').fileinput('lock');

        return false;
    }
});

fileInput$pref.on('filebatchpreupload', function(event, data, previewId, index) {
    uploadButtonClicked$pref = true;
});

//fileInput.on('filebatchuploadcomplete', function(event, files, extra) { // all files successfully uploaded
fileInput$pref.on('filebatchuploadsuccess', function(event, data, previewId, index) {
    filesUploaded = true;
    $('#file-input$pref').fileinput('unlock');
    if (uploadButtonClicked$pref) {
        form$pref.submit();
    } else {
        uploadButtonClicked$pref = false;
    }
});

fileInput$pref.on('filebatchselected', function(event, files) { // there are some files to upload
    filesToUpload$pref = files.length
});

fileInput$pref.on('filecleared', function(event) { // no files to upload
    filesToUpload$pref = 0;
});

JS;

        \Yii::$app->view->registerJs($js);
    }

    public function run()
    {
        $this->pluginOptions['uploadExtraData']['attribute'] = $this->attribute;
        $this->options['id'] = 'file-input_' . $this->attribute;
        $fileinput = FileInput::widget([
            'id' => $this->attribute,
            'model' => new UploadForm(),
            'attribute' => "file[$this->attribute][]",
            'options' => $this->options,
            'pluginOptions' => $this->pluginOptions
        ]);

        return Html::tag('div', $fileinput, ['class' => 'form-group']);
    }
}