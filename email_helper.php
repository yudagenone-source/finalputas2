<?php
require_once 'config/database.php';

function sendInvoiceEmail($pdo, $student_id, $order_id) {
    try {
        // Get email settings
        $stmt = $pdo->prepare("SELECT * FROM email_settings WHERE id = 1");
        $stmt->execute();
        $email_settings = $stmt->fetch();

        if (!$email_settings || !$email_settings['is_active']) {
            return false;
        }

        // Get student and payment details
        $stmt = $pdo->prepare("
            SELECT s.nama_lengkap, s.email, p.order_id, p.gross_amount, 
                   p.harga_kursus, p.biaya_pendaftaran, p.kode_promo, p.created_at,
                   pr.nama_promo
            FROM siswa s 
            JOIN payments p ON s.id = p.student_id 
            LEFT JOIN promo_codes pr ON p.kode_promo = pr.kode_promo
            WHERE s.id = ? AND p.order_id = ?
        ");
        $stmt->execute([$student_id, $order_id]);
        $data = $stmt->fetch();

        if (!$data) {
            return false;
        }

        // Create email content
        $subject = "Invoice Pendaftaran - Anastasya Vocal Arts";
        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .header { background-color: #6366f1; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                .invoice-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                .invoice-table th, .invoice-table td { border: 1px solid #ddd; padding: 10px; text-align: left; }
                .invoice-table th { background-color: #f8f9fa; }
                .total { font-weight: bold; background-color: #e9ecef; }
                .footer { margin-top: 30px; padding: 20px; background-color: #f8f9fa; text-align: center; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>Invoice Pendaftaran</h1>
                <h2>Anastasya Vocal Arts</h2>
            </div>
            <div class='content'>
                <p>Yth. " . htmlspecialchars($data['nama_lengkap']) . ",</p>
                <p>Terima kasih telah mendaftar di Anastasya Vocal Arts. Berikut adalah detail invoice pembayaran Anda:</p>

                <table class='invoice-table'>
                    <tr><th>Order ID</th><td>" . htmlspecialchars($data['order_id']) . "</td></tr>
                    <tr><th>Tanggal</th><td>" . date('d F Y', strtotime($data['created_at'])) . "</td></tr>
                    <tr><th>Paket</th><td>" . htmlspecialchars($data['nama_promo'] ?? 'Reguler') . "</td></tr>
                    <tr><th>Kode Promo</th><td>" . htmlspecialchars($data['kode_promo'] ?? '-') . "</td></tr>
                </table>

                <table class='invoice-table'>
                    <tr><th>Item</th><th>Harga</th></tr>
                    <tr><td>Biaya Kursus</td><td>Rp " . number_format($data['harga_kursus'], 0, ',', '.') . "</td></tr>
                    <tr><td>Biaya Pendaftaran</td><td>Rp " . number_format($data['biaya_pendaftaran'], 0, ',', '.') . "</td></tr>
                    <tr class='total'><td>Total</td><td>Rp " . number_format($data['gross_amount'], 0, ',', '.') . "</td></tr>
                </table>

                <p><strong>Silakan selesaikan pembayaran Anda melalui link berikut:</strong></p>
                <p><a href='" . "https://" . $_SERVER['HTTP_HOST'] . "/payment_link.php?order_id=" . urlencode($data['order_id']) . "&email=" . urlencode($data['email']) . "' style='background-color: #6366f1; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Bayar Sekarang</a></p>
            </div>
            <div class='footer'>
                <p>Anastasya Vocal Arts<br>
                Email: " . htmlspecialchars($email_settings['from_email']) . "</p>
            </div>
        </body>
        </html>
        ";

        // Send email using PHP mail() or your preferred method
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: " . $email_settings['from_name'] . " <" . $email_settings['from_email'] . ">" . "\r\n";

        return mail($data['email'], $subject, $message, $headers);

    } catch (Exception $e) {
        error_log("Email error: " . $e->getMessage());
        return false;
    }
}

function sendPasswordResetEmail($pdo, $email, $nama_lengkap, $token) {
    try {
        // Get email settings
        $stmt = $pdo->prepare("SELECT * FROM email_settings WHERE id = 1");
        $stmt->execute();
        $email_settings = $stmt->fetch();

        if (!$email_settings || !$email_settings['is_active']) {
            return false;
        }

        // Create reset URL
        $reset_url = "https://" . $_SERVER['HTTP_HOST'] . "/student/reset_password.php?token=" . urlencode($token);

        // Create email content
        $subject = "Reset Password - Anastasya Vocal Arts";
        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .header { background-color: #EE3A6A; color: white; padding: 20px; text-align: center; }
                .content { padding: 30px; }
                .button { 
                    display: inline-block; 
                    background-color: #EE3A6A; 
                    color: white; 
                    padding: 12px 24px; 
                    text-decoration: none; 
                    border-radius: 5px; 
                    margin: 20px 0;
                }
                .footer { margin-top: 30px; padding: 20px; background-color: #f8f9fa; text-align: center; font-size: 12px; color: #666; }
                .warning { background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>Reset Password</h1>
                <h2>Anastasya Vocal Arts</h2>
            </div>
            <div class='content'>
                <p>Halo " . htmlspecialchars($nama_lengkap) . ",</p>
                <p>Kami menerima permintaan untuk mereset password akun Anda di Anastasya Vocal Arts.</p>
                
                <p>Klik tombol di bawah ini untuk mereset password Anda:</p>
                <p style='text-align: center;'>
                    <a href='" . $reset_url . "' class='button'>Reset Password Saya</a>
                </p>
                
                <p>Atau salin dan tempel link berikut di browser Anda:</p>
                <p style='word-break: break-all; background-color: #f8f9fa; padding: 10px; border-radius: 5px;'>" . htmlspecialchars($reset_url) . "</p>
                
                <div class='warning'>
                    <strong>Penting:</strong>
                    <ul>
                        <li>Link ini hanya berlaku selama 1 jam</li>
                        <li>Jika Anda tidak meminta reset password, abaikan email ini</li>
                        <li>Jangan bagikan link ini dengan siapapun</li>
                    </ul>
                </div>
            </div>
            <div class='footer'>
                <p>Anastasya Vocal Arts<br>
                Email: " . htmlspecialchars($email_settings['from_email']) . "</p>
                <p>Email ini dikirim secara otomatis, mohon jangan membalas email ini.</p>
            </div>
        </body>
        </html>
        ";

        // Send email
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: " . $email_settings['from_name'] . " <" . $email_settings['from_email'] . ">" . "\r\n";

        return mail($email, $subject, $message, $headers);

    } catch (Exception $e) {
        error_log("Password reset email error: " . $e->getMessage());
        return false;
    }
}

function sendWhatsAppNotification($pdo, $message) {
    try {
        // Get admin settings
        $stmt = $pdo->prepare("SELECT whatsapp_number FROM admin_settings WHERE id = 1");
        $stmt->execute();
        $admin_settings = $stmt->fetch();

        if (!$admin_settings || !$admin_settings['whatsapp_number']) {
            return false;
        }

        // Format WhatsApp URL
        $whatsapp_number = $admin_settings['whatsapp_number'];
        $encoded_message = urlencode($message);

        // You can integrate with WhatsApp API here
        // For now, we'll log it
        error_log("WhatsApp notification to {$whatsapp_number}: {$message}");

        return true;

    } catch (Exception $e) {
        error_log("WhatsApp notification error: " . $e->getMessage());
        return false;
    }
}
?>