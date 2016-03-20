<?php
/**
* class mail_event
*/

class mail_event {
    var $templates_path = '';
    var $templates_ext  = '.txt';

    var $debug  = array();
    var $errors = array();

    function mail_event($templates_path='', $templates_ext  = '.txt') {
        require_once(cms_LIB_PATH.'class.phpmailer.php');
        require_once(cms_LIB_PATH.'class.smtp.php');
        $this->templates_path = $templates_path;
        $this->templates_ext  = $templates_ext;
    }

    function event($event, $info) {
        //echo "<pre>"; print_r($info); die();
        $sp = new strcom_parser();
        $filename = $this->templates_path.$event.$this->templates_ext;
        if (file_exists($filename)) {
        } else {
            $this->errors[] = 'File not exists: '.$filename;
            return false;
        }

        if (!$content = file_get_contents($filename)) {
            $this->errors[] = 'Error reading file: '.$filename;
            return false;
        }
        $event_result = array();
        while (list($key, $value) = each ($info)) {
            $content = str_replace('{#'.$key.'}', $value, $content);
        }
        // очищуєм пусті зміннi
        $empty = $sp->getTagsList($content, "{#", "}", "");
        while (list($key, $value) = each ($empty)) {
            $content = str_replace('{#'.$value.'}', '', $content);
        }
        $blocks = array();
        $blocks = $sp->getTag($content, "message", "@@", "@@", "");
        $event_result = array('empty'=>0, 'send'=>0, 'failed'=>0);
        $mail = new PHPMailer();
        while (list($i, $value) = each ($blocks)) {
             $from_address = array_shift($sp->getVar($blocks[$i], 'from'));
             $from_name    = array_shift($sp->getVar($blocks[$i], 'from_name'));
             $header_cc    = array_shift($sp->getVar($blocks[$i], 'cc'));
             $header_bcc   = array_shift($sp->getVar($blocks[$i], 'bcc'));
             $subject      = array_shift($sp->getVar($blocks[$i], 'subject'));
             $to_address   = array_shift($sp->getVar($blocks[$i], 'to'));
    	     $to_name      = array_shift($sp->getVar($blocks[$i], 'to_name'));
             $message      = array_shift($sp->getTag($blocks[$i], 'body', '[', ']', ''));
             $mail_type    = array_shift($sp->getVar($blocks[$i], 'mail_type'));
    	     $reply_name   = $from_name;
    	     $reply_address = $from_address;
    	     $error_delivery_address = $from_address;
             if (empty($to_address)) {
                 $event_result['empty']++;
                 continue;
             }
            $mail->From     = $from_address;
            $mail->FromName = $from_address;
            $mail->CharSet = 'UTF-8';
            $mail->Mailer   = "smtp";
            $mail->isHTML(true);
            //$mail->Mailer   = "sendmail";
            //$mail->Mailer   = "mail";
            $mail->Subject = $subject;

             if ($mail_type == 'html') {
                $body  = $message; //html
                $text_body  = '';// Plain text
             } else {
                $text_body  = $message;// Plain text
				//$message = eregi_replace("((http|https|mailto|ftp):(\/\/)?[^[:space:]<>]{1,})", "<a href='\1'>\1</a>",$message);
				//$message = preg_replace('#(http://)([^\s]*)#', '<a href="\\1\\2">\\1\\2</a>', $message);
                $message  = nl2br($message); //html
				$body  = $message; //html
             }
            $mail->Body    = $body;
            $mail->AltBody = $text_body;
            $mail->AddAddress($to_address);
            //$mail->AddBCC($mail_info['from_email']);
            //$mail->AddStringAttachment(Date('d.m.Y H:i:s'), "time.txt");

            if (isset($info['file']) && is_file($info['file']))
            {
            	$mail->AddAttachment($info['file'], "bill.xls");
            }

            if (!$mail->Send()) {
                //$this->errors[] = "Mail($to_address, $subject) failed: ".$error;
                $this->errors[] = "Mail($to_address, $subject) failed"; 
                $event_result['failed']++;
                //$result = false;
            } 
            else {
                //$result = true;
                $event_result['send']++;
            }
         	
            $mail->ClearAddresses();
            $mail->ClearAttachments();
        }
        return $event_result;
    }

    function event_old($event, $info) {
        $sp = new strcom_parser();
        $filename = $this->templates_path.$event.$this->templates_ext;
        if (file_exists($filename)) {
        } else {
            $this->errors[] = 'File not exists: '.$filename;
            return false;
        }
        if (!$content = file_get_contents($filename)) {
            $this->errors[] = 'Error reading file: '.$filename;
            return false;
        }
        $event_result = array();
        while (list($key, $value) = each ($info)) {
            $content = str_replace('{#'.$key.'}', $value, $content);
        }
        // очищуєм пусті зміннi
        $empty = $sp->getTagsList($content, "{#", "}", "");
        while (list($key, $value) = each ($empty)) {
            $content = str_replace('{#'.$value.'}', '', $content);
        }
        $blocks = array();
        $blocks = $sp->getTag($content, "message", "@@", "@@", "");
        $event_result = array('empty'=>0, 'send'=>0, 'failed'=>0);
        while (list($i, $value) = each ($blocks)) {
             $from_address = array_shift($sp->getVar($blocks[$i], 'from'));
             $from_name    = array_shift($sp->getVar($blocks[$i], 'from_name'));
             $header_cc    = array_shift($sp->getVar($blocks[$i], 'cc'));
             $header_bcc   = array_shift($sp->getVar($blocks[$i], 'bcc'));
             $subject      = array_shift($sp->getVar($blocks[$i], 'subject'));
             $to_address   = array_shift($sp->getVar($blocks[$i], 'to'));
    	     $to_name      = array_shift($sp->getVar($blocks[$i], 'to_name'));
             $message      = array_shift($sp->getTag($blocks[$i], 'body', '[', ']', ''));
             $mail_type    = array_shift($sp->getVar($blocks[$i], 'mail_type'));
    	     $reply_name   = $from_name;
    	     $reply_address = $from_address;
    	     $error_delivery_address = $from_address;
             if (empty($to_address)) {
                 $event_result['empty']++;
                 continue;
             }
        	 $email_message=new email_message_class;
        	 $email_message->default_charset = "UTF-8";
        	 if (!empty($to_address))        {$email_message->SetEncodedEmailHeader("To",$to_address,$to_name);}
        	 if (!empty($from_address))      {$email_message->SetEncodedEmailHeader("From",$from_address,$from_name);}
        	 if (!empty($reply_address))     {$email_message->SetEncodedEmailHeader("Reply-To",$reply_address,$reply_name);}
        	 if (!empty($header_cc))         {$email_message->SetEncodedEmailHeader("Cc",$header_cc,'');}
        	 if (!empty($header_bcc))        {$email_message->SetEncodedEmailHeader("Bcc",$header_bcc,'');}
        	 $email_message->SetEncodedHeader("Sender",$from_address);
        	 $email_message->SetEncodedHeader("Subject",$subject);
             if ($mail_type == 'html') {
        	    $email_message->AddEncodedQuotedPrintableTextPart($subject);
                $email_message->AddHTMLPart($message);
        	    //$email_message->AddQuotedPrintableHTMLPart($message);
             } else {
        	    $email_message->AddPlainTextPart($message);
             }
             //die($message);
             $error=$email_message->Send();
             if(strcmp($error,"")) {
                    $this->errors[] = "Mail($to_address, $subject) failed: ".$error;
                    $event_result['failed']++;
             } else {
                    $event_result['send']++;
             }
        }
        return $event_result;
    }

}

?>
