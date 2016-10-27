<?php
/**
 * @copyright (c) 2011 jooyea.cn
 * @file smtp_class.php
 * @brief 发送邮件,包括:(1)通过本地的smtp服务发送(php内置mail);(2)通过Socket发送;
 * @author chendeshan
 * @date 2010-12-6
 * @version 0.6
 */

/**
 * @class ISmtp
 * @brief 发送电子邮件
 */
class ISmtp
{
	private $smtp_port;  //端口号
	private $time_out;   //超时时间
	private $host_name;  //主机名
	private $log_file;   //日志名
	private $relay_host; //响应主机ip
	private $auth;       //认证
	private $user;       //用户名
	private $pass;       //端口
	private $sock;
	private $debug;
	private $sendType;

	/**
	 * @brief 构造函数
	 * @param string $relay_host 响应的服务器地址 如果利用本地smtp发送邮件请留空值
	 * @param int $smtp_port 端口号
	 * @param string $user 用户名
	 * @param string $pass 密码
	 * @param bool $debug 是否开启debug调试模式
	 * @note 如果采用本地服务器方式发送邮件那么所有参数可以不填写
	 */
	function __construct($relay_host = "", $smtp_port = 25, $user = false, $pass = false, $debug = false)
	{
		$this->relay_host = $relay_host;
		$this->smtp_port = $smtp_port;
		$this->user = $user;
		$this->pass = $pass;

		$this->debug = $debug;
		$this->time_out = 40;
		$this->host_name = "localhost"; //测试本地socket

		$this->auth = false;
		if($this->user || $this->pass)
		{
			$this->auth = true;
		}

		if(!$this->relay_host)
		{
			$this->sendType = "mail";
		}

		//记录日志文件路径
		$this->log_file = "";

		$this->sock = FALSE;
	}

	/**
	 * @brief 发送邮件前数据初始化
	 * @param string $to 收件人email地址
	 * @param string $from 发件人email地址
	 * @param string $subject 邮件主题
	 * @param string $body 邮件内容
	 * @param string $mailtype 邮件发送类型
	 * @param string $cc 抄送其他人
	 * @param string $bcc 暗送其他人
	 * @param string $additional_headers 附加头信息
	 * @return bool 发送状态 值: true:成功; false:失败;
	 */
	public function send($to, $from = "" , $subject = "", $body = "", $additional_headers = "", $mailtype = "HTML", $cc = "", $bcc = "")
	{
		$mail_from = $this->get_address($this->strip_comment($from));
		$body = preg_replace("/(^|(\r\n))(\\.)/i", "\\1.\\3", $body);
		$header="";
		$header .= "MIME-Version:1.0\r\n";
		if($mailtype=="HTML")
		{
			$header .= "Content-Type:text/html\r\n";
		}
		$header .= "To: ".$to."\r\n";
		if ($cc != "")
		{
			$header .= "Cc: ".$cc."\r\n";
		}
		$header .= "From: $from<".$from.">\r\n";
		$header .= "Subject: ".$subject."\r\n";
		$header .= $additional_headers;
		$header .= "Date: ".date("r")."\r\n";
		$header .= "X-Mailer:By Redhat (PHP/".phpversion().")\r\n";
		list($msec, $sec) = explode(" ", microtime());
		$header .= "Message-ID: <".date("YmdHis", $sec).".".($msec*1000000).".".$mail_from.">\r\n";
		$TO = explode(",", $this->strip_comment($to));

		if ($cc != "")
		{
			$TO = array_merge($TO, explode(",", $this->strip_comment($cc)));
		}

		if ($bcc != "") {
			$TO = array_merge($TO, explode(",", $this->strip_comment($bcc)));
		}

		$sent = TRUE;
		foreach ($TO as $rcpt_to)
		{
			//php内置mail发送
			if($this->sendType=="mail")
			{
				return mail($rcpt_to,'',$body,$header);
			}

			//socket发送方式
			$rcpt_to = $this->get_address($rcpt_to);
			if (!$this->smtp_sockopen($rcpt_to))
			{
				$this->log_write("Error: Cannot send email to ".$rcpt_to."\n");
				$sent = FALSE;
				continue;
			}
			if ($this->smtpSend($this->host_name, $mail_from, $rcpt_to, $header, $body))
			{
				$this->log_write("E-mail has been sent to <".$rcpt_to.">\n");
			} else
			{
				$this->log_write("Error: Cannot send email to <".$rcpt_to.">\n");
				$sent = FALSE;
			}
			fclose($this->sock);
			$this->log_write("Disconnected from remote host\n");
		}
		return $sent;
	}

	/**
	 * @brief 开始发送邮件
	 * @param string $helo 链接smtp hello
	 * @param string $from 发件人
	 * @param string $to 收件人
	 * @param string $header 头信息
	 * @param string $body 邮件内容
	 * @return bool 发送状态 值: true:成功; false:失败;
	 */
	private function smtpSend($helo, $from, $to, $header, $body = "")
	{

		if (!$this->smtp_putcmd("HELO", $helo))
		{
			return $this->smtp_error("sending HELO command");
		}

		if($this->auth)
		{
			if (!$this->smtp_putcmd("AUTH LOGIN", base64_encode($this->user)))
			{
				return $this->smtp_error("sending HELO command");
			}

			if (!$this->smtp_putcmd("", base64_encode($this->pass)))
			{
				return $this->smtp_error("sending HELO command");
			}
		}
		if (!$this->smtp_putcmd("MAIL", "FROM:<".$from.">"))
		{
			return $this->smtp_error("sending MAIL FROM command");
		}

		if (!$this->smtp_putcmd("RCPT", "TO:<".$to.">"))
		{
			return $this->smtp_error("sending RCPT TO command");
		}

		if (!$this->smtp_putcmd("DATA"))
		{
			return $this->smtp_error("sending DATA command");
		}

		if (!$this->smtp_message($header, $body))
		{
			return $this->smtp_error("sending message");
		}

		if (!$this->smtp_eom())
		{
			return $this->smtp_error("sending <CR><LF>.<CR><LF> [EOM]");
		}

		if (!$this->smtp_putcmd("QUIT"))
		{
			return $this->smtp_error("sending QUIT command");
		}

		return TRUE;
	}

	/**
	 * @brief 链接socket
	 * @param string $address 自定义链接地址
	 * @return bool 是否成功打开socket
	 */
	private function smtp_sockopen($address)
	{
		if ($this->relay_host == "")
		{
			return $this->smtp_sockopen_mx($address);
		} else
		{
			return $this->smtp_sockopen_relay();
		}
	}

	/**
	 * @brief 打开smtp配置的socket
	 * @return bool 是否成功打开socket
	 */
	private function smtp_sockopen_relay()
	{
		$this->log_write("Trying to ".$this->relay_host.":".$this->smtp_port."\n");
		$this->sock = @fsockopen($this->relay_host, $this->smtp_port, $errno, $errstr, $this->time_out);
		if (!($this->sock && $this->smtp_ok()))
		{
			$this->log_write("Error: Cannot connenct to relay host ".$this->relay_host."\n");
			$this->log_write("Error: ".$errstr." (".$errno.")\n");
			return FALSE;
		}
		$this->log_write("Connected to relay host ".$this->relay_host."\n");
		return TRUE;;
	}

	/**
	 * @brief 打开自定义的socket链接
	 * @return bool 是否成功打开socket
	 */
	private function smtp_sockopen_mx($address)
	{
		$domain = preg_replace("/^.+@([^@]+)$/i", "\\1", $address);
		if (!@getmxrr($domain, $MXHOSTS))
		{
			$this->log_write("Error: Cannot resolve MX \"".$domain."\"\n");
			return FALSE;
		}
		foreach ($MXHOSTS as $host)
		{
			$this->log_write("Trying to ".$host.":".$this->smtp_port."\n");
			$this->sock = @fsockopen($host, $this->smtp_port, $errno, $errstr, $this->time_out);
			if (!($this->sock && $this->smtp_ok())) {
				$this->log_write("Warning: Cannot connect to mx host ".$host."\n");
				$this->log_write("Error: ".$errstr." (".$errno.")\n");
				continue;
			}
			$this->log_write("Connected to mx host ".$host."\n");
			return TRUE;
		}
		$this->log_write("Error: Cannot connect to any mx hosts (".implode(", ", $MXHOSTS).")\n");
		return FALSE;
	}

	/**
	 * @brief 邮件的信息处理
	 * @param string $header 头信息
	 * @param string $body 内容
	 * @return bool 信息处理状态
	 */
	private function smtp_message($header, $body)
	{
		fwrite($this->sock, $header."\r\n".$body);
		$this->smtp_debug("> ".str_replace("\r\n", "\n"."> ", $header."\n> ".$body."\n> "));

		return TRUE;
	}

	private function smtp_eom()
	{
		fwrite($this->sock, "\r\n.\r\n");
		$this->smtp_debug(". [EOM]\n");

		return $this->smtp_ok();
	}

	private function smtp_ok()
	{
		$response = str_replace("\r\n", "", fgets($this->sock, 512));
		$this->smtp_debug($response."\n");

		if (!preg_match("/^[23]/i", $response))
		{
			fputs($this->sock, "QUIT\r\n");
			fgets($this->sock, 512);
			$this->log_write("Error: Remote host returned \"".$response."\"\n");
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * @brief 命令处理
	 * @param string $cmd 命令
	 * @param string $arg
	 * @return bool 命令处理状态
	 */
	private function smtp_putcmd($cmd, $arg = "")
	{

		if ($arg != "")
		{
			if($cmd=="") $cmd = $arg;
			else $cmd = $cmd." ".$arg;
		}

		fwrite($this->sock, $cmd."\r\n");
		$this->smtp_debug("> ".$cmd."\n");

		return $this->smtp_ok();
	}

	private function smtp_error($string)
	{
		$this->log_write("Error: Error occurred while ".$string.".\n");
		return FALSE;
	}

	/**
	 * @brief 写入日志
	 * @param string 信息
	 * @return bool 写入状态
	 */
	private function log_write($message)
	{
		$this->smtp_debug($message);

		if ($this->log_file == "")
		{
			return TRUE;
		}

		$message = date("M d H:i:s ").get_current_user()."[".getmypid()."]: ".$message;
		if (!@file_exists($this->log_file) || !($fp = @fopen($this->log_file, "a")))
		{
			$this->smtp_debug("Warning: Cannot open log file \"".$this->log_file."\"\n");
			return FALSE;
		}
		flock($fp, LOCK_EX);
		fputs($fp, $message);
		fclose($fp);

		return TRUE;
	}

	private function strip_comment($address)
	{
		$comment = "\\([^()]*\\)";
		while (preg_match('/'.$comment.'/i', $address))
		{
			$address = preg_replace('/'.$comment.'/i', "", $address);
		}

		return $address;
	}

	/**
	 * @brief 处理email地址
	 * @param string $address 地址
	 * @return string 处理后的地址
	 */
	private function get_address($address)
	{
		$address = preg_replace("/([ \t\r\n])+/i", "", $address);
		$address = preg_replace("/^.*<(.+)>.*$/i", "\\1", $address);

		return $address;
	}

	/**
	 * @brief debug调试模式信息输出
	 * @param string $message 信息
	 */
	private function smtp_debug($message)
	{
		if ($this->debug)
		{
			echo $message."<br>";
		}
	}

	/**
	 * @brief 获取附件信息
	 * @param string $image_tag 附件文件
	 * @return array 附件信息 键: context:内容; filename:文件名; type:文件类型;
	 */
	public function get_attach_type($image_tag)
	{
		$filedata = array();
		$img_file_con=fopen($image_tag,"r");
		unset($image_data);
		while ($tem_buffer=addSlashes(fread($img_file_con,filesize($image_tag))))
			$image_data.=$tem_buffer;
		fclose($img_file_con);
		$filedata['context'] = $image_data;
		$filedata['filename']= basename($image_tag);
		$extension=substr($image_tag,strrpos($image_tag,"."),strlen($image_tag)-strrpos($image_tag,"."));
		switch($extension){
		case ".gif":
			$filedata['type'] = "image/gif";
			break;
		case ".gz":
			$filedata['type'] = "application/x-gzip";
			break;
		case ".htm":
			$filedata['type'] = "text/html";
			break;
		case ".html":
			$filedata['type'] = "text/html";
			break;
		case ".jpg":
			$filedata['type'] = "image/jpeg";
			break;
		case ".tar":
			$filedata['type'] = "application/x-tar";
			break;
		case ".txt":
			$filedata['type'] = "text/plain";
			break;
		case ".zip":
			$filedata['type'] = "application/zip";
			break;
		default:
			$filedata['type'] = "application/octet-stream";
			break;
		}
		return $filedata;
	}
}
?>
