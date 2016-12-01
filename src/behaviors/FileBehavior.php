<?php
/**
 * Created by PhpStorm.
 * User: Алимжан
 * Date: 27.01.2015
 * Time: 12:24
 */

namespace nemmo\attachments\behaviors;

use nemmo\attachments\models\File;
use nemmo\attachments\ModuleTrait;
use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\helpers\FileHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\UploadedFile;

class FileBehavior extends Behavior
{
    use ModuleTrait;

    public $attributes = ['main' => ['count' => 1]];

    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_INSERT => 'saveUploads',
            ActiveRecord::EVENT_AFTER_UPDATE => 'saveUploads',
            ActiveRecord::EVENT_AFTER_DELETE => 'deleteUploads'
        ];
    }

    public function saveUploads($event)
    {
        foreach ($this->attributes as $attribute => $data) {
            $files = UploadedFile::getInstancesByName("UploadForm[file][$attribute]");

            if (!empty($files)) {

                if ($data['replace']) $this->deleteUploadsByAttribute($attribute);

                foreach ($files as $file) {
                    if (!$file->saveAs($this->getModule()->getUserDirPath($attribute) . $file->name)) {
                        throw new \Exception(\Yii::t('yii', 'File upload failed.'));
                    }
                }
            }
        }

        foreach ($this->attributes as $attribute => $data) {
            $userTempDir = $this->getModule()->getUserDirPath($attribute);
            $files = FileHelper::findFiles($userTempDir);
            if (!empty($files)) {
                if ($data['replace']) $this->deleteUploadsByAttribute($attribute);
            }
            
            foreach ($files as $file) {
                if (!$this->getModule()->attachFile($file, $this->owner, $attribute)) {
                    throw new \Exception(\Yii::t('yii', 'File upload failed.'));
                }
            }
            rmdir($userTempDir);
        }
    }

    public function deleteUploads($event)
    {
        foreach ($this->getFiles() as $file) {
            $this->getModule()->detachFile($file->id);
        }
    }

    public function deleteUploadsByAttribute($attribute)
    {
        foreach ($this->getFiles($attribute) as $file) {
            $this->getModule()->detachFile($file->id);
        }
    }

    /**
     * @return File[]
     * @throws \Exception
     */
    public function getFiles($attribute = false)
    {
        $condition = [
            'itemId' => $this->owner->id,
            'model' => $this->getModule()->getShortClass($this->owner)
        ];

        if ($attribute) {
            $condition['attribute'] = $attribute;
        }

        $fileQuery = File::find()
            ->where($condition);
        $fileQuery->orderBy(['id' => SORT_ASC]);

        return $fileQuery->all();
    }

    /**
     * @return File
     * @throws \Exception
     */
    public function getFile($attribute = false)
    {
        $condition = [
            'itemId' => $this->owner->id,
            'model' => $this->getModule()->getShortClass($this->owner)
        ];

        if ($attribute) {
            $condition['attribute'] = $attribute;
        }

        $fileQuery = File::find()
            ->where($condition);
        $fileQuery->orderBy(['id' => SORT_ASC]);

        return $fileQuery->one();
    }

    public function getInitialPreview($attribute)
    {
        $initialPreview = [];

        $userTempDir = $this->getModule()->getUserDirPath($attribute);
        foreach (FileHelper::findFiles($userTempDir) as $file) {
            if (substr(FileHelper::getMimeType($file), 0, 5) === 'image') {
                $initialPreview[] = Html::img(['/attachments/file/download-temp', 'filename' => basename($file)], ['class' => 'file-preview-image']);
            } else {
                $initialPreview[] = Html::beginTag('div', ['class' => 'file-preview-other']) .
                    Html::beginTag('h2') .
                    Html::tag('i', '', ['class' => 'glyphicon glyphicon-file']) .
                    Html::endTag('h2') .
                    Html::endTag('div');
            }
        }

        foreach ($this->getFiles($attribute) as $file) {
            if (substr($file->mime, 0, 5) === 'image') {
                $initialPreview[] = Html::img($file->getUrl(), ['class' => 'file-preview-image']);
            } else {
                $initialPreview[] = Html::beginTag('div', ['class' => 'file-preview-other']) .
                    Html::beginTag('h2') .
                    Html::tag('i', '', ['class' => 'glyphicon glyphicon-file']) .
                    Html::endTag('h2') .
                    Html::endTag('div');
            }
        }

        return $initialPreview;
    }

    public function getInitialPreviewConfig($attribute)
    {
        $initialPreviewConfig = [];

        $userTempDir = $this->getModule()->getUserDirPath($attribute);
        foreach (FileHelper::findFiles($userTempDir) as $file) {
            $filename = basename($file);
            $initialPreviewConfig[] = [
                'caption' => $filename,
                'url' => Url::to(['/attachments/file/delete-temp',
                    'filename' => $filename
                ]),
            ];
        }

        foreach ($this->getFiles($attribute) as $index => $file) {
            $initialPreviewConfig[] = [
                'caption' => "$file->name.$file->type",
                'url' => Url::toRoute(['/attachments/file/delete',
                    'id' => $file->id
                ]),
            ];
        }

        return $initialPreviewConfig;
    }
}