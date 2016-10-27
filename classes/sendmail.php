<?php
/**
 * @copyright (c) 2011 [group]
 * @file sendmail.php
 * @brief 积分日志记录处理类
 * @author chendeshan
 * @date 2011-6-15 14:58:39
 * @version 0.6
 */
class SendMail
{
	private $config = array();//邮件配置信息
	private $smtp   = null;   //邮件发送对象
	private $error  = '';     //错误信息

	//构造函数
	public function __construct($site_config = null)
	{
		if($site_config == null)
		{
			$siteConfigObj = new Config("site_config");
			$site_config   = $siteConfigObj->getInfo();
			$this->config  = $site_config;
		}
		else
		{
			$this->config  = $site_config;
		}

		if($this->checkEmailConf($site_config))
		{
			//使用系统mail函数发送
			if(isset($site_config['email_type']) && $site_config['email_type']=='2')
			{
				$this->smtp = new ISmtp();
			}
			else
			{
				//使用外部SMTP服务器发送
				$server     = $site_config['smtp'];
				$port       = $site_config['smtp_port'];
				$account    = $site_config['smtp_user'];
				$password   = $site_config['smtp_pwd'];
				$this->smtp = new ISmtp($server,$port,$account,$password);
			}
			if(!$this->smtp)
			{
				$this->error = '无法创建smtp类';
			}
		}
		else
		{
			$this->error = '配置参数填写不完整';
		}
	}

	//获取错误信息
	public function getError()
	{
		return $this->error;
	}

	/**
	 * @brief 检查邮件配置信息的合法性
	 * @parms $site_config array 配置信息
	 * @return bool true:成功;false:失败;
	 */
	public function checkEmailConf($site_config)
	{
		if(isset($site_config['email_type']) && isset($site_config['mail_address']))
		{
			if($site_config['email_type'] == 1)
			{
				$mustConfig = array('smtp','smtp_user','smtp_pwd','smtp_port');
				foreach($mustConfig as $val)
				{
					if(!isset($site_config[$val]) || $site_config[$val] == '')
					{
						return false;
					}
				}
				return true;
			}
			else
			{
				return true;
			}
		}
		else
		{
			return false;
		}
	}

	/**
	 * @brief 邮件发送
	 * @parms  $to      string 收件人
	 * @parms  $title   string 标题
	 * @parms  $content string 内容
	 * @parms  $bcc     string 抄送人(";"分号间隔的email地址)
	 * @return bool true:成功;false:失败;
	 */
	public function send($to,$title,$content,$bcc = '')
	{
		if(is_object($this->smtp))
		{
			$from = $this->config['mail_address'];
			return $this->smtp->send($to,$from,$title,$content , "" , "HTML" , "" , $bcc );
		}
		else
		{
			return false;
		}
	}

	//获取配置信息
	public function getConfigItem($key)
	{
		return isset($this->config[$key]) ? $this->config[$key] : null;
	}
}