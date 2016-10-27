<?php
/**
 * @brief ��̬��������ͼ��
 */
class Thumb
{
	/**
	 * @brief ��������ͼ
	 * @param string $image_url ͼƬ·��
	 * @param int $width ͼƬ���
	 * @param int $height ͼƬ�߶�
	 */
    public static function get($image_url,$width=100,$height=100)
    {
    	if($image_url == '')
    	{
    		return '';
    	}

        $fileExt       = IFile::getFileSuffix($image_url);
        $extName       = "_{$width}_{$height}";
        $thumbFileName = str_replace('.'.$fileExt,$extName.'.'.$fileExt,$image_url);

        if(is_file(IWeb::$app->getRuntimePath().$thumbFileName) == false)
        {
        	PhotoUpload::thumb($image_url,$width,$height,"_{$width}_{$height}",IWeb::$app->getRuntimePath().dirname($image_url));
        }

        return 'runtime/'.$thumbFileName;
    }
}