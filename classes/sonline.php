<?php
/**
 * @copyright (c) 2011 jooyea
 * @file sonline.php
 * @brief ���߿ͷ����,�˲���ǻ���jquery��
 * @author chendeshan
 * @date 2013/7/15 0:29:17
 * @version 1.0.0
 */
class Sonline
{
	//���·��
	public $path;

	/**
	 * ���캯��
	 * @param string $style ���
	 */
	public function __construct($style = 'red')
	{
		$this->path = IUrl::creatUrl().'plugins/sonline/';

echo <<< OEF
	<link rel="stylesheet" href="{$this->path}style/{$style}.css" />
	<script type="text/javascript" src="{$this->path}js/jquery.Sonline.js"></script>
OEF;
	}

	/**
	 * չʾ���
	 * @param string $tel �绰����
	 * @param string $qqSer ���л�������
	 */
	public function show($tel,$qqSer)
	{
		if(!$qqSer)
		{
			return null;
		}
		$qqArray = unserialize($qqSer);
		$tempArray = array();
		foreach($qqArray as $val)
		{
			$tempArray[] = $val['qq']."|".$val['name'];
		}
		$qqResult = join(',',$tempArray);

echo <<< OEF
<script type='text/javascript'>
$(function(){
	$().Sonline({
		"Position":"left",
		"Tel":"{$tel}",
		"Qqlist":"{$qqResult}"
	});
});
</script>
OEF;
	}
}