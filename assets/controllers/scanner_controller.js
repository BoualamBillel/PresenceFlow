import { Controller } from '@hotwired/stimulus';
import { Html5Qrcode } from 'html5-qrcode';

export default class extends Controller {
    static targets = ["reader"];

    async connect() {
        this.scanned = false;
        
        // Initialisation de l'instance pure
        this.html5QrCode = new Html5Qrcode(this.readerTarget.id);
        
        const config = { 
            fps: 15, 
            qrbox: { width: 250, height: 250 },
            aspectRatio: window.innerWidth / window.innerHeight // S'adapte à l'écran du téléphone
        };

        try {
            // On force l'utilisation de la caméra arrière (environment)
            await this.html5QrCode.start(
                { facingMode: "environment" },
                config,
                this.onScanSuccess.bind(this)
            );
        } catch (err) {
            console.error("Impossible d'accéder à la caméra : ", err);
        }
    }

    disconnect() {
        // Arrêt propre de la caméra quand on quitte la page
        if (this.html5QrCode) {
            this.html5QrCode.stop().catch(err => console.error("Erreur à l'arrêt du scanner", err));
        }
    }

    onScanSuccess(decodedText, decodedResult) {
        if (this.scanned) return;
        this.scanned = true;

        // On coupe la vidéo pour donner un effet de "capture" instantané
        this.html5QrCode.stop().then(() => {
            window.location.href = decodedText;
        });
    }
}