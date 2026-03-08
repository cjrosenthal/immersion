<?php

class EmailService {
    
    public static function send($to, $subject, $body, $cc = null, $bcc = null) {
        require_once BASE_PATH . '/lib/Application.php';
        
        $config = Application::getConfig();
        
        // Build headers
        $headers = [];
        $headers[] = 'From: ' . $config['smtp']['from_name'] . ' <' . $config['smtp']['from_email'] . '>';
        $headers[] = 'Reply-To: ' . $config['smtp']['from_email'];
        $headers[] = 'X-Mailer: PHP/' . phpversion();
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/plain; charset=UTF-8';
        
        // Add CC recipients
        if ($cc) {
            if (is_array($cc)) {
                $headers[] = 'Cc: ' . implode(', ', $cc);
            } else {
                $headers[] = 'Cc: ' . $cc;
            }
        }
        
        // Add BCC recipients
        if ($bcc) {
            if (is_array($bcc)) {
                $headers[] = 'Bcc: ' . implode(', ', $bcc);
            } else {
                $headers[] = 'Bcc: ' . $bcc;
            }
        }
        
        // Send email
        $success = mail($to, $subject, $body, implode("\r\n", $headers));
        
        if (!$success) {
            error_log("Email send failed to: " . $to);
        }
        
        return $success;
    }
}
