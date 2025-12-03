import AutoNumeric from 'autonumeric';

// 1. Konfigurasi Global (Agar konsisten di seluruh aplikasi)
export const autoNumericOptions = {
    digitGroupSeparator: '.',
    decimalCharacter: ',',
    decimalCharacterAlternative: '.',
    currencySymbol: 'Rp ',
    currencySymbolPlacement: 'p',
    roundingMethod: 'U',
    minimumValue: '0',
    aPad: false
};

// 2. Fungsi Utama untuk Inisialisasi
export function initFormatters() {
    // Expose ke window (opsional, jika Anda butuh debug di console browser)
    window.AutoNumeric = AutoNumeric;

    // Cari semua elemen dengan class .input-currency
    const currencyInputs = document.querySelectorAll('.input-currency');
    
    currencyInputs.forEach(el => {
        // Cek apakah elemen ini sudah ada AutoNumeric-nya supaya tidak double init
        if (!AutoNumeric.getAutoNumericElement(el)) {
            new AutoNumeric(el, autoNumericOptions);
        }
    });
}