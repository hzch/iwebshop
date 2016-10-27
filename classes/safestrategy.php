<?php
/**
 * check website safe strategy
 * @date 2013/9/8 17:00:46
 * @author nswe
 */
class safeStrategy
{
	private $safeInfo = array();

	/**
	 * constructor
	 */
	public function __construct()
	{
	}

	/**
	 * start check website safe options and return a array
	 * @return array
	 */
	public function check()
	{
		$this->cInstall();
		$this->cAuthorize();
		return $this->safeInfo;
	}

	/**
	 * check authorize info
	 */
	private function cAuthorize()
	{
		$return = Proxy::getAuthorize();
		if($return == false)
		{
			$this->safeInfo[] = array('content' => '您所使用的软件为盗版，请尽快购买商业授权版本，更稳定，更高效！');
		}
	}

	/**
	 * check the install dir whether exists
	 * @return boolean
	 */
	private function cInstall()
	{
		$appBasePath = IWeb::$app->getBasePath();
		$installPath = $appBasePath . 'install';

		if(file_exists($installPath))
		{
			$this->safeInfo[] = array('content' => '您的安装目录没有删除，为了商店安全，请尽快删除或者重新命名');
		}
	}
}