<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * @author Athul AK
 * athul@owpm.com
 *
 */

$config['email_protocol'] = 'smtp';

$config['smtp_host'] = 'yourconference.live';

$config['smtp_port'] = 465;

$config['smtp_user'] = 'no-reply@yourconference.live';

//$config['smtp_pass'] = 'yc_email1234#';
$config['smtp_pass'] = 'yc_email1234#';

$config['mailtype'] = 'html';

$config['charset'] = 'iso-8859-1';

$config['newline'] = "\r\n";
$config['crlf'] = "\r\n";
$config['wordwrap'] = "\r\n";
$config['smtp_auth'] = true;
$config['mailPath'] = "/usr/sbin/sendmail";
$config['smtp_crypto'] = "ssl";

//Working config -- Rexter
//			$config['protocol'] = 'smtp';
//		$config['smtp_host'] = 'owpm2.com';
//		$config['mailType'] = 'html';
//		$config['mailPath'] = '/usr/sbin/sendmail';
//		$config['smtp_auth'] = true;
//		$config['newline'] = "\r\n";
//
//		$config['charset'] = 'iso-8859-1';
//		$config['smtp_user'] = "no-reply@owpm2.com";
//		$config['smtp_pass'] = "owpm2_email#";
//		$config['smtp_port'] = 465;
//		$config['smtp_timeout'] = 5;
//		$config['crlf'] = "\r\n";
//		$config['wordwrap'] = TRUE;
//		$config['smtp_crypto'] = 'ssl';
