import { Button } from '@/Components/ui/button';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import { Palette, Save, Trash2, Upload } from 'lucide-react';
import { useRef, useState } from 'react';

type Settings = {
    app_name: string;
    app_logo: string | null;
    primary_color: string;
    footer_text: string;
};

export default function Appearance({ settings }: { settings: Settings }) {
    const [form, setForm] = useState({
        app_name: settings.app_name,
        primary_color: settings.primary_color,
        footer_text: settings.footer_text,
    });
    const [logoFile, setLogoFile] = useState<File | null>(null);
    const [removeLogo, setRemoveLogo] = useState(false);
    const [logoPreview, setLogoPreview] = useState<string | null>(settings.app_logo ? `/storage/${settings.app_logo}` : null);
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const fileInput = useRef<HTMLInputElement>(null);

    const handleFile = (event: React.ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0];
        if (file) {
            setLogoFile(file);
            setRemoveLogo(false);
            setLogoPreview(URL.createObjectURL(file));
        }
    };

    const handleRemoveLogo = () => {
        setLogoFile(null);
        setRemoveLogo(true);
        setLogoPreview(null);
        if (fileInput.current) fileInput.current.value = '';
    };

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        setProcessing(true);

        const formData = new FormData();
        formData.append('_method', 'PUT');
        formData.append('app_name', form.app_name);
        formData.append('primary_color', form.primary_color);
        formData.append('footer_text', form.footer_text);
        if (logoFile) formData.append('app_logo', logoFile);
        if (removeLogo) formData.append('remove_logo', '1');

        router.post(route('settings.appearance.update'), formData, {
            forceFormData: true,
            onFinish: () => setProcessing(false),
            onError: (validationErrors) => setErrors(validationErrors),
            onSuccess: () => setErrors({}),
        });
    };

    const presetColors = ['#16A34A', '#2563EB', '#7C3AED', '#DC2626', '#D97706', '#0891B2', '#4F46E5', '#DB2777'];

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <p className="text-sm text-slate-500">Pengaturan</p>
                    <h1 className="text-[26px] font-bold text-slate-900">Tampilan Sistem</h1>
                    <p className="mt-1 text-sm text-slate-500">Sesuaikan identitas dan tampilan aplikasi agar lebih nyaman digunakan.</p>
                </div>
            }
        >
            <Head title="Tampilan Sistem" />
            <div className="mx-auto max-w-3xl p-4 sm:p-6 lg:p-8">
                <form className="space-y-6" onSubmit={submit}>
                    <section className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 className="text-base font-semibold text-slate-900">Identitas Aplikasi</h2>
                        <p className="mt-1 text-sm text-slate-500">Nama dan logo yang akan tampil di sidebar serta halaman masuk.</p>
                        <div className="mt-5 space-y-4">
                            <label className="block text-sm font-medium text-slate-700">
                                Nama Aplikasi
                                <input
                                    className="mt-1 h-10 w-full rounded-[9px] border-slate-300 text-sm focus:border-green-600 focus:ring-green-600"
                                    maxLength={100}
                                    required
                                    value={form.app_name}
                                    onChange={(event) => setForm({ ...form, app_name: event.target.value })}
                                />
                                {errors.app_name && <span className="mt-1 block text-xs text-red-600">{errors.app_name}</span>}
                            </label>
                            <div>
                                <p className="text-sm font-medium text-slate-700">Logo</p>
                                <p className="mt-1 text-xs text-slate-500">Unggah gambar berformat PNG, SVG, atau WebP. Ukuran maksimal 512 KB.</p>
                                <div className="mt-3 flex items-center gap-4">
                                    {logoPreview ? (
                                        <img src={logoPreview} alt="Pratinjau logo" className="size-16 rounded-lg border border-slate-200 object-contain p-1" />
                                    ) : (
                                        <div className="flex size-16 items-center justify-center rounded-lg border border-dashed border-slate-300 text-slate-400">
                                            <Palette size={24} />
                                        </div>
                                    )}
                                    <div className="flex flex-wrap gap-2">
                                        <Button type="button" variant="secondary" onClick={() => fileInput.current?.click()}>
                                            <Upload size={16} />Unggah Logo
                                        </Button>
                                        {(logoPreview || settings.app_logo) && (
                                            <Button type="button" variant="secondary" onClick={handleRemoveLogo}>
                                                <Trash2 size={16} />Hapus
                                            </Button>
                                        )}
                                    </div>
                                    <input ref={fileInput} type="file" accept="image/png,image/svg+xml,image/webp" className="hidden" onChange={handleFile} />
                                </div>
                                {errors.app_logo && <span className="mt-1 block text-xs text-red-600">{errors.app_logo}</span>}
                            </div>
                        </div>
                    </section>

                    <section className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 className="text-base font-semibold text-slate-900">Warna Tema</h2>
                        <p className="mt-1 text-sm text-slate-500">Pilih warna utama yang digunakan pada tombol, lencana, dan elemen interaktif lainnya.</p>
                        <div className="mt-5 space-y-3">
                            <div className="flex flex-wrap gap-2">
                                {presetColors.map((color) => (
                                    <button
                                        key={color}
                                        type="button"
                                        className={`size-10 rounded-lg border-2 transition ${form.primary_color === color ? 'border-slate-900 ring-2 ring-slate-900/20' : 'border-slate-200'}`}
                                        style={{ backgroundColor: color }}
                                        onClick={() => setForm({ ...form, primary_color: color })}
                                        aria-label={`Pilih warna ${color}`}
                                    />
                                ))}
                            </div>
                            <label className="flex items-center gap-3 text-sm text-slate-700">
                                Kode warna kustom
                                <input
                                    type="text"
                                    className="h-10 w-32 rounded-[9px] border-slate-300 font-mono text-sm focus:border-green-600 focus:ring-green-600"
                                    maxLength={7}
                                    pattern="^#[0-9A-Fa-f]{6}$"
                                    value={form.primary_color}
                                    onChange={(event) => setForm({ ...form, primary_color: event.target.value })}
                                />
                                <span className="size-8 rounded-lg border border-slate-200" style={{ backgroundColor: form.primary_color }} />
                            </label>
                            {errors.primary_color && <span className="block text-xs text-red-600">{errors.primary_color}</span>}
                        </div>
                    </section>

                    <section className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 className="text-base font-semibold text-slate-900">Teks Footer</h2>
                        <p className="mt-1 text-sm text-slate-500">Teks singkat yang ditampilkan di bagian bawah sidebar, misalnya nama yayasan atau hak cipta.</p>
                        <label className="mt-5 block text-sm font-medium text-slate-700">
                            Footer
                            <input
                                className="mt-1 h-10 w-full rounded-[9px] border-slate-300 text-sm focus:border-green-600 focus:ring-green-600"
                                maxLength={200}
                                value={form.footer_text}
                                onChange={(event) => setForm({ ...form, footer_text: event.target.value })}
                                placeholder="Contoh: © 2026 Yayasan Anda"
                            />
                            {errors.footer_text && <span className="mt-1 block text-xs text-red-600">{errors.footer_text}</span>}
                        </label>
                    </section>

                    <div className="flex justify-end">
                        <Button type="submit" disabled={processing}>
                            <Save size={16} />{processing ? 'Menyimpan...' : 'Simpan Pengaturan'}
                        </Button>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}