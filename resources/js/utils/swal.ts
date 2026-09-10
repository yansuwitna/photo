import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

// Custom Photobooth Themed SweetAlert
export const themeSwal = Swal.mixin({
    background: '#0f172a',
    color: '#f8fafc',
    confirmButtonColor: '#f59e0b',
    cancelButtonColor: '#334155',
    customClass: {
        popup: 'rounded-3xl border border-white/10 shadow-2xl backdrop-blur-xl',
        title: 'text-white font-black text-xl',
        htmlContainer: 'text-slate-300 text-sm',
        confirmButton: 'px-5 py-2.5 rounded-xl font-black text-xs shadow-lg uppercase tracking-wider text-slate-950 transition-transform active:scale-95',
        cancelButton: 'px-5 py-2.5 rounded-xl font-bold text-xs shadow text-slate-300 transition-transform active:scale-95',
    },
});

/**
 * Tampilkan pesan Sukses
 */
export async function showSuccess(title: string, text?: string) {
    return themeSwal.fire({
        icon: 'success',
        title,
        text,
        timer: 2500,
        showConfirmButton: true,
        confirmButtonText: 'OKE',
    });
}

/**
 * Tampilkan pesan Error
 */
export async function showError(title: string, text?: string) {
    return themeSwal.fire({
        icon: 'error',
        title,
        text,
        confirmButtonText: 'MENGERTI',
    });
}

/**
 * Tampilkan pesan Peringatan / Warning
 */
export async function showWarning(title: string, text?: string) {
    return themeSwal.fire({
        icon: 'warning',
        title,
        text,
        confirmButtonText: 'LANJUTKAN',
    });
}

/**
 * Tampilkan pesan Informasi
 */
export async function showInfo(title: string, text?: string) {
    return themeSwal.fire({
        icon: 'info',
        title,
        text,
        confirmButtonText: 'OKE',
    });
}

/**
 * Konfirmasi Tindakan (Yes / No)
 * Mengembalikan boolean true jika disetujui, false jika dibatalkan.
 */
export async function showConfirm(
    title: string,
    text?: string,
    confirmButtonText: string = 'Ya, Lanjutkan',
    cancelButtonText: string = 'Batal'
): Promise<boolean> {
    const result = await themeSwal.fire({
        icon: 'question',
        title,
        text,
        showCancelButton: true,
        confirmButtonText,
        cancelButtonText,
        reverseButtons: true,
    });

    return result.isConfirmed;
}

/**
 * Konfirmasi Hapus Data Khusus (Warna Tombol Merah)
 */
export async function showDeleteConfirm(
    itemName: string,
    customText: string = 'Tindakan ini permanen dan tidak dapat dibatalkan.'
): Promise<boolean> {
    const result = await themeSwal.fire({
        icon: 'warning',
        title: `Hapus ${itemName}?`,
        text: customText,
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#334155',
        confirmButtonText: 'Ya, Hapus Sekarang',
        cancelButtonText: 'Batal',
        reverseButtons: true,
        customClass: {
            popup: 'rounded-3xl border border-red-500/20 shadow-2xl',
            confirmButton: 'px-5 py-2.5 rounded-xl font-black text-xs text-white bg-red-600 hover:bg-red-500 transition-transform active:scale-95',
            cancelButton: 'px-5 py-2.5 rounded-xl font-bold text-xs shadow text-slate-300 transition-transform active:scale-95',
        }
    });

    return result.isConfirmed;
}

/**
 * Toast Notifikasi Cepat di Pojok Kanan Atas
 */
export function showToast(title: string, icon: 'success' | 'error' | 'warning' | 'info' = 'success') {
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 2500,
        timerProgressBar: true,
        background: '#0f172a',
        color: '#ffffff',
        customClass: {
            popup: 'rounded-2xl border border-white/10 shadow-xl',
        },
    });

    Toast.fire({
        icon,
        title,
    });
}

export default themeSwal;
