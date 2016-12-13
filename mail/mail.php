<?php
/*
 * Paste <https://github.com/jordansamuel/PASTE>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 3
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License in GPL.txt for more details.
 */
error_reporting(1);

function default_mail($admin_mail, $admin_name, $sent_mail, $subject, $body)
{
    // Functions
    require_once('class.phpmailer.php');
    
    $mail = new PHPMailer();
    
    $body = eregi_replace("[\]", '', $body);
    
    $mail->AddReplyTo($admin_mail, $admin_name);
    
    $mail->SetFrom($admin_mail, $admin_name);
    
    $mail->AddReplyTo($admin_mail, $admin_name);
    
    $address = $sent_mail;
    
    $mail->AddAddress($address);
    
    $mail->Subject = $subject;
    
    $mail->MsgHTML($body);
    
    $mail->AltBody = "To view the message, please use an HTML compatible viewer";
    
    if (!$mail->Send()) {
        $msg = "Mailer Error: " . $mail->ErrorInfo;
    } else {
        $msg = "Message has been sent";
    }
    return $msg;
    
}

function smtp_mail($smtp_host, $smtp_port = 587, $smtp_auth, $smtp_user, $smtp_pass, $smtp_sec = 'tls', $admin_mail, $admin_name, $sent_mail, $subject, $body)
{
    require_once('class.phpmailer.php');
    require_once('class.smtp.php');
    $mail = new PHPMailer;
    $mail->IsSMTP(); // Set mailer to use SMTP
    $mail->Host       = $smtp_host; // Specify main and backup server
    $mail->Port       = $smtp_port; // Set the SMTP port
    $mail->SMTPAuth   = $smtp_auth; // Enable SMTP authentication
    $mail->Username   = $smtp_user; // SMTP username
    $mail->Password   = $smtp_pass; // SMTP password
    $mail->SMTPSecure = $smtp_sec; // Enable encryption, 'ssl' also accepted
    
    $mail->From     = $admin_mail;
    $mail->FromName = $admin_name;
    $mail->AddAddress($sent_mail); // Add a recipient
    
    $mail->IsHTML(true); // Set email format to HTML
    
    $mail->Subject = $subject;
    $mail->Body    = $body;
    $mail->AltBody = "To view this message, please use an HTML compatible viewer";
    
    if (!$mail->Send()) {
        $msg = 'Mailer Error: ' . $mail->ErrorInfo;
    } else {
        $msg = 'Message has been sent';
    }
    return $msg;
}
?>