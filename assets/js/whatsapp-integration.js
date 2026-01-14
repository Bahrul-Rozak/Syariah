// WhatsApp Integration Helper
class WhatsAppIntegration {
    constructor() {
        this.adminNumber = '6281234567890'; // Default admin number
    }
    
    // Generate payment confirmation message
    generatePaymentConfirmation(userName, userId, amount, date) {
        const message = `Assalamu'alaikum ${userName} (ID: ${userId}),%0A%0A` +
                       `Pembayaran Anda sebesar Rp ${amount.toLocaleString()} telah dikonfirmasi pada ${date}.%0A%0A` +
                       `Status: ✅ TERKONFIRMASI%0A` +
                       `Selanjutnya, admin akan menghubungi Anda untuk proses matching.%0A%0A` +
                       `Terima kasih,%0ATaaruf Islami`;
        return message;
    }
    
    // Generate payment reminder message
    generatePaymentReminder(userName, userId, amount, dueDate) {
        const message = `Assalamu'alaikum ${userName} (ID: ${userId}),%0A%0A` +
                       `Mengingatkan untuk pembayaran biaya taaruf sebesar Rp ${amount.toLocaleString()}.%0A%0A` +
                       `Batas waktu: ${dueDate}%0A` +
                       `Silakan transfer ke:%0A` +
                       `Bank: BCA%0A` +
                       `No Rek: 1234567890%0A` +
                       `Atas Nama: Taaruf Islami%0A%0A` +
                       `Setelah transfer, konfirmasi ke admin dengan mengirim bukti transfer.%0A%0A` +
                       `Terima kasih,%0ATaaruf Islami`;
        return message;
    }
    
    // Generate match notification message
    generateMatchNotification(userName, matchedUserName) {
        const message = `Assalamu'alaikum ${userName},%0A%0A` +
                       `Kami telah menemukan calon pasangan yang sesuai untuk Anda:%0A` +
                       `Nama: ${matchedUserName}%0A%0A` +
                       `Silakan login ke dashboard Anda untuk melihat detail dan memberikan respon.%0A` +
                       `Jika cocok, admin akan mengatur pertemuan dengan pendampingan ustadz.%0A%0A` +
                       `Terima kasih,%0ATaaruf Islami`;
        return message;
    }
    
    // Open WhatsApp chat
    openWhatsApp(phoneNumber, message = '') {
        const url = `https://wa.me/${phoneNumber}${message ? `?text=${message}` : ''}`;
        window.open(url, '_blank');
    }
    
    // Send bulk messages (for admin use)
    sendBulkMessages(recipients, message) {
        // recipients: array of {name, phone, userId}
        recipients.forEach(recipient => {
            const personalizedMessage = message
                .replace('{{name}}', recipient.name)
                .replace('{{userId}}', recipient.userId);
            
            const encodedMessage = encodeURIComponent(personalizedMessage);
            const url = `https://wa.me/${recipient.phone}?text=${encodedMessage}`;
            
            // Open in new tabs (might be blocked by popup blocker)
            setTimeout(() => {
                window.open(url, '_blank');
            }, 1000);
        });
    }
    
    // Generate WhatsApp link for admin panel
    generateAdminLink(action, data) {
        let message = '';
        
        switch(action) {
            case 'payment_confirmation':
                message = this.generatePaymentConfirmation(
                    data.name,
                    data.userId,
                    data.amount,
                    data.date
                );
                break;
                
            case 'payment_reminder':
                message = this.generatePaymentReminder(
                    data.name,
                    data.userId,
                    data.amount,
                    data.dueDate
                );
                break;
                
            case 'match_notification':
                message = this.generateMatchNotification(
                    data.name,
                    data.matchedName
                );
                break;
                
            default:
                message = 'Assalamu\'alaikum, ada pesan dari Taaruf Islami.';
        }
        
        return `https://wa.me/${data.phone}?text=${encodeURIComponent(message)}`;
    }
}

// Initialize WhatsApp integration
const whatsapp = new WhatsAppIntegration();

// Make it available globally
window.whatsapp = whatsapp;

// Helper functions for admin panel
function sendPaymentConfirmation(phone, name, userId, amount) {
    const message = whatsapp.generatePaymentConfirmation(
        name,
        userId,
        amount,
        new Date().toLocaleDateString('id-ID')
    );
    whatsapp.openWhatsApp(phone, message);
}

function sendPaymentReminder(phone, name, userId, amount, dueDate = 'besok') {
    const message = whatsapp.generatePaymentReminder(
        name,
        userId,
        amount,
        dueDate
    );
    whatsapp.openWhatsApp(phone, message);
}

function sendMatchNotification(phone, name, matchedName) {
    const message = whatsapp.generateMatchNotification(name, matchedName);
    whatsapp.openWhatsApp(phone, message);
}

// Auto-send functionality for admin
document.addEventListener('DOMContentLoaded', function() {
    // Auto-send payment confirmation after status change
    document.querySelectorAll('[data-action="confirm-payment"]').forEach(button => {
        button.addEventListener('click', function() {
            const phone = this.dataset.phone;
            const name = this.dataset.name;
            const userId = this.dataset.userId;
            const amount = this.dataset.amount;
            
            if (confirm(`Kirim konfirmasi ke ${name} via WhatsApp?`)) {
                sendPaymentConfirmation(phone, name, userId, amount);
            }
        });
    });
    
    // Auto-send reminders
    document.querySelectorAll('[data-action="send-reminder"]').forEach(button => {
        button.addEventListener('click', function() {
            const phone = this.dataset.phone;
            const name = this.dataset.name;
            const userId = this.dataset.userId;
            const amount = this.dataset.amount;
            
            if (confirm(`Kirim reminder ke ${name} via WhatsApp?`)) {
                sendPaymentReminder(phone, name, userId, amount);
            }
        });
    });
});